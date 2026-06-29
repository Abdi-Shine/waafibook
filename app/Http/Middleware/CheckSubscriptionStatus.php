<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;

class CheckSubscriptionStatus
{
    // Once a company's trial or paid term has run out, block new data
    // from being entered — but still let them view existing records,
    // pay/renew, or log out so they aren't completely locked out.
    private const EXEMPT_ROUTES = [
        'login', 'logout',
        'subscribers.checkout', 'subscribers.checkout.pay',
        'host.*',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return $next($request);
        }

        if ($request->routeIs(self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        $subscription = Subscription::where('company_id', $user->company_id)
            ->latest('id')
            ->first();

        if ($subscription && !$subscription->hasAccess()) {
            return redirect()->back()->with('error',
                'Your trial period has ended and no active subscription was found. Please contact your platform administrator to renew before adding new data.'
            );
        }

        return $next($request);
    }
}
