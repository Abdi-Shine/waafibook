@extends('super_admin.layouts.master')
@section('page_title', 'Platform Overview')

@section('content')

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 style="font-weight:800; color:#111827; margin:0;">Platform Overview</h4>
            <p style="color:#6b7280; font-size:.875rem; margin:.2rem 0 0;">Monitor and manage your entire ERP SaaS platform</p>
        </div>
        <div style="font-size:.8rem; color:#9ca3af;">
            <i class="bi bi-clock me-1"></i> {{ now()->format('D, d M Y') }}
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="sa-stat">
                <div class="sa-stat-icon" style="background:rgba(0,65,97,.08); color:#004161;">
                    <i class="bi bi-building"></i>
                </div>
                <div class="sa-stat-val" style="color:#004161;">{{ number_format($totalCompanies) }}</div>
                <div class="sa-stat-lbl">Total Companies</div>
                <div class="sa-stat-sub pos">
                    <i class="bi bi-arrow-up"></i> +{{ $newThisMonth }} this month
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sa-stat">
                <div class="sa-stat-icon" style="background:rgba(153,204,51,.12); color:#6a9e00;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="sa-stat-val" style="color:#6a9e00;">${{ number_format($monthlyRevenue, 0) }}</div>
                <div class="sa-stat-lbl">Monthly Revenue</div>
                <div class="sa-stat-sub pos">
                    <i class="bi bi-calendar-month"></i> {{ now()->format('F Y') }}
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sa-stat">
                <div class="sa-stat-icon" style="background:rgba(59,130,246,.1); color:#2563eb;">
                    <i class="bi bi-people"></i>
                </div>
                <div class="sa-stat-val" style="color:#2563eb;">{{ number_format($totalUsers) }}</div>
                <div class="sa-stat-lbl">Total Users</div>
                <div class="sa-stat-sub pos">
                    <i class="bi bi-person-check"></i> Across all companies
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sa-stat">
                <div class="sa-stat-icon" style="background:rgba(220,38,38,.08); color:#dc2626;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="sa-stat-val" style="color:#dc2626;">{{ $expiringSoon }}</div>
                <div class="sa-stat-lbl">Expiring Soon</div>
                <div class="sa-stat-sub warn">
                    <i class="bi bi-clock-history"></i> Within next 7 days
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Recent companies --}}
        <div class="col-lg-8">
            <div class="sa-card">
                <div class="sa-card-head">
                    <h6><i class="bi bi-building me-2"></i>Recent Companies</h6>
                    <a href="{{ route('host.companies') }}"
                       style="font-size:.78rem; font-weight:600; color:#004161; text-decoration:none;">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Plan</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCompanies as $company)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($company->logo)
                                            <img src="{{ asset($company->logo) }}" style="width:36px;height:36px;border-radius:8px;object-fit:contain;background:#f3f4f6;padding:3px;">
                                        @else
                                            <div style="width:36px;height:36px;border-radius:8px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:#0369a1;flex-shrink:0;">
                                                {{ strtoupper(substr($company->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div style="font-weight:600;font-size:.85rem;color:#111827;">{{ $company->name }}</div>
                                            @if($company->email)
                                            <div style="font-size:.72rem;color:#9ca3af;">{{ $company->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($company->subscription?->plan)
                                        <span class="sa-badge sa-badge-blue">{{ $company->subscription->plan->name }}</span>
                                    @else
                                        <span class="sa-badge sa-badge-gray">No Plan</span>
                                    @endif
                                </td>
                                <td style="font-weight:600;">{{ $company->user_count }}</td>
                                <td>
                                    @php
                                        $sub = $company->subscription;
                                        $isExpiring = $sub && $sub->expiry_date
                                            && \Carbon\Carbon::parse($sub->expiry_date)->isFuture()
                                            && \Carbon\Carbon::parse($sub->expiry_date)->diffInDays(now()) <= 7
                                            && $sub->status === 'active';
                                    @endphp
                                    @if(!$sub || $sub->status !== 'active')
                                        <span class="sa-badge sa-badge-gray">No Sub</span>
                                    @elseif($isExpiring)
                                        <span class="sa-badge sa-badge-yellow"><i class="bi bi-clock"></i> Expiring</span>
                                    @else
                                        <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                                    @endif
                                </td>
                                <td style="color:#6b7280;font-size:.8rem;">{{ $company->created_at->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color:#9ca3af;font-size:.85rem;">
                                    No companies registered yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Plan distribution + quick actions --}}
        <div class="col-lg-4">
            <div class="sa-card">
                <div class="sa-card-head">
                    <h6><i class="bi bi-pie-chart me-2"></i>Plan Distribution</h6>
                </div>
                <div class="p-4">
                    @forelse($planDistribution as $plan)
                    @php
                        $pct    = round(($plan->active_count / $totalActiveSubs) * 100);
                        $colors = ['#004161','#99CC33','#f59e0b','#3b82f6','#8b5cf6'];
                        $color  = $colors[$loop->index % count($colors)];
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                            <span style="font-weight:600;color:#374151;">{{ $plan->name }}</span>
                            <span style="font-weight:700;color:#111827;">{{ $plan->active_count }} ({{ $pct }}%)</span>
                        </div>
                        <div style="height:7px;background:#f3f4f6;border-radius:99px;overflow:hidden;">
                            <div style="width:{{ $pct }}%;height:100%;background:{{ $color }};border-radius:99px;"></div>
                        </div>
                    </div>
                    @empty
                    <p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:1rem 0;">No subscription plans yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-head"><h6><i class="bi bi-lightning me-2"></i>Quick Actions</h6></div>
                <div class="p-3 d-grid gap-2">
                    <a href="{{ route('host.companies') }}"
                       style="display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-radius:8px;background:#f0f7ff;color:#004161;text-decoration:none;font-size:.85rem;font-weight:600;">
                        <i class="bi bi-building-add"></i> Manage Companies
                    </a>
                    <a href="{{ route('host.subscriptions') }}"
                       style="display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-radius:8px;background:#f0fdf4;color:#16a34a;text-decoration:none;font-size:.85rem;font-weight:600;">
                        <i class="bi bi-credit-card-2-front"></i> Manage Subscriptions
                    </a>
                    <a href="{{ route('host.plans') }}"
                       style="display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-radius:8px;background:#fefce8;color:#a16207;text-decoration:none;font-size:.85rem;font-weight:600;">
                        <i class="bi bi-tags"></i> Pricing Plans
                    </a>
                    <a href="{{ route('host.payments') }}"
                       style="display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-radius:8px;background:#fdf4ff;color:#7e22ce;text-decoration:none;font-size:.85rem;font-weight:600;">
                        <i class="bi bi-cash-stack"></i> View Payments
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
