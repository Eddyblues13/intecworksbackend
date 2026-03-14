<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * GET /supplier/subscription/plans
     * Return all available plans.
     */
    public function plans(): JsonResponse
    {
        return response()->json(['plans' => array_values(Subscription::plans())]);
    }

    /**
     * GET /supplier/subscription/status
     * Return the current supplier's active subscription (if any).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (!$sub) {
            return response()->json([
                'hasActiveSubscription' => false,
                'subscription'          => null,
            ]);
        }

        return response()->json([
            'hasActiveSubscription' => true,
            'subscription'          => $sub->toApiArray(),
        ]);
    }

    /**
     * POST /supplier/subscription/purchase
     * Purchase a subscription plan.
     * Body: { planId: string, paymentReference?: string }
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'planId'           => 'required|string',
            'paymentReference' => 'nullable|string',
        ]);

        $user  = $request->user();
        $plans = Subscription::plans();
        $plan  = $plans[$request->planId] ?? null;

        if (!$plan) {
            return response()->json(['message' => 'Invalid plan ID.'], 422);
        }

        // Expire any currently-active subscription
        Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $now = Carbon::now();

        $sub = Subscription::create([
            'user_id'           => $user->id,
            'plan_id'           => $plan['id'],
            'plan_name'         => $plan['name'],
            'amount'            => $plan['amount'],
            'currency'          => $plan['currency'],
            'status'            => 'active',
            'payment_reference' => $request->paymentReference,
            'starts_at'         => $now,
            'expires_at'        => $now->copy()->addDays($plan['duration']),
        ]);

        // Promote user account_status to 'active'
        if (in_array($user->account_status, ['approved', 'subscription_required'])) {
            $user->update(['account_status' => 'active']);
        }

        return response()->json([
            'message'      => 'Subscription activated successfully.',
            'subscription' => $sub->toApiArray(),
            'user'         => $user->fresh()->toApiArray(),
        ]);
    }

    /**
     * POST /supplier/subscription/renew
     * Renew (same plan or upgrade).
     */
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'planId'           => 'required|string',
            'paymentReference' => 'nullable|string',
        ]);

        // Re-use purchase logic — it already expires old subs
        return $this->purchase($request);
    }

    /**
     * POST /supplier/subscription/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$sub) {
            return response()->json(['message' => 'No active subscription to cancel.'], 404);
        }

        $sub->update([
            'status'       => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        // Move user back to subscription_required
        $user->update(['account_status' => 'subscription_required']);

        return response()->json([
            'message' => 'Subscription cancelled.',
            'user'    => $user->fresh()->toApiArray(),
        ]);
    }
}
