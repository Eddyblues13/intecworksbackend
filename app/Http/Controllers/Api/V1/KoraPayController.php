<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KoraPayController extends Controller
{
    /**
     * Initialize a KoraPay transaction for escrow funding.
     *
     * POST /payment/korapay/initialize
     * Body: { jobId, amount, purpose: "deposit"|"remaining" }
     */
    public function initialize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jobId'   => 'required|exists:service_jobs,id',
            'amount'  => 'required|numeric|min:100',
            'purpose' => 'required|in:deposit,remaining',
        ]);

        $user      = $request->user();
        $amount    = (float) $data['amount'];
        $reference = 'kp_' . Str::random(12) . '_' . time();

        // Create a pending payment record
        $payment = Payment::create([
            'service_job_id'    => $data['jobId'],
            'payer_id'          => $user->id,
            'amount'            => $amount,
            'method'            => 'korapay',
            'status'            => 'pending',
            'reference'         => $reference,
            'purpose'           => $data['purpose'],
        ]);

        // Call Korapay API
        $secretKey = Setting::get('korapay_secret_key');

        $response = Http::withToken($secretKey)
            ->post('https://api.korapay.com/merchant/api/v1/charges/initialize', [
                'reference' => $reference,
                'customer'  => [
                    'name'  => $user->full_name,
                    'email' => $user->email,
                ],
                'amount'       => $amount,
                'currency'     => 'NGN',
                'redirect_url' => config('app.url') . '/api/v1/payment/korapay/callback',
            ]);

        if ($response->successful() && $response->json('status') === true) {
            return response()->json([
                'success'      => true,
                'checkout_url' => $response->json('data.checkout_url'),
                'reference'    => $reference,
            ]);
        }

        \Log::error('KoraPay Init Failed: ' . $response->body());
        $payment->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize KoraPay payment.',
            'error'   => $response->json(),
        ], 400);
    }

    /**
     * Verify a KoraPay transaction and credit escrow.
     *
     * POST /payment/korapay/verify
     * Body: { reference }
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => 'required|string',
        ]);

        $payment = Payment::where('reference', $data['reference'])->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json(['success' => true, 'message' => 'Payment already verified.']);
        }

        $secretKey = Setting::get('korapay_secret_key');

        $response = Http::withToken($secretKey)
            ->get("https://api.korapay.com/merchant/api/v1/charges/{$data['reference']}");

        if ($response->successful() && $response->json('status') === true) {
            $chargeStatus = $response->json('data.status');

            if ($chargeStatus === 'success') {
                $payment->update(['status' => 'completed']);

                // Credit escrow
                $this->creditEscrow($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified and escrow credited.',
                ]);
            } elseif (in_array($chargeStatus, ['failed', 'expired'])) {
                $payment->update(['status' => 'failed']);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment verification failed or still pending.',
        ], 400);
    }

    /**
     * Credit the escrow based on payment purpose.
     */
    private function creditEscrow(Payment $payment): void
    {
        $escrowService = app(EscrowService::class);

        if ($payment->purpose === 'deposit') {
            $escrowService->fundDeposit(
                $payment->service_job_id,
                $payment->amount,
                'korapay',
                $payment->reference,
            );
        } elseif ($payment->purpose === 'remaining') {
            $escrowService->fundRemaining(
                $payment->service_job_id,
                'korapay',
                $payment->reference,
            );
        }
    }
}
