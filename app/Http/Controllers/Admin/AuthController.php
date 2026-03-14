<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin-web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if ($admin->status !== 'active') {
            return back()->withErrors(['email' => 'Your admin account is inactive.'])->withInput();
        }

        Auth::guard('admin-web')->login($admin, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin-web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
