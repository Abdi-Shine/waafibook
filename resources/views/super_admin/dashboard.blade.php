@extends('super_admin.layouts.master')
@section('page_title', 'Dashboard')

@section('content')

    <div class="mb-4">
        <h2 style="font-weight:800;color:#111827;font-size:1.4rem;margin:0;">Platform Overview</h2>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">A snapshot of every tenant, user and dollar moving through Waafibook right now.</p>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">Platform-Wide</span>
                <div class="sa-stat-icon" style="background:rgba(0,65,97,.08);color:#004161;"><i class="bi bi-building"></i></div>
                <div class="sa-stat-val" style="color:#004161;">{{ number_format($totalCompanies) }}</div>
                <div class="sa-stat-lbl mb-2">Total Companies</div>
                <a href="{{ route('host.companies') }}" class="sa-stat-link">See Companies <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">Tenants</span>
                <div class="sa-stat-icon" style="background:rgba(153,204,51,.15);color:#5a7a1a;"><i class="bi bi-check-circle"></i></div>
                <div class="sa-stat-val" style="color:#5a7a1a;">{{ number_format($activeCompanies) }}</div>
                <div class="sa-stat-lbl mb-2">Active Companies</div>
                <a href="{{ route('host.companies') }}?status=active" class="sa-stat-link">See Companies <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">Tenants</span>
                <div class="sa-stat-icon" style="background:rgba(245,158,11,.12);color:#d97706;"><i class="bi bi-slash-circle"></i></div>
                <div class="sa-stat-val" style="color:#d97706;">{{ number_format($suspendedCompanies) }}</div>
                <div class="sa-stat-lbl mb-2">Suspended Companies</div>
                <a href="{{ route('host.companies') }}?status=suspended" class="sa-stat-link">See Companies <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">All Tenants</span>
                <div class="sa-stat-icon" style="background:rgba(59,130,246,.1);color:#2563eb;"><i class="bi bi-people"></i></div>
                <div class="sa-stat-val" style="color:#2563eb;">{{ number_format($totalUsers) }}</div>
                <div class="sa-stat-lbl mb-2">Total Users</div>
                <a href="{{ route('host.users') }}" class="sa-stat-link">See Users <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">Monthly</span>
                <div class="sa-stat-icon" style="background:rgba(0,65,97,.08);color:#004161;"><i class="bi bi-cash-stack"></i></div>
                <div class="sa-stat-val" style="color:#004161;">${{ number_format($monthlyRevenue, 0) }}</div>
                <div class="sa-stat-lbl mb-2">Total Revenue</div>
                <a href="{{ route('host.payments') }}" class="sa-stat-link">See Billing <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="sa-stat">
                <span class="sa-pill">This Month</span>
                <div class="sa-stat-icon" style="background:rgba(153,204,51,.15);color:#5a7a1a;"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="sa-stat-val" style="color:#5a7a1a;">{{ number_format($newThisMonth) }}</div>
                <div class="sa-stat-lbl mb-2">New Signups</div>
                <a href="{{ route('host.companies') }}" class="sa-stat-link">See Companies <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>

    {{-- Fill cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="sa-fill-card sa-fill-navy">
                <div class="icon"><i class="bi bi-person-plus"></i></div>
                <div class="lbl-top">New Companies</div>
                <div class="val">{{ number_format($newThisMonth) }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="sa-fill-card sa-fill-lime">
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="lbl-top">Pending Approvals</div>
                <div class="val">{{ number_format($pendingApprovals) }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="sa-fill-card sa-fill-navy">
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
                <div class="lbl-top">Revenue Collected</div>
                <div class="val">${{ number_format($revenueCollected, 0) }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="sa-fill-card sa-fill-lime">
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="lbl-top">Overdue Accounts</div>
                <div class="val">{{ number_format($overdueAccounts) }}</div>
            </div>
        </div>
    </div>


@endsection
