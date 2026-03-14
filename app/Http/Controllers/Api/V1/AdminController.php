<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\BroadcastNotification;
use App\Models\Dispute;
use App\Models\MaterialOrder;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\ServiceJob;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Get the authenticated Admin model (from admin-api guard).
     */
    private function admin(): Admin
    {
        return Auth::guard('admin-api')->user();
    }

    // ═══════════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════════

    public function dashboard(Request $request): JsonResponse
    {
        $stats = [
            'totalUsers'     => User::count(),
            'totalClients'   => User::where('role', 'client')->count(),
            'totalArtisans'  => User::where('role', 'artisan')->count(),
            'totalSuppliers' => User::where('role', 'supplier')->count(),
            'activeJobs'     => ServiceJob::whereNotIn('status', ['completed', 'cancelled', 'closed'])->count(),
            'completedJobs'  => ServiceJob::where('status', 'completed')->count(),
            'totalOrders'    => MaterialOrder::count(),
            'platformRevenue'=> Payment::where('status', 'completed')->sum('amount'),
        ];

        $pendingVerifications = User::where('account_status', 'verification_under_review')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($u) => [
                'id'        => (string) $u->id,
                'userId'    => (string) $u->id,
                'userName'  => $u->full_name,
                'userEmail' => $u->email,
                'role'      => $u->role,
                'status'    => 'pending',
                'submittedAt' => $u->updated_at?->toIso8601String(),
            ]);

        $recentUsers = User::latest()->take(5)->get()
            ->map(fn ($u) => $u->toApiArray());

        $recentJobs = ServiceJob::with('client', 'category')->latest()->take(5)->get()
            ->map(fn ($j) => [
                'id'           => (string) $j->id,
                'categoryName' => $j->category?->name ?? '',
                'status'       => $j->status,
                'location'     => $j->location ?? '',
                'clientName'   => $j->client?->full_name ?? '',
                'createdAt'    => $j->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'stats'                => $stats,
            'pendingVerifications' => $pendingVerifications,
            'recentUsers'          => $recentUsers,
            'recentJobs'           => $recentJobs,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  USER MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return response()->json([
            'data' => $users->items() ? collect($users->items())->map(fn ($u) => $u->toApiArray()) : [],
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function userDetail(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json(array_merge($user->toApiArray(), [
            'phone'          => $user->phone,
            'accountStatus'  => $user->flutter_account_status,
            'registeredAt'   => $user->created_at?->toIso8601String(),
            'jobsCount'      => $user->role === 'client'
                ? $user->clientJobs()->count()
                : ($user->role === 'artisan' ? $user->artisanJobs()->count() : 0),
            'status'         => $user->account_status,
        ]));
    }

    public function suspendUser(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $user->update(['account_status' => 'suspended']);

        $this->logActivity('suspended_user', 'user', $user->id);

        return response()->json(['message' => 'User suspended.']);
    }

    public function activateUser(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $user->update(['account_status' => 'active']);

        $this->logActivity('activated_user', 'user', $user->id);

        return response()->json(['message' => 'User activated.']);
    }

    public function deleteUser(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $this->logActivity('deleted_user', 'user', $user->id, [
            'name' => $user->full_name,
            'email' => $user->email,
        ]);
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    // ═══════════════════════════════════════════════════════
    //  VERIFICATION MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function pendingVerifications(Request $request): JsonResponse
    {
        $query = User::whereIn('account_status', ['verification_pending', 'verification_under_review'])
            ->has('verificationDocuments');

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20);

        $data = collect($users->items())->map(function ($user) {
            $docs = $user->verificationDocuments;
            return [
                'id'           => (string) $user->id,
                'userId'       => (string) $user->id,
                'userName'     => $user->full_name,
                'userEmail'    => $user->email,
                'role'         => $user->role,
                'status'       => $user->account_status === 'verification_under_review' ? 'pending' : 'submitted',
                'documentUrls' => $docs->pluck('document_url')->toArray(),
                'notes'        => $user->rejection_reason,
                'submittedAt'  => $user->updated_at?->toIso8601String(),
            ];
        });

        return response()->json($data->values());
    }

    public function verificationDetail(string $userId): JsonResponse
    {
        $user = User::with('verificationDocuments')->findOrFail($userId);
        $docs = $user->verificationDocuments;

        return response()->json([
            'id'           => (string) $user->id,
            'userId'       => (string) $user->id,
            'userName'     => $user->full_name,
            'userEmail'    => $user->email,
            'role'         => $user->role,
            'status'       => $user->account_status,
            'documentUrls' => $docs->pluck('document_url')->toArray(),
            'notes'        => $user->rejection_reason,
            'submittedAt'  => $user->updated_at?->toIso8601String(),
        ]);
    }

    public function approveVerification(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $newStatus = $user->role === 'supplier' ? 'subscription_required' : 'active';
        $user->update([
            'account_status'   => $newStatus,
            'rejection_reason' => null,
        ]);

        $this->logActivity('approved_verification', 'user', $user->id);

        return response()->json(['message' => 'Verification approved.']);
    }

    public function rejectVerification(string $userId, Request $request): JsonResponse
    {
        $user = User::findOrFail($userId);
        $user->update([
            'account_status'   => 'rejected',
            'rejection_reason' => $request->input('reason', 'Your verification was rejected.'),
        ]);

        $this->logActivity('rejected_verification', 'user', $user->id);

        return response()->json(['message' => 'Verification rejected.']);
    }

    // ═══════════════════════════════════════════════════════
    //  JOB MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function jobs(Request $request): JsonResponse
    {
        $query = ServiceJob::with('client', 'artisan', 'category');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $jobs = $query->latest()->paginate(20);

        $data = collect($jobs->items())->map(fn ($j) => [
            'id'           => (string) $j->id,
            'categoryName' => $j->category?->name ?? '',
            'status'       => $j->status,
            'location'     => $j->location ?? '',
            'clientName'   => $j->client?->full_name ?? '',
            'artisanName'  => $j->artisan?->full_name ?? '',
            'description'  => $j->description ?? '',
            'isFlagged'    => (bool) ($j->is_flagged ?? false),
            'createdAt'    => $j->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    public function jobDetail(string $jobId): JsonResponse
    {
        $job = ServiceJob::with('client', 'artisan', 'category')->findOrFail($jobId);

        return response()->json([
            'id'            => (string) $job->id,
            'category_name' => $job->category?->name ?? '',
            'status'        => $job->status,
            'location'      => $job->location ?? '',
            'description'   => $job->description ?? '',
            'client_name'   => $job->client?->full_name ?? '',
            'artisan_name'  => $job->artisan?->full_name ?? '',
            'is_flagged'    => (bool) ($job->is_flagged ?? false),
            'flagged_reason'=> $job->flagged_reason,
            'created_at'    => $job->created_at?->toIso8601String(),
        ]);
    }

    public function flagJob(string $jobId, Request $request): JsonResponse
    {
        $job = ServiceJob::findOrFail($jobId);
        $job->update([
            'is_flagged'     => true,
            'flagged_reason' => $request->input('reason', 'Flagged by admin'),
        ]);

        $this->logActivity('flagged_job', 'job', $job->id);

        return response()->json(['message' => 'Job flagged.']);
    }

    public function removeJob(string $jobId): JsonResponse
    {
        $job = ServiceJob::findOrFail($jobId);
        $this->logActivity('removed_job', 'job', $job->id);
        $job->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Job removed (cancelled).']);
    }

    // ═══════════════════════════════════════════════════════
    //  ORDER MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function orders(Request $request): JsonResponse
    {
        $query = MaterialOrder::with(['materialRequest.artisan', 'supplier']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(20);

        $data = collect($orders->items())->map(fn ($o) => [
            'id'           => (string) $o->id,
            'status'       => $o->status,
            'total_amount' => $o->total_amount ?? 0,
            'buyer_name'   => $o->materialRequest?->artisan?->full_name ?? '',
            'supplier_name'=> $o->supplier?->full_name ?? '',
            'created_at'   => $o->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function orderDetail(string $orderId): JsonResponse
    {
        $order = MaterialOrder::with(['materialRequest.artisan', 'supplier', 'items'])->findOrFail($orderId);

        return response()->json([
            'id'             => (string) $order->id,
            'status'         => $order->status,
            'total_amount'   => $order->total_amount ?? 0,
            'buyer_name'     => $order->materialRequest?->artisan?->full_name ?? '',
            'supplier_name'  => $order->supplier?->full_name ?? '',
            'payment_status' => $order->payment_status ?? 'pending',
            'items'          => ($order->items ?? collect())->map(fn ($item) => [
                'material_name' => $item->material_name ?? $item->name ?? '',
                'quantity'      => $item->quantity ?? 0,
                'price'         => $item->price ?? 0,
            ]),
            'created_at'     => $order->created_at?->toIso8601String(),
        ]);
    }

    public function cancelOrder(string $orderId, Request $request): JsonResponse
    {
        $order = MaterialOrder::findOrFail($orderId);
        $order->update(['status' => 'cancelled']);

        $this->logActivity('cancelled_order', 'order', $order->id);

        return response()->json(['message' => 'Order cancelled.']);
    }

    // ═══════════════════════════════════════════════════════
    //  PAYMENT MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::with('payer');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->query('type')) {
            $query->where('method', $type);
        }

        $payments = $query->latest()->paginate(20);

        $data = collect($payments->items())->map(fn ($p) => [
            'id'        => (string) $p->id,
            'userId'    => (string) $p->payer_id,
            'userName'  => $p->payer?->full_name ?? '',
            'type'      => $p->method ?? 'card',
            'amount'    => (string) $p->amount,
            'status'    => $p->status,
            'reference' => $p->reference ?? '',
            'createdAt' => $p->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    public function paymentDetail(string $paymentId): JsonResponse
    {
        $payment = Payment::with('payer')->findOrFail($paymentId);

        return response()->json([
            'id'        => (string) $payment->id,
            'user_name' => $payment->payer?->full_name ?? '',
            'type'      => $payment->method ?? 'card',
            'amount'    => (string) $payment->amount,
            'status'    => $payment->status,
            'reference' => $payment->reference ?? '',
            'created_at'=> $payment->created_at?->toIso8601String(),
        ]);
    }

    public function issueRefund(string $paymentId, Request $request): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->update(['status' => 'refunded']);

        $this->logActivity('issued_refund', 'payment', $payment->id, [
            'amount' => $request->input('amount', $payment->amount),
            'reason' => $request->input('reason'),
        ]);

        return response()->json(['message' => 'Refund issued.']);
    }

    // ═══════════════════════════════════════════════════════
    //  DISPUTE MANAGEMENT
    // ═══════════════════════════════════════════════════════

    public function disputes(Request $request): JsonResponse
    {
        $query = Dispute::with(['reportedBy', 'against']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $disputes = $query->latest()->paginate(20);

        $data = collect($disputes->items())->map(fn ($d) => [
            'id'             => (string) $d->id,
            'jobId'          => (string) $d->service_job_id,
            'reportedById'   => (string) $d->reported_by_id,
            'reportedByName' => $d->reportedBy?->full_name ?? '',
            'againstId'      => (string) $d->against_id,
            'againstName'    => $d->against?->full_name ?? '',
            'reason'         => $d->reason,
            'status'         => $d->status,
            'resolution'     => $d->resolution,
            'createdAt'      => $d->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $disputes->currentPage(),
                'last_page'    => $disputes->lastPage(),
                'total'        => $disputes->total(),
            ],
        ]);
    }

    public function disputeDetail(string $disputeId): JsonResponse
    {
        $dispute = Dispute::with(['reportedBy', 'against', 'resolvedBy'])->findOrFail($disputeId);

        return response()->json([
            'id'               => (string) $dispute->id,
            'job_id'           => $dispute->service_job_id,
            'reported_by_name' => $dispute->reportedBy?->full_name ?? '',
            'against_name'     => $dispute->against?->full_name ?? '',
            'reason'           => $dispute->reason,
            'status'           => $dispute->status,
            'resolution'       => $dispute->resolution,
            'admin_notes'      => $dispute->admin_notes,
            'resolved_by'      => $dispute->resolvedBy?->full_name,
            'resolved_at'      => $dispute->resolved_at?->toIso8601String(),
            'created_at'       => $dispute->created_at?->toIso8601String(),
        ]);
    }

    public function resolveDispute(string $disputeId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'resolution' => 'required|string|in:resolved,dismissed',
            'notes'      => 'nullable|string',
        ]);

        $dispute = Dispute::findOrFail($disputeId);
        $dispute->update([
            'status'        => $data['resolution'],
            'resolution'    => $data['resolution'],
            'admin_notes'   => $data['notes'] ?? null,
            'resolved_by_id'=> $this->admin()->id,
            'resolved_at'   => now(),
        ]);

        $this->logActivity('resolved_dispute', 'dispute', $dispute->id);

        return response()->json(['message' => "Dispute {$data['resolution']}."]);
    }

    // ═══════════════════════════════════════════════════════
    //  ANALYTICS
    // ═══════════════════════════════════════════════════════

    public function analytics(Request $request): JsonResponse
    {
        $period = $request->query('period', '30d');
        $days = match ($period) {
            '7d'  => 7,
            '30d' => 30,
            '90d' => 90,
            '1y'  => 365,
            default => 30,
        };

        $from = now()->subDays($days);

        $userGrowth = User::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as label, COUNT(*) as value')
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (double) $r->value]);

        $revenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as label, SUM(amount) as value')
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'value' => (double) $r->value]);

        $jobsByCategory = ServiceJob::with('category')
            ->where('created_at', '>=', $from)
            ->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($r) => [
                'label' => $r->category?->name ?? 'Unknown',
                'value' => (double) $r->cnt,
            ]);

        $summary = [
            'new_users'     => User::where('created_at', '>=', $from)->count(),
            'total_revenue' => Payment::where('status', 'completed')->where('created_at', '>=', $from)->sum('amount'),
            'jobs_posted'   => ServiceJob::where('created_at', '>=', $from)->count(),
            'total_orders'  => MaterialOrder::where('created_at', '>=', $from)->count(),
        ];

        return response()->json([
            'user_growth'      => $userGrowth,
            'revenue'          => $revenue,
            'jobs_by_category' => $jobsByCategory,
            'summary'          => $summary,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  NOTIFICATIONS
    // ═══════════════════════════════════════════════════════

    public function sendBroadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'body'            => 'required|string',
            'target_role'     => 'nullable|string|in:client,artisan,supplier',
            'target_user_ids' => 'nullable|array',
        ]);

        $notification = BroadcastNotification::create([
            'admin_id'        => $this->admin()->id,
            'title'           => $data['title'],
            'body'            => $data['body'],
            'target_role'     => $data['target_role'] ?? null,
            'target_user_ids' => $data['target_user_ids'] ?? null,
        ]);

        $this->logActivity('sent_broadcast', 'notification', $notification->id);

        return response()->json(['message' => 'Broadcast sent.']);
    }

    public function notificationHistory(Request $request): JsonResponse
    {
        $notifications = BroadcastNotification::latest()->paginate(20);

        $data = collect($notifications->items())->map(fn ($n) => [
            'id'          => (string) $n->id,
            'title'       => $n->title,
            'body'        => $n->body,
            'target_role' => $n->target_role ?? 'All',
            'created_at'  => $n->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  SETTINGS
    // ═══════════════════════════════════════════════════════

    public function getSettings(): JsonResponse
    {
        $commission  = PlatformSetting::getValue('commission_percent', 10);
        $jobCats     = PlatformSetting::getValue('job_categories', []);
        $matCats     = PlatformSetting::getValue('material_categories', []);
        $toggles     = PlatformSetting::getValue('feature_toggles', []);

        return response()->json([
            'commissionPercent'  => $commission,
            'jobCategories'      => $jobCats,
            'materialCategories' => $matCats,
            'featureToggles'     => $toggles,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->all();

        if (isset($data['commissionPercent'])) {
            PlatformSetting::setValue('commission_percent', $data['commissionPercent']);
        }
        if (isset($data['jobCategories'])) {
            PlatformSetting::setValue('job_categories', $data['jobCategories']);
        }
        if (isset($data['materialCategories'])) {
            PlatformSetting::setValue('material_categories', $data['materialCategories']);
        }
        if (isset($data['featureToggles'])) {
            PlatformSetting::setValue('feature_toggles', $data['featureToggles']);
        }

        $this->logActivity('updated_settings', 'settings', null);

        return response()->json(['message' => 'Settings updated.']);
    }

    // ═══════════════════════════════════════════════════════
    //  ADMIN PROFILE
    // ═══════════════════════════════════════════════════════

    public function profile(Request $request): JsonResponse
    {
        $admin = $this->admin();

        return response()->json([
            'id'    => (string) $admin->id,
            'name'  => $admin->full_name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'role'  => 'admin',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $admin = $this->admin();

        $data = $request->validate([
            'name'  => 'sometimes|string|min:2',
            'email' => 'sometimes|email|unique:admins,email,' . $admin->id,
        ]);

        if (isset($data['name']))  $admin->full_name = $data['name'];
        if (isset($data['email'])) $admin->email     = $data['email'];
        $admin->save();

        $this->logActivity('updated_profile', 'admin', $admin->id);

        return response()->json(['message' => 'Profile updated.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ]);

        $admin = $this->admin();

        if (!Hash::check($data['current_password'], $admin->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $admin->update(['password' => $data['password']]);

        $this->logActivity('changed_password', 'admin', $admin->id);

        return response()->json(['message' => 'Password changed.']);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        $logs = AdminActivityLog::where('admin_id', $this->admin()->id)
            ->latest()
            ->paginate(30);

        $data = collect($logs->items())->map(fn ($log) => [
            'id'          => (string) $log->id,
            'action'      => $log->action,
            'target_type' => $log->target_type,
            'target_id'   => $log->target_id,
            'details'     => $log->details,
            'created_at'  => $log->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  HELPER: log admin action
    // ═══════════════════════════════════════════════════════

    private function logActivity(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $details = null,
    ): void {
        AdminActivityLog::create([
            'admin_id'    => $this->admin()->id,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => $details,
        ]);
    }
}
