@extends('super_admin.layouts.master')
@section('page_title', 'Reports — Revenue')
@section('content')

<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Revenue Report</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Payment analytics, monthly trends, and breakdown by plan and payment method.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($years as $y)
                <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="?{{ http_build_query(array_merge(request()->query(), ['export'=>'csv'])) }}"
           class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export CSV</a>
    </div>
</div>

@include('super_admin.reports._subnav')

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    @php $summaryCards = [
        ['label'=>'All-Time Revenue',    'value'=>'$'.number_format($summary['total_all_time'],2), 'icon'=>'bi-trophy-fill',    'color'=>'#004161'],
        ['label'=>$year.' Revenue',      'value'=>'$'.number_format($summary['total_year'],2),     'icon'=>'bi-calendar-check', 'color'=>'#6366f1'],
        ['label'=>'This Month Revenue',  'value'=>'$'.number_format($summary['total_month'],2),    'icon'=>'bi-calendar-event', 'color'=>'#10b981'],
        ['label'=>'Pending Payments',    'value'=>'$'.number_format($summary['total_pending'],2),  'icon'=>'bi-hourglass-split','color'=>'#f97316'],
        ['label'=>'Completed Payments',  'value'=>number_format($summary['payment_count']),         'icon'=>'bi-receipt-cutoff', 'color'=>'#8b5cf6'],
    ];
    @endphp
    @foreach($summaryCards as $c)
    <div class="col-6 col-md">
        <div class="sa-card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi {{ $c['icon'] }}" style="color:{{ $c['color'] }};font-size:1.1rem;"></i>
                <span style="font-size:.7rem;color:#6b7280;font-weight:600;">{{ $c['label'] }}</span>
            </div>
            <div style="font-size:1.3rem;font-weight:800;color:#111827;">{{ $c['value'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- Monthly Bar Chart --}}
    <div class="col-lg-8">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-bar-chart me-2"></i>Monthly Revenue — {{ $year }}
            </h6>
            <canvas id="monthlyChart" height="130"></canvas>
        </div>
    </div>

    {{-- Payment Methods --}}
    <div class="col-lg-4">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-credit-card me-2"></i>By Payment Method
            </h6>
            @foreach($byMethod as $m)
            @php $pct = $summary['total_all_time'] > 0 ? round($m->total / $summary['total_all_time'] * 100) : 0; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold text-capitalize">{{ str_replace('_',' ',$m->payment_method ?? 'Unknown') }}</span>
                    <span class="text-muted">${{ number_format($m->total,0) }} ({{ $m->count }})</span>
                </div>
                <div style="height:6px;background:#e5e7eb;border-radius:3px;">
                    <div style="height:6px;background:#004161;border-radius:3px;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Revenue by Plan --}}
<div class="sa-card p-4 mb-4">
    <h6 class="fw-black mb-4 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
        <i class="bi bi-tags me-2"></i>Revenue by Subscription Plan
    </h6>
    <div class="row g-3">
        @foreach($byPlan as $p)
        @php
            $pct = $summary['total_all_time'] > 0 ? round(($p->revenue ?? 0) / $summary['total_all_time'] * 100) : 0;
            $colors = ['#004161','#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6'];
            $col = $colors[$loop->index % count($colors)];
        @endphp
        <div class="col-md-6 col-lg-3">
            <div class="p-3 rounded-3" style="background:{{ $col }}0a;border:1px solid {{ $col }}22;">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:{{ $col }};margin-bottom:.5rem;">{{ $p->name }}</div>
                <div style="font-size:1.5rem;font-weight:800;color:#111827;">${{ number_format($p->revenue ?? 0, 0) }}</div>
                <div style="height:4px;background:{{ $col }}22;border-radius:2px;margin-top:.5rem;">
                    <div style="height:4px;background:{{ $col }};border-radius:2px;width:{{ $pct }}%;"></div>
                </div>
                <div style="font-size:.72rem;color:#6b7280;margin-top:.3rem;">{{ $pct }}% of total</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Recent Payments Table --}}
<div class="sa-card p-0">
    <div class="p-4 pb-2">
        <h6 class="fw-black mb-0 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
            <i class="bi bi-receipt me-2"></i>Recent Payments (Last 50)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
            <thead style="background:rgba(0,65,97,.04);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">
                <tr>
                    <th class="ps-4">Company</th><th>Plan</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $p)
                @php
                    $stColor = ['completed'=>'#10b981','pending'=>'#f59e0b','failed'=>'#ef4444'][$p->status] ?? '#9ca3af';
                @endphp
                <tr>
                    <td class="ps-4 fw-semibold">{{ $p->subscription?->company?->name ?? '—' }}</td>
                    <td class="text-muted">{{ $p->subscription?->plan?->name ?? '—' }}</td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;">${{ number_format($p->amount,2) }}</td>
                    <td class="text-muted text-capitalize">{{ str_replace('_',' ',$p->payment_method ?? '—') }}</td>
                    <td class="text-muted" style="font-size:.78rem;">{{ $p->transaction_id ?? '—' }}</td>
                    <td class="text-muted" style="font-size:.8rem;white-space:nowrap;">{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d M Y') : '—' }}</td>
                    <td class="text-center">
                        <span style="background:{{ $stColor }}18;color:{{ $stColor }};padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                            {{ ucfirst($p->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthly = @json($monthly);
new Chart(document.getElementById('monthlyChart'), {
    type:'bar',
    data:{
        labels: monthly.map(m => m.label),
        datasets:[
            { label:'Completed ($)', data: monthly.map(m=>m.completed), backgroundColor:'#004161cc', borderRadius:5, borderSkipped:false },
            { label:'Pending ($)',   data: monthly.map(m=>m.pending),   backgroundColor:'#f59e0b88', borderRadius:5, borderSkipped:false },
        ]
    },
    options:{ plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, boxWidth:10 } } }, scales:{
        x:{ticks:{font:{size:11}},grid:{display:false}},
        y:{ticks:{font:{size:11}, callback:v=>'$'+v.toLocaleString()},grid:{color:'#e5e7eb'}},
        stacked:false
    }}
});
</script>
@endpush

@endsection
