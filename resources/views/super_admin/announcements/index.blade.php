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
    <h4 class="fw-bold mb-1">Announcements &amp; Notifications</h4>
    <p class="text-muted mb-0">Send a message to every tenant, or target a specific company.</p>
</div>

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
                <div class="col-md-4">
                    <label class="form-label fw-bold">Target</label>
                    <select name="target" id="annTarget" class="form-select" onchange="document.getElementById('annCompanyWrap').classList.toggle('d-none', this.value !== 'specific')">
                        <option value="All">All Companies</option>
                        <option value="specific">Specific Company…</option>
                    </select>
                </div>
                <div class="col-md-4 d-none" id="annCompanyWrap">
                    <label class="form-label fw-bold">Company</label>
                    <select name="company_id" class="form-select">
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="Info">Info</option>
                        <option value="Warning">Warning</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="submit" name="submit_as" value="Draft" class="btn btn-outline-primary"><i class="bi bi-save2"></i> Save as Draft</button>
                <button type="submit" name="submit_as" value="Sent" class="btn btn-primary"><i class="bi bi-send"></i> Send Now</button>
            </div>
        </form>
    </div>
</div>

<div class="sa-card">
    <div class="sa-card-head"><h6>Past Announcements</h6></div>
    <table class="sa-table">
        <thead>
            <tr><th>Title</th><th>Target</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($announcements as $a)
            <tr>
                <td class="fw-semibold">{{ $a->title }}</td>
                <td class="text-muted">{{ $a->targetCompany->name ?? 'All Companies' }}</td>
                <td><span class="sa-badge {{ $a->priority === 'Critical' ? 'sa-badge-red' : ($a->priority === 'Warning' ? 'sa-badge-yellow' : 'sa-badge-blue') }}">{{ $a->priority }}</span></td>
                <td><span class="sa-badge {{ $a->status === 'Sent' ? 'sa-badge-green' : 'sa-badge-gray' }}">{{ $a->status }}</span></td>
                <td class="text-muted">{{ $a->created_at->format('d M Y') }}</td>
                <td>
                    @if($a->status === 'Draft')
                    <form method="POST" action="{{ route('host.announcements.send', $a->id) }}" onsubmit="return confirm('Send this announcement now?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Send Now"><i class="bi bi-send"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-5 text-muted">No announcements yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($announcements->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">{{ $announcements->links() }}</div>
    @endif
</div>

@endsection
