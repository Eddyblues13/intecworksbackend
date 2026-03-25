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

class PaystackController extends Controller
{
    /**
     * Initialize a Paystack transaction for escrow funding.
     *
     * POST /payment/paystack/initialize
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
        $reference = 'ps_' . Str::random(12) . '_' . time();

        // Create a pending payment record
        $payment = Payment::create([
            'service_job_id'    => $data['jobId'],
            'payer_id'          => $user->id,
            'amount'            => $amount,
            'method'            => 'paystack',
            'status'            => 'pending',
            'reference'         => $reference,
            'purpose'           => $data['purpose'],
        ]);

        // Call Paystack API
        $secretKey = Setting::get('paystack_secret_key');

        $response = Http::withToken($secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'     => $user->email,
                'amount'    => (int) ($amount * 100), // Paystack uses kobo (amount × 100)
                'reference' => $reference,
                'callback_url' => config('app.url') . '/api/v1/payment/paystack/callback',
                'metadata'  => [
                    'job_id'  => $data['jobId'],
                    'purpose' => $data['purpose'],
                    'user_id' => $user->id,
                ],
            ]);

        if ($response->successful() && $response->json('status') === true) {
            return response()->json([
                'success'           => true,
                'authorization_url' => $response->json('data.authorization_url'),
                'reference'         => $reference,
            ]);
        }

        $payment->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize Paystack payment.',
            'error'   => $response->json(),
        ], 400);
    }

    /**
     * Verify a Paystack transaction and credit escrow.
     *
     * POST /payment/paystack/verify
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

        $secretKey = Setting::get('paystack_secret_key');

        $response = Http::withToken($secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$data['reference']}");

        if ($response->successful() && $response->json('data.status') === 'success') {
            $payment->update(['status' => 'completed']);

            // Credit escrow
            $this->creditEscrow($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and escrow credited.',
            ]);
        }

        if ($response->successful() && in_array($response->json('data.status'), ['failed', 'abandoned'])) {
            $payment->update(['status' => 'failed']);
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
                'paystack',
                $payment->reference,
            );
        } elseif ($payment->purpose === 'remaining') {
            $escrowService->fundRemaining(
                $payment->service_job_id,
                'paystack',
                $payment->reference,
            );
        }
    }
}
