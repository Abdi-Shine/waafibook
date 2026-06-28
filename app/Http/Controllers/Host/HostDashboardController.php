<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;

class HostDashboardController extends Controller
{
    public function dashboard()
    {
        $totalCompanies = Company::count();
        $totalUsers     = User::withoutGlobalScopes()->count();
        $newThisMonth   = Company::whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->count();

        $monthlyRevenue = SubscriptionPayment::query()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where('status', 'paid')
            ->sum('amount');

        $expiringSoon = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $recentCompanies = Company::with(['subscription.plan'])
            ->withCount('users as user_count')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $planDistribution = SubscriptionPlan::withCount([
            'subscriptions as active_count' => fn($q) => $q->where('status', 'active'),
        ])->get();

        $totalActiveSubs = $planDistribution->sum('active_count') ?: 1;

        return view('host.dashboard', compact(
            'totalCompanies', 'totalUsers', 'monthlyRevenue',
            'expiringSoon', 'newThisMonth', 'recentCompanies',
            'planDistribution', 'totalActiveSubs'
        ));
    }

    public function manageCompanies()
    {
        $companies = Company::with(['subscription.plan'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('host.companies.index', compact('companies'));
    }

    public function toggleCompanyStatus($id)
    {
        $company = Company::findOrFail($id);
        $company->status = $company->status === 'suspended' ? 'active' : 'suspended';
        $company->save();

        \App\Models\AuditLog::log('Company', ucfirst($company->status) . " company: {$company->name}", 'UPDATE');

        return redirect()->route('host.companies')->with('success', "{$company->name} is now {$company->status}.");
    }

    public function demoRequests()
    {
        $requests = DemoRequest::orderByDesc('created_at')->paginate(20);
        $pendingCount = DemoRequest::where('status', 'pending')->count();

        return view('host.demo_requests.index', compact('requests', 'pendingCount'));
    }

    public function updateDemoRequestStatus($id, Request $request)
    {
        $demo = DemoRequest::findOrFail($id);
        $demo->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function users(Request $request)
    {
        $companies = Company::orderBy('name')->get();
        $query = User::withoutGlobalScopes()->with('company')->orderByDesc('created_at');
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        $users = $query->paginate(20)->withQueryString();
        return view('host.users.index', compact('users', 'companies'));
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::with(['company', 'plan'])->orderByDesc('created_at')->paginate(20);
        return view('host.subscriptions.index', compact('subscriptions'));
    }

    public function payments()
    {
        $payments = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])
            ->orderByDesc('payment_date')->paginate(20);
        $totalRevenue = SubscriptionPayment::where('status', 'paid')->sum('amount');
        return view('host.payments.index', compact('payments', 'totalRevenue'));
    }

    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        return view('host.plans.index', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'billing_cycle'    => 'required|in:monthly,quarterly,yearly',
            'max_users'        => 'required|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'status'           => 'nullable|in:active,inactive',
        ]);

        SubscriptionPlan::create([
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'billing_cycle'    => $request->billing_cycle,
            'max_users'        => $request->max_users,
            'storage_limit_gb' => $request->storage_limit_gb ?? 2,
            'features'         => $request->features ? array_map('trim', explode(',', $request->features)) : [],
            'status'           => $request->status ?? 'active',
            'is_popular'       => $request->boolean('is_popular'),
        ]);

        return redirect()->route('host.plans')->with('success', 'Plan created successfully.');
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $request->validate([
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'billing_cycle'    => 'required|in:monthly,quarterly,yearly',
            'max_users'        => 'required|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'status'           => 'nullable|in:active,inactive',
        ]);

        $plan->update([
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'billing_cycle'    => $request->billing_cycle,
            'max_users'        => $request->max_users,
            'storage_limit_gb' => $request->storage_limit_gb,
            'features'         => $request->features ? array_map('trim', explode(',', $request->features)) : [],
            'status'           => $request->status ?? 'active',
            'is_popular'       => $request->boolean('is_popular'),
        ]);

        return redirect()->route('host.plans')->with('success', 'Plan updated successfully.');
    }

    public function destroyPlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        if (Subscription::where('subscription_plan_id', $id)->where('status', 'active')->exists()) {
            return redirect()->route('host.plans')
                ->with('error', 'Cannot delete "' . $plan->name . '" — companies are actively subscribed to it.');
        }

        $plan->delete();
        return redirect()->route('host.plans')->with('success', 'Plan deleted.');
    }

    public function reports()
    {
        $totalCompanies = Company::count();
        $totalUsers     = User::withoutGlobalScopes()->count();
        $totalRevenue   = SubscriptionPayment::where('status', 'paid')->sum('amount');
        $activeSubs     = Subscription::where('status', 'active')->count();

        // Monthly revenue last 6 months
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyRevenue[] = [
                'month'  => $date->format('M Y'),
                'amount' => SubscriptionPayment::where('status', 'paid')
                    ->whereMonth('payment_date', $date->month)
                    ->whereYear('payment_date', $date->year)
                    ->sum('amount'),
            ];
        }

        $planDistribution = SubscriptionPlan::withCount([
            'subscriptions' => fn($q) => $q->where('status', 'active'),
        ])->get();

        return view('host.reports.index', compact(
            'totalCompanies', 'totalUsers', 'totalRevenue',
            'activeSubs', 'monthlyRevenue', 'planDistribution'
        ));
    }

    public function settings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return view('host.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'platform_name'       => 'required|string|max:255',
            'support_email'       => 'required|email|max:255',
            'trial_days'          => 'required|integer|min:1|max:365',
            'default_currency'    => 'required|string|max:10',
            'evc_merchant_number' => 'nullable|string|max:50',
            'bank_name'           => 'nullable|string|max:255',
            'bank_account_name'   => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_swift_code'     => 'nullable|string|max:20',
        ]);

        $keys = [
            'platform_name', 'support_email', 'trial_days', 'default_currency',
            'evc_merchant_number', 'bank_name', 'bank_account_name',
            'bank_account_number', 'bank_swift_code',
        ];

        foreach ($keys as $key) {
            SystemSetting::set($key, $request->input($key));
        }

        return redirect()->route('host.settings')->with('success', 'Settings saved successfully.');
    }
}
