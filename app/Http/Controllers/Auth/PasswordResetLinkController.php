<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Only admin-role users can reset their password by email.
        // Non-admin staff must contact their company admin.
        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        if (!$user || $user->role !== 'admin') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Password reset by email is only available for company administrator accounts. Please contact your administrator.']);
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', 'Password reset link sent! Please check your email inbox.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Unable to send the reset link. Please try again or contact support.']);
    }
}
