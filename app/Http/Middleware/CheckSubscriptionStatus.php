<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CheckSubscriptionStatus
{
    // Write requests to these URL segments are blocked when subscription is restricted.
    // GET requests are always allowed (read-only mode).
    private const BLOCKED_URL_SEGMENTS = [
        '/sales',           // sales orders, invoices, approvals
        '/purchase',        // purchase bills, orders, returns
        '/expense',         // expense vouchers
        '/payment',         // customer & supplier payments
        '/stock-transfer',  // inter-warehouse transfers
        '/inventory',       // adjustments, stock-take
        '/pos',             // point of sale
        '/payroll',         // salary processing
        '/service-order',   // service orders
        '/service-quotation',
        '/service-schedule',
        '/debit-note',
        '/credit-note',
        '/journal',         // manual journal entries
    ];

    // Routes always permitted regardless of subscription status.
    private const ALWAYS_ALLOWED_ROUTES = [
        'login', 'logout', 'password.*', 'landing', 'offline', 'terms', 'privacy',
        'manifest', 'sw', 'demo.*',
        'subscribers.*', 'my-subscription',
        'host.*',
        'announcements.dismiss',
        'unlock',
        'profile.*', 'profile-user',
        'ask-ai', 'ask-ai.ask',
    ];

    // URL path prefixes always allowed for writes (settings, master data, admin).
    private const ALWAYS_ALLOWED_PATH_PREFIXES = [
        '/logout', '/unlock', '/profile', '/company', '/feature-settings',
        '/backup', '/role', '/employee', '/branch', '/shareholder', '/capital-deposit',
        '/subscribers', '/customer', '/supplier', '/product', '/account',
        '/announcements', '/ask-ai',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            View::share('subscriptionRestricted', false);
            View::share('subscriptionRestrictionReason', null);
            View::share('currentSubscription', null);
            return $next($request);
        }

        $user = auth()->user();

        // Super admin panel never restricted
        if (!$user->company_id || $request->routeIs('host.*')) {
            View::share('subscriptionRestricted', false);
            View::share('subscriptionRestrictionReason', null);
            View::share('currentSubscription', null);
            return $next($request);
        }

        $subscription = Subscription::with('plan')
            ->where('company_id', $user->company_id)
            ->latest('id')
            ->first();

        // Auto-expire any trial whose period has passed
        if ($subscription && $subscription->status === 'trial' && $subscription->expiry_date) {
            if (\Carbon\Carbon::parse($subscription->expiry_date)->endOfDay()->isPast()) {
                $subscription->update(['status' => 'expired']);
                $subscription->status = 'expired';
            }
        }

        $company       = $user->company;
        $isSuspended   = $company && $company->status === 'suspended';
        $restrictionReason = $this->resolveRestriction($subscription, $isSuspended);
        $isRestricted  = $restrictionReason !== null;

        View::share('subscriptionRestricted', $isRestricted);
        View::share('subscriptionRestrictionReason', $restrictionReason);
        View::share('currentSubscription', $subscription);

        // GET / HEAD → always allow (full read-only access)
        if (in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        // Named-route exemptions
        if ($request->routeIs(self::ALWAYS_ALLOWED_ROUTES)) {
            return $next($request);
        }

        // Path-prefix exemptions (settings, master data writes)
        $path = '/' . ltrim($request->path(), '/');
        foreach (self::ALWAYS_ALLOWED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        // Suspended: block all non-exempt writes
        if ($isSuspended) {
            return $this->deny($request,
                'Your account is suspended. Please contact the platform administrator.'
            );
        }

        // Expired / cancelled: block writes only to transaction paths
        if ($isRestricted) {
            foreach (self::BLOCKED_URL_SEGMENTS as $segment) {
                if (str_contains($path, $segment)) {
                    return $this->deny($request,
                        'Your trial has expired. Please subscribe to a plan to continue creating or modifying transactions.'
                    );
                }
            }
        }

        return $next($request);
    }

    private function resolveRestriction(?Subscription $subscription, bool $isSuspended): ?string
    {
        if ($isSuspended) {
            return 'suspended';
        }

        if (!$subscription) {
            return null;
        }

        if (in_array($subscription->status, ['expired', 'cancelled'])) {
            return $subscription->status === 'cancelled' ? 'cancelled' : 'trial_expired';
        }

        // Trial with a past expiry date (shouldn't normally reach here after auto-expire above)
        if ($subscription->status === 'trial' && $subscription->expiry_date
            && \Carbon\Carbon::parse($subscription->expiry_date)->endOfDay()->isPast()) {
            return 'trial_expired';
        }

        return null;
    }

    private function deny(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => $message, 'restricted' => true], 402);
        }

        return redirect()->back()->with('error', $message);
    }
}
