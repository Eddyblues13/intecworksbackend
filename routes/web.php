<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// ── Admin Auth (guest) ──
Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
});

// ── Admin Dashboard (authenticated) ──
Route::prefix('admin')->middleware('admin-web')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.alias');

    // Users
    Route::get('/users', [DashboardController::class, 'users'])->name('admin.users');
    Route::get('/users/{userId}', [DashboardController::class, 'userDetail'])->name('admin.users.show');
    Route::post('/users/{userId}/suspend', [DashboardController::class, 'suspendUser'])->name('admin.users.suspend');
    Route::post('/users/{userId}/activate', [DashboardController::class, 'activateUser'])->name('admin.users.activate');
    Route::post('/users/{userId}/delete', [DashboardController::class, 'deleteUser'])->name('admin.users.delete');

    // Verifications
    Route::get('/verifications', [DashboardController::class, 'verifications'])->name('admin.verifications');
    Route::get('/verifications/{userId}', [DashboardController::class, 'verificationDetail'])->name('admin.verifications.show');
    Route::post('/verifications/{userId}/approve', [DashboardController::class, 'approveVerification'])->name('admin.verifications.approve');
    Route::post('/verifications/{userId}/reject', [DashboardController::class, 'rejectVerification'])->name('admin.verifications.reject');

    // Manual verification actions
    Route::post('/users/{userId}/verify-otp', [DashboardController::class, 'manuallyVerifyOtp'])->name('admin.users.verify-otp');
    Route::post('/users/{userId}/advance-status', [DashboardController::class, 'advanceUserStatus'])->name('admin.users.advance-status');

    // Jobs
    Route::get('/jobs', [DashboardController::class, 'jobs'])->name('admin.jobs');
    Route::get('/jobs/{jobId}', [DashboardController::class, 'jobDetail'])->name('admin.jobs.show');
    Route::post('/jobs/{jobId}/flag', [DashboardController::class, 'flagJob'])->name('admin.jobs.flag');
    Route::post('/jobs/{jobId}/remove', [DashboardController::class, 'removeJob'])->name('admin.jobs.remove');

    // Orders
    Route::get('/orders', [DashboardController::class, 'orders'])->name('admin.orders');
    Route::get('/orders/{orderId}', [DashboardController::class, 'orderDetail'])->name('admin.orders.show');
    Route::post('/orders/{orderId}/cancel', [DashboardController::class, 'cancelOrder'])->name('admin.orders.cancel');

    // Payments
    Route::get('/payments', [DashboardController::class, 'payments'])->name('admin.payments');
    Route::get('/payments/{paymentId}', [DashboardController::class, 'paymentDetail'])->name('admin.payments.show');
    Route::post('/payments/{paymentId}/refund', [DashboardController::class, 'issueRefund'])->name('admin.payments.refund');

    // Disputes
    Route::get('/disputes', [DashboardController::class, 'disputes'])->name('admin.disputes');
    Route::get('/disputes/{disputeId}', [DashboardController::class, 'disputeDetail'])->name('admin.disputes.show');
    Route::post('/disputes/{disputeId}/resolve', [DashboardController::class, 'resolveDispute'])->name('admin.disputes.resolve');

    // Analytics
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('admin.analytics');

    // Notifications
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('admin.notifications');
    Route::post('/notifications/broadcast', [DashboardController::class, 'sendBroadcast'])->name('admin.notifications.broadcast');

    // Settings
    Route::get('/settings', [DashboardController::class, 'settings'])->name('admin.settings');
    Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('admin.settings.update');

    // API Settings
    Route::get('/api-settings', [DashboardController::class, 'apiSettings'])->name('admin.api-settings');
    Route::post('/api-settings', [DashboardController::class, 'updateApiSettings'])->name('admin.api-settings.update');

    // Profile
    Route::get('/profile', [DashboardController::class, 'profile'])->name('admin.profile');
    Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/change-password', [DashboardController::class, 'changePassword'])->name('admin.profile.password');

    // Activity Logs
    Route::get('/activity-logs', [DashboardController::class, 'activityLogs'])->name('admin.activity-logs');
});
