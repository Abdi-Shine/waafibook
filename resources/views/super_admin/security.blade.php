@extends('super_admin.layouts.master')
@section('page_title', 'Security & Audit Log')
@section('content')

</div>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Security &amp; Audit Log</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Every action taken on the platform, and who's currently signed in.</p>
    </div>
    <a href="{{ route('host.security.export') }}?{{ http_build_query(request()->query()) }}"
       class="btn btn-outline-primary">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
</div>

{{-- Single card with tabbed navigation --}}
<div class="sa-card">

    {{-- Tab bar --}}
    <div class="d-flex border-bottom px-4 pt-3" style="background:#fafafa;gap:.25rem;">
        @php $activeTab = request('tab', 'audit'); @endphp
        <a href="?tab=audit{{ request()->filled('module') ? '&module='.request('module') : '' }}{{ request()->filled('from') ? '&from='.request('from') : '' }}{{ request()->filled('to') ? '&to='.request('to') : '' }}"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;
                  {{ $activeTab === 'audit'
                      ? 'background:white;border-color:#e5e7eb;border-bottom-color:white;color:var(--primary);margin-bottom:-1px;'
                      : 'color:#6b7280;' }}">
            <i class="bi bi-shield-check"></i> Audit Log
            <span style="background:{{ $activeTab==='audit' ? 'var(--primary)' : '#e5e7eb' }};color:{{ $activeTab==='audit' ? '#fff' : '#374151' }};font-size:.6rem;font-weight:800;border-radius:20px;padding:1px 7px;">
                {{ $logs->total() }}
            </span>
        </a>
        <a href="?tab=sessions"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;
                  {{ $activeTab === 'sessions'
                      ? 'background:white;border-color:#e5e7eb;border-bottom-color:white;color:var(--primary);margin-bottom:-1px;'
                      : 'color:#6b7280;' }}">
            <i class="bi bi-display"></i> Active Sessions
            <span style="background:{{ $activeTab==='sessions' ? 'var(--primary)' : '#e5e7eb' }};color:{{ $activeTab==='sessions' ? '#fff' : '#374151' }};font-size:.6rem;font-weight:800;border-radius:20px;padding:1px 7px;">
                {{ $sessions->count() }}
            </span>
        </a>
    </div>

    @if($activeTab === 'audit')
    {{-- ── Audit Log Tab ── --}}
    <div class="p-3 border-bottom d-flex gap-2 flex-wrap align-items-center" style="background:#fafafa;">
        <form method="GET" class="d-flex gap-2 flex-wrap m-0 flex-fill">
            <input type="hidden" name="tab" value="audit">
            <select name="module" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
                <option value="">All Modules</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="max-width:155px;" placeholder="From">
            <input type="date" name="to"   value="{{ request('to') }}"   class="form-control form-control-sm" style="max-width:155px;" placeholder="To">
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->anyFilled(['module','from','to']))
                <a href="?tab=audit" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $actionColor = match($log->action) {
                        'CREATE' => '#10b981', 'UPDATE' => '#3b82f6',
                        'DELETE' => '#ef4444', 'LOGIN'  => '#8b5cf6',
                        default  => '#6b7280',
                    };
                @endphp
                <tr>
                    <td style="font-size:.79rem;color:#6b7280;white-space:nowrap;">
                        {{ $log->created_at->format('d M Y') }}<br>
                        <span style="font-size:.72rem;">{{ $log->created_at->format('H:i') }}</span>
                    </td>
                    <td><span class="sa-badge sa-badge-blue">{{ $log->module }}</span></td>
                    <td>
                        <span style="font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:{{ $actionColor }};">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td style="font-size:.84rem;max-width:320px;">{{ $log->description }}</td>
                    <td style="font-size:.83rem;font-weight:600;">{{ $log->user->name ?? '—' }}</td>
                    <td style="font-size:.79rem;color:#6b7280;">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color:#9ca3af;">
                        <i class="bi bi-shield" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        No matching log entries.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">
        {{ $logs->appends(['tab' => 'audit'])->links() }}
    </div>
    @endif

    @else
    {{-- ── Active Sessions Tab ── --}}
    <div class="table-responsive">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>IP Address</th>
                    <th>Last Activity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                <tr>
                    <td class="fw-semibold" style="font-size:.85rem;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0;">
                                {{ strtoupper(substr($s->name ?? 'G', 0, 2)) }}
                            </div>
                            {{ $s->name ?? 'Guest' }}
                        </div>
                    </td>
                    <td style="font-size:.82rem;color:#6b7280;">{{ $s->email ?? '—' }}</td>
                    <td style="font-size:.82rem;color:#6b7280;">{{ $s->ip_address ?? '—' }}</td>
                    <td style="font-size:.82rem;color:#6b7280;">
                        {{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('host.security.force-logout', $s->id) }}"
                              onsubmit="return confirm('End this session immediately?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Force Logout">
                                <i class="bi bi-power"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color:#9ca3af;">
                        <i class="bi bi-display" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        No active sessions.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

</div>

@endsection
