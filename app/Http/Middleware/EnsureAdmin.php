<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Authenticate against the "admin-api" guard (Admin model + admins table).
     * This is true Laravel multi-auth — completely separate from the User model.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate via the admin-api guard (Sanctum + admins provider)
        if (!Auth::guard('admin-api')->check()) {
            return response()->json(['message' => 'Unauthorized. Admin access only.'], 401);
        }

        // Set the authenticated admin on the request so $request->user() works
        Auth::shouldUse('admin-api');

        $admin = Auth::guard('admin-api')->user();

        if ($admin->status !== 'active') {
            return response()->json(['message' => 'Admin account is inactive.'], 403);
        }

        return $next($request);
    }
}
