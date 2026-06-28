<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\AuditLog;

class HostLoginController extends Controller
{
    /** Show the super-admin login form. */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'Super Admin') {
                return redirect()->route('host.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return view('super_admin.users.admin_login');
    }

    /** Handle super-admin login attempt. */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Invalid credentials. Please check your email and password.',
            ])->onlyInput('email');
        }

        // Reject non-super-admins immediately
        if (Auth::user()->role !== 'Super Admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Access denied. This portal is for Super Admins only.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        AuditLog::log('Authentication', 'Super Admin logged into the host portal', 'LOGIN');

        return redirect()->route('host.dashboard');
    }

    /** Logout from super-admin session. */
    public function destroy(Request $request): RedirectResponse
    {
        AuditLog::log('Authentication', 'Super Admin logged out', 'LOGOUT');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('host.login');
    }
}
