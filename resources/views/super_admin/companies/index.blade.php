@extends('super_admin.layouts.master')
@section('page_title', 'Companies')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 style="font-weight:800;color:#111827;margin:0;">Companies</h4>
            <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">
                {{ $companies->total() }} registered {{ Str::plural('tenant', $companies->total()) }} on the platform
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('host.companies') }}" class="d-flex gap-2 flex-wrap mb-3">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" style="max-width:260px;" placeholder="Search by name or email...">
        <select name="plan" class="form-select" style="max-width:160px;" onchange="this.form.submit()">
            <option value="">All Plans</option>
            @foreach($plans as $p)
                <option value="{{ $p }}" {{ request('plan') === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select" style="max-width:160px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>

        <form method="POST" action="{{ route('host.companies.bulk') }}" id="bulkForm" class="d-flex gap-2 ms-auto">
            @csrf
            <select name="action" class="form-select" style="max-width:200px;">
                <option value="">Bulk Action…</option>
                <option value="suspend">Suspend Selected</option>
                <option value="reactivate">Reactivate Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" class="btn btn-outline-primary" onclick="return confirmBulk(event)">Apply</button>
        </form>
    </form>

    <div class="sa-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Subscription</th>
                        <th>Expiry</th>
                        <th>Country</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    <tr>
                        <td><input type="checkbox" class="company-check" name="ids[]" value="{{ $company->id }}" form="bulkForm"></td>
                        <td style="color:#9ca3af;font-size:.78rem;">{{ $companies->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($company->logo)
                                    <img src="{{ asset($company->logo) }}" style="width:34px;height:34px;border-radius:8px;object-fit:contain;background:#f9fafb;padding:3px;">
                                @else
                                    <div style="width:34px;height:34px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;color:#1d4ed8;flex-shrink:0;">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;font-size:.85rem;color:#111827;">{{ $company->name }}</div>
                                    @if($company->email)
                                    <div style="font-size:.72rem;color:#9ca3af;">{{ $company->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($company->subscription?->plan)
                                <span class="sa-badge sa-badge-blue">{{ $company->subscription->plan->name }}</span>
                            @else
                                <span class="sa-badge sa-badge-gray">—</span>
                            @endif
                        </td>
                        <td>
                            @php $sub = $company->subscription; @endphp
                            @if(!$sub)
                                <span class="sa-badge sa-badge-gray">None</span>
                            @elseif($sub->status === 'active')
                                <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                            @elseif($sub->status === 'expired')
                                <span class="sa-badge sa-badge-red"><i class="bi bi-x-circle"></i> Expired</span>
                            @else
                                <span class="sa-badge sa-badge-yellow">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:#6b7280;">
                            @if($company->subscription?->expiry_date)
                                @php
                                    $expiry   = \Carbon\Carbon::parse($company->subscription->expiry_date);
                                    $warning  = $expiry->isFuture() && $expiry->diffInDays(now()) <= 7;
                                @endphp
                                <span style="{{ $warning ? 'color:#d97706;font-weight:600;' : '' }}">
                                    {{ $expiry->format('d M Y') }}
                                    @if($warning) <i class="bi bi-exclamation-circle"></i>@endif
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:.82rem;">{{ $company->country ?? '—' }}</td>
                        <td style="font-size:.8rem;color:#6b7280;">{{ $company->created_at->format('d M Y') }}</td>
                        <td>
                            @if($company->status === 'suspended')
                                <span class="sa-badge sa-badge-red"><i class="bi bi-slash-circle"></i> Suspended</span>
                            @else
                                <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                            @endif
                        </td>
                        <td>
                            <div class="sa-row-actions">
                                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="View"
                                        onclick="viewCompany({{ $company->id }})"><i class="bi bi-eye"></i></button>
                                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="Edit"
                                        onclick="editCompany({{ $company->id }}, '{{ addslashes($company->name) }}', '{{ addslashes($company->email) }}', '{{ addslashes($company->phone) }}')"><i class="bi bi-pencil"></i></button>

                                <form method="POST" action="{{ route('host.companies.toggle-status', $company->id) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $company->status === 'suspended' ? 'Reactivate' : 'Suspend' }} {{ addslashes($company->name) }}?');">
                                    @csrf
                                    @method('PATCH')
                                    @if($company->status === 'suspended')
                                        <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Reactivate"><i class="bi bi-play-circle"></i></button>
                                    @else
                                        <button type="submit" class="sa-btn-icon warn" data-bs-toggle="tooltip" title="Suspend"><i class="bi bi-pause-circle"></i></button>
                                    @endif
                                </form>

                                <button type="button" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Delete"
                                        onclick="deleteCompany({{ $company->id }}, '{{ addslashes($company->name) }}')"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5" style="color:#9ca3af;">No companies match your filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div class="px-4 py-3 border-top" style="background:#fafafa;">
            {{ $companies->links() }}
        </div>
        @endif
    </div>

    {{-- View Company Modal --}}
    <div class="modal fade" id="viewCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Company Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="viewCompanyBody">
                    <div class="text-center py-4 text-muted">Loading…</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Company Modal --}}
    <div class="modal fade" id="editCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="editCompanyForm">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Edit Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label fw-bold">Company Name</label><input type="text" name="name" id="editName" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Owner Email</label><input type="email" name="email" id="editEmail" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Phone</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Company Form (two-step confirm via JS) --}}
    <form method="POST" id="deleteCompanyForm" class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('js')
<script>
function viewCompany(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewCompanyModal'));
    document.getElementById('viewCompanyBody').innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
    modal.show();
    fetch('/host/companies/' + id + '/show').then(r => r.json()).then(c => {
        document.getElementById('viewCompanyBody').innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Company Info</h6>
                        <div class="d-flex justify-content-between small py-1 border-bottom"><span class="text-muted">Name</span><span>${c.name}</span></div>
                        <div class="d-flex justify-content-between small py-1 border-bottom"><span class="text-muted">Country</span><span>${c.country ?? '—'}</span></div>
                        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Joined</span><span>${c.joined}</span></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Owner / Plan</h6>
                        <div class="d-flex justify-content-between small py-1 border-bottom"><span class="text-muted">Email</span><span>${c.email ?? '—'}</span></div>
                        <div class="d-flex justify-content-between small py-1 border-bottom"><span class="text-muted">Phone</span><span>${c.phone ?? '—'}</span></div>
                        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Plan</span><span class="sa-badge sa-badge-blue">${c.plan}</span></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row g-2 text-center">
                        <div class="col-4"><div class="border rounded-3 p-3"><div class="fw-bold fs-5">${c.users_count}</div><div class="small text-muted">Users</div></div></div>
                        <div class="col-4"><div class="border rounded-3 p-3"><div class="fw-bold fs-5">${c.products_count}</div><div class="small text-muted">Products</div></div></div>
                        <div class="col-4"><div class="border rounded-3 p-3"><div class="fw-bold fs-5">${c.sales_count}</div><div class="small text-muted">Sales</div></div></div>
                    </div>
                </div>
            </div>
        `;
    });
}

function editCompany(id, name, email, phone) {
    document.getElementById('editCompanyForm').action = '/host/companies/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
}

function deleteCompany(id, name) {
    if (!confirm('Delete ' + name + '? This will permanently remove the company and all of its data — products, sales, and users. This cannot be undone.')) return;
    const typed = prompt('Type "' + name + '" to confirm permanent deletion:');
    if (typed !== name) { if (typed !== null) alert('Company name did not match. Deletion cancelled.'); return; }
    const form = document.getElementById('deleteCompanyForm');
    form.action = '/host/companies/' + id;
    form.submit();
}

function confirmBulk(e) {
    const action = document.querySelector('#bulkForm select[name="action"]').value;
    const checked = document.querySelectorAll('.company-check:checked').length;
    if (!action) { alert('Choose a bulk action first.'); e.preventDefault(); return false; }
    if (!checked) { alert('Select at least one company.'); e.preventDefault(); return false; }
    if (!confirm(action.charAt(0).toUpperCase() + action.slice(1) + ' ' + checked + ' selected companies?')) { e.preventDefault(); return false; }
    return true;
}

document.getElementById('selectAll').addEventListener('change', e => {
    document.querySelectorAll('.company-check').forEach(cb => cb.checked = e.target.checked);
});
</script>
@endpush
