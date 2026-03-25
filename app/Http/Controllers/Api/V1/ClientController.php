<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FavoriteArtisan;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\EscrowService;

class ClientController extends Controller
{
    // ═══════════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════════

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Counts for stat cards
        $activeJobCount    = ServiceJob::where('client_id', $user->id)
                ->whereNotIn('status', ['completed', 'closed', 'cancelled'])->count();
        $pendingJobCount   = ServiceJob::where('client_id', $user->id)
                ->whereIn('status', ['created', 'open', 'pending'])->count();
        $completedJobCount = ServiceJob::where('client_id', $user->id)
                ->where('status', 'completed')->count();

        // Recent active jobs (list for cards)
        $activeJobs = ServiceJob::where('client_id', $user->id)
                ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
                ->latest()->take(5)->get()
                ->map->toApiArray()->toArray();

        // Recent completed jobs
        $completedJobs = ServiceJob::where('client_id', $user->id)
                ->where('status', 'completed')
                ->latest()->take(5)->get()
                ->map->toApiArray()->toArray();

        // Pending requests count
        $pendingRequests = JobApplication::whereHas('serviceJob', function ($q) use ($user) {
            $q->where('client_id', $user->id);
        })->where('status', 'pending')->count();

        // Recommended artisans (top rated available artisans)
        $recommendedArtisans = User::where('role', 'artisan')
                ->where('account_status', 'active')
                ->orderByDesc('trust_score')
                ->take(10)->get()
                ->map(function ($a) {
                    return [
                        'id'              => (string) $a->id,
                        'fullName'        => $a->full_name,
                        'avatarUrl'       => $a->avatar_url,
                        'rating'          => (float) ($a->trust_score ?? 0),
                        'completedJobs'   => ServiceJob::where('artisan_id', $a->id)->where('status', 'completed')->count(),
                        'skillCategories' => [],
                        'distance'        => null,
                        'hourlyRate'      => null,
                        'isAvailable'     => true,
                        'bio'             => null,
                    ];
                })->toArray();

        // Recently hired artisans by this client
        $recentlyHiredIds = ServiceJob::where('client_id', $user->id)
                ->whereNotNull('artisan_id')
                ->latest()->take(5)->pluck('artisan_id')->unique();
        $recentlyHired = User::whereIn('id', $recentlyHiredIds)->get()
                ->map(function ($a) {
                    return [
                        'id'              => (string) $a->id,
                        'fullName'        => $a->full_name,
                        'avatarUrl'       => $a->avatar_url,
                        'rating'          => (float) ($a->trust_score ?? 0),
                        'completedJobs'   => ServiceJob::where('artisan_id', $a->id)->where('status', 'completed')->count(),
                        'skillCategories' => [],
                        'distance'        => null,
                        'hourlyRate'      => null,
                        'isAvailable'     => true,
                        'bio'             => null,
                    ];
                })->toArray();

        return response()->json([
            'activeJobCount'      => $activeJobCount,
            'pendingJobCount'     => $pendingJobCount,
            'completedJobCount'   => $completedJobCount,
            'activeJobs'          => $activeJobs,
            'completedJobs'       => $completedJobs,
            'pendingRequests'     => $pendingRequests,
            'recommendedArtisans' => $recommendedArtisans,
            'recentlyHired'       => $recentlyHired,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  CATEGORIES
    // ═══════════════════════════════════════════════════════

    public function categories(): JsonResponse
    {
        $categories = Category::with('subcategories')
            ->where('is_active', true)->get()
            ->map->toApiArray()->toArray();

        return response()->json($categories);
    }

    public function subcategories(Category $category): JsonResponse
    {
        return response()->json(
            $category->subcategories->map->toApiArray()->toArray()
        );
    }

    // ═══════════════════════════════════════════════════════
    //  POST JOB
    // ═══════════════════════════════════════════════════════

    public function createJob(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categoryId'    => 'required|exists:categories,id',
            'subcategoryId' => 'nullable|exists:subcategories,id',
            'jobType'       => 'nullable|in:fixedPrice,custom',
            'description'   => 'required|string|min:10',
            'images'        => 'nullable|array',
            'images.*'      => 'string',
            'location'      => 'required|string',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        $job = ServiceJob::create([
            'client_id'      => $request->user()->id,
            'category_id'    => $data['categoryId'],
            'subcategory_id' => $data['subcategoryId'] ?? null,
            'job_type'       => $data['jobType'] ?? 'custom',
            'description'    => $data['description'],
            'images'         => $data['images'] ?? [],
            'location'       => $data['location'],
            'lat'            => $data['lat'] ?? null,
            'lng'            => $data['lng'] ?? null,
            'status'         => 'created',
        ]);

        return response()->json($job->toApiArray(), 201);
    }

    // ═══════════════════════════════════════════════════════
    //  MY JOBS (client view)
    // ═══════════════════════════════════════════════════════

    public function myJobs(Request $request): JsonResponse
    {
        $query = ServiceJob::where('client_id', $request->user()->id)->latest();

        if ($request->has('status')) {
            $status = $request->input('status');
            switch ($status) {
                case 'active':
                    $query->whereNotIn('status', ['created', 'open', 'pending', 'completed', 'closed', 'cancelled']);
                    break;
                case 'pending':
                    $query->whereIn('status', ['created', 'open', 'pending']);
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
                    break;
                default:
                    $query->where('status', $status);
            }
        }

        $jobs = $query->get()->map->toApiArray()->toArray();
        return response()->json($jobs);
    }

    public function jobDetail(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($serviceJob->toApiArray());
    }

    public function updateJob(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (in_array($serviceJob->status, ['accepted', 'completed', 'closed', 'cancelled'])) {
            return response()->json(['message' => 'Job cannot be modified in its current state.'], 422);
        }

        $data = $request->validate([
            'categoryId'    => 'sometimes|exists:categories,id',
            'subcategoryId' => 'nullable|exists:subcategories,id',
            'jobType'       => 'sometimes|in:fixedPrice,custom',
            'description'   => 'sometimes|string|min:10',
            'images'        => 'nullable|array',
            'images.*'      => 'string',
            'location'      => 'sometimes|string',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        if (isset($data['categoryId'])) $serviceJob->category_id = $data['categoryId'];
        if (array_key_exists('subcategoryId', $data)) $serviceJob->subcategory_id = $data['subcategoryId'];
        if (isset($data['jobType'])) $serviceJob->job_type = $data['jobType'];
        if (isset($data['description'])) $serviceJob->description = $data['description'];
        if (array_key_exists('images', $data)) $serviceJob->images = $data['images'];
        if (isset($data['location'])) $serviceJob->location = $data['location'];
        if (array_key_exists('lat', $data)) $serviceJob->lat = $data['lat'];
        if (array_key_exists('lng', $data)) $serviceJob->lng = $data['lng'];

        $serviceJob->save();

        return response()->json($serviceJob->fresh()->toApiArray());
    }

    public function deleteJob(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (in_array($serviceJob->status, ['accepted', 'completed', 'closed'])) {
            return response()->json(['message' => 'Job cannot be deleted in its current state.'], 422);
        }

        $serviceJob->delete();

        return response()->json(['message' => 'Job deleted successfully.']);
    }

    // ═══════════════════════════════════════════════════════
    //  JOB APPLICANTS
    // ═══════════════════════════════════════════════════════

    public function jobApplicants(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $applicants = $serviceJob->applications()
            ->with('artisan')
            ->where('status', 'pending')
            ->get()
            ->map(function (JobApplication $app) {
                $artisan = $app->artisan;
                $completedJobs = ServiceJob::where('artisan_id', $artisan->id)->where('status', 'completed')->count();
                $avgRating     = Review::where('reviewee_id', $artisan->id)->avg('rating') ?? 0;
                return [
                    'id'             => (string) $artisan->id,
                    'fullName'       => $artisan->full_name,
                    'avatarUrl'      => $artisan->avatar_url,
                    'rating'         => round($avgRating, 1),
                    'completedJobs'  => $completedJobs,
                    'skillCategories'=> [],
                    'distance'       => null,
                    'hourlyRate'     => null,
                    'isAvailable'    => true,
                    'bio'            => null,
                ];
            })->toArray();

        return response()->json($applicants);
    }

    public function acceptArtisan(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate(['artisanId' => 'required|exists:users,id']);

        // Accept this applicant
        JobApplication::where('service_job_id', $serviceJob->id)
            ->where('artisan_id', $data['artisanId'])
            ->update(['status' => 'accepted']);

        // Reject the rest
        JobApplication::where('service_job_id', $serviceJob->id)
            ->where('artisan_id', '!=', $data['artisanId'])
            ->update(['status' => 'rejected']);

        $serviceJob->update([
            'artisan_id'  => $data['artisanId'],
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        // Create escrow — client must fund deposit before artisan begins
        $escrowService = app(EscrowService::class);
        $escrow = $escrowService->createEscrow($serviceJob->fresh());

        return response()->json([
            'message' => 'Artisan accepted. Please fund the escrow deposit.',
            'escrow'  => $escrow->toApiArray(),
        ]);
    }

    public function rejectArtisan(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate(['artisanId' => 'required|exists:users,id']);

        JobApplication::where('service_job_id', $serviceJob->id)
            ->where('artisan_id', $data['artisanId'])
            ->update(['status' => 'rejected']);

        return response()->json(['message' => 'Artisan rejected.']);
    }

    // ═══════════════════════════════════════════════════════
    //  BROWSE ARTISANS
    // ═══════════════════════════════════════════════════════

    public function browseArtisans(Request $request): JsonResponse
    {
        $query = User::where('role', 'artisan')->where('account_status', 'active');

        // ── Text search (name / email) ──
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoryId')) {
            // Filter by artisans who have done jobs in this category
            $catId = $request->input('categoryId');
            $query->whereHas('artisanJobs', fn($q) => $q->where('category_id', $catId));
        }

        $artisans = $query->get()->map(function (User $u) {
            $completedJobs = ServiceJob::where('artisan_id', $u->id)->where('status', 'completed')->count();
            $avgRating     = Review::where('reviewee_id', $u->id)->avg('rating') ?? 0;
            return [
                'id'             => (string) $u->id,
                'fullName'       => $u->full_name,
                'avatarUrl'      => $u->avatar_url,
                'rating'         => round($avgRating, 1),
                'completedJobs'  => $completedJobs,
                'skillCategories'=> [],
                'distance'       => null,
                'hourlyRate'     => null,
                'isAvailable'    => true,
                'bio'            => null,
            ];
        })->toArray();

        return response()->json($artisans);
    }

    public function artisanProfile(User $artisan): JsonResponse
    {
        if ($artisan->role !== 'artisan') {
            return response()->json(['message' => 'Not an artisan'], 404);
        }

        $completedJobs = ServiceJob::where('artisan_id', $artisan->id)->where('status', 'completed')->count();
        $avgRating     = Review::where('reviewee_id', $artisan->id)->avg('rating') ?? 0;
        $reviewCount   = Review::where('reviewee_id', $artisan->id)->count();

        return response()->json([
            'id'             => (string) $artisan->id,
            'fullName'       => $artisan->full_name,
            'avatarUrl'      => $artisan->avatar_url,
            'rating'         => round($avgRating, 1),
            'reviewCount'    => $reviewCount,
            'completedJobs'  => $completedJobs,
            'skillCategories'=> [],
            'distance'       => null,
            'hourlyRate'     => null,
            'isAvailable'    => true,
            'bio'            => null,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  QUOTES (client side)
    // ═══════════════════════════════════════════════════════

    public function jobQuote(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quote = $serviceJob->quotes()->with('items')->latest()->first();
        if (!$quote) {
            return response()->json(['message' => 'No quote yet.'], 404);
        }

        return response()->json($quote->toApiArray());
    }

    public function approveQuote(Quote $quote, Request $request): JsonResponse
    {
        $job = $quote->serviceJob;
        if ($job->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quote->update([
            'status'             => 'approved',
            'client_responded_at'=> now(),
        ]);
        $job->update(['status' => 'quoteApproved']);

        // Calculate remaining escrow amount from quote
        $escrowService = app(EscrowService::class);
        $escrow = $escrowService->setRemainingFromQuote(
            $job->id,
            $quote->total_amount ?? 0,
            $quote->material_total ?? 0
        );
        
        // Notify Artisan
        if ($job->artisan) {
            $job->artisan->notify(new \App\Notifications\JobEventNotification(
                'Quote Approved',
                "Your quote was approved. You can start the work!",
                $job->id,
                'quote_approved'
            ));
        }

        return response()->json([
            'message' => 'Quote approved. Please fund the remaining escrow balance.',
            'escrow'  => $escrow?->toApiArray(),
        ]);
    }

    public function rejectQuote(Quote $quote, Request $request): JsonResponse
    {
        $job = $quote->serviceJob;
        if ($job->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $quote->update([
            'status'             => 'rejected',
            'client_responded_at'=> now(),
        ]);
        $job->update(['status' => 'quoteRejected']);

        return response()->json(['message' => 'Quote rejected.']);
    }

    // ═══════════════════════════════════════════════════════
    //  JOB PROGRESS & COMPLETION
    // ═══════════════════════════════════════════════════════

    public function jobProgress(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'job'    => $serviceJob->toApiArray(),
            'quote'  => $serviceJob->quotes()->with('items')->latest()->first()?->toApiArray(),
        ]);
    }

    public function approveCompletion(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $serviceJob->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // Release escrow funds to artisan (minus platform commission)
        $escrowService = app(EscrowService::class);
        $escrow = $escrowService->releaseFunds($serviceJob->id);
        
        // Notify Artisan
        if ($serviceJob->artisan) {
            $serviceJob->artisan->notify(new \App\Notifications\JobEventNotification(
                'Work Completed Confirmed',
                "The client confirmed the completion. Funds have been released.",
                $serviceJob->id,
                'completion_approved'
            ));
        }

        return response()->json([
            'message' => 'Job completed. Escrow funds released to artisan.',
            'escrow'  => $escrow?->toApiArray(),
        ]);
    }

    public function requestRevision(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate(['reason' => 'required|string']);

        $serviceJob->update(['status' => 'workInProgress']);

        return response()->json(['message' => 'Revision requested.']);
    }

    // ═══════════════════════════════════════════════════════
    //  REVIEWS
    // ═══════════════════════════════════════════════════════

    public function submitReview(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$serviceJob->artisan_id) {
            return response()->json(['message' => 'No artisan assigned.'], 400);
        }

        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::updateOrCreate(
            ['service_job_id' => $serviceJob->id, 'reviewer_id' => $request->user()->id],
            [
                'reviewee_id' => $serviceJob->artisan_id,
                'rating'      => $data['rating'],
                'comment'     => $data['comment'] ?? null,
            ]
        );

        return response()->json($review->toApiArray(), 201);
    }

    // ═══════════════════════════════════════════════════════
    //  PAYMENTS
    // ═══════════════════════════════════════════════════════

    public function paymentHistory(Request $request): JsonResponse
    {
        $payments = Payment::where('payer_id', $request->user()->id)
            ->latest()->get()
            ->map->toApiArray()->toArray();

        return response()->json($payments);
    }

    public function paymentDetail(Payment $payment, Request $request): JsonResponse
    {
        if ($payment->payer_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($payment->toApiArray());
    }

    public function makePayment(ServiceJob $serviceJob, Request $request): JsonResponse
    {
        if ($serviceJob->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:card,eft,wallet',
        ]);

        $payment = Payment::create([
            'service_job_id' => $serviceJob->id,
            'payer_id'       => $request->user()->id,
            'amount'         => $data['amount'],
            'method'         => $data['method'],
            'status'         => 'completed',
            'reference'      => 'PAY-' . strtoupper(substr(md5(now()), 0, 10)),
        ]);

        return response()->json($payment->toApiArray(), 201);
    }

    // ═══════════════════════════════════════════════════════
    //  PROFILE
    // ═══════════════════════════════════════════════════════

    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user()->toApiArray());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fullName' => 'nullable|string|min:3',
            'location' => 'nullable|string',
            'avatarUrl'=> 'nullable|string',
        ]);

        $user = $request->user();
        if (isset($data['fullName'])) $user->full_name  = $data['fullName'];
        if (isset($data['location'])) $user->location    = $data['location'];
        if (isset($data['avatarUrl'])) $user->avatar_url = $data['avatarUrl'];
        $user->save();

        return response()->json($user->fresh()->toApiArray());
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword'     => 'required|string|min:8',
        ]);

        $user = $request->user();
        if (!\Hash::check($data['currentPassword'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => $data['newPassword']]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function hiringHistory(Request $request): JsonResponse
    {
        $jobs = ServiceJob::where('client_id', $request->user()->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->get()
            ->map->toApiArray()->toArray();

        return response()->json($jobs);
    }

    // ═══════════════════════════════════════════════════════
    //  FAVORITES
    // ═══════════════════════════════════════════════════════

    public function favorites(Request $request): JsonResponse
    {
        $clientId = $request->user()->id;

        $favorites = FavoriteArtisan::where('client_id', $clientId)
            ->with('artisan')
            ->latest()
            ->get()
            ->map(function (FavoriteArtisan $fav) {
                $artisan = $fav->artisan;
                if (!$artisan) return null;

                $completedJobs = ServiceJob::where('artisan_id', $artisan->id)
                    ->where('status', 'completed')->count();
                $avgRating = Review::where('reviewee_id', $artisan->id)
                    ->avg('rating') ?? 0;

                return [
                    'id'             => (string) $fav->id,
                    'artisanId'      => (string) $artisan->id,
                    'fullName'       => $artisan->full_name,
                    'avatarUrl'      => $artisan->avatar_url,
                    'rating'         => round($avgRating, 1),
                    'completedJobs'  => $completedJobs,
                    'location'       => $artisan->location,
                    'isAvailable'    => true,
                    'createdAt'      => $fav->created_at?->toIso8601String(),
                ];
            })->filter()->values()->toArray();

        return response()->json($favorites);
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $request->validate(['artisanId' => 'required|exists:users,id']);

        $clientId  = $request->user()->id;
        $artisanId = $request->input('artisanId');

        $existing = FavoriteArtisan::where('client_id', $clientId)
            ->where('artisan_id', $artisanId)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Removed from favorites', 'isFavorite' => false]);
        }

        FavoriteArtisan::create(['client_id' => $clientId, 'artisan_id' => $artisanId]);
        return response()->json(['message' => 'Added to favorites', 'isFavorite' => true]);
    }

    public function removeFavorite(Request $request, string $artisanId): JsonResponse
    {
        FavoriteArtisan::where('client_id', $request->user()->id)
            ->where('artisan_id', $artisanId)
            ->delete();

        return response()->json(['message' => 'Removed from favorites']);
    }
}
// NOTE: This is being appended incorrectly, I need to insert before the closing brace
