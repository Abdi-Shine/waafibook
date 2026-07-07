@extends('super_admin.layouts.master')
@section('page_title', 'Reports — Overview')
@section('content')

<div class="mb-4">
    <h4 style="font-weight:800;color:#111827;margin:0;">Platform Reports</h4>
    <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Real-time analytics across all companies, subscriptions, revenue, and system activity.</p>
</div>

@include('super_admin.reports._subnav')

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
        $kpis = [
            ['label'=>'Total Companies',    'value'=>$overview['companies'],       'icon'=>'bi-building',         'color'=>'#004161'],
            ['label'=>'Active Subscriptions','value'=>$overview['active_subs'],    'icon'=>'bi-patch-check-fill', 'color'=>'#6366f1'],
            ['label'=>'Trial Accounts',     'value'=>$overview['trial_subs'],      'icon'=>'bi-hourglass-split',  'color'=>'#f59e0b'],
            ['label'=>'Total Revenue',      'value'=>'$'.number_format($overview['total_revenue'],0), 'icon'=>'bi-currency-dollar','color'=>'#10b981'],
            ['label'=>'Pending Payments',   'value'=>'$'.number_format($overview['pending_revenue'],0),'icon'=>'bi-clock-history','color'=>'#f97316'],
            ['label'=>'Total Users',        'value'=>$overview['total_users'],     'icon'=>'bi-people-fill',      'color'=>'#8b5cf6'],
            ['label'=>'Expired Accounts',   'value'=>$overview['expired_subs'],    'icon'=>'bi-x-circle',         'color'=>'#ef4444'],
        ];
    @endphp
    @foreach($kpis as $k)
    <div class="col-6 col-md-3">
        <div class="sa-card p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:{{ $k['color'] }}18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $k['icon'] }}" style="font-size:1.2rem;color:{{ $k['color'] }};"></i>
                </div>
                <div>
                    <div style="font-size:1.4rem;font-weight:800;color:#111827;line-height:1.1;">{{ $k['value'] }}</div>
                    <div style="font-size:.72rem;color:#6b7280;font-weight:500;margin-top:.1rem;">{{ $k['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-bar-chart me-2"></i>Revenue — Last 6 Months
            </h6>
            <canvas id="revenueChart" height="110"></canvas>
        </div>
    </div>

    {{-- Subscription Status --}}
    <div class="col-lg-4">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-pie-chart me-2"></i>Subscription Status
            </h6>
            <canvas id="statusChart" height="160"></canvas>
            <div class="mt-3">
                @foreach($statusDist as $status => $count)
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted text-capitalize">{{ $status }}</span>
                    <span class="fw-bold">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Company Growth --}}
    <div class="col-lg-6">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-graph-up me-2"></i>New Companies — Last 6 Months
            </h6>
            <canvas id="growthChart" height="130"></canvas>
        </div>
    </div>

    {{-- Plan Distribution --}}
    <div class="col-lg-6">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-tags me-2"></i>Active Subscriptions by Plan
            </h6>
            <canvas id="planChart" height="130"></canvas>
        </div>
    </div>
</div>

{{-- Quick Links --}}
<div class="row g-3 mt-2">
    @foreach([
        ['route'=>'host.reports.companies',     'label'=>'View Company Report',      'icon'=>'bi-building',         'color'=>'#004161'],
        ['route'=>'host.reports.subscriptions', 'label'=>'View Subscription Report', 'icon'=>'bi-credit-card',      'color'=>'#6366f1'],
        ['route'=>'host.reports.revenue',       'label'=>'View Revenue Report',      'icon'=>'bi-currency-dollar',  'color'=>'#10b981'],
        ['route'=>'host.reports.users',         'label'=>'View User Report',         'icon'=>'bi-people',           'color'=>'#8b5cf6'],
        ['route'=>'host.reports.activity',      'label'=>'View Activity Log',        'icon'=>'bi-activity',         'color'=>'#f97316'],
    ] as $ql)
    <div class="col-6 col-md-4 col-lg-2">
        <a href="{{ route($ql['route']) }}" class="sa-card p-3 d-flex flex-column align-items-center text-center text-decoration-none h-100"
           style="transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,65,97,.12)'" onmouseout="this.style.boxShadow=''">
            <i class="bi {{ $ql['icon'] }} fs-3 mb-2" style="color:{{ $ql['color'] }};"></i>
            <span style="font-size:.75rem;font-weight:600;color:#374151;">{{ $ql['label'] }}</span>
        </a>
    </div>
    @endforeach
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const navy = '#004161', lime = '#99CC33', gray = '#e5e7eb';
const fontDef = { family: "'Inter', sans-serif", size: 11 };

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: @json($revenueChart->pluck('label')),
        datasets:[{ label:'Revenue ($)', data: @json($revenueChart->pluck('amount')),
            backgroundColor: navy + 'cc', borderRadius: 6, borderSkipped: false }]
    },
    options:{ plugins:{legend:{display:false}}, scales:{
        x:{ticks:{font:fontDef},grid:{display:false}},
        y:{ticks:{font:fontDef, callback:v=>'$'+v.toLocaleString()},grid:{color:gray}}
    }}
});

// Status pie
const statusData = @json($statusDist);
const statusColors = { active:'#10b981', trial:'#f59e0b', expired:'#ef4444', cancelled:'#9ca3af', pending_payment:'#6366f1' };
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase()+s.slice(1).replace('_',' ')),
        datasets:[{ data: Object.values(statusData),
            backgroundColor: Object.keys(statusData).map(s => statusColors[s] ?? '#9ca3af'),
            borderWidth: 2, borderColor: '#fff' }]
    },
    options:{ cutout:'68%', plugins:{ legend:{ position:'bottom', labels:{ font:fontDef, boxWidth:10 } } } }
});

// Company growth line
new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: @json($companyGrowth->pluck('label')),
        datasets:[{ label:'New Companies', data: @json($companyGrowth->pluck('count')),
            borderColor: lime, backgroundColor: lime+'22', fill:true, tension:.4,
            pointBackgroundColor: lime, pointRadius:4 }]
    },
    options:{ plugins:{legend:{display:false}}, scales:{
        x:{ticks:{font:fontDef},grid:{display:false}},
        y:{ticks:{font:fontDef,stepSize:1},grid:{color:gray}}
    }}
});

// Plan bar
new Chart(document.getElementById('planChart'), {
    type: 'bar',
    data: {
        labels: @json($planDist->pluck('name')),
        datasets:[{ label:'Active', data: @json($planDist->pluck('subscriptions_count')),
            backgroundColor: [navy+'dd','#6366f1cc','#10b981cc','#f59e0bcc'],
            borderRadius:6, borderSkipped:false }]
    },
    options:{ plugins:{legend:{display:false}}, scales:{
        x:{ticks:{font:fontDef},grid:{display:false}},
        y:{ticks:{font:fontDef,stepSize:1},grid:{color:gray}}
    }}
});
</script>
@endpush

@endsection
