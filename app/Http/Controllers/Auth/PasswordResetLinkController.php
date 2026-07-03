<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        if (!$user || $user->role !== 'admin') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'No administrator account found with that email address. Please contact WaafiBook support.']);
        }

        $tempPassword = Str::random(10);
        $user->update(['password' => Hash::make($tempPassword)]);

        return back()->with('temp_password', $tempPassword);
    }
}
