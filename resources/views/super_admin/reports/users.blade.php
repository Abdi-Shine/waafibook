@extends('super_admin.layouts.master')
@section('page_title', 'Reports — Users')
@section('content')

<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">User Report</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">All platform users across all companies — roles, status, and growth trends.</p>
    </div>
    <a href="?{{ http_build_query(array_merge(request()->query(), ['export'=>'csv'])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export CSV</a>
</div>

@include('super_admin.reports._subnav')

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sa-card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#004161;">{{ $users->total() }}</div>
            <div style="font-size:.72rem;color:#6b7280;font-weight:600;margin-top:.2rem;">Total Users</div>
        </div>
    </div>
    @foreach($byStatus as $st => $cnt)
    <div class="col-6 col-md-3">
        <div class="sa-card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:{{ $st==='active'?'#10b981':'#ef4444' }};">{{ $cnt }}</div>
            <div style="font-size:.72rem;color:#6b7280;font-weight:600;text-transform:capitalize;margin-top:.2rem;">{{ $st }} Users</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- Growth Chart --}}
    <div class="col-lg-6">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-graph-up me-2"></i>New Users — Last 6 Months
            </h6>
            <canvas id="growthChart" height="140"></canvas>
        </div>
    </div>

    {{-- Roles Pie --}}
    <div class="col-lg-3">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-person-badge me-2"></i>By Role
            </h6>
            <canvas id="roleChart" height="180"></canvas>
        </div>
    </div>

    {{-- Top Companies --}}
    <div class="col-lg-3">
        <div class="sa-card p-4 h-100">
            <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                <i class="bi bi-building me-2"></i>Most Users
            </h6>
            @php $maxUsers = $topCompanies->max('user_count') ?: 1; @endphp
            @foreach($topCompanies as $c)
            @php $pct = round($c->user_count / $maxUsers * 100); @endphp
            <div class="mb-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold" style="font-size:.78rem;">{{ Str::limit($c->name,18) }}</span>
                    <span class="text-muted">{{ $c->user_count }}</span>
                </div>
                <div style="height:4px;background:#e5e7eb;border-radius:2px;">
                    <div style="height:4px;background:#6366f1;border-radius:2px;width:{{ $pct }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="sa-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label fw-semibold small mb-1">Search</label>
        <input type="text" name="search" class="form-control form-control-sm" style="min-width:200px;"
               placeholder="Name or email…" value="{{ request('search') }}">
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Role</label>
        <select name="role" class="form-select form-select-sm">
            <option value="">All Roles</option>
            @foreach($roles as $r)
            <option value="{{ $r }}" {{ request('role')===$r?'selected':'' }}>{{ $r }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label fw-semibold small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>Suspended</option>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm text-white" style="background:var(--primary);"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="{{ route('host.reports.users') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="sa-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
            <thead style="background:rgba(0,65,97,.04);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">
                <tr>
                    <th class="ps-4">#</th><th>Name</th><th>Email</th><th>Role</th><th>Company</th><th class="text-center">Status</th><th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $u)
                @php $st = $u->status ?? 'active'; $sc = $st==='active'?'#10b981':'#ef4444'; @endphp
                <tr>
                    <td class="ps-4 text-muted">{{ $users->firstItem() + $i }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:50%;background:#004161;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($u->name,0,2)) }}
                            </div>
                            <span class="fw-semibold">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="text-muted">{{ $u->email }}</td>
                    <td>
                        <span style="background:#00416118;color:#004161;padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;">{{ $u->role }}</span>
                    </td>
                    <td class="text-muted">{{ $u->company?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span style="background:{{ $sc }}18;color:{{ $sc }};padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">{{ ucfirst($st) }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;white-space:nowrap;">{{ $u->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="p-3 border-top">{{ $users->links() }}</div>@endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('growthChart'), {
    type:'line',
    data:{ labels: @json($monthlyNew->pluck('label')),
        datasets:[{ label:'New Users', data: @json($monthlyNew->pluck('count')),
            borderColor:'#6366f1', backgroundColor:'#6366f122', fill:true, tension:.4,
            pointBackgroundColor:'#6366f1', pointRadius:4 }]
    },
    options:{ plugins:{legend:{display:false}}, scales:{
        x:{ticks:{font:{size:11}},grid:{display:false}},
        y:{ticks:{font:{size:11},stepSize:1},grid:{color:'#e5e7eb'}}
    }}
});

const roles = @json($byRole);
const rColors = ['#004161','#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'];
new Chart(document.getElementById('roleChart'), {
    type:'doughnut',
    data:{ labels: Object.keys(roles),
        datasets:[{ data: Object.values(roles),
            backgroundColor: Object.keys(roles).map((_,i)=>rColors[i%rColors.length]),
            borderWidth:2, borderColor:'#fff' }] },
    options:{ cutout:'60%', plugins:{ legend:{ position:'bottom', labels:{ font:{size:10}, boxWidth:8 } } } }
});
</script>
@endpush

@endsection
