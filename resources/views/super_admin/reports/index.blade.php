@extends('super_admin.layouts.master')
@section('page_title', 'Reports')
@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">Reports</h4>
    <p class="text-muted mb-0">Platform-wide analytics and statistics</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                <i class="bi bi-building"></i>
            </div>
            <div class="sa-stat-val">{{ $totalCompanies }}</div>
            <div class="sa-stat-lbl">Total Companies</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div class="sa-stat-val">{{ $activeSubs }}</div>
            <div class="sa-stat-lbl">Active Subscriptions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:#d1fae5;color:#059669;">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="sa-stat-val">${{ number_format($totalRevenue, 0) }}</div>
            <div class="sa-stat-lbl">Total Revenue</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:#fef3c7;color:#d97706;">
                <i class="bi bi-people"></i>
            </div>
            <div class="sa-stat-val">{{ $totalUsers }}</div>
            <div class="sa-stat-lbl">Total Users</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><i class="bi bi-bar-chart me-2"></i>Monthly Revenue (Last 6 Months)</h6>
            </div>
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthlyRevenue as $row)
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        <td class="text-end fw-semibold">${{ number_format($row['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="sa-card">
            <div class="sa-card-head">
                <h6><i class="bi bi-pie-chart me-2"></i>Plan Distribution</h6>
            </div>
            <div class="p-3">
                @forelse($planDistribution as $plan)
                @php
                    $total = $planDistribution->sum('subscriptions_count') ?: 1;
                    $pct   = round($plan->subscriptions_count / $total * 100);
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
                        <span class="fw-semibold">{{ $plan->name }}</span>
                        <span>{{ $plan->subscriptions_count }} ({{ $pct }}%)</span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:4px;">
                        <div class="progress-bar"
                             style="width:{{ $pct }}%;background:var(--primary);border-radius:4px;"></div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3">No plan data</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
