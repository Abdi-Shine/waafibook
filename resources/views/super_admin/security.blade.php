@extends('super_admin.layouts.master')
@section('page_title', 'Security & Audit Log')
@section('content')

</div>

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Security &amp; Audit Log</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Every action taken on the platform, and who's currently signed in.</p>
    </div>
    <a href="{{ route('host.security.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-outline-primary">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<div class="sa-card">
    <div class="p-3 border-bottom" style="background:#fafafa;">
        <form method="GET" class="d-flex gap-2 flex-wrap m-0">
            <select name="module" class="form-select" style="max-width:180px;" onchange="this.form.submit()">
                <option value="">All Action Types</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="max-width:160px;">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="max-width:160px;">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->anyFilled(['module','from','to']))
                <a href="{{ route('host.security') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="sa-card-head"><h6>Full Audit Log</h6></div>
    <div class="table-responsive">
    <table class="sa-table">
        <thead>
            <tr><th>Timestamp</th><th>Module</th><th>Action</th><th>Description</th><th>User</th><th>IP Address</th></tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-muted" style="font-size:.8rem;">{{ $log->created_at->format('d M Y, H:i') }}</td>
                <td><span class="sa-badge sa-badge-blue">{{ $log->module }}</span></td>
                <td>{{ $log->action }}</td>
                <td class="fw-semibold">{{ $log->description }}</td>
                <td>{{ $log->user->name ?? '—' }}</td>
                <td class="text-muted" style="font-size:.8rem;">{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">No matching log entries.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($logs->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">{{ $logs->links() }}</div>
    @endif
</div>

<div class="sa-card">
    <div class="sa-card-head"><h6>Active Sessions</h6></div>
    <div class="table-responsive">
    <table class="sa-table">
        <thead>
            <tr><th>User</th><th>IP Address</th><th>Last Activity</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($sessions as $s)
            <tr>
                <td class="fw-semibold">{{ $s->name ?? 'Guest' }} <span class="text-muted">{{ $s->email ? "($s->email)" : '' }}</span></td>
                <td class="text-muted" style="font-size:.8rem;">{{ $s->ip_address ?? '—' }}</td>
                <td class="text-muted" style="font-size:.8rem;">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
                <td>
                    <form method="POST" action="{{ route('host.security.force-logout', $s->id) }}"
                          onsubmit="return confirm('End this session immediately?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Force Logout"><i class="bi bi-power"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center py-5 text-muted">No active sessions.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@endsection
