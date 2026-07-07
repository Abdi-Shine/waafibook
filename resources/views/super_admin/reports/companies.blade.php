@extends('super_admin.layouts.master')
@section('page_title', 'Reports — Companies')
@section('content')

<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Company Report</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">All registered companies, their status, plans, and growth trends.</p>
    </div>
    <a href="?{{ http_build_query(array_merge(request()->query(), ['export'=>'csv'])) }}"
       class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i> Export CSV</a>
</div>

@include('super_admin.reports._subnav')


{{-- Filters --}}
<form method="GET" class="sa-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
    <div>
        <label class="form-label fw-semibold small mb-1">Search</label>
        <input type="text" name="search" class="form-control form-control-sm" style="min-width:200px;"
               placeholder="Company name, email, country…" value="{{ request('search') }}">
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
        <button type="submit" class="btn btn-sm text-white" style="background:var(--primary);">
            <i class="bi bi-funnel me-1"></i> Filter
        </button>
        <a href="{{ route('host.reports.companies') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="sa-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
            <thead style="background:rgba(0,65,97,.04);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;">
                <tr>
                    <th class="ps-4">#</th>
                    <th>Company</th>
                    <th>Owner / Email</th>
                    <th>Country</th>
                    <th>Plan</th>
                    <th class="text-center">Users</th>
                    <th class="text-center">Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $i => $c)
                @php
                    $plan   = $c->subscription?->plan?->name ?? '—';
                    $status = $c->status ?? 'active';
                    $sc = ['active'=>'#10b981','suspended'=>'#ef4444'][$status] ?? '#9ca3af';
                    $owner  = $c->users()->orderBy('id')->first();
                @endphp
                <tr>
                    <td class="ps-4 text-muted">{{ $companies->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-semibold">{{ $c->name }}</div>
                        <div class="text-muted" style="font-size:.75rem;">{{ $c->phone }}</div>
                    </td>
                    <td>
                        <div>{{ $owner?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.75rem;">{{ $c->email }}</div>
                    </td>
                    <td class="text-muted">{{ $c->country ?: '—' }}</td>
                    <td>
                        @if($plan !== '—')
                        <span class="badge rounded-pill" style="background:#004161;color:#fff;font-size:.7rem;">{{ $plan }}</span>
                        @else <span class="text-muted">No Plan</span> @endif
                    </td>
                    <td class="text-center fw-bold">{{ $c->user_count }}</td>
                    <td class="text-center">
                        <span style="background:{{ $sc }}18;color:{{ $sc }};padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td class="text-muted" style="white-space:nowrap;font-size:.8rem;">{{ $c->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">No companies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())
    <div class="p-3 border-top">{{ $companies->links() }}</div>
    @endif
</div>


@endsection
