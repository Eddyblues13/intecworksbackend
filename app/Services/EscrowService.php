<?php

namespace App\Services;

use App\Models\Escrow;
use App\Models\EscrowTransaction;
use App\Models\ServiceJob;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    /**
     * Create an escrow when an artisan is accepted for a job.
     * Default deposit = ₦5,000 flat (can be made configurable via PlatformSetting).
     */
    public function createEscrow(ServiceJob $job, float $depositAmount = 5000.00): Escrow
    {
        return Escrow::create([
            'job_id'         => $job->id,
            'client_id'      => $job->client_id,
            'status'         => 'deposit_required',
            'deposit_amount' => $depositAmount,
        ]);
    }

    /**
     * Fund the initial escrow deposit.
     */
    public function fundDeposit(
        string  $jobId,
        float   $amount,
        string  $paymentMethod,
        ?string $paymentReference = null
    ): Escrow {
        return DB::transaction(function () use ($jobId, $amount, $paymentMethod, $paymentReference) {
            $escrow = Escrow::where('job_id', $jobId)->lockForUpdate()->firstOrFail();

            if ($amount < $escrow->deposit_amount) {
                throw new \InvalidArgumentException(
                    "Amount (₦{$amount}) is less than the required deposit (₦{$escrow->deposit_amount})."
                );
            }

            EscrowTransaction::create([
                'escrow_id'         => $escrow->id,
                'job_id'            => $jobId,
                'type'              => 'deposit',
                'amount'            => $amount,
                'payment_method'    => $paymentMethod,
                'payment_reference' => $paymentReference,
                'description'       => 'Initial escrow deposit',
            ]);

            $newTotal = $escrow->total_funded + $amount;
            $escrow->update([
                'total_funded' => $newTotal,
                'status'       => 'awaiting_quote_approval',
            ]);

            // Update job status so artisan can begin inspection
            $escrow->job->update(['status' => 'escrowFunded']);

            return $escrow->fresh();
        });
    }

    /**
     * Set the remaining amount when the quote is approved by the client.
     * Called from ClientController::approveQuote.
     */
    public function setRemainingFromQuote(string $jobId, float $quoteTotal, float $materialTotal = 0): void
    {
        $escrow = Escrow::where('job_id', $jobId)->firstOrFail();
        $remaining = max(0, $quoteTotal - $escrow->total_funded);

        $escrow->update([
            'remaining_amount' => $remaining,
            'material_amount'  => $materialTotal,
            'status'           => 'awaiting_remaining_balance',
        ]);
    }

    /**
     * Fund remaining balance after quote approval.
     */
    public function fundRemaining(
        string  $jobId,
        string  $paymentMethod,
        ?string $paymentReference = null
    ): Escrow {
        return DB::transaction(function () use ($jobId, $paymentMethod, $paymentReference) {
            $escrow = Escrow::where('job_id', $jobId)->lockForUpdate()->firstOrFail();

            if ($escrow->remaining_amount <= 0) {
                throw new \InvalidArgumentException('No remaining balance to fund.');
            }

            $amount = $escrow->remaining_amount;

            EscrowTransaction::create([
                'escrow_id'         => $escrow->id,
                'job_id'            => $jobId,
                'type'              => 'remaining_balance',
                'amount'            => $amount,
                'payment_method'    => $paymentMethod,
                'payment_reference' => $paymentReference,
                'description'       => 'Remaining balance payment',
            ]);

            $escrow->update([
                'total_funded'    => $escrow->total_funded + $amount,
                'status'          => 'fully_funded',
                'fully_funded_at' => now(),
            ]);

            // Job is now fully funded — work can begin
            $escrow->job->update(['status' => 'escrowFunded']);

            return $escrow->fresh();
        });
    }

    /**
     * Release funds to artisan after job completion is confirmed.
     */
    public function releaseFunds(string $jobId, float $commissionRate = 0.10): Escrow
    {
        return DB::transaction(function () use ($jobId, $commissionRate) {
            $escrow = Escrow::where('job_id', $jobId)->lockForUpdate()->firstOrFail();

            $available     = $escrow->total_funded - $escrow->total_released - $escrow->total_refunded;
            $commission    = round($available * $commissionRate, 2);
            $artisanPayout = $available - $commission;

            if ($commission > 0) {
                EscrowTransaction::create([
                    'escrow_id'   => $escrow->id,
                    'job_id'      => $jobId,
                    'type'        => 'commission_deduction',
                    'amount'      => $commission,
                    'description' => 'Platform commission (' . ($commissionRate * 100) . '%)',
                ]);
            }

            EscrowTransaction::create([
                'escrow_id'   => $escrow->id,
                'job_id'      => $jobId,
                'type'        => 'release_artisan',
                'amount'      => $artisanPayout,
                'description' => 'Payout to artisan',
            ]);

            $escrow->update([
                'total_released' => $escrow->total_released + $available,
                'status'         => 'released',
                'released_at'    => now(),
            ]);

            return $escrow->fresh();
        });
    }

    /**
     * Refund escrow to client (cancellation/dispute).
     */
    public function refund(string $jobId, float $amount, string $reason = 'Refund'): Escrow
    {
        return DB::transaction(function () use ($jobId, $amount, $reason) {
            $escrow = Escrow::where('job_id', $jobId)->lockForUpdate()->firstOrFail();

            $maxRefundable = $escrow->total_funded - $escrow->total_released - $escrow->total_refunded;
            if ($amount > $maxRefundable) {
                throw new \InvalidArgumentException("Cannot refund R{$amount}. Max refundable: R{$maxRefundable}");
            }

            EscrowTransaction::create([
                'escrow_id'   => $escrow->id,
                'job_id'      => $jobId,
                'type'        => 'refund',
                'amount'      => $amount,
                'description' => $reason,
            ]);

            $escrow->update([
                'total_refunded' => $escrow->total_refunded + $amount,
                'status'         => 'refunded',
            ]);

            return $escrow->fresh();
        });
    }
}
