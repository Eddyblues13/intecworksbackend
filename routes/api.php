<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ArtisanController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

// ── API v1 ──
Route::prefix('v1')->group(function () {

    // ── Public Auth Routes ──
    Route::prefix('auth')->group(function () {
        Route::post('/register',        [AuthController::class, 'register']);
        Route::post('/verify-otp',      [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp',      [AuthController::class, 'resendOtp']);
        Route::post('/login',           [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
    });

    // ── Protected Routes (Sanctum) ──
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/auth/logout',        [AuthController::class, 'logout']);

        // Cloudinary config (client-side upload)
        Route::get('/cloudinary-config', [VerificationController::class, 'cloudinaryConfig']);

        // Verification
        Route::prefix('verification')->group(function () {
            Route::post('/submit',   [VerificationController::class, 'submit']);
            Route::get('/status',    [VerificationController::class, 'checkStatus']);
            Route::post('/resubmit', [VerificationController::class, 'resubmit']);
        });

        // ═══════════════════════════════════════════════════════
        //  CATEGORIES (shared)
        // ═══════════════════════════════════════════════════════
        Route::get('/categories',                        [ClientController::class, 'categories']);
        Route::get('/categories/{category}/subcategories', [ClientController::class, 'subcategories']);

        // ═══════════════════════════════════════════════════════
        //  CLIENT routes (paths match Flutter ClientAPI exactly)
        // ═══════════════════════════════════════════════════════
        Route::prefix('client')->group(function () {
            Route::get('/dashboard', [ClientController::class, 'dashboard']);
            Route::post('/jobs',     [ClientController::class, 'createJob']);
            Route::get('/jobs',      [ClientController::class, 'myJobs']);
            Route::get('/jobs/{serviceJob}', [ClientController::class, 'jobDetail']);

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
        });

        // ═══════════════════════════════════════════════════════
        //  ARTISAN routes (paths match Flutter ArtisanAPI exactly)
        // ═══════════════════════════════════════════════════════
        Route::prefix('artisan')->group(function () {
            // Dashboard
            Route::get('/dashboard', [ArtisanController::class, 'dashboard']);

            // Nearby / available jobs
            Route::get('/jobs/nearby',    [ArtisanController::class, 'nearbyJobs']);
            Route::get('/jobs/active',    [ArtisanController::class, 'activeJobs']);
            Route::get('/jobs/scheduled', [ArtisanController::class, 'scheduledJobs']);

            // Accept / decline
            Route::post('/jobs/{serviceJob}/accept',  [ArtisanController::class, 'acceptJob']);
            Route::post('/jobs/{serviceJob}/decline',  [ArtisanController::class, 'declineJob']);

            // Job detail
            Route::get('/jobs/{serviceJob}', [ArtisanController::class, 'jobDetail']);

            // Inspection
            Route::post('/jobs/{serviceJob}/inspection', [ArtisanController::class, 'submitInspection']);

            // Scope classification
            Route::post('/jobs/{serviceJob}/scope', [ArtisanController::class, 'submitScopeClassification']);

            // Quotes
            Route::post('/jobs/{serviceJob}/quote', [ArtisanController::class, 'submitQuote']);
            Route::get('/quotes/{quote}',           [ArtisanController::class, 'quoteDetail']);

            // Materials
            Route::post('/jobs/{serviceJob}/material-request',              [ArtisanController::class, 'submitMaterialRequest']);
            Route::get('/material-requests/{materialRequest}/supplier-quotes', [ArtisanController::class, 'supplierQuotes']);
            Route::post('/material-requests/{materialRequest}/select-supplier', [ArtisanController::class, 'selectSupplier']);
            Route::post('/material-orders/{materialOrder}/confirm-delivery',    [ArtisanController::class, 'confirmDelivery']);

            // Job execution
            Route::post('/jobs/{serviceJob}/start-work', [ArtisanController::class, 'startWork']);
            Route::put('/jobs/{serviceJob}/progress',    [ArtisanController::class, 'updateProgress']);

            // Completion
            Route::post('/jobs/{serviceJob}/before-photos', [ArtisanController::class, 'uploadBeforePhotos']);
            Route::post('/jobs/{serviceJob}/after-photos',  [ArtisanController::class, 'uploadAfterPhotos']);
            Route::post('/jobs/{serviceJob}/complete',      [ArtisanController::class, 'markComplete']);

            // Wallet
            Route::get('/wallet',              [ArtisanController::class, 'wallet']);
            Route::get('/wallet/transactions', [ArtisanController::class, 'transactions']);

            // Profile
            Route::get('/profile',      [ArtisanController::class, 'profile']);
            Route::put('/profile',      [ArtisanController::class, 'updateProfile']);
            Route::get('/trust-score',  [ArtisanController::class, 'trustScore']);
        });
    });
});
