<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HostReportsController extends Controller
{
    // ── Shared helpers ────────────────────────────────────────────────────────

    private function monthRange(int $months = 12): array
    {
        $range = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $range[] = now()->subMonths($i);
        }
        return $range;
    }

    private function exportCsv(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── Overview / Index ──────────────────────────────────────────────────────

    public function index()
    {
        $months = $this->monthRange(6);

        $overview = [
            'companies'       => Company::count(),
            'active_subs'     => Subscription::where('status', 'active')->count(),
            'trial_subs'      => Subscription::where('status', 'trial')->count(),
            'total_revenue'   => SubscriptionPayment::where('status', 'completed')->sum('amount'),
            'pending_revenue' => SubscriptionPayment::where('status', 'pending')->sum('amount'),
            'total_users'     => User::withoutGlobalScopes()->whereNotNull('company_id')->count(),
            'expired_subs'    => Subscription::where('status', 'expired')->count(),
        ];

        $revenueChart = collect($months)->map(fn($d) => [
            'label'  => $d->format('M y'),
            'amount' => SubscriptionPayment::where('status', 'completed')
                ->whereMonth('payment_date', $d->month)->whereYear('payment_date', $d->year)->sum('amount'),
        ]);

        $companyGrowth = collect($months)->map(fn($d) => [
            'label' => $d->format('M y'),
            'count' => Company::whereMonth('created_at', $d->month)->whereYear('created_at', $d->year)->count(),
        ]);

        $planDist = SubscriptionPlan::withCount([
            'subscriptions' => fn($q) => $q->where('status', 'active'),
        ])->get();

        $statusDist = Subscription::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        return view('super_admin.reports.index', compact(
            'overview', 'revenueChart', 'companyGrowth', 'planDist', 'statusDist'
        ));
    }

    // ── Companies Report ──────────────────────────────────────────────────────

    public function companies(Request $request)
    {
        $query = Company::withCount([
            'users as user_count',
        ])->with(['subscription.plan']);

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")->orWhere('country', 'like', "%{$q}%"));
        }

        $companies = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Summary stats
        $byStatus  = Company::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $byCountry = Company::selectRaw('country, COUNT(*) as total')
            ->whereNotNull('country')->where('country', '!=', '')
            ->groupBy('country')->orderByDesc('total')->limit(10)->pluck('total', 'country');

        $monthlyNew = collect($this->monthRange(6))->map(fn($d) => [
            'label' => $d->format('M y'),
            'count' => Company::whereMonth('created_at', $d->month)->whereYear('created_at', $d->year)->count(),
        ]);

        if ($request->input('export') === 'csv') {
            $all = Company::with(['subscription.plan'])
                ->withCount('users as user_count')->get();
            return $this->exportCsv('companies-report-' . now()->format('Y-m-d') . '.csv',
                ['#', 'Company', 'Email', 'Phone', 'Country', 'Status', 'Plan', 'Users', 'Joined'],
                $all->map(fn($c, $i) => [
                    $i + 1, $c->name, $c->email, $c->phone, $c->country, $c->status,
                    $c->subscription?->plan?->name ?? 'None',
                    $c->user_count, $c->created_at->format('Y-m-d'),
                ])->toArray()
            );
        }

        return view('super_admin.reports.companies',
            compact('companies', 'byStatus', 'byCountry', 'monthlyNew'));
    }

    // ── Subscriptions Report ──────────────────────────────────────────────────

    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['company', 'plan'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('plan_id')) $query->where('subscription_plan_id', $request->plan_id);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('company', fn($w) => $w->where('name', 'like', "%{$q}%"));
        }

        $subs  = $query->paginate(25)->withQueryString();
        $plans = SubscriptionPlan::orderBy('price')->get();

        $statusDist = Subscription::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $planDist = SubscriptionPlan::withCount([
            'subscriptions as active_count'  => fn($q) => $q->where('status', 'active'),
            'subscriptions as trial_count'   => fn($q) => $q->where('status', 'trial'),
            'subscriptions as expired_count' => fn($q) => $q->where('status', 'expired'),
        ])->get();

        $expiringNext30 = Subscription::with(['company', 'plan'])
            ->whereIn('status', ['trial', 'active'])
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->orderBy('expiry_date')->get();

        $conversionRate = (function () {
            $trials    = Subscription::where('status', 'trial')->count();
            $converted = Subscription::where('status', 'active')->count();
            $total     = $trials + $converted;
            return $total > 0 ? round(($converted / $total) * 100, 1) : 0;
        })();

        if ($request->input('export') === 'csv') {
            $all = Subscription::with(['company', 'plan'])->get();
            return $this->exportCsv('subscriptions-report-' . now()->format('Y-m-d') . '.csv',
                ['#', 'Company', 'Plan', 'Status', 'Start Date', 'Expiry Date', 'Payment Method'],
                $all->map(fn($s, $i) => [
                    $i + 1, $s->company?->name, $s->plan?->name, $s->status,
                    $s->start_date, $s->expiry_date, $s->payment_method,
                ])->toArray()
            );
        }

        return view('super_admin.reports.subscriptions',
            compact('subs', 'plans', 'statusDist', 'planDist', 'expiringNext30', 'conversionRate'));
    }

    // ── Revenue Report ────────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $monthly = collect(range(1, 12))->map(fn($m) => [
            'label'     => Carbon::create($year, $m)->format('M'),
            'completed' => SubscriptionPayment::where('status', 'completed')
                ->whereMonth('payment_date', $m)->whereYear('payment_date', $year)->sum('amount'),
            'pending'   => SubscriptionPayment::where('status', 'pending')
                ->whereMonth('payment_date', $m)->whereYear('payment_date', $year)->sum('amount'),
        ]);

        $byPlan = SubscriptionPlan::withSum(
            ['payments as revenue' => fn($q) => $q->where('subscription_payments.status', 'completed')],
            'amount'
        )->orderByDesc('revenue')->get();

        $byMethod = SubscriptionPayment::where('status', 'completed')
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')->orderByDesc('total')->get();

        $summary = [
            'total_all_time' => SubscriptionPayment::where('status', 'completed')->sum('amount'),
            'total_year'     => SubscriptionPayment::where('status', 'completed')
                ->whereYear('payment_date', $year)->sum('amount'),
            'total_month'    => SubscriptionPayment::where('status', 'completed')
                ->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'total_pending'  => SubscriptionPayment::where('status', 'pending')->sum('amount'),
            'payment_count'  => SubscriptionPayment::where('status', 'completed')->count(),
        ];

        $recentPayments = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])
            ->orderByDesc('payment_date')->limit(50)->get();

        $years = SubscriptionPayment::selectRaw('YEAR(payment_date) as y')
            ->groupBy('y')->orderByDesc('y')->pluck('y');

        if ($request->input('export') === 'csv') {
            $all = SubscriptionPayment::with(['subscription.company', 'subscription.plan'])
                ->orderByDesc('payment_date')->get();
            return $this->exportCsv('revenue-report-' . now()->format('Y-m-d') . '.csv',
                ['#', 'Company', 'Plan', 'Amount', 'Method', 'Status', 'Date', 'Reference'],
                $all->map(fn($p, $i) => [
                    $i + 1,
                    $p->subscription?->company?->name,
                    $p->subscription?->plan?->name,
                    $p->amount, $p->payment_method, $p->status,
                    $p->payment_date, $p->transaction_id,
                ])->toArray()
            );
        }

        return view('super_admin.reports.revenue',
            compact('monthly', 'byPlan', 'byMethod', 'summary', 'recentPayments', 'year', 'years'));
    }

    // ── Users Report ──────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $query = User::withoutGlobalScopes()
            ->with('company')
            ->whereNotNull('company_id')
            ->orderByDesc('created_at');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('role'))   $query->where('role', $request->role);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"));
        }

        $users = $query->paginate(30)->withQueryString();

        $byRole   = User::withoutGlobalScopes()->whereNotNull('company_id')
            ->selectRaw('role, COUNT(*) as total')->groupBy('role')
            ->orderByDesc('total')->pluck('total', 'role');

        $byStatus = User::withoutGlobalScopes()->whereNotNull('company_id')
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $topCompanies = Company::withCount(['users as user_count'])
            ->orderByDesc('user_count')->limit(10)->get();

        $monthlyNew = collect($this->monthRange(6))->map(fn($d) => [
            'label' => $d->format('M y'),
            'count' => User::withoutGlobalScopes()->whereNotNull('company_id')
                ->whereMonth('created_at', $d->month)->whereYear('created_at', $d->year)->count(),
        ]);

        $roles = User::withoutGlobalScopes()->whereNotNull('company_id')
            ->select('role')->distinct()->orderBy('role')->pluck('role');

        if ($request->input('export') === 'csv') {
            $all = User::withoutGlobalScopes()->with('company')->whereNotNull('company_id')->get();
            return $this->exportCsv('users-report-' . now()->format('Y-m-d') . '.csv',
                ['#', 'Name', 'Email', 'Role', 'Status', 'Company', 'Joined'],
                $all->map(fn($u, $i) => [
                    $i + 1, $u->name, $u->email, $u->role, $u->status ?? 'active',
                    $u->company?->name, $u->created_at->format('Y-m-d'),
                ])->toArray()
            );
        }

        return view('super_admin.reports.users',
            compact('users', 'byRole', 'byStatus', 'topCompanies', 'monthlyNew', 'roles'));
    }

}
