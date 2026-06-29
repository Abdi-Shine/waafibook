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
        $totalCompanies     = Company::count();
        $activeCompanies    = Company::where('status', 'active')->count();
        $suspendedCompanies = Company::where('status', 'suspended')->count();
        $totalUsers         = User::withoutGlobalScopes()->count();
        $newThisMonth       = Company::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count();

        $monthlyRevenue = SubscriptionPayment::query()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where('status', 'completed')
            ->sum('amount');

        $revenueCollected = SubscriptionPayment::where('status', 'completed')->sum('amount');
        $pendingApprovals = DemoRequest::where('status', 'pending')->count();
        $overdueAccounts  = Subscription::where('status', 'expired')->count();

        $recentActivity = \App\Models\AuditLog::with('user')->orderByDesc('created_at')->take(10)->get();

        $newSignupsThisWeek = Company::with(['subscription.plan'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->get();

        return view('super_admin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'suspendedCompanies', 'totalUsers',
            'monthlyRevenue', 'newThisMonth', 'revenueCollected', 'pendingApprovals',
            'overdueAccounts', 'recentActivity', 'newSignupsThisWeek'
        ));
    }

    public function manageCompanies(Request $request)
    {
        $companies = Company::with(['subscription.plan', 'users' => fn ($q) => $q->orderByRaw("role = 'admin' desc")])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('plan'), fn ($q) => $q->whereHas('subscription.plan', fn ($q2) => $q2->where('name', $request->plan)))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $allPlans = SubscriptionPlan::orderBy('price')->get();
        $plans = $allPlans->pluck('name');

        return view('super_admin.companies.index', compact('companies', 'plans', 'allPlans'));
    }

    public function storeCompany(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255|unique:companies,name',
            'owner_name'       => 'required|string|max:255',
            'owner_email'      => 'required|email|max:255|unique:users,email',
            'phone'            => 'nullable|string|max:50',
            'country'          => 'nullable|string|max:100',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
        ]);

        $company = \DB::transaction(function () use ($request) {
            $company = Company::create([
                'name'    => $request->name,
                'email'   => $request->owner_email,
                'phone'   => $request->phone,
                'country' => $request->country,
                'status'  => 'active',
            ]);

            $tempPassword = \Illuminate\Support\Str::random(12);
            User::withoutGlobalScopes()->create([
                'name'              => $request->owner_name,
                'fullname'          => $request->owner_name,
                'email'             => $request->owner_email,
                'password'          => \Illuminate\Support\Facades\Hash::make($tempPassword),
                'role'              => 'admin',
                'company_id'        => $company->id,
                'email_verified_at' => now(),
            ]);

            if ($request->subscription_plan_id) {
                Subscription::create([
                    'company_id'           => $company->id,
                    'subscription_plan_id' => $request->subscription_plan_id,
                    'start_date'           => now(),
                    'expiry_date'          => now()->addDays(14),
                    'status'               => 'trial',
                    'auto_renew'           => false,
                ]);
            }

            return $company;
        });

        \App\Models\AuditLog::log('Company', "Created new company: {$company->name}", 'CREATE');

        // Mail delivery is best-effort — a bad/unreachable address shouldn't
        // undo the company that was already created above.
        $emailSent = true;
        try {
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $request->owner_email]);
        } catch (\Throwable $e) {
            $emailSent = false;
        }

        return redirect()->route('host.companies')->with('success', $emailSent
            ? "{$company->name} has been created and the owner has been notified by email."
            : "{$company->name} has been created, but the welcome email could not be sent — please share login details manually.");
    }

    public function managePlan(Request $request, $id)
    {
        $request->validate(['subscription_plan_id' => 'required|exists:subscription_plans,id']);

        $company = Company::with('subscription')->findOrFail($id);

        if ($company->subscription) {
            $company->subscription->update(['subscription_plan_id' => $request->subscription_plan_id]);
        } else {
            Subscription::create([
                'company_id'           => $company->id,
                'subscription_plan_id' => $request->subscription_plan_id,
                'start_date'           => now(),
                'expiry_date'          => now()->addDays(14),
                'status'               => 'trial',
                'auto_renew'           => false,
            ]);
        }

        $plan = SubscriptionPlan::find($request->subscription_plan_id);
        \App\Models\AuditLog::log('Company', "Changed subscription plan for {$company->name} to {$plan->name}", 'UPDATE');

        return redirect()->route('host.companies')->with('success', "{$company->name} is now on the {$plan->name} plan.");
    }

    public function toggleCompanyStatus($id)
    {
        $company = Company::findOrFail($id);
        $company->status = $company->status === 'suspended' ? 'active' : 'suspended';
        $company->save();

        \App\Models\AuditLog::log('Company', ucfirst($company->status) . " company: {$company->name}", 'UPDATE');

        return redirect()->route('host.companies')->with('success', "{$company->name} is now {$company->status}.");
    }

    public function showCompany($id)
    {
        $company = Company::with(['subscription.plan'])->findOrFail($id);

        return response()->json([
            'name'         => $company->name,
            'email'        => $company->email,
            'phone'        => $company->phone,
            'country'      => $company->country,
            'status'       => $company->status,
            'plan'         => $company->subscription?->plan?->name ?? '—',
            'joined'       => $company->created_at->format('d M Y'),
            'users_count'  => $company->users()->count(),
            'products_count' => \App\Models\Product::withoutGlobalScopes()->where('company_id', $company->id)->count(),
            'sales_count'  => \App\Models\SalesOrder::withoutGlobalScopes()->where('company_id', $company->id)->count(),
        ]);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $company->update($request->only('name', 'email', 'phone'));

        \App\Models\AuditLog::log('Company', "Updated company details: {$company->name}", 'UPDATE');

        return redirect()->route('host.companies')->with('success', "{$company->name}'s details have been updated.");
    }

    public function destroyCompany($id)
    {
        $company = Company::findOrFail($id);
        $name = $company->name;

        \Illuminate\Support\Facades\DB::transaction(fn () => $company->delete());

        \App\Models\AuditLog::log('Company', "Deleted company: {$name}", 'DELETE');

        return redirect()->route('host.companies')->with('success', "{$name} has been permanently deleted.");
    }

    public function demoRequests()
    {
        $requests = DemoRequest::orderByDesc('created_at')->paginate(20);
        $pendingCount = DemoRequest::where('status', 'pending')->count();

        return view('super_admin.demo_requests.index', compact('requests', 'pendingCount'));
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
        $query = User::withoutGlobalScopes()->with('company')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');
        $users = $query->paginate(20)->withQueryString();
        return view('super_admin.users.index', compact('users', 'companies'));
    }

    public function resetUserPassword($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);

        try {
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            \App\Models\AuditLog::log('User', "Password reset email failed for: {$user->name}", 'UPDATE');
            return redirect()->route('host.users')->with('error', "Could not send the reset email to {$user->email}.");
        }

        \App\Models\AuditLog::log('User', "Sent password reset link to: {$user->name}", 'UPDATE');

        return redirect()->route('host.users')->with('success', "A password reset link has been emailed to {$user->email}.");
    }

    public function toggleUserStatus($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $user->status = $user->status === 'suspended' ? 'active' : 'suspended';
        $user->save();

        \App\Models\AuditLog::log('User', ucfirst($user->status) . " user account: {$user->name}", 'UPDATE');

        return redirect()->route('host.users')->with('success', "{$user->name} is now {$user->status}.");
    }

    public function destroyUser($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $name = $user->name;
        $user->delete();

        \App\Models\AuditLog::log('User', "Deleted user account: {$name}", 'DELETE');

        return redirect()->route('host.users')->with('success', "{$name} has been removed.");
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::with(['company', 'plan'])->orderByDesc('created_at')->paginate(20);

        $totalMrr = Subscription::with('plan')->where('status', 'active')->get()
            ->sum(fn ($s) => $s->plan ? $s->plan->price / ($s->plan->billing_cycle === 'yearly' ? 12 : ($s->plan->billing_cycle === 'quarterly' ? 3 : 1)) : 0);

        $overduePayments = SubscriptionPayment::whereIn('status', ['pending', 'failed'])->count();

        $expiringThisMonth = Subscription::where('status', 'active')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        return view('super_admin.subscriptions.index', compact('subscriptions', 'totalMrr', 'overduePayments', 'expiringThisMonth'));
    }

    public function payments()
    {
        $payments = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])
            ->orderByDesc('payment_date')->paginate(20);
        $totalRevenue = SubscriptionPayment::where('status', 'completed')->sum('amount');
        return view('super_admin.payments.index', compact('payments', 'totalRevenue'));
    }

    public function markPaymentPaid($id)
    {
        $payment = SubscriptionPayment::with('subscription.company')->findOrFail($id);
        $payment->update(['status' => 'completed']);

        \App\Models\AuditLog::log('Billing', "Marked payment as paid for: {$payment->subscription->company->name}", 'UPDATE');

        return redirect()->route('host.payments')->with('success', 'Payment marked as paid.');
    }

    public function cancelSubscriptionAction($id)
    {
        $subscription = Subscription::with('company')->findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        \App\Models\AuditLog::log('Billing', "Cancelled subscription for: {$subscription->company->name}", 'UPDATE');

        return redirect()->route('host.subscriptions')->with('success', "Subscription for {$subscription->company->name} has been cancelled.");
    }

    public function sendInvoice($id)
    {
        $subscription = Subscription::with('company')->findOrFail($id);

        \App\Models\AuditLog::log('Billing', "Sent invoice to: {$subscription->company->name}", 'UPDATE');

        return redirect()->back()->with('success', "Invoice logged for {$subscription->company->name}.");
    }

    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        return view('super_admin.plans.index', compact('plans'));
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

    public function announcements()
    {
        $announcements = \App\Models\Announcement::with('targetCompany')->orderByDesc('created_at')->paginate(20);
        $companies = Company::orderBy('name')->get();

        return view('super_admin.announcements.index', compact('announcements', 'companies'));
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'target'     => 'required|in:All,specific',
            'company_id' => 'required_if:target,specific|nullable|exists:companies,id',
            'priority'   => 'required|in:Info,Warning,Critical',
            'submit_as'  => 'required|in:Sent,Draft',
        ]);

        $announcement = \App\Models\Announcement::create([
            'title'             => $request->title,
            'message'           => $request->message,
            'target_company_id' => $request->target === 'specific' ? $request->company_id : null,
            'priority'          => $request->priority,
            'status'            => $request->submit_as,
            'sent_at'           => $request->submit_as === 'Sent' ? now() : null,
        ]);

        \App\Models\AuditLog::log('Announcement', ($request->submit_as === 'Sent' ? 'Sent' : 'Saved draft') . " announcement: {$announcement->title}", 'CREATE');

        return redirect()->route('host.announcements')->with('success', $request->submit_as === 'Sent' ? 'Announcement sent.' : 'Draft saved.');
    }

    public function sendAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->update(['status' => 'Sent', 'sent_at' => now()]);

        \App\Models\AuditLog::log('Announcement', "Sent announcement: {$announcement->title}", 'UPDATE');

        return redirect()->route('host.announcements')->with('success', 'Announcement sent.');
    }

    public function security(Request $request)
    {
        $logs = \App\Models\AuditLog::query()->with('user')
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $modules = \App\Models\AuditLog::query()->distinct()->orderBy('module')->pluck('module');

        $sessions = \Illuminate\Support\Facades\DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name', 'users.email')
            ->orderByDesc('sessions.last_activity')
            ->get();

        return view('super_admin.security.index', compact('logs', 'modules', 'sessions'));
    }

    public function exportAuditLog(Request $request)
    {
        $logs = \App\Models\AuditLog::query()->with('user')
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at')
            ->get();

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Timestamp', 'Module', 'Action', 'Description', 'User', 'IP Address']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('d M Y, H:i'),
                    $log->module,
                    $log->action,
                    $log->description,
                    $log->user->name ?? '—',
                    $log->ip_address,
                ]);
            }
            fclose($handle);
        }, 'audit-log-' . now()->format('Y-m-d') . '.csv');
    }

    public function forceLogoutSession($sessionId)
    {
        \Illuminate\Support\Facades\DB::table('sessions')->where('id', $sessionId)->delete();

        \App\Models\AuditLog::log('Security', 'Forced logout of an active session', 'DELETE');

        return redirect()->route('host.security')->with('success', 'Session has been terminated.');
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

        return view('super_admin.reports.index', compact(
            'totalCompanies', 'totalUsers', 'totalRevenue',
            'activeSubs', 'monthlyRevenue', 'planDistribution'
        ));
    }

    public function settings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        $maintenanceMode = SystemSetting::get('maintenance_mode', '0') === '1';
        return view('super_admin.settings.index', compact('settings', 'maintenanceMode'));
    }

    public function toggleMaintenanceMode()
    {
        $enabled = SystemSetting::get('maintenance_mode', '0') === '1';
        SystemSetting::set('maintenance_mode', $enabled ? '0' : '1');

        \App\Models\AuditLog::log('Settings', ($enabled ? 'Disabled' : 'Enabled') . ' maintenance mode', 'UPDATE');

        return redirect()->route('host.settings')->with('success', 'Maintenance mode is now ' . ($enabled ? 'OFF' : 'ON') . '.');
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
