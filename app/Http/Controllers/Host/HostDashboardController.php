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
use App\Mail\SubscriptionExpiryReminderMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HostDashboardController extends Controller
{
    public function dashboard()
    {
        $totalCompanies     = Company::count();
        $activeCompanies    = Company::where('status', 'active')
                                ->where(fn ($q) => $q
                                    ->whereDoesntHave('subscription')
                                    ->orWhereHas('subscription', fn ($q2) => $q2
                                        ->where('status', '!=', 'expired')
                                        ->where(fn ($q3) => $q3
                                            ->whereNull('expiry_date')
                                            ->orWhere('expiry_date', '>=', now()->toDateString())
                                        )
                                    )
                                )->count();
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

        $revenueCollected   = SubscriptionPayment::where('status', 'completed')->sum('amount');
        $pendingApprovals   = DemoRequest::where('status', 'pending')->count();
        $overdueAccounts    = Subscription::where('status', 'expired')->count();
        $expiredCompanies   = Subscription::where(fn ($q) => $q
                                ->where('status', 'expired')
                                ->orWhere(fn ($q2) => $q2
                                    ->whereIn('status', ['trial', 'active'])
                                    ->whereNotNull('expiry_date')
                                    ->where('expiry_date', '<', now()->toDateString()))
                            )->count();

        $newSignupsThisWeek = Company::with(['subscription.plan'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->get();

        return view('super_admin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'suspendedCompanies', 'totalUsers',
            'monthlyRevenue', 'newThisMonth', 'revenueCollected', 'pendingApprovals',
            'overdueAccounts', 'expiredCompanies', 'newSignupsThisWeek'
        ));
    }

    public function sendTrialReminder($id)
    {
        $company      = Company::with('subscription.plan')->findOrFail($id);
        $subscription = $company->subscription;

        if (!$subscription || !$subscription->expiry_date) {
            return redirect()->back()->with('error', "{$company->name} has no active trial to remind.");
        }

        $recipient = User::withoutGlobalScopes()->where('company_id', $company->id)->where('role', 'admin')->first();
        $email     = $recipient->email ?? $company->email;

        if (!$email) {
            return redirect()->back()->with('error', "{$company->name} has no email address on file.");
        }

        $expiry   = Carbon::parse($subscription->expiry_date)->startOfDay();
        $daysLeft = (int) Carbon::today()->diffInDays($expiry, false);
        $userName = $recipient->name ?? $company->name;

        try {
            Mail::to($email)->send(new SubscriptionExpiryReminderMail(
                $company->name, $userName, $daysLeft, $expiry, $subscription->status === 'trial'
            ));
        } catch (\Exception $e) {
            Log::error("Manual trial reminder failed for company #{$company->id} ({$email}): " . $e->getMessage());

            return redirect()->back()->with('error', "Failed to send reminder to {$company->name}.");
        }

        $subscription->update(['last_reminder_sent_at' => now()]);

        return redirect()->back()->with('success', "Reminder email sent to {$company->name}.");
    }

    public function sendTrialRemindersBulk()
    {
        $companies = Company::with('subscription.plan')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereHas('subscription', fn ($q) => $q->where('status', 'trial')->whereNotNull('expiry_date'))
            ->get();

        $sent    = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            $subscription = $company->subscription;
            $recipient    = User::withoutGlobalScopes()->where('company_id', $company->id)->where('role', 'admin')->first();
            $email        = $recipient->email ?? $company->email;

            if (!$email) {
                $skipped++;
                continue;
            }

            $expiry   = Carbon::parse($subscription->expiry_date)->startOfDay();
            $daysLeft = (int) Carbon::today()->diffInDays($expiry, false);
            $userName = $recipient->name ?? $company->name;

            try {
                Mail::to($email)->send(new SubscriptionExpiryReminderMail(
                    $company->name, $userName, $daysLeft, $expiry, true
                ));
                $subscription->update(['last_reminder_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                Log::error("Bulk trial reminder failed for company #{$company->id} ({$email}): " . $e->getMessage());
                $skipped++;
            }
        }

        $message = "Sent {$sent} reminder email" . ($sent === 1 ? '' : 's') . '.'
            . ($skipped ? " {$skipped} skipped (no email on file)." : '');

        return redirect()->back()->with('success', $message);
    }

    public function expiredCompanies(Request $request)
    {
        $today = now()->toDateString();

        $companies = Company::with(['subscription.plan'])
            ->whereHas('subscription', fn ($q) => $q->where('status', 'expired')
                ->orWhere(fn ($q2) => $q2
                    ->whereIn('status', ['trial', 'active'])
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', $today)))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $allPlans = SubscriptionPlan::orderBy('price')->get();

        return view('super_admin.expired_companies', compact('companies', 'allPlans'));
    }

    public function manageCompanies(Request $request)
    {
        $companies = Company::query()
            ->selectRaw('companies.*,
                (SELECT email FROM users WHERE users.company_id = companies.id ORDER BY users.id ASC LIMIT 1) AS admin_email,
                (SELECT COUNT(*) FROM subscription_payments sp JOIN subscriptions s ON sp.subscription_id = s.id WHERE s.company_id = companies.id) AS payments_count')
            ->with(['subscription.plan', 'users' => fn ($q) => $q->withoutGlobalScopes()->orderBy('id')])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('companies.name', 'like', '%' . $request->search . '%')
                ->orWhere('companies.email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status') && $request->status !== 'expired', fn ($q) => $q->where('companies.status', $request->status))
            ->when($request->status === 'expired', fn ($q) => $q->whereHas('subscription', fn ($q2) => $q2
                ->where('status', 'expired')
                ->orWhere(fn ($q3) => $q3->whereIn('status', ['trial', 'active'])->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString()))))
            ->when($request->filled('plan'), fn ($q) => $q->whereHas('subscription.plan', fn ($q2) => $q2->where('name', $request->plan)))
            ->orderByDesc('companies.created_at')
            ->paginate(20)
            ->withQueryString();

        $allPlans = SubscriptionPlan::orderBy('price')->get();
        $plans = $allPlans->pluck('name');

        return view('super_admin.companies', compact('companies', 'plans', 'allPlans'));
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
                'email'             => $request->owner_email,
                'password'          => \Illuminate\Support\Facades\Hash::make($tempPassword),
                'role'              => 'admin',
                'company_id'        => $company->id,
                'email_verified_at' => now(),
            ]);

            if ($request->subscription_plan_id) {
                $newPlan = SubscriptionPlan::find($request->subscription_plan_id);
                Subscription::create([
                    'company_id'           => $company->id,
                    'subscription_plan_id' => $request->subscription_plan_id,
                    'start_date'           => now(),
                    'expiry_date'          => $newPlan && $newPlan->price == 0
                                                ? now()->addDays(7)
                                                : now()->addMonths($newPlan && $newPlan->billing_cycle === 'yearly' ? 12 : 1),
                    'status'               => 'trial',
                    'auto_renew'           => false,
                ]);
            }

            return $company;
        });

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
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_amount'       => 'nullable|numeric|min:0.01',
            'payment_method'       => 'nullable|string|max:50',
            'payment_date'         => 'nullable|date',
            'transaction_id'       => 'nullable|string|max:100',
        ]);

        $company = Company::with('subscription')->findOrFail($id);
        $plan = $this->applyCompanyPlan($company, $request->subscription_plan_id);

        // If the admin provided payment details, record them against the
        // newly activated subscription so billing history stays accurate.
        if ($request->filled('payment_amount') && $company->subscription) {
            SubscriptionPayment::create([
                'subscription_id' => $company->subscription->id,
                'amount'          => $request->payment_amount,
                'payment_date'    => $request->payment_date ?? now()->toDateString(),
                'payment_method'  => $request->payment_method ?? 'Manual',
                'transaction_id'  => $request->transaction_id,
                'status'          => 'completed',
            ]);
        }

        return redirect()->route('host.companies')->with('success', "{$company->name} is now on the {$plan->name} plan." . ($request->filled('payment_amount') ? ' Payment recorded.' : ''));
    }

    public function toggleCompanyStatus($id)
    {
        $company = Company::findOrFail($id);
        $company->status = $company->status === 'suspended' ? 'active' : 'suspended';
        $company->save();

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
        $company = Company::with('subscription')->findOrFail($id);

        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
        ]);

        $company->update($request->only('name', 'email', 'phone'));

        if ($request->filled('subscription_plan_id')) {
            $plan = $this->applyCompanyPlan($company, $request->subscription_plan_id);
        }

        return redirect()->route('host.companies')->with('success', "{$company->name}'s details have been updated.");
    }

    // Assigning a plan here represents a confirmed, paid plan change —
    // there's no separate "mark as paid" step elsewhere in the app — so
    // it always activates the subscription with a fresh expiry date,
    // taking a company off Free Trial the moment Super Admin uses this.
    private function applyCompanyPlan(Company $company, $planId): SubscriptionPlan
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $expiryDate = $plan->price == 0
            ? now()->addDays(7)
            : match ($plan->billing_cycle) {
                'yearly'    => now()->addYear(),
                'quarterly' => now()->addMonths(3),
                '7days'     => now()->addDays(7),
                default     => now()->addMonth(),
            };

        if ($company->subscription) {
            $company->subscription->update([
                'subscription_plan_id' => $planId,
                'status'                => 'active',
                'start_date'            => now()->toDateString(),
                'expiry_date'           => $expiryDate,
            ]);
            // Auto-complete any pending payment requests so history stays in sync
            $company->subscription->payments()
                ->where('status', 'pending')
                ->update(['status' => 'completed']);
        } else {
            Subscription::create([
                'company_id'           => $company->id,
                'subscription_plan_id' => $planId,
                'start_date'            => now(),
                'expiry_date'           => $expiryDate,
                'status'                => 'active',
                'auto_renew'            => false,
            ]);
        }

        return $plan;
    }

    public function destroyCompany($id)
    {
        $company = Company::with('subscription')->findOrFail($id);
        $name = $company->name;

        $paymentsCount = $company->subscription
            ? SubscriptionPayment::where('subscription_id', $company->subscription->id)->count()
            : 0;

        if ($paymentsCount > 0) {
            return redirect()->route('host.companies')
                ->with('error', "\"{$name}\" has {$paymentsCount} subscription payment record(s) and cannot be deleted.");
        }

        \Illuminate\Support\Facades\DB::transaction(fn () => $company->delete());

        return redirect()->route('host.companies')->with('success', "{$name} has been permanently deleted.");
    }

    public function bulkCompanyAction(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'action' => 'required|in:suspend,reactivate,delete,export',
        ]);

        $companies = Company::whereIn('id', $request->ids)->get();

        if ($request->action === 'export') {
            return response()->streamDownload(function () use ($companies) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Company Name', 'Email', 'Phone', 'Status', 'Joined']);
                foreach ($companies as $company) {
                    fputcsv($out, [$company->name, $company->email, $company->phone, $company->status, $company->created_at->format('Y-m-d')]);
                }
                fclose($out);
            }, 'companies-export-' . now()->format('Ymd-His') . '.csv');
        }

        foreach ($companies as $company) {
            if ($request->action === 'suspend') $company->update(['status' => 'suspended']);
            if ($request->action === 'reactivate') $company->update(['status' => 'active']);
            if ($request->action === 'delete') \Illuminate\Support\Facades\DB::transaction(fn () => $company->delete());
        }

        return redirect()->route('host.companies')->with('success', ucfirst($request->action) . ' applied to ' . $companies->count() . ' ' . \Illuminate\Support\Str::plural('company', $companies->count()) . '.');
    }

    public function demoRequests()
    {
        $requests = DemoRequest::orderByDesc('created_at')->paginate(20);
        $pendingCount = DemoRequest::where('status', 'pending')->count();

        return view('super_admin.demo_requests', compact('requests', 'pendingCount'));
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
        return view('super_admin.users', compact('users', 'companies'));
    }

    public function resetUserPassword($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);

        $tempPassword = \Illuminate\Support\Str::random(10);
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($tempPassword)]);

        return redirect()->route('host.users')->with('success',
            "Password for <strong>{$user->name}</strong> has been reset. Temporary password: <code style='background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:14px;'><strong>{$tempPassword}</strong></code> — share this with the user and ask them to change it after login.");
    }

    public function toggleUserStatus($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $user->status = $user->status === 'suspended' ? 'active' : 'suspended';
        $user->save();

        return redirect()->route('host.users')->with('success', "{$user->name} is now {$user->status}.");
    }

    public function destroyUser($id)
    {
        $user = User::withoutGlobalScopes()->findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('host.users')->with('success', "{$name} has been removed.");
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::with(['company', 'plan', 'payments'])
            ->whereIn('status', ['active', 'pending_payment'])
            ->orderByDesc('created_at')->paginate(20);

        $totalMrr = Subscription::with('plan')->where('status', 'active')->get()
            ->sum(fn ($s) => $s->plan ? $s->plan->price / ($s->plan->billing_cycle === 'yearly' ? 12 : ($s->plan->billing_cycle === 'quarterly' ? 3 : 1)) : 0);

        $overduePayments = SubscriptionPayment::whereIn('status', ['pending', 'failed'])->count();

        $expiringThisMonth = Subscription::where('status', 'active')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $totalArr = $totalMrr * 12;
        $allPlans = SubscriptionPlan::orderBy('price')->get();

        return view('super_admin.subscriptions', compact('subscriptions', 'totalMrr', 'totalArr', 'overduePayments', 'expiringThisMonth', 'allPlans'));
    }

    public function payments(Request $request)
    {
        $payments = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('payment_date')->paginate(20)->withQueryString();
        $totalRevenue = SubscriptionPayment::where('status', 'completed')->sum('amount');
        $subscriptions = Subscription::with(['company', 'plan'])->orderBy('id', 'desc')->get();
        return view('super_admin.payments', compact('payments', 'totalRevenue', 'subscriptions'));
    }

    // Lets Super Admin record a payment that happened outside the
    // automated checkout flow (e.g. cash, manual bank transfer) so the
    // company's billing history and revenue totals stay accurate.
    public function storePayment(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount'          => 'required|numeric|min:0.01',
            'payment_date'    => 'required|date',
            'payment_method'  => 'required|string|max:50',
            'transaction_id'  => 'nullable|string|max:100',
            'status'          => 'required|in:completed,pending,failed',
        ]);

        $subscription = Subscription::with('company')->findOrFail($request->subscription_id);

        $payment = SubscriptionPayment::create($request->only(
            'subscription_id', 'amount', 'payment_date', 'payment_method', 'transaction_id', 'status'
        ));

        return redirect()->route('host.payments')->with('success', 'Payment recorded successfully.');
    }

    public function destroyPayment($id)
    {
        $payment = SubscriptionPayment::with('subscription.company')->findOrFail($id);
        $companyName = $payment->subscription->company->name ?? 'Unknown company';
        $amount = $payment->amount;

        $payment->delete();

        return redirect()->route('host.payments')->with('success', 'Payment record deleted.');
    }

    public function markPaymentPaid($id)
    {
        $payment      = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])->findOrFail($id);
        $subscription = $payment->subscription;
        $plan         = $subscription->plan;

        $payment->update(['status' => 'completed']);

        // Activate the subscription when payment is approved
        if ($subscription && $plan) {
            $expiresAt = match ($plan->billing_cycle) {
                'yearly'    => now()->addYear(),
                'quarterly' => now()->addMonths(3),
                '7days'     => now()->addDays(7),
                default     => now()->addMonth(),
            };
            $subscription->update([
                'status'      => 'active',
                'start_date'  => now()->toDateString(),
                'expiry_date' => $expiresAt->toDateString(),
            ]);
        }

        return redirect()->route('host.payments')->with('success', 'Payment approved — subscription is now active.');
    }

    public function cancelSubscriptionAction($id)
    {
        $subscription = Subscription::with('company')->findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        return redirect()->route('host.subscriptions')->with('success', "Subscription for {$subscription->company->name} has been cancelled.");
    }

    public function sendInvoice($id)
    {
        $subscription = Subscription::with('company')->findOrFail($id);

        return redirect()->back()->with('success', "Invoice logged for {$subscription->company->name}.");
    }

    public function viewInvoice($id)
    {
        $subscription = Subscription::with(['company', 'plan', 'payments'])->findOrFail($id);
        $lastPayment  = $subscription->payments->where('status', 'completed')->sortByDesc('payment_date')->first();
        $invoiceNo    = 'INV-SUB-' . str_pad($subscription->id, 5, '0', STR_PAD_LEFT);

        return view('super_admin.subscription_invoice', compact('subscription', 'lastPayment', 'invoiceNo'));
    }

    public function downloadInvoicePdf($id)
    {
        $subscription = Subscription::with(['company', 'plan', 'payments'])->findOrFail($id);
        $lastPayment  = $subscription->payments->where('status', 'completed')->sortByDesc('payment_date')->first();
        $invoiceNo    = 'INV-SUB-' . str_pad($subscription->id, 5, '0', STR_PAD_LEFT);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'super_admin.subscription_invoice_pdf',
            compact('subscription', 'lastPayment', 'invoiceNo')
        )->setPaper('a4', 'portrait');

        return $pdf->download($invoiceNo . '.pdf');
    }

    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        return view('super_admin.plans', compact('plans'));
    }

    public function subscriptionPlans()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        return view('super_admin.subscription_plans', compact('plans'));
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

        return view('super_admin.announcements', compact('announcements', 'companies'));
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
            'start_time' => 'nullable|date',
            'end_time'   => 'nullable|date|after_or_equal:start_time',
        ]);

        $announcement = \App\Models\Announcement::create([
            'title'             => $request->title,
            'message'           => $request->message,
            'target_company_id' => $request->target === 'specific' ? $request->company_id : null,
            'priority'          => $request->priority,
            'status'            => $request->submit_as,
            'sent_at'           => $request->submit_as === 'Sent' ? now() : null,
            'start_time'        => $request->start_time ?: null,
            'end_time'          => $request->end_time ?: null,
        ]);

        return redirect()->route('host.announcements')->with('success', $request->submit_as === 'Sent' ? 'Announcement sent.' : 'Draft saved.');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);

        $request->validate([
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'target'     => 'required|in:All,specific',
            'company_id' => 'required_if:target,specific|nullable|exists:companies,id',
            'priority'   => 'required|in:Info,Warning,Critical',
            'start_time' => 'nullable|date',
            'end_time'   => 'nullable|date|after_or_equal:start_time',
        ]);

        $announcement->update([
            'title'             => $request->title,
            'message'           => $request->message,
            'target_company_id' => $request->target === 'specific' ? $request->company_id : null,
            'priority'          => $request->priority,
            'start_time'        => $request->start_time ?: null,
            'end_time'          => $request->end_time ?: null,
        ]);

        return redirect()->route('host.announcements')->with('success', 'Announcement updated.');
    }

    public function destroyAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $title = $announcement->title;
        $announcement->delete();

        return redirect()->route('host.announcements')->with('success', 'Announcement deleted.');
    }

    public function sendAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::findOrFail($id);
        $announcement->update(['status' => 'Sent', 'sent_at' => now()]);

        return redirect()->route('host.announcements')->with('success', 'Announcement sent.');
    }

    public function security(Request $request)
    {
        $sessions = \Illuminate\Support\Facades\DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name', 'users.email')
            ->orderByDesc('sessions.last_activity')
            ->get();

        return view('super_admin.security', compact('sessions'));
    }

    public function forceLogoutSession($sessionId)
    {
        \Illuminate\Support\Facades\DB::table('sessions')->where('id', $sessionId)->delete();

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

        return view('super_admin.reports', compact(
            'totalCompanies', 'totalUsers', 'totalRevenue',
            'activeSubs', 'monthlyRevenue', 'planDistribution'
        ));
    }

    public function settings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        $maintenanceMode = SystemSetting::get('maintenance_mode', '0') === '1';
        return view('super_admin.settings', compact('settings', 'maintenanceMode'));
    }

    public function toggleMaintenanceMode()
    {
        $enabled = SystemSetting::get('maintenance_mode', '0') === '1';
        SystemSetting::set('maintenance_mode', $enabled ? '0' : '1');

        return redirect()->route('host.settings')->with('success', 'Maintenance mode is now ' . ($enabled ? 'OFF' : 'ON') . '.');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'platform_name'          => 'required|string|max:255',
            'support_email'          => 'required|email|max:255',
            'trial_days'             => 'required|integer|min:1|max:365',
            'default_currency'       => 'required|string|max:10',
            'evc_merchant_number'    => 'nullable|string|max:50',
            'bank_name'              => 'nullable|string|max:255',
            'bank_account_name'      => 'nullable|string|max:255',
            'bank_account_number'    => 'nullable|string|max:100',
            'bank_swift_code'        => 'nullable|string|max:20',
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
