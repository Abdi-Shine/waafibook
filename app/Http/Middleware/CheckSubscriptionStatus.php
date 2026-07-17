<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CheckSubscriptionStatus
{
    // URL segments whose pages are fully blocked (GET included) when subscription is restricted.
    private const TRANSACTION_URL_SEGMENTS = [
        '/sales', '/purchase', '/expense', '/payment', '/pos',
        '/stock-transfer', '/inventory', '/payroll', '/loan',
        '/journal', '/sales-return', '/purchase-return',
        '/debit-note', '/credit-note', '/service-order',
        '/service-quotation', '/service-schedule',
    ];

    // URL path prefixes always allowed for writes regardless of subscription state.
    private const ALWAYS_ALLOWED_PATH_PREFIXES = [
        '/logout', '/unlock', '/profile', '/company', '/feature-settings',
        '/backup', '/role', '/employee', '/branch', '/shareholder',
        '/capital-deposit', '/subscribers', '/customer', '/supplier',
        '/product', '/account', '/announcements', '/ask-ai',
    ];

    // Named routes always allowed.
    private const ALWAYS_ALLOWED_ROUTES = [
        'login', 'logout', 'password.*', 'landing', 'offline', 'terms', 'privacy',
        'manifest', 'sw', 'demo.*', 'subscribers.*', 'my-subscription',
        'host.*', 'announcements.dismiss', 'unlock',
        'profile.*', 'profile-user', 'ask-ai', 'ask-ai.ask',
        'dashboard', 'reports.*',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            $this->shareDefaults();
            return $next($request);
        }

        $user = auth()->user();

        if (!$user->company_id || $request->routeIs('host.*')) {
            $this->shareDefaults();
            return $next($request);
        }

        // Load subscription and auto-expire trials
        $subscription = Subscription::with('plan')
            ->where('company_id', $user->company_id)
            ->latest('id')
            ->first();

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
        $planLevel     = $this->planLevel($subscription, $isRestricted);
        $plan = $subscription?->plan;
        $currentPlanName = ($plan instanceof \App\Models\SubscriptionPlan ? $plan->name : null)
            ?? ($subscription ? 'Trial' : 'None');

        View::share('subscriptionRestricted', $isRestricted);
        View::share('subscriptionRestrictionReason', $restrictionReason);
        View::share('currentSubscription', $subscription);
        View::share('currentPlanLevel', $planLevel);
        View::share('currentPlanName', $currentPlanName);

        $path = '/' . ltrim($request->path(), '/');
        $isGet = \in_array($request->method(), ['GET', 'HEAD'], true);

        // Mobile plan: restrict desktop/web access entirely, regardless of the
        // broader always-allowed-routes list below (that list exists for
        // trial/suspension messaging, not device gating). Only
        // login/logout/password-reset stay open so a desktop browser isn't
        // completely locked out with no way to sign out.
        if ($plan && strtolower(trim($plan->name)) === 'mobile'
            && !$request->routeIs(['login', 'logout', 'password.*'])
            && !$this->isMobileRequest($request)) {
            if ($isGet) {
                return $this->lockPage(null, $currentPlanName,
                    'Mobile Access Only',
                    'Your plan only works on a mobile device. Please open WaafiBook on your phone to continue.'
                );
            }
            return $this->denyWrite($request, 'Your plan only works on a mobile device.');
        }

        // Always-allowed named routes (read-only pages, settings, reports)
        if ($request->routeIs(self::ALWAYS_ALLOWED_ROUTES)) {
            return $next($request);
        }

        // Suspended: block everything except login/logout
        if ($isSuspended) {
            if ($isGet) {
                return $this->lockPage(null, $currentPlanName,
                    'Account Suspended',
                    'Your account has been suspended. Please contact the platform administrator to restore access.'
                );
            }
            return $this->denyWrite($request, 'Your account is suspended. Please contact the platform administrator.');
        }

        // Trial expired / cancelled: block transaction pages
        if ($isRestricted) {
            if ($isGet && $this->isTransactionPath($path)) {
                return $this->lockPage(null, $currentPlanName);
            }
            if (!$isGet) {
                // Allow settings/admin writes
                foreach (self::ALWAYS_ALLOWED_PATH_PREFIXES as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        return $next($request);
                    }
                }
                if ($this->isTransactionPath($path)) {
                    return $this->denyWrite($request, 'Your trial has expired. Please subscribe to continue.');
                }
            }
            return $next($request);
        }

        return $next($request);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function planLevel(?Subscription $subscription, bool $isRestricted): int
    {
        if ($isRestricted || !$subscription?->plan) {
            return 0;
        }
        // Any active (non-restricted) subscription — trial or paid, any tier —
        // has full feature access. Plan tiers govern price/limits, not feature gating.
        return 3;
    }

    private function resolveRestriction(?Subscription $subscription, bool $isSuspended): ?string
    {
        if ($isSuspended) return 'suspended';
        if (!$subscription) return null;
        if (\in_array($subscription->status, ['expired', 'cancelled'], true)) {
            return $subscription->status === 'cancelled' ? 'cancelled' : 'trial_expired';
        }
        if ($subscription->status === 'trial' && $subscription->expiry_date
            && \Carbon\Carbon::parse($subscription->expiry_date)->endOfDay()->isPast()) {
            return 'trial_expired';
        }
        return null;
    }

    private function isMobileRequest(Request $request): bool
    {
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $request->userAgent() ?? '');
    }

    private function isTransactionPath(string $path): bool
    {
        foreach (self::TRANSACTION_URL_SEGMENTS as $segment) {
            if (str_contains($path, $segment)) {
                return true;
            }
        }
        return false;
    }

    private function lockPage(
        ?string $requiredPlan,
        string  $currentPlanName,
        string  $title   = 'Upgrade to unlock this feature',
        string  $message = ''
    ) {
        return response()->view('admin.plan_locked', [
            'lockTitle'       => $title,
            'lockMessage'     => $message,
            'requiredPlan'    => $requiredPlan,
            'currentPlanName' => $currentPlanName,
        ], $requiredPlan ? 403 : 402);
    }

    private function denyWrite(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => $message, 'restricted' => true], 402);
        }
        return redirect()->back()->with('error', $message);
    }

    private function shareDefaults(): void
    {
        View::share('subscriptionRestricted', false);
        View::share('subscriptionRestrictionReason', null);
        View::share('currentSubscription', null);
        View::share('currentPlanLevel', 3);
        View::share('currentPlanName', '');
    }
}
