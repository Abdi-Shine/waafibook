@extends('super_admin.layouts.master')
@section('page_title', 'Reports — System Activity')
@section('content')

<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">System Activity Report</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Audit log entries, user actions, and platform activity across all companies.</p>
    </div>
    <a href="?{{ http_build_query(array_merge(request()->query(), ['export'=>'csv'])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export CSV (max 5000)</a>
</div>

@include('super_admin.reports._subnav')

<div class="row g-4 mb-4">
    {{-- Daily Activity Chart --}}
    <div class="col-lg-8">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-activity me-2"></i>Daily Activity — Last 14 Days
            </h6>
            <canvas id="dailyChart" height="120"></canvas>
        </div>
    </div>

    {{-- Top Companies --}}
    <div class="col-lg-4">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-building me-2"></i>Most Active Companies
            </h6>
            @php $maxAct = $topCompanies->max('total') ?: 1; @endphp
            @foreach($topCompanies as $c)
            @php $pct = round($c->total / $maxAct * 100); @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold" style="font-size:.78rem;">{{ Str::limit($c->name,18) }}</span>
                    <span class="text-muted">{{ $c->total }}</span>
                </div>
                <div style="height:4px;background:#e5e7eb;border-radius:2px;">
                    <div style="height:4px;background:#f97316;border-radius:2px;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By Module --}}
    <div class="col-lg-6">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-grid me-2"></i>Actions by Module
            </h6>
            @php $maxMod = $byModule->max() ?: 1; @endphp
            @foreach($byModule as $mod => $cnt)
            @php $pct = round($cnt / $maxMod * 100); @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold" style="font-size:.78rem;">{{ $mod }}</span>
                    <span class="text-muted">{{ number_format($cnt) }}</span>
                </div>
                <div style="height:5px;background:#e5e7eb;border-radius:2px;">
                    <div style="height:5px;background:#004161;border-radius:2px;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By Action --}}
    <div class="col-lg-6">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-lightning me-2"></i>Top Actions
            </h6>
            @php $maxAc = $byAction->max() ?: 1; @endphp
            @foreach($byAction as $ac => $cnt)
            @php $pct = round($cnt / $maxAc * 100); @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold" style="font-size:.78rem;">{{ $ac }}</span>
                    <span class="text-muted">{{ number_format($cnt) }}</span>
                </div>
                <div style="height:5px;background:#e5e7eb;border-radius:2px;">
                    <div style="height:5px;background:#6366f1;border-radius:2px;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="sa-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label fw-semibold small mb-1">Search Description</label>
        <input type="text" name="search" class="form-control form-control-sm" style="min-width:180px;"
               placeholder="Search…" value="{{ request('search') }}">
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Module</label>
        <select name="module" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($modules as $m)
            <option value="{{ $m }}" {{ request('module')===$m?'selected':'' }}>{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Action</label>
        <select name="action" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($actions as $a)
            <option value="{{ $a }}" {{ request('action')===$a?'selected':'' }}>{{ $a }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Company</label>
        <select name="company_id" class="form-select form-select-sm" style="max-width:160px;">
            <option value="">All</option>
            @foreach($companies as $c)
            <option value="{{ $c->id }}" {{ request('company_id')==$c->id?'selected':'' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm text-white" style="background:var(--primary);"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="{{ route('host.reports.activity') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="sa-card p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-black mb-0 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
            Audit Log — {{ number_format($logs->total()) }} records
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
            <thead style="background:rgba(0,65,97,.04);font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">
                <tr>
                    <th class="ps-4">User</th><th>Module</th><th>Action</th><th>Description</th><th>IP</th><th>Country</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $l)
                @php
                    $actionColors = ['LOGIN'=>'#10b981','LOGOUT'=>'#6b7280','CREATE'=>'#6366f1','UPDATE'=>'#f59e0b','DELETE'=>'#ef4444'];
                    $ac = $actionColors[$l->action] ?? '#9ca3af';
                @endphp
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold" style="font-size:.82rem;">{{ $l->user?->name ?? 'System' }}</div>
                    </td>
                    <td><span style="background:#00416112;color:#004161;padding:1px 7px;border-radius:20px;font-size:.7rem;font-weight:600;">{{ $l->module }}</span></td>
                    <td><span style="background:{{ $ac }}18;color:{{ $ac }};padding:1px 7px;border-radius:20px;font-size:.7rem;font-weight:700;">{{ $l->action }}</span></td>
                    <td class="text-muted" style="max-width:260px;font-size:.78rem;">{{ Str::limit($l->description, 70) }}</td>
                    <td class="text-muted" style="font-size:.78rem;white-space:nowrap;">{{ $l->ip_address }}</td>
                    <td class="text-muted" style="font-size:.78rem;">{{ $l->country }}</td>
                    <td class="text-muted" style="font-size:.78rem;white-space:nowrap;">{{ $l->created_at->format('d M Y, H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">No activity records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="p-3 border-top">{{ $logs->links() }}</div>@endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const daily = @json($dailyActivity);
new Chart(document.getElementById('dailyChart'), {
    type:'bar',
    data:{ labels: daily.map(d=>d.label),
        datasets:[{ label:'Actions', data: daily.map(d=>d.count),
            backgroundColor:'#004161bb', borderRadius:4, borderSkipped:false }]
    },
    options:{ plugins:{legend:{display:false}}, scales:{
        x:{ticks:{font:{size:10}},grid:{display:false}},
        y:{ticks:{font:{size:11},stepSize:1},grid:{color:'#e5e7eb'}}
    }}
});
</script>
@endpush

@endsection
