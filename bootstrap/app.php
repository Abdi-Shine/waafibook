<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        try {
            $company = \App\Models\Company::withoutGlobalScopes()->first();
            if ($company && $company->auto_backup_enabled) {
                $time = $company->backup_time ?? '02:00';
                $schedule->command('app:system-backup')
                         ->dailyAt($time)
                         ->withoutOverlapping();
            }
        } catch (\Exception $e) {
            // Log fallback or skip gracefully if DB not ready
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare's proxy headers (X-Forwarded-Proto, etc.) so the app
        // knows requests arrived over HTTPS even though Cloudflare talks plain
        // HTTP to this origin (SSL mode: Flexible) — otherwise url()/redirect()
        // generate http:// links and downgrade visitors off HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'permission'      => \App\Http\Middleware\CheckPermission::class,
            'role'            => \App\Http\Middleware\CheckRole::class,
            'tenant.owns'     => \App\Http\Middleware\EnsureTenantOwnership::class,
            'super.admin'     => \App\Http\Middleware\EnsureSuperAdmin::class,
            'delete.password' => \App\Http\Middleware\RequireDeletePassword::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // On 419 (CSRF expired) during logout — just clear the session and redirect to login
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('logout')) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('status', 'Your session expired. Please log in again.');
            }
        });
    })->create();
