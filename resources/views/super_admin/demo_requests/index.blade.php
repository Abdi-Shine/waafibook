@extends('super_admin.layouts.master')
@section('page_title', 'Demo Requests')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Demo Requests</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Manage incoming demo scheduling requests</p>
    </div>
    @if($pendingCount > 0)
        <span class="sa-badge sa-badge-yellow" style="font-size:.8rem;padding:.4rem .85rem;">
            <i class="bi bi-clock me-1"></i>{{ $pendingCount }} Pending
        </span>
    @endif
</div>

<div class="sa-card">
    <div class="table-responsive">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Company</th>
                    <th>Preferred Date</th>
                    <th>Industry</th>
                    <th>Areas of Interest</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                @php
                    $statusBadge = match($req->status) {
                        'confirmed'  => 'sa-badge-blue',
                        'completed'  => 'sa-badge-green',
                        'cancelled'  => 'sa-badge-gray',
                        default      => 'sa-badge-yellow', // pending
                    };
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;color:#111827;">
                            {{ $req->first_name }} {{ $req->last_name }}
                        </div>
                        <div style="font-size:.75rem;color:#9ca3af;">{{ $req->email }}</div>
                        <div style="font-size:.75rem;color:#9ca3af;">{{ $req->phone }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;color:#111827;">{{ $req->company_name }}</div>
                        <div style="font-size:.75rem;color:#9ca3af;">
                            {{ $req->job_title }}@if($req->country) &middot; {{ $req->country }}@endif
                        </div>
                        @if($req->company_size)
                            <div style="font-size:.75rem;color:#9ca3af;">{{ $req->company_size }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:.85rem;font-weight:600;color:#374151;">
                            {{ \Carbon\Carbon::parse($req->preferred_date)->format('d M, Y') }}
                        </div>
                        @if($req->preferred_time)
                            <div style="font-size:.75rem;color:#9ca3af;">{{ $req->preferred_time }}</div>
                        @endif
                    </td>
                    <td style="font-size:.85rem;color:#374151;">{{ $req->industry ?? '—' }}</td>
                    <td>
                        @if($req->areas_of_interest)
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($req->areas_of_interest as $area)
                                    <span class="sa-badge sa-badge-blue" style="font-size:.7rem;">{{ $area }}</span>
                                @endforeach
                            </div>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="sa-badge {{ $statusBadge }}">{{ ucfirst($req->status) }}</span>
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    style="font-size:.78rem;" data-bs-toggle="dropdown">
                                Update
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @foreach(['pending', 'confirmed', 'completed', 'cancelled'] as $status)
                                    @if($status !== $req->status)
                                    <li>
                                        <button class="dropdown-item" style="font-size:.83rem;"
                                                onclick="updateStatus({{ $req->id }}, '{{ $status }}')">
                                            {{ ucfirst($status) }}
                                        </button>
                                    </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @if($req->notes)
                        <button class="btn btn-sm btn-outline-secondary mt-1"
                                style="font-size:.78rem;"
                                data-bs-toggle="tooltip" title="{{ $req->notes }}">
                            <i class="bi bi-chat-left-text"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color:#9ca3af;font-size:.85rem;">
                        <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem;opacity:.2;"></i>
                        No demo requests yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">
        {{ $requests->links() }}
    </div>
    @endif
</div>

@push('js')
<script>
function updateStatus(id, status) {
    fetch(`/host/demo-requests/${id}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status }),
    })
    .then(r => r.json())
    .then(() => location.reload())
    .catch(() => alert('Failed to update status.'));
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
});
</script>
@endpush

@endsection
