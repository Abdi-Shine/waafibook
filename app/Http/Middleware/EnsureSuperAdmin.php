<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('host.login');
        }

        if (auth()->user()->role !== 'Super Admin') {
            // Regular users who accidentally hit a super_admin URL go to their dashboard
            return redirect()->route('dashboard')
                ->withErrors(['auth' => 'Access denied. This area is for Super Admins only.']);
        }

        return $next($request);
    }
}
