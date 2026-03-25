<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminAuthController;
use App\Http\Controllers\Api\V1\ArtisanController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\EscrowController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaystackController;
use App\Http\Controllers\Api\V1\KoraPayController;
use Illuminate\Support\Facades\Route;

// ── API v1 ──
Route::prefix('v1')->group(function () {

    // ── Public Auth Routes (Users: client/artisan/supplier) ──
    Route::prefix('auth')->group(function () {
        Route::post('/register',       [AuthController::class, 'register']);
        Route::post('/verify-otp',     [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp',     [AuthController::class, 'resendOtp']);
        Route::post('/login',          [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
    });

    // ═════════════════════════════════════════════════════════
    //  ADMIN AUTH — Public (separate login, separate model)
    // ═════════════════════════════════════════════════════════
    Route::prefix('admin/auth')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    // ═════════════════════════════════════════════════════════
    //  ADMIN AUTH — Protected (admin-api guard via middleware)
    // ═════════════════════════════════════════════════════════
    Route::prefix('admin/auth')->middleware('admin')->group(function () {
        Route::post('/refresh-token', [AdminAuthController::class, 'refreshToken']);
        Route::post('/logout',        [AdminAuthController::class, 'logout']);
        Route::get('/me',             [AdminAuthController::class, 'me']);
    });

    // ── Protected Routes (Sanctum — Users: client/artisan/supplier) ──
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('/auth/firebase-token', [AuthController::class, 'firebaseToken']);
        Route::post('/auth/logout',        [AuthController::class, 'logout']);

        // Cloudinary config (client-side upload)
        Route::get('/cloudinary-config', [VerificationController::class, 'cloudinaryConfig']);

        // Verification
        Route::prefix('verification')->group(function () {
            Route::post('/submit',   [VerificationController::class, 'submit']);
            Route::get('/status',    [VerificationController::class, 'checkStatus']);
            Route::post('/resubmit', [VerificationController::class, 'resubmit']);
        });

        // ═════════════════════════════════════════════════════
        //  CATEGORIES (shared)
        // ═════════════════════════════════════════════════════
        Route::get('/categories',                          [ClientController::class, 'categories']);
        Route::get('/categories/{category}/subcategories', [ClientController::class, 'subcategories']);

        // ═════════════════════════════════════════════════════
        //  CLIENT routes
        // ═════════════════════════════════════════════════════
        Route::prefix('client')->group(function () {
            Route::get('/dashboard', [ClientController::class, 'dashboard']);
            Route::post('/jobs',     [ClientController::class, 'createJob']);
            Route::get('/jobs',      [ClientController::class, 'myJobs']);
            Route::get('/jobs/{serviceJob}', [ClientController::class, 'jobDetail']);
            Route::put("/jobs/{serviceJob}",      [ClientController::class, "updateJob"]);
            Route::delete("/jobs/{serviceJob}",   [ClientController::class, "deleteJob"]);

            Route::get('/jobs/{serviceJob}/applicants',       [ClientController::class, 'jobApplicants']);
            Route::post('/jobs/{serviceJob}/accept-artisan',  [ClientController::class, 'acceptArtisan']);
            Route::post('/jobs/{serviceJob}/reject-artisan',  [ClientController::class, 'rejectArtisan']);

            Route::get('/jobs/{serviceJob}/quote',            [ClientController::class, 'jobQuote']);
            Route::post('/quotes/{quote}/approve',            [ClientController::class, 'approveQuote']);
            Route::post('/quotes/{quote}/reject',             [ClientController::class, 'rejectQuote']);

            Route::get('/jobs/{serviceJob}/progress',         [ClientController::class, 'jobProgress']);
            Route::post('/jobs/{serviceJob}/approve-completion', [ClientController::class, 'approveCompletion']);
            Route::post('/jobs/{serviceJob}/request-revision',  [ClientController::class, 'requestRevision']);

            Route::post('/jobs/{serviceJob}/review',          [ClientController::class, 'submitReview']);

            Route::get('/payments',            [ClientController::class, 'paymentHistory']);
            Route::get('/payments/{payment}',  [ClientController::class, 'paymentDetail']);
            Route::post('/jobs/{serviceJob}/pay', [ClientController::class, 'makePayment']);

            Route::get('/artisans',            [ClientController::class, 'browseArtisans']);
            Route::get('/artisans/{artisan}',  [ClientController::class, 'artisanProfile']);

            Route::get('/profile',             [ClientController::class, 'profile']);
            Route::put('/profile',             [ClientController::class, 'updateProfile']);
            Route::post('/change-password',    [ClientController::class, 'changePassword']);

            Route::get('/hiring-history',      [ClientController::class, 'hiringHistory']);

            Route::get('/favorites',           [ClientController::class, 'favorites']);
            Route::post('/favorites/toggle',   [ClientController::class, 'toggleFavorite']);
            Route::delete('/favorites/{artisanId}', [ClientController::class, 'removeFavorite']);
        });

        // ═════════════════════════════════════════════════════
        //  ARTISAN routes
        // ═════════════════════════════════════════════════════
        Route::prefix('artisan')->group(function () {
            Route::get('/dashboard', [ArtisanController::class, 'dashboard']);

            Route::get('/jobs/nearby',    [ArtisanController::class, 'nearbyJobs']);
            Route::get('/jobs/active',    [ArtisanController::class, 'activeJobs']);
            Route::get('/jobs/scheduled', [ArtisanController::class, 'scheduledJobs']);

            Route::post('/jobs/{serviceJob}/accept',  [ArtisanController::class, 'acceptJob']);
            Route::post('/jobs/{serviceJob}/decline',  [ArtisanController::class, 'declineJob']);

            Route::get('/jobs/{serviceJob}', [ArtisanController::class, 'jobDetail']);
            Route::put("/jobs/{serviceJob}",      [ClientController::class, "updateJob"]);
            Route::delete("/jobs/{serviceJob}",   [ClientController::class, "deleteJob"]);

            Route::post('/jobs/{serviceJob}/inspection', [ArtisanController::class, 'submitInspection']);
            Route::post('/jobs/{serviceJob}/scope', [ArtisanController::class, 'submitScopeClassification']);

            Route::post('/jobs/{serviceJob}/quote', [ArtisanController::class, 'submitQuote']);
            Route::get('/quotes/{quote}',           [ArtisanController::class, 'quoteDetail']);

            Route::post('/jobs/{serviceJob}/material-request',              [ArtisanController::class, 'submitMaterialRequest']);
            Route::get('/material-requests/{materialRequest}/supplier-quotes', [ArtisanController::class, 'supplierQuotes']);
            Route::post('/material-requests/{materialRequest}/select-supplier', [ArtisanController::class, 'selectSupplier']);
            Route::post('/material-orders/{materialOrder}/confirm-delivery',    [ArtisanController::class, 'confirmDelivery']);

            Route::post('/jobs/{serviceJob}/start-work', [ArtisanController::class, 'startWork']);
            Route::put('/jobs/{serviceJob}/progress',    [ArtisanController::class, 'updateProgress']);

            Route::post('/jobs/{serviceJob}/before-photos', [ArtisanController::class, 'uploadBeforePhotos']);
            Route::post('/jobs/{serviceJob}/after-photos',  [ArtisanController::class, 'uploadAfterPhotos']);
            Route::post('/jobs/{serviceJob}/complete',      [ArtisanController::class, 'markComplete']);

            Route::get('/wallet',              [ArtisanController::class, 'wallet']);
            Route::get('/wallet/transactions', [ArtisanController::class, 'transactions']);

            Route::get('/profile',     [ArtisanController::class, 'profile']);
            Route::put('/profile',     [ArtisanController::class, 'updateProfile']);
            Route::get('/trust-score', [ArtisanController::class, 'trustScore']);
        });

        // ═════════════════════════════════════════════════════
        //  SUPPLIER SUBSCRIPTION routes
        // ═════════════════════════════════════════════════════
        Route::prefix('supplier/subscription')->group(function () {
            Route::get('/plans',    [SubscriptionController::class, 'plans']);
            Route::get('/status',   [SubscriptionController::class, 'status']);
            Route::post('/purchase', [SubscriptionController::class, 'purchase']);
            Route::post('/renew',    [SubscriptionController::class, 'renew']);
            Route::post('/cancel',   [SubscriptionController::class, 'cancel']);
        });

        // ═════════════════════════════════════════════════════
        //  SUPPLIER routes
        // ═════════════════════════════════════════════════════
        Route::prefix('supplier')->group(function () {
            Route::get('/dashboard', [SupplierController::class, 'dashboard']);

            // Material requests
            Route::get('/material-requests',      [SupplierController::class, 'materialRequests']);
            Route::get('/material-requests/{materialRequest}', [SupplierController::class, 'materialRequestDetail']);
            Route::post('/material-requests/{materialRequest}/quote', [SupplierController::class, 'submitQuote']);

            // Quotes
            Route::get('/quotes',         [SupplierController::class, 'myQuotes']);
            Route::get('/quotes/{quote}', [SupplierController::class, 'quoteDetail']);

            // Orders
            Route::get('/orders',                        [SupplierController::class, 'orders']);
            Route::get('/orders/{materialOrder}',        [SupplierController::class, 'orderDetail']);
            Route::post('/orders/{materialOrder}/out-for-delivery', [SupplierController::class, 'markOutForDelivery']);
            Route::post('/orders/{materialOrder}/delivery-proof',   [SupplierController::class, 'uploadDeliveryProof']);
            Route::post('/orders/{materialOrder}/delivered',        [SupplierController::class, 'markDelivered']);

            // Products
            Route::get('/products',               [SupplierController::class, 'products']);
            Route::get('/products/{supplierProduct}', [SupplierController::class, 'productDetail']);
            Route::post('/products',              [SupplierController::class, 'createProduct']);
            Route::put('/products/{supplierProduct}', [SupplierController::class, 'updateProduct']);
            Route::delete('/products/{supplierProduct}', [SupplierController::class, 'deleteProduct']);

            // Wallet
            Route::get('/wallet',              [SupplierController::class, 'wallet']);
            Route::get('/wallet/transactions', [SupplierController::class, 'transactions']);

            // Profile
            Route::get('/profile',  [SupplierController::class, 'profile']);
            Route::put('/profile',  [SupplierController::class, 'updateProfile']);

            // Compliance
            Route::get('/compliance-score', [SupplierController::class, 'complianceScore']);
        });

        // ═════════════════════════════════════════════════════
        //  CHAT routes (shared — all authenticated users)
        // ═════════════════════════════════════════════════════
        Route::prefix('chat')->group(function () {
            Route::get('/conversations',                       [ChatController::class, 'conversations']);
            Route::post('/conversations',                      [ChatController::class, 'startConversation']);
            Route::get('/conversations/{chatThread}/messages',  [ChatController::class, 'messages']);
            Route::post('/conversations/{chatThread}/messages', [ChatController::class, 'sendMessage']);
            Route::post('/conversations/{chatThread}/read',     [ChatController::class, 'markAsRead']);
            Route::post('/conversations/{chatThread}/meta',     [ChatController::class, 'updateThreadMeta']);
            Route::post('/messages/{chatMessage}/flag',         [ChatController::class, 'flagMessage']);
        });

        // ═════════════════════════════════════════════════════
        //  NOTIFICATIONS routes (shared — all authenticated users)
        // ═════════════════════════════════════════════════════
        Route::prefix('notifications')->group(function () {
            Route::get('/',           [NotificationController::class, 'index']);
            Route::post('/mark-read', [NotificationController::class, 'markRead']);
            Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken']);
        });

        // ═══════════════════════════════════════════════════════
        //  PAYMENT GATEWAY routes
        // ═══════════════════════════════════════════════════════
        Route::prefix('payment')->group(function () {
            Route::post('/paystack/initialize', [PaystackController::class, 'initialize']);
            Route::post('/paystack/verify',     [PaystackController::class, 'verify']);
            Route::post('/korapay/initialize',   [KoraPayController::class, 'initialize']);
            Route::post('/korapay/verify',       [KoraPayController::class, 'verify']);
        });

        // ═══════════════════════════════════════════════════════
        //  ESCROW routes (authenticated clients)
        // ═══════════════════════════════════════════════════════
        Route::prefix('escrow')->group(function () {
            Route::get('/{jobId}',              [EscrowController::class, 'show']);
            Route::post('/fund',                [EscrowController::class, 'fund']);
            Route::post('/{jobId}/fund-remaining', [EscrowController::class, 'fundRemaining']);
            Route::get('/{jobId}/transactions', [EscrowController::class, 'transactions']);
        });
    });

    // ═════════════════════════════════════════════════════════
    //  ADMIN routes — protected by admin middleware
    //  (uses admin-api guard → admins table, NOT users)
    // ═════════════════════════════════════════════════════════
    Route::prefix('admin')->middleware('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // User management
        Route::get('/users',              [AdminController::class, 'users']);
        Route::get('/users/{userId}',     [AdminController::class, 'userDetail']);
        Route::post('/users/{userId}/suspend', [AdminController::class, 'suspendUser']);
        Route::post('/users/{userId}/activate', [AdminController::class, 'activateUser']);
        Route::post('/users/{userId}/delete',   [AdminController::class, 'deleteUser']);

        // Verification management
        Route::get('/verifications',              [AdminController::class, 'pendingVerifications']);
        Route::get('/verifications/{userId}',     [AdminController::class, 'verificationDetail']);
        Route::post('/verifications/{userId}/approve', [AdminController::class, 'approveVerification']);
        Route::post('/verifications/{userId}/reject',  [AdminController::class, 'rejectVerification']);

        // Job management
        Route::get('/jobs',              [AdminController::class, 'jobs']);
        Route::get('/jobs/{jobId}',      [AdminController::class, 'jobDetail']);
        Route::post('/jobs/{jobId}/flag', [AdminController::class, 'flagJob']);
        Route::post('/jobs/{jobId}/remove', [AdminController::class, 'removeJob']);

        // Order management
        Route::get('/orders',              [AdminController::class, 'orders']);
        Route::get('/orders/{orderId}',    [AdminController::class, 'orderDetail']);
        Route::post('/orders/{orderId}/cancel', [AdminController::class, 'cancelOrder']);

        // Payment management
        Route::get('/payments',              [AdminController::class, 'payments']);
        Route::get('/payments/{paymentId}',  [AdminController::class, 'paymentDetail']);
        Route::post('/payments/{paymentId}/refund', [AdminController::class, 'issueRefund']);

        // Dispute management
        Route::get('/disputes',              [AdminController::class, 'disputes']);
        Route::get('/disputes/{disputeId}',  [AdminController::class, 'disputeDetail']);
        Route::post('/disputes/{disputeId}/resolve', [AdminController::class, 'resolveDispute']);

        // Analytics
        Route::get('/analytics', [AdminController::class, 'analytics']);

        // Notifications
        Route::post('/notifications/broadcast', [AdminController::class, 'sendBroadcast']);
        Route::get('/notifications',            [AdminController::class, 'notificationHistory']);

        // Settings
        Route::get('/settings',  [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);

        // Profile
        Route::get('/profile',           [AdminController::class, 'profile']);
        Route::post('/profile',          [AdminController::class, 'updateProfile']);
        Route::post('/change-password',  [AdminController::class, 'changePassword']);
        Route::get('/activity-logs',     [AdminController::class, 'activityLogs']);
    });
});
