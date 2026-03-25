<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArtisanProfile;
use App\Models\InspectionReport;
use App\Models\MaterialOrder;
use App\Models\MaterialRequest;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\ServiceJob;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtisanController extends Controller
{
    private function getOrCreateProfile($userId): ArtisanProfile
    {
        return ArtisanProfile::firstOrCreate(
            ['user_id' => $userId],
            [
                'skill_categories' => [], 'skill_proof_urls' => [],
                'verification_status' => 'draft', 'service_radius' => 15.0,
                'trust_score' => 0, 'tier' => 'bronze',
                'current_active_jobs' => 0, 'current_scheduled_jobs' => 0,
                'is_available' => true,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════════

    public function dashboard(Request $request): JsonResponse
    {
        $user    = $request->user();
        $profile = $this->getOrCreateProfile($user->id);

        $activeStatuses = [
            'inspected', 'scopeClassified', 'quoted', 'quoteAdminReview', 
            'quoteReady', 'workInProgress', 'completionPending', 'completionApproved'
        ];
        
        $scheduledStatuses = [
            'accepted', 'quoteApproved', 'escrowFunded'
        ];

        // Combine both for the "Active Jobs" list shown on the dashboard natively
        $allActiveOrScheduled = array_merge($activeStatuses, $scheduledStatuses);

        $activeJobs = ServiceJob::where('artisan_id', $user->id)
            ->whereIn('status', $allActiveOrScheduled)
            ->latest()->take(5)->get()->map->toApiArray()->toArray();

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'profile'           => $profile->toApiArray(),
            'activeJobCount'    => \App\Models\ServiceJob::where('artisan_id', $user->id)
                ->whereIn('status', $activeStatuses)->count(),
            'scheduledJobCount' => \App\Models\ServiceJob::where('artisan_id', $user->id)
                ->whereIn('status', $scheduledStatuses)->count(),
            'completedJobCount' => \App\Models\ServiceJob::where('artisan_id', $user->id)
                ->where('status', 'completed')->count(),
            'activeJobs'        => $activeJobs,
            'walletBalance'     => $wallet->balance,
            'pendingBalance'    => $wallet->pending_balance,
            'totalEarned'       => $wallet->total_earned,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  NEARBY / AVAILABLE JOBS
    // ═══════════════════════════════════════════════════════

    public function nearbyJobs(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = ArtisanProfile::where('user_id', $user->id)->first();

        $query = ServiceJob::whereNull('artisan_id')
            ->where('status', 'created')
            ->where('client_id', '!=', $user->id)
            ->latest();

        if ($profile && !empty($profile->skill_categories)) {
            $categoryIds = \App\Models\Category::whereIn('name', $profile->skill_categories)
                ->pluck('id')->toArray();
            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        return response()->json($query->take(20)->get()->map->toApiArray()->toArray());
    }

    // ═══════════════════════════════════════════════════════
    //  ACCEPT / DECLINE JOB
    // ═══════════════════════════════════════════════════════

    public function acceptJob(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($serviceJob->artisan_id !== null) {
            return response()->json(['message' => 'Job already assigned'], 422);
        }

        $profile = $this->getOrCreateProfile($user->id);

        $activeStatuses = [
            'inspected', 'scopeClassified', 'quoted', 'quoteAdminReview', 
            'quoteReady', 'workInProgress', 'completionPending', 'completionApproved'
        ];
        
        $scheduledStatuses = [
            'accepted', 'quoteApproved', 'escrowFunded'
        ];

        $activeCount = ServiceJob::where('artisan_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->count();
            
        $scheduledCount = ServiceJob::where('artisan_id', $user->id)
            ->whereIn('status', $scheduledStatuses)
            ->count();

        if ($activeCount >= 2 || $scheduledCount >= 1) {
            return response()->json([
                'message' => "Workload limit reached (Max: 2 Active, 1 Scheduled). You currently have $activeCount active and $scheduledCount scheduled jobs."
            ], 422);
        }

        $serviceJob->update([
            'artisan_id'  => $user->id,
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);
        
        // Notify Client
        if ($serviceJob->client) {
            $serviceJob->client->notify(new \App\Notifications\JobEventNotification(
                'Job Accepted',
                "Your job has been accepted by an artisan.",
                $serviceJob->id,
                'job_accepted'
            ));
        }

        // Scheduled job increments immediately because status goes to 'accepted'
        $profile->increment('current_scheduled_jobs');

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    public function declineJob(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        return response()->json(['message' => 'Job declined']);
    }

    // ═══════════════════════════════════════════════════════
    //  ACTIVE / SCHEDULED JOBS
    // ═══════════════════════════════════════════════════════

    public function activeJobs(Request $request): JsonResponse
    {
        $jobs = ServiceJob::where('artisan_id', $request->user()->id)
            ->whereNotIn('status', ['created', 'completed', 'closed', 'cancelled'])
            ->latest()->get()->map->toApiArray()->toArray();

        return response()->json($jobs);
    }

    public function scheduledJobs(Request $request): JsonResponse
    {
        $jobs = ServiceJob::where('artisan_id', $request->user()->id)
            ->whereIn('status', ['accepted', 'quoteApproved', 'escrowFunded'])
            ->latest()->get()->map->toApiArray()->toArray();

        return response()->json($jobs);
    }

    // ═══════════════════════════════════════════════════════
    //  JOB DETAIL
    // ═══════════════════════════════════════════════════════

    public function jobDetail(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $serviceJob->toApiArray();

        $inspection = InspectionReport::where('service_job_id', $serviceJob->id)->latest()->first();
        if ($inspection) {
            $data['inspectionReport'] = $inspection->toApiArray();
        }

        $quote = Quote::where('service_job_id', $serviceJob->id)
            ->where('artisan_id', $request->user()->id)->latest()->first();
        if ($quote) {
            $data['quote'] = array_merge($quote->toApiArray(), [
                'items' => $quote->items->map->toApiArray()->toArray(),
            ]);
        }

        $materialRequests = MaterialRequest::where('service_job_id', $serviceJob->id)->get();
        if ($materialRequests->isNotEmpty()) {
            $data['materialRequests'] = $materialRequests->map->toApiArray()->toArray();
        }

        return response()->json($data);
    }

    // ═══════════════════════════════════════════════════════
    //  INSPECTION
    // ═══════════════════════════════════════════════════════

    public function submitInspection(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'findings'          => 'required|string|min:10',
            'images'            => 'nullable|array',
            'images.*'          => 'string',
            'conditionRating'   => 'nullable|in:poor,fair,good',
            'recommendedScope'  => 'nullable|in:normal,custom,projectReferral',
            'requiresMaterials' => 'nullable|boolean',
            'notes'             => 'nullable|string',
        ]);

        $report = InspectionReport::create([
            'service_job_id'    => $serviceJob->id,
            'artisan_id'        => $request->user()->id,
            'findings'          => $data['findings'],
            'images'            => $data['images'] ?? [],
            'condition_rating'  => $data['conditionRating'] ?? 'fair',
            'recommended_scope' => $data['recommendedScope'] ?? null,
            'requires_materials'=> $data['requiresMaterials'] ?? false,
            'notes'             => $data['notes'] ?? null,
            'submitted_at'      => now(),
        ]);

        // Use valid ENUM value: 'inspected'
        $serviceJob->update([
            'status' => 'inspected',
            'inspection_submitted_at' => now(),
        ]);

        return response()->json($report->toApiArray(), 201);
    }

    // ═══════════════════════════════════════════════════════
    //  SCOPE CLASSIFICATION
    // ═══════════════════════════════════════════════════════

    public function submitScopeClassification(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'classification' => 'required|in:normal,custom,projectReferral',
            'notes'          => 'nullable|string',
        ]);

        // Map to valid ENUM: scopeClassified (or referred for project referrals)
        $newStatus = $data['classification'] === 'projectReferral' ? 'referred' : 'scopeClassified';

        $serviceJob->update([
            'scope_classification' => $data['classification'],
            'status'               => $newStatus,
            'scope_classified_at'  => now(),
        ]);

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    // ═══════════════════════════════════════════════════════
    //  QUOTES
    // ═══════════════════════════════════════════════════════

    public function submitQuote(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.type'        => 'nullable|in:labor,material,other',
            'items.*.quantity'    => 'required|numeric|min:1',
            'items.*.unitPrice'   => 'required|numeric|min:0',
            'timeline'            => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $laborTotal = 0;
        $materialTotal = 0;

        foreach ($data['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unitPrice'];
            if (($item['type'] ?? 'labor') === 'material') {
                $materialTotal += $lineTotal;
            } else {
                $laborTotal += $lineTotal;
            }
        }

        $quote = Quote::create([
            'service_job_id' => $serviceJob->id,
            'artisan_id'     => $request->user()->id,
            'status'         => 'draft',
            'total_amount'   => $laborTotal + $materialTotal,
            'labor_total'    => $laborTotal,
            'material_total' => $materialTotal,
            'timeline'       => $data['timeline'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'submitted_at'   => now(),
        ]);

        foreach ($data['items'] as $item) {
            QuoteItem::create([
                'quote_id'    => $quote->id,
                'description' => $item['description'],
                'type'        => $item['type'] ?? 'labor',
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unitPrice'],
                'total_price' => $item['quantity'] * $item['unitPrice'],
            ]);
        }

        // Use valid ENUM value: 'quoted'
        $serviceJob->update([
            'status'             => 'quoted',
            'quote_submitted_at' => now(),
        ]);
        
        // Notify Client
        if ($serviceJob->client) {
            $serviceJob->client->notify(new \App\Notifications\JobEventNotification(
                'Quote Submitted',
                "The artisan has submitted a quote for your review.",
                $serviceJob->id,
                'quote_submitted'
            ));
        }

        return response()->json(
            array_merge($quote->fresh()->toApiArray(), [
                'items' => $quote->items->map->toApiArray()->toArray(),
            ]),
            201,
        );
    }

    public function quoteDetail(Quote $quote, Request $request): JsonResponse
    {
        if ($quote->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            array_merge($quote->toApiArray(), [
                'items' => $quote->items->map->toApiArray()->toArray(),
            ])
        );
    }

    // ═══════════════════════════════════════════════════════
    //  MATERIALS
    // ═══════════════════════════════════════════════════════

    public function submitMaterialRequest(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit'     => 'nullable|string',
            'items.*.specs'    => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $matReq = MaterialRequest::create([
            'service_job_id' => $serviceJob->id,
            'artisan_id'     => $request->user()->id,
            'items'          => $data['items'],
            'status'         => 'pending',
            'notes'          => $data['notes'] ?? null,
        ]);

        return response()->json($matReq->toApiArray(), 201);
    }

    public function supplierQuotes(MaterialRequest $materialRequest, Request $request): JsonResponse
    {
        $orders = MaterialOrder::where('material_request_id', $materialRequest->id)
            ->get()->map->toApiArray()->toArray();

        return response()->json($orders);
    }

    public function selectSupplier(MaterialRequest $materialRequest, Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplierId' => 'required|exists:users,id',
        ]);

        $materialRequest->update(['status' => 'supplier_selected']);

        MaterialOrder::where('material_request_id', $materialRequest->id)
            ->where('supplier_id', $data['supplierId'])
            ->update(['status' => 'confirmed']);

        return response()->json(['message' => 'Supplier selected']);
    }

    public function confirmDelivery(MaterialOrder $materialOrder, Request $request): JsonResponse
    {
        $materialOrder->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
            'confirmed_at' => now(),
        ]);

        $materialOrder->materialRequest->update(['status' => 'delivered']);

        return response()->json($materialOrder->fresh()->toApiArray());
    }

    // ═══════════════════════════════════════════════════════
    //  JOB EXECUTION
    // ═══════════════════════════════════════════════════════

    public function startWork(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Use valid ENUM value: 'workInProgress'
        $serviceJob->update([
            'status'     => 'workInProgress',
            'started_at' => now(),
        ]);

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    public function updateProgress(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'progressPercent' => 'nullable|integer|min:0|max:100',
            'progressNotes'   => 'nullable|string',
        ]);

        $serviceJob->update([
            'progress_percent' => $data['progressPercent'] ?? $serviceJob->progress_percent,
            'progress_notes'   => $data['progressNotes'] ?? $serviceJob->progress_notes,
        ]);

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    // ═══════════════════════════════════════════════════════
    //  COMPLETION (before/after photos, mark complete)
    // ═══════════════════════════════════════════════════════

    public function uploadBeforePhotos(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'string',
        ]);

        $existing = $serviceJob->before_photos ?? [];
        $serviceJob->update([
            'before_photos' => array_merge($existing, $data['images']),
        ]);

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    public function uploadAfterPhotos(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'string',
        ]);

        $existing = $serviceJob->after_photos ?? [];
        $serviceJob->update([
            'after_photos' => array_merge($existing, $data['images']),
        ]);

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    public function markComplete(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->artisan_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Use valid ENUM: 'completionPending' (waiting for client confirmation)
        $serviceJob->update([
            'status'           => 'completionPending',
            'completion_notes' => $data['notes'] ?? null,
            'progress_percent' => 100,
            'completed_at'     => now(),
        ]);

        // Notify Client
        if ($serviceJob->client) {
            $serviceJob->client->notify(new \App\Notifications\JobEventNotification(
                'Work Completion Pending',
                "The artisan has requested completion approval.",
                $serviceJob->id,
                'completion_pending'
            ));
        }

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    // ═══════════════════════════════════════════════════════
    //  WALLET / EARNINGS
    // ═══════════════════════════════════════════════════════

    public function wallet(Request $request): JsonResponse
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json($wallet->toApiArray());
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $request->user()->id]);

        $txns = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()->paginate(20);

        return response()->json(
            $txns->getCollection()->map->toApiArray()->toArray()
        );
    }

    // ═══════════════════════════════════════════════════════
    //  PROFILE
    // ═══════════════════════════════════════════════════════

    public function profile(Request $request): JsonResponse
    {
        $user    = $request->user();
        $profile = $this->getOrCreateProfile($user->id);

        return response()->json(array_merge(
            $user->toApiArray(),
            ['artisanProfile' => $profile->toApiArray()]
        ));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fullName'        => 'nullable|string|min:3',
            'location'        => 'nullable|string',
            'avatarUrl'       => 'nullable|string',
            'skillCategories' => 'nullable|array',
            'serviceRadius'   => 'nullable|numeric|min:1|max:100',
            'isAvailable'     => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (isset($data['fullName']))  $user->full_name  = $data['fullName'];
        if (isset($data['location']))  $user->location   = $data['location'];
        if (isset($data['avatarUrl'])) $user->avatar_url = $data['avatarUrl'];
        $user->save();

        $profile = $this->getOrCreateProfile($user->id);
        if (isset($data['skillCategories'])) $profile->skill_categories = $data['skillCategories'];
        if (isset($data['serviceRadius']))   $profile->service_radius   = $data['serviceRadius'];
        if (isset($data['isAvailable']))     $profile->is_available     = $data['isAvailable'];
        $profile->save();

        return response()->json(array_merge(
            $user->fresh()->toApiArray(),
            ['artisanProfile' => $profile->fresh()->toApiArray()]
        ));
    }

    public function trustScore(Request $request): JsonResponse
    {
        $user    = $request->user();
        $profile = $this->getOrCreateProfile($user->id);

        $completedCount = ServiceJob::where('artisan_id', $user->id)
            ->where('status', 'completed')->count();

        $avgRating = \App\Models\Review::whereHas('serviceJob', function ($q) use ($user) {
            $q->where('artisan_id', $user->id);
        })->avg('rating') ?? 0;

        return response()->json([
            'trustScore'    => $profile->trust_score,
            'tier'          => $profile->tier,
            'skillBadge'    => $profile->skill_badge,
            'completedJobs' => $completedCount,
            'averageRating' => round($avgRating, 2),
            'isAvailable'   => $profile->is_available,
        ]);
    }
}
