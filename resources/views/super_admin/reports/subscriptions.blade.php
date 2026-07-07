@extends('super_admin.layouts.master')
@section('page_title', 'Reports — Subscriptions')
@section('content')

<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Subscription Report</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Status distribution, plan breakdown, conversion rates, and expiry alerts.</p>
    </div>
    <a href="?{{ http_build_query(array_merge(request()->query(), ['export'=>'csv'])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export CSV</a>
</div>

@include('super_admin.reports._subnav')

{{-- KPIs --}}
<div class="row g-3 mb-4">
    @php
        $statusColors = ['active'=>'#10b981','trial'=>'#f59e0b','expired'=>'#ef4444','cancelled'=>'#9ca3af','pending_payment'=>'#6366f1'];
    @endphp
    @foreach($statusDist as $st => $cnt)
    <div class="col-6 col-md-2">
        <div class="sa-card p-3 text-center">
            <div style="font-size:1.6rem;font-weight:800;color:{{ $statusColors[$st] ?? '#6b7280' }};line-height:1;">{{ $cnt }}</div>
            <div style="font-size:.7rem;color:#6b7280;font-weight:600;text-transform:capitalize;margin-top:.2rem;">{{ str_replace('_',' ',$st) }}</div>
        </div>
    </div>
    @endforeach
    <div class="col-6 col-md-2">
        <div class="sa-card p-3 text-center">
            <div style="font-size:1.6rem;font-weight:800;color:#004161;line-height:1;">{{ $conversionRate }}%</div>
            <div style="font-size:.7rem;color:#6b7280;font-weight:600;margin-top:.2rem;">Conversion Rate</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Status Pie --}}
    <div class="col-lg-4">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-pie-chart me-2"></i>Status Distribution
            </h6>
            <canvas id="statusChart" height="180"></canvas>
        </div>
    </div>

    {{-- Plan breakdown --}}
    <div class="col-lg-8">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-bar-chart me-2"></i>Subscriptions by Plan
            </h6>
            <canvas id="planChart" height="140"></canvas>
        </div>
    </div>
</div>

{{-- Expiring Soon --}}
@if($expiringNext30->count())
<div class="sa-card p-0 mb-4">
    <div class="p-3 border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;"></i>
        <h6 class="fw-black mb-0 text-uppercase" style="color:#b45309;font-size:.75rem;letter-spacing:.08em;">
            Expiring in Next 30 Days ({{ $expiringNext30->count() }})
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
            <thead style="background:rgba(245,158,11,.06);font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#92400e;">
                <tr>
                    <th class="ps-4">Company</th><th>Plan</th><th>Status</th><th>Expiry</th><th>Days Left</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expiringNext30 as $s)
                @php $days = now()->diffInDays(\Carbon\Carbon::parse($s->expiry_date), false); @endphp
                <tr>
                    <td class="ps-4 fw-semibold">{{ $s->company?->name }}</td>
                    <td>{{ $s->plan?->name ?? '—' }}</td>
                    <td><span style="background:{{ $statusColors[$s->status] ?? '#6b7280' }}18;color:{{ $statusColors[$s->status] ?? '#6b7280' }};padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;">{{ ucfirst($s->status) }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($s->expiry_date)->format('d M Y') }}</td>
                    <td><span style="font-weight:700;color:{{ $days <= 3 ? '#ef4444' : ($days <= 7 ? '#f97316' : '#f59e0b') }};">{{ $days }}d</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Filters + Table --}}
<form method="GET" class="sa-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label fw-semibold small mb-1">Search Company</label>
        <input type="text" name="search" class="form-control form-control-sm" style="min-width:200px;"
               placeholder="Company name…" value="{{ request('search') }}">
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach(['active','trial','expired','cancelled','pending_payment'] as $st)
            <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Plan</label>
        <select name="plan_id" class="form-select form-select-sm">
            <option value="">All Plans</option>
            @foreach($plans as $p)
            <option value="{{ $p->id }}" {{ request('plan_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm text-white" style="background:var(--primary);"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="{{ route('host.reports.subscriptions') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="sa-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
            <thead style="background:rgba(0,65,97,.04);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">
                <tr>
                    <th class="ps-4">#</th><th>Company</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Method</th><th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subs as $i => $s)
                @php $sc = $statusColors[$s->status] ?? '#9ca3af'; @endphp
                <tr>
                    <td class="ps-4 text-muted">{{ $subs->firstItem() + $i }}</td>
                    <td class="fw-semibold">{{ $s->company?->name ?? '—' }}</td>
                    <td>{{ $s->plan?->name ?? '—' }}</td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $s->start_date ? \Carbon\Carbon::parse($s->start_date)->format('d M Y') : '—' }}</td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $s->expiry_date ? \Carbon\Carbon::parse($s->expiry_date)->format('d M Y') : '—' }}</td>
                    <td class="text-muted text-capitalize">{{ str_replace('_',' ',$s->payment_method ?? '—') }}</td>
                    <td class="text-center">
                        <span style="background:{{ $sc }}18;color:{{ $sc }};padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                            {{ ucfirst(str_replace('_',' ',$s->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">No subscriptions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subs->hasPages())<div class="p-3 border-top">{{ $subs->links() }}</div>@endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const statusData   = @json($statusDist);
const statusColors = { active:'#10b981', trial:'#f59e0b', expired:'#ef4444', cancelled:'#9ca3af', pending_payment:'#6366f1' };
new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data:{ labels: Object.keys(statusData).map(s=>s.charAt(0).toUpperCase()+s.slice(1).replace('_',' ')),
        datasets:[{ data: Object.values(statusData),
            backgroundColor: Object.keys(statusData).map(s=>statusColors[s]??'#9ca3af'),
            borderWidth:2, borderColor:'#fff' }] },
    options:{ cutout:'65%', plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, boxWidth:10 } } } }
});
new Chart(document.getElementById('planChart'), {
    type:'bar',
    data:{
        labels: @json($planDist->pluck('name')),
        datasets:[
            { label:'Active',  data: @json($planDist->pluck('active_count')),  backgroundColor:'#10b981cc', borderRadius:4, borderSkipped:false },
            { label:'Trial',   data: @json($planDist->pluck('trial_count')),   backgroundColor:'#f59e0bcc', borderRadius:4, borderSkipped:false },
            { label:'Expired', data: @json($planDist->pluck('expired_count')), backgroundColor:'#ef4444cc', borderRadius:4, borderSkipped:false },
        ]
    },
    options:{ plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, boxWidth:10 } } }, scales:{
        x:{ticks:{font:{size:11}},grid:{display:false}},
        y:{ticks:{font:{size:11},stepSize:1},grid:{color:'#e5e7eb'}}
    }}
});
</script>
@endpush

@endsection
