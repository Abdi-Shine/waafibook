<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\HostLoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// ── Super Admin portal login (separate from company login) ──
Route::get('super_admin/login',  [HostLoginController::class, 'create'])->name('host.login');
Route::post('super_admin/login', [HostLoginController::class, 'store'])->name('host.login.store');

Route::middleware('auth')->post('super_admin/logout', [HostLoginController::class, 'destroy'])->name('host.logout');

Route::middleware('guest')->group(function () {
    // The standalone full-page registration form is retired in favor of the
    // "Sign up" tab on the login page — redirect any existing links there.
    Route::get('register', fn () => redirect()->route('login', ['tab' => 'register']))
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('register/verify', [RegisteredUserController::class, 'showOtpForm'])
        ->name('register.verify');

    Route::post('register/verify', [RegisteredUserController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('register.verify.store');

    Route::post('register/verify/resend', [RegisteredUserController::class, 'resendOtp'])
        ->middleware('throttle:3,1')
        ->name('register.verify.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::match(['get', 'post'], 'logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
