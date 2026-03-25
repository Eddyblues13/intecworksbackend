<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\TermiiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly TermiiService $termii,
    ) {}

    // ───────────────────────────── Register ─────────────────────────────
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fullName'  => 'required|string|min:3',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|min:10|unique:users,phone',
            'password'  => 'required|string|min:8',
            'location'  => 'required|string|min:2',
            'role'      => 'required|in:client,artisan,supplier',
        ]);

        try {
            return DB::transaction(function () use ($data) {
                $user = User::create([
                    'full_name'      => $data['fullName'],
                    'email'          => $data['email'],
                    'phone'          => $data['phone'],
                    'password'       => $data['password'],
                    'location'       => $data['location'],
                    'role'           => $data['role'],
                    'account_status' => 'otp_pending',
                ]);

                // Send OTP via Termii (dev bypass: skip if local)
                $pinId = null;
                if (app()->environment('local', 'testing')) {
                    $pinId = 'dev_' . Str::random(20);
                } else {
                    $pinId = $this->termii->sendOtp($user->phone);
                }

                if (!$pinId) {
                    throw new \RuntimeException('Failed to send OTP.');
                }

                $verificationId = Str::uuid()->toString();

                OtpVerification::create([
                    'verification_id' => $verificationId,
                    'user_id'         => $user->id,
                    'phone'           => $user->phone,
                    'pin_id'          => $pinId,
                    'status'          => 'pending',
                    'expires_at'      => now()->addMinutes(10),
                ]);

                return response()->json([
                    'verificationId' => $verificationId,
                    'message'        => 'OTP sent to your phone number.',
                ], 201);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ───────────────────────────── Verify OTP ───────────────────────────
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verificationId' => 'required|string',
            'otp'            => 'required|string|size:6',
        ]);

        $verification = OtpVerification::where('verification_id', $data['verificationId'])
            ->where('status', 'pending')
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired verification.'], 404);
        }

        if ($verification->isExpired()) {
            $verification->update(['status' => 'expired']);
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 410);
        }

        if ($verification->attempts >= 3) {
            return response()->json(['message' => 'Too many attempts. Please request a new OTP.'], 429);
        }

        $verification->increment('attempts');

        // Verify with Termii (dev bypass: accept 123456 in local)
        $verified = false;
        if (app()->environment('local', 'testing') && $data['otp'] === '123456') {
            $verified = true;
        } else {
            $verified = $this->termii->verifyOtp($verification->pin_id, $data['otp']);
        }

        if (!$verified) {
            return response()->json(['message' => 'Invalid OTP. Please try again.'], 422);
        }

        // Mark verified
        $verification->update(['status' => 'verified']);

        $user = $verification->user;
        $user->update([
            'phone_verified_at' => now(),
            'account_status'    => $user->isProvider() ? 'verification_pending' : 'active',
        ]);

        // Issue Sanctum token
        $accessToken  = $user->createToken('access', ['*'], now()->addDays(30))->plainTextToken;
        $refreshToken = $user->createToken('refresh', ['refresh'], now()->addDays(90))->plainTextToken;

        return response()->json([
            'user'         => $user->fresh()->toApiArray(),
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
            'message'      => 'Phone verified successfully.',
        ]);
    }

    // ───────────────────────────── Resend OTP ───────────────────────────
    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verificationId' => 'required|string',
        ]);

        $verification = OtpVerification::where('verification_id', $data['verificationId'])->first();

        if (!$verification) {
            return response()->json(['message' => 'Verification not found.'], 404);
        }

        // Expire the old one
        $verification->update(['status' => 'expired']);

        // Send new OTP (dev bypass: skip if local)
        $pinId = null;
        if (app()->environment('local', 'testing')) {
            $pinId = 'dev_' . Str::random(20);
        } else {
            $pinId = $this->termii->sendOtp($verification->phone);
        }

        if (!$pinId) {
            return response()->json(['message' => 'Failed to resend OTP.'], 500);
        }

        $newVerificationId = Str::uuid()->toString();

        OtpVerification::create([
            'verification_id' => $newVerificationId,
            'user_id'         => $verification->user_id,
            'phone'           => $verification->phone,
            'pin_id'          => $pinId,
            'status'          => 'pending',
            'expires_at'      => now()->addMinutes(10),
        ]);

        return response()->json([
            'verificationId' => $newVerificationId,
            'message'        => 'New OTP sent.',
        ]);
    }

    // ───────────────────────────── Login ─────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'emailOrPhone' => 'required|string',
            'password'     => 'required|string',
        ]);

        $field = filter_var($data['emailOrPhone'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user  = User::where($field, $data['emailOrPhone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->isPhoneVerified()) {
            return response()->json(['message' => 'Phone number not verified.'], 403);
        }

        if ($user->account_status === 'suspended') {
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        // Revoke old tokens
        $user->tokens()->where('name', 'access')->delete();
        $user->tokens()->where('name', 'refresh')->delete();

        $accessToken  = $user->createToken('access', ['*'], now()->addDays(30))->plainTextToken;
        $refreshToken = $user->createToken('refresh', ['refresh'], now()->addDays(90))->plainTextToken;

        return response()->json([
            'user'         => $user->toApiArray(),
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
        ]);
    }

    // ───────────────────────────── Forgot Password ──────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent.']);
        }

        return response()->json(['message' => 'Unable to send reset link.'], 500);
    }

    // ───────────────────────────── Reset Password ───────────────────────
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => 'required|string',
            'email'       => 'required|email',
            'newPassword' => 'required|string|min:8',
        ]);

        $status = Password::reset(
            [
                'email'                 => $data['email'],
                'password'              => $data['newPassword'],
                'password_confirmation' => $data['newPassword'],
                'token'                 => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired token.'], 422);
    }

    // ───────────────────────────── Refresh Token ────────────────────────
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current access tokens only
        $user->tokens()->where('name', 'access')->delete();

        $accessToken = $user->createToken('access', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'accessToken' => $accessToken,
        ]);
    }

    // ───────────────────────────── Firebase Token ──────────────────────────
    public function firebaseToken(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;

        try {
            $factory = (new \Kreait\Firebase\Factory)
                ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS', 'firebase_credentials.json')));
            
            $auth = $factory->createAuth();
            $customToken = $auth->createCustomToken($userId);

            return response()->json([
                'firebaseToken' => $customToken->toString(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Firebase Token Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate Firebase token.',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ───────────────────────────── Logout ────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
