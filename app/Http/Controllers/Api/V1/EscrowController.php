<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    private EscrowService $escrowService;

    public function __construct(EscrowService $escrowService)
    {
        $this->escrowService = $escrowService;
    }

    /**
     * GET /escrow/{jobId}
     * Get escrow details for a job.
     */
    public function show(string $jobId, Request $request): JsonResponse
    {
        $escrow = Escrow::where('job_id', $jobId)->first();

        if (!$escrow) {
            return response()->json(['message' => 'No escrow found for this job.'], 404);
        }

        if ($escrow->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($escrow->toApiArray());
    }

    /**
     * POST /escrow/fund
     * Fund initial escrow deposit.
     */
    public function fund(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jobId'            => 'required|exists:service_jobs,id',
            'amount'           => 'required|numeric|min:1',
            'paymentMethod'    => 'required|in:card,bank_transfer,wallet',
            'paymentReference' => 'nullable|string',
        ]);

        try {
            $escrow = $this->escrowService->fundDeposit(
                $data['jobId'],
                (float) $data['amount'],
                $data['paymentMethod'],
                $data['paymentReference'] ?? null,
            );

            return response()->json([
                'message' => 'Escrow deposit funded successfully.',
                'escrow'  => $escrow->toApiArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /escrow/{jobId}/fund-remaining
     * Fund the remaining escrow balance after quote approval.
     */
    public function fundRemaining(string $jobId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'paymentMethod'    => 'required|in:card,bank_transfer,wallet',
            'paymentReference' => 'nullable|string',
        ]);

        $escrow = Escrow::where('job_id', $jobId)->firstOrFail();
        if ($escrow->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $escrow = $this->escrowService->fundRemaining(
                $jobId,
                $data['paymentMethod'],
                $data['paymentReference'] ?? null,
            );

            return response()->json([
                'message' => 'Remaining balance funded successfully.',
                'escrow'  => $escrow->toApiArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /escrow/{jobId}/transactions
     * Get escrow transaction history.
     */
    public function transactions(string $jobId, Request $request): JsonResponse
    {
        $escrow = Escrow::where('job_id', $jobId)->first();

        if (!$escrow) {
            return response()->json([]);
        }

        if ($escrow->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transactions = $escrow->transactions->map->toApiArray()->toArray();

        return response()->json($transactions);
    }
}
