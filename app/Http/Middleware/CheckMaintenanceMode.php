<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $enabled = SystemSetting::get('maintenance_mode', '0') === '1';

        if (!$enabled) {
            return $next($request);
        }

        // Super Admin (host portal) and the login/logout routes must always stay reachable,
        // otherwise nobody could ever turn maintenance mode back off.
        if ($request->routeIs('host.*') || $request->routeIs('login') || $request->routeIs('logout')) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->role === 'Super Admin') {
            return $next($request);
        }

        return response()->view('errors.maintenance', [], 503);
    }
}
