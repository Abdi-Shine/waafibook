@extends('super_admin.layouts.master')
@section('page_title', 'Announcements')
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="mb-4">
    <h4 style="font-weight:800;color:#111827;margin:0;">Announcements &amp; Notifications</h4>
    <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Send a message to every tenant, or target a specific company.</p>
</div>

{{-- Compose Form --}}
<div class="sa-card">
    <div class="sa-card-head"><h6>Compose Announcement</h6></div>
    <div class="p-4">
        <form method="POST" action="{{ route('host.announcements.store') }}" id="annForm">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Scheduled maintenance this weekend" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Message</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Write the announcement message..." required></textarea>
                </div>
                {{-- One-row: Target | Company | Priority | Start Time | End Time --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold">Target</label>
                    <select name="target" id="annTarget" class="form-select" onchange="document.getElementById('annCompanyWrap').classList.toggle('d-none', this.value !== 'specific')">
                        <option value="All">All Companies</option>
                        <option value="specific">Specific Company…</option>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="annCompanyWrap">
                    <label class="form-label fw-bold">Company</label>
                    <select name="company_id" class="form-select">
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="Info">Info</option>
                        <option value="Warning">Warning</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Start Time</label>
                    <input type="datetime-local" name="start_time" id="ann_start_time" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">End Time <span class="text-muted fw-normal" style="font-size:.75rem;">(optional)</span></label>
                    <input type="datetime-local" name="end_time" class="form-control">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="submit" name="submit_as" value="Draft" class="btn btn-outline-primary"><i class="bi bi-save2"></i> Save as Draft</button>
                <button type="submit" name="submit_as" value="Sent" class="btn btn-primary"><i class="bi bi-send"></i> Send Now</button>
            </div>
        </form>
    </div>
</div>

{{-- Past Announcements --}}
<div class="sa-card">
    <div class="sa-card-head"><h6>Past Announcements</h6></div>
    <table class="sa-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Target</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($announcements as $a)
            <tr>
                <td class="fw-semibold">{{ $a->title }}</td>
                <td class="text-muted">{{ $a->targetCompany->name ?? 'All Companies' }}</td>
                <td><span class="sa-badge {{ $a->priority === 'Critical' ? 'sa-badge-red' : ($a->priority === 'Warning' ? 'sa-badge-yellow' : 'sa-badge-blue') }}">{{ $a->priority }}</span></td>
                <td><span class="sa-badge {{ $a->status === 'Sent' ? 'sa-badge-green' : 'sa-badge-gray' }}">{{ $a->status }}</span></td>
                <td class="text-muted" style="font-size:.8rem;">
                    {{ $a->start_time ? $a->start_time->format('d M Y H:i') : '—' }}
                </td>
                <td class="text-muted" style="font-size:.8rem;">
                    {{ $a->end_time ? $a->end_time->format('d M Y H:i') : '—' }}
                </td>
                <td class="text-muted">{{ $a->created_at->format('d M Y') }}</td>
                <td>
                    <div class="sa-row-actions">
                        {{-- Send (drafts only) --}}
                        @if($a->status === 'Draft')
                        <form method="POST" action="{{ route('host.announcements.send', $a->id) }}" onsubmit="return confirm('Send this announcement now?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Send Now"><i class="bi bi-send"></i></button>
                        </form>
                        @endif

                        {{-- Edit --}}
                        <button type="button" class="sa-btn-icon"
                            style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;"
                            data-bs-toggle="modal" data-bs-target="#editModal{{ $a->id }}"
                            title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>

                        {{-- Delete --}}
                        <form method="POST" action="{{ route('host.announcements.destroy', $a->id) }}"
                              onsubmit="return confirm('Delete this announcement permanently?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-5 text-muted">No announcements yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($announcements->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">{{ $announcements->links() }}</div>
    @endif
</div>

{{-- Edit Modals --}}
@foreach($announcements as $a)
<div class="modal fade" id="editModal{{ $a->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('host.announcements.update', $a->id) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-black">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ $a->title }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Message</label>
                            <textarea name="message" class="form-control" rows="3" required>{{ $a->message }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Target</label>
                            <select name="target" class="form-select edit-target-sel" data-ann="{{ $a->id }}"
                                onchange="document.getElementById('editCoWrap{{ $a->id }}').classList.toggle('d-none', this.value !== 'specific')">
                                <option value="All" {{ is_null($a->target_company_id) ? 'selected' : '' }}>All Companies</option>
                                <option value="specific" {{ !is_null($a->target_company_id) ? 'selected' : '' }}>Specific Company…</option>
                            </select>
                        </div>
                        <div class="col-md-4 {{ is_null($a->target_company_id) ? 'd-none' : '' }}" id="editCoWrap{{ $a->id }}">
                            <label class="form-label fw-bold">Company</label>
                            <select name="company_id" class="form-select">
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" {{ $a->target_company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Info"     {{ $a->priority === 'Info'     ? 'selected' : '' }}>Info</option>
                                <option value="Warning"  {{ $a->priority === 'Warning'  ? 'selected' : '' }}>Warning</option>
                                <option value="Critical" {{ $a->priority === 'Critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Start Time</label>
                            <input type="datetime-local" name="start_time" class="form-control"
                                value="{{ $a->start_time ? $a->start_time->format('Y-m-d\TH:i') : '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">End Time</label>
                            <input type="datetime-local" name="end_time" class="form-control"
                                value="{{ $a->end_time ? $a->end_time->format('Y-m-d\TH:i') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save2 me-1"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
    // Set start_time default to current local datetime
    (function () {
        const el = document.getElementById('ann_start_time');
        if (el && !el.value) {
            const now = new Date();
            now.setSeconds(0, 0);
            const pad = n => String(n).padStart(2, '0');
            el.value = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate())
                     + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        }
    })();
</script>
@endsection
