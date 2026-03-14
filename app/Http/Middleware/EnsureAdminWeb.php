<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
{
    /**
     * Authenticate against the "admin-web" guard (session-based).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin-web')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin-web')->user();

        if ($admin->status !== 'active') {
            Auth::guard('admin-web')->logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Your admin account is inactive.']);
        }

        return $next($request);
    }
}
