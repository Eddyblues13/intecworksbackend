<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['message' => 'Artisan accepted.']);
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

        return response()->json(['message' => 'Quote approved.']);
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

        return response()->json(['message' => 'Job marked as completed.']);
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
}
