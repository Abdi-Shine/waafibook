@extends('super_admin.layouts.master')
@section('page_title', 'Security')
@section('content')

</div>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Active Sessions</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Who's currently signed in across the platform.</p>
    </div>
</div>

<div class="sa-card">
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
</div>

@endsection
