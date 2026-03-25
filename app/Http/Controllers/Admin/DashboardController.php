<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\BroadcastNotification;
use App\Models\Dispute;
use App\Models\MaterialOrder;
use App\Models\OtpVerification;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\ServiceJob;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    private function admin(): Admin
    {
        return Auth::guard('admin-web')->user();
    }

    private function logActivity(string $action, ?string $targetType = null, ?int $targetId = null, ?array $details = null): void
    {
        AdminActivityLog::create([
            'admin_id' => $this->admin()->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }

    // ══════════════════════════════════════════════
    //  DASHBOARD
    // ══════════════════════════════════════════════

    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalClients' => User::where('role', 'client')->count(),
            'totalArtisans' => User::where('role', 'artisan')->count(),
            'totalSuppliers' => User::where('role', 'supplier')->count(),
            'activeJobs' => ServiceJob::whereNotIn('status', ['completed', 'cancelled', 'closed'])->count(),
            'completedJobs' => ServiceJob::where('status', 'completed')->count(),
            'totalOrders' => MaterialOrder::count(),
            'platformRevenue' => Payment::where('status', 'completed')->sum('amount'),
        ];

        $pendingVerifications = User::where('account_status', 'verification_under_review')
            ->latest()->take(5)->get();

        $recentUsers = User::latest()->take(5)->get();

        $recentJobs = ServiceJob::with('client', 'category')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'pendingVerifications', 'recentUsers', 'recentJobs'));
    }

    // ══════════════════════════════════════════════
    //  USERS
    // ══════════════════════════════════════════════

    public function users(Request $request)
    {
        $query = User::query();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($status = $request->query('status')) {
            $query->where('account_status', $status);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function userDetail(string $userId)
    {
        $user = User::with(['verificationDocuments', 'otpVerifications'])->findOrFail($userId);
        $jobsCount = $user->role === 'client'
            ? $user->clientJobs()->count()
            : ($user->role === 'artisan' ? $user->artisanJobs()->count() : 0);
        $otpHistory = $user->otpVerifications()->latest()->get();

        return view('admin.users.show', compact('user', 'jobsCount', 'otpHistory'));
    }

    public function suspendUser(string $userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['account_status' => 'suspended']);
        $this->logActivity('suspended_user', 'user', $user->id);

        return back()->with('success', 'User suspended successfully.');
    }

    public function activateUser(string $userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['account_status' => 'active']);
        $this->logActivity('activated_user', 'user', $user->id);

        return back()->with('success', 'User activated successfully.');
    }

    public function deleteUser(string $userId)
    {
        $user = User::findOrFail($userId);
        $this->logActivity('deleted_user', 'user', $user->id, [
            'name' => $user->full_name,
            'email' => $user->email,
        ]);
        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    // ══════════════════════════════════════════════
    //  VERIFICATIONS
    // ══════════════════════════════════════════════

    public function verifications(Request $request)
    {
        $query = User::query();

        // Filter by verification stage
        $stage = $request->query('stage');
        if ($stage) {
            $query->where('account_status', $stage);
        } else {
            // Default: show all users that need attention
            $query->whereIn('account_status', [
                'otp_pending',
                'verification_pending',
                'verification_under_review',
                'rejected',
            ]);
        }

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

        $users = $query->with(['otpVerifications', 'verificationDocuments'])
            ->latest()->paginate(20)->withQueryString();

        // Stage counts for the filter badges
        $stageCounts = [
            'all' => User::whereIn('account_status', ['otp_pending', 'verification_pending', 'verification_under_review', 'rejected'])->count(),
            'otp_pending' => User::where('account_status', 'otp_pending')->count(),
            'verification_pending' => User::where('account_status', 'verification_pending')->count(),
            'verification_under_review' => User::where('account_status', 'verification_under_review')->count(),
            'rejected' => User::where('account_status', 'rejected')->count(),
        ];

        return view('admin.verifications.index', compact('users', 'stageCounts'));
    }

    public function verificationDetail(string $userId)
    {
        $user = User::with(['verificationDocuments', 'otpVerifications'])->findOrFail($userId);
        $otpHistory = $user->otpVerifications()->latest()->get();
        $latestDoc = $user->verificationDocuments()->latest()->first();

        return view('admin.verifications.show', compact('user', 'otpHistory', 'latestDoc'));
    }

    public function approveVerification(string $userId)
    {
        $user = User::findOrFail($userId);
        $newStatus = $user->role === 'supplier' ? 'subscription_required' : 'active';
        $user->update([
            'account_status' => $newStatus,
            'rejection_reason' => null,
        ]);

        // Mark latest document as approved
        $latestDoc = $user->verificationDocuments()->where('status', 'pending')->latest()->first();
        if ($latestDoc) {
            $latestDoc->update([
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);
        }

        $this->logActivity('approved_verification', 'user', $user->id);

        return redirect()->route('admin.verifications')->with('success', "Verification approved for {$user->full_name}.");
    }

    public function rejectVerification(string $userId, Request $request)
    {
        $request->validate(['reason' => 'required|string|min:5']);

        $user = User::findOrFail($userId);
        $user->update([
            'account_status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        // Mark latest document as rejected
        $latestDoc = $user->verificationDocuments()->where('status', 'pending')->latest()->first();
        if ($latestDoc) {
            $latestDoc->update([
                'status' => 'rejected',
                'rejection_reason' => $request->input('reason'),
                'reviewed_at' => now(),
            ]);
        }

        $this->logActivity('rejected_verification', 'user', $user->id);

        return redirect()->route('admin.verifications')->with('success', "Verification rejected for {$user->full_name}.");
    }

    /**
     * Manually verify a user's OTP / phone — bypasses the SMS flow.
     * Moves user from otp_pending → verification_pending (providers) or active (clients).
     */
    public function manuallyVerifyOtp(string $userId)
    {
        $user = User::findOrFail($userId);

        // Mark phone as verified
        $user->update([
            'phone_verified_at' => now(),
        ]);

        // Mark all pending OTP records as verified
        OtpVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'verified']);

        // Advance account status
        if ($user->account_status === 'otp_pending') {
            if ($user->isProvider()) {
                $user->update(['account_status' => 'verification_pending']);
            } else {
                // Clients don't need document verification
                $user->update(['account_status' => 'active']);
            }
        }

        $this->logActivity('manually_verified_otp', 'user', $user->id, [
            'phone' => $user->phone,
        ]);

        return back()->with('success', "Phone/OTP manually verified for {$user->full_name}.");
    }

    /**
     * Admin can advance a user to any status in the verification pipeline.
     */
    public function advanceUserStatus(string $userId, Request $request)
    {
        $data = $request->validate([
            'new_status' => 'required|string|in:otp_pending,verification_pending,verification_under_review,approved,active,subscription_required,rejected,suspended',
        ]);

        $user = User::findOrFail($userId);
        $oldStatus = $user->account_status;

        // If advancing to active or approved, also mark phone as verified if not already
        if (in_array($data['new_status'], ['active', 'approved', 'subscription_required'])) {
            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // Map 'approved' → actual status based on role
        $newStatus = $data['new_status'];
        if ($newStatus === 'approved') {
            $newStatus = $user->role === 'supplier' ? 'subscription_required' : 'active';
        }

        $user->update([
            'account_status' => $newStatus,
            'rejection_reason' => null,
        ]);

        $this->logActivity('advanced_user_status', 'user', $user->id, [
            'from' => $oldStatus,
            'to' => $newStatus,
        ]);

        return back()->with('success', "Status updated from '{$oldStatus}' to '{$newStatus}' for {$user->full_name}.");
    }

    // ══════════════════════════════════════════════
    //  JOBS
    // ══════════════════════════════════════════════

    public function jobs(Request $request)
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

        $jobs = $query->latest()->paginate(20)->withQueryString();

        return view('admin.jobs.index', compact('jobs'));
    }

    public function jobDetail(string $jobId)
    {
        $job = ServiceJob::with('client', 'artisan', 'category')->findOrFail($jobId);

        return view('admin.jobs.show', compact('job'));
    }

    public function flagJob(string $jobId, Request $request)
    {
        $request->validate(['reason' => 'required|string|min:3']);

        $job = ServiceJob::findOrFail($jobId);
        $job->update([
            'is_flagged' => true,
            'flagged_reason' => $request->input('reason'),
        ]);

        $this->logActivity('flagged_job', 'job', $job->id);

        return back()->with('success', 'Job flagged.');
    }

    public function removeJob(string $jobId)
    {
        $job = ServiceJob::findOrFail($jobId);
        $this->logActivity('removed_job', 'job', $job->id);
        $job->update(['status' => 'cancelled']);

        return back()->with('success', 'Job removed (cancelled).');
    }

    // ══════════════════════════════════════════════
    //  ORDERS
    // ══════════════════════════════════════════════

    public function orders(Request $request)
    {
        $query = MaterialOrder::with(['materialRequest.artisan', 'supplier']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function orderDetail(string $orderId)
    {
        $order = MaterialOrder::with(['materialRequest.artisan', 'supplier', 'items'])->findOrFail($orderId);

        return view('admin.orders.show', compact('order'));
    }

    public function cancelOrder(string $orderId)
    {
        $order = MaterialOrder::findOrFail($orderId);
        $order->update(['status' => 'cancelled']);
        $this->logActivity('cancelled_order', 'order', $order->id);

        return back()->with('success', 'Order cancelled.');
    }

    // ══════════════════════════════════════════════
    //  PAYMENTS
    // ══════════════════════════════════════════════

    public function payments(Request $request)
    {
        $query = Payment::with('payer');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function paymentDetail(string $paymentId)
    {
        $payment = Payment::with('payer')->findOrFail($paymentId);

        return view('admin.payments.show', compact('payment'));
    }

    public function issueRefund(string $paymentId, Request $request)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->update(['status' => 'refunded']);
        $this->logActivity('issued_refund', 'payment', $payment->id, [
            'amount' => $request->input('amount', $payment->amount),
            'reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Refund issued.');
    }

    // ══════════════════════════════════════════════
    //  DISPUTES
    // ══════════════════════════════════════════════

    public function disputes(Request $request)
    {
        $query = Dispute::with(['reportedBy', 'against']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $disputes = $query->latest()->paginate(20)->withQueryString();

        return view('admin.disputes.index', compact('disputes'));
    }

    public function disputeDetail(string $disputeId)
    {
        $dispute = Dispute::with(['reportedBy', 'against', 'resolvedBy'])->findOrFail($disputeId);

        return view('admin.disputes.show', compact('dispute'));
    }

    public function resolveDispute(string $disputeId, Request $request)
    {
        $data = $request->validate([
            'resolution' => 'required|string|in:resolved,dismissed',
            'notes' => 'nullable|string',
        ]);

        $dispute = Dispute::findOrFail($disputeId);
        $dispute->update([
            'status' => $data['resolution'],
            'resolution' => $data['resolution'],
            'admin_notes' => $data['notes'] ?? null,
            'resolved_by_id' => $this->admin()->id,
            'resolved_at' => now(),
        ]);

        $this->logActivity('resolved_dispute', 'dispute', $dispute->id);

        return back()->with('success', "Dispute {$data['resolution']}.");
    }

    // ══════════════════════════════════════════════
    //  ANALYTICS
    // ══════════════════════════════════════════════

    public function analytics(Request $request)
    {
        $period = $request->query('period', '30d');
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $from = now()->subDays($days);

        $userGrowth = User::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as label, COUNT(*) as value')
            ->groupBy('label')->orderBy('label')->get();

        $revenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as label, SUM(amount) as value')
            ->groupBy('label')->orderBy('label')->get();

        $jobsByCategory = ServiceJob::with('category')
            ->where('created_at', '>=', $from)
            ->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')->get();

        $summary = [
            'new_users' => User::where('created_at', '>=', $from)->count(),
            'total_revenue' => Payment::where('status', 'completed')->where('created_at', '>=', $from)->sum('amount'),
            'jobs_posted' => ServiceJob::where('created_at', '>=', $from)->count(),
            'total_orders' => MaterialOrder::where('created_at', '>=', $from)->count(),
        ];

        return view('admin.analytics', compact('userGrowth', 'revenue', 'jobsByCategory', 'summary', 'period'));
    }

    // ══════════════════════════════════════════════
    //  NOTIFICATIONS
    // ══════════════════════════════════════════════

    public function notifications()
    {
        $notifications = BroadcastNotification::latest()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function sendBroadcast(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_role' => 'nullable|string|in:client,artisan,supplier',
        ]);

        $notification = BroadcastNotification::create([
            'admin_id' => $this->admin()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'target_role' => $data['target_role'] ?? null,
        ]);

        $this->logActivity('sent_broadcast', 'notification', $notification->id);

        return back()->with('success', 'Broadcast notification sent.');
    }

    // ══════════════════════════════════════════════
    //  SETTINGS
    // ══════════════════════════════════════════════

    public function settings()
    {
        $commission = PlatformSetting::getValue('commission_percent', 10);
        $toggles = PlatformSetting::getValue('feature_toggles', []);

        return view('admin.settings', compact('commission', 'toggles'));
    }

    public function updateSettings(Request $request)
    {
        if ($request->has('commissionPercent')) {
            PlatformSetting::setValue('commission_percent', $request->input('commissionPercent'));
        }
        if ($request->has('featureToggles')) {
            PlatformSetting::setValue('feature_toggles', $request->input('featureToggles'));
        }

        $this->logActivity('updated_settings', 'settings', null);

        return back()->with('success', 'Settings updated.');
    }

    // ══════════════════════════════════════════════
    //  API SETTINGS
    // ══════════════════════════════════════════════

    public function apiSettings()
    {
        $keys = [
            'paystack_public_key',
            'paystack_secret_key',
            'korapay_public_key',
            'korapay_secret_key',
            'korapay_encryption_key',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        return view('admin.api-settings', compact('settings'));
    }

    public function updateApiSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'paystack_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:255',
            'korapay_public_key' => 'nullable|string|max:255',
            'korapay_secret_key' => 'nullable|string|max:255',
            'korapay_encryption_key' => 'nullable|string|max:255',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'type' => 'string']
            );
        }

        $this->logActivity('updated_api_settings', 'settings', null);

        return back()->with('success', 'API settings saved successfully.');
    }

    // ══════════════════════════════════════════════
    //  PROFILE
    // ══════════════════════════════════════════════

    public function profile()
    {
        $admin = $this->admin();

        return view('admin.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = $this->admin();
        $data = $request->validate([
            'name' => 'sometimes|string|min:2',
            'email' => 'sometimes|email|unique:admins,email,'.$admin->id,
            'phone' => 'sometimes|string',
        ]);

        if (isset($data['name'])) {
            $admin->full_name = $data['name'];
        }
        if (isset($data['email'])) {
            $admin->email = $data['email'];
        }
        if (isset($data['phone'])) {
            $admin->phone = $data['phone'];
        }
        $admin->save();

        $this->logActivity('updated_profile', 'admin', $admin->id);

        return back()->with('success', 'Profile updated.');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = $this->admin();

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password' => $data['password']]);
        $this->logActivity('changed_password', 'admin', $admin->id);

        return back()->with('success', 'Password changed.');
    }

    // ══════════════════════════════════════════════
    //  ACTIVITY LOGS
    // ══════════════════════════════════════════════

    public function activityLogs()
    {
        $logs = AdminActivityLog::with('admin')->latest()->paginate(30);

        return view('admin.activity-logs', compact('logs'));
    }
}
