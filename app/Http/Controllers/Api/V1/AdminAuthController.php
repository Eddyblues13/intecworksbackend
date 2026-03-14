<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Admin Login — authenticates against the `admins` table.
     * Issues Sanctum tokens scoped to the Admin model.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'emailOrPhone' => 'required|string',
            'password'     => 'required|string',
        ]);

        $field = filter_var($data['emailOrPhone'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $admin = Admin::where($field, $data['emailOrPhone'])->first();

        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($admin->status === 'suspended') {
            return response()->json(['message' => 'Your admin account has been suspended.'], 403);
        }

        if ($admin->status !== 'active') {
            return response()->json(['message' => 'Your admin account is inactive.'], 403);
        }

        // Revoke old tokens
        $admin->tokens()->where('name', 'access')->delete();
        $admin->tokens()->where('name', 'refresh')->delete();

        $accessToken  = $admin->createToken('access', ['*'], now()->addDays(30))->plainTextToken;
        $refreshToken = $admin->createToken('refresh', ['refresh'], now()->addDays(90))->plainTextToken;

        return response()->json([
            'user'         => $admin->toApiArray(),
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
        ]);
    }

    /**
     * Refresh admin access token.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $admin = $request->user('admin-api');

        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Revoke old access tokens
        $admin->tokens()->where('name', 'access')->delete();

        $accessToken = $admin->createToken('access', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'accessToken' => $accessToken,
        ]);
    }

    /**
     * Admin logout — revokes current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user('admin-api');

        if ($admin) {
            $admin->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Get authenticated admin profile.
     */
    public function me(Request $request): JsonResponse
    {
        $admin = $request->user('admin-api');

        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($admin->toApiArray());
    }
}
