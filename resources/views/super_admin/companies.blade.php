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

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 style="font-weight:800;color:#111827;margin:0;">Company Management</h4>
            <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">
                Every tenant registered on the platform — search, filter, and act on any of them.
            </p>
        </div>
        <button type="button" class="btn btn-primary fw-bold" style="background:var(--primary);border-color:var(--primary);" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
            <i class="bi bi-plus-lg"></i> Add Company
        </button>
    </div>

    <div class="sa-card">
        <div class="p-3 border-bottom d-flex gap-2 align-items-center" style="background:#fafafa;">
            {{-- Filter form --}}
            <form method="GET" action="{{ route('host.companies') }}" class="d-flex gap-2 align-items-center flex-fill m-0">
                <div class="position-relative flex-fill" style="min-width:160px;">
                    <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.85rem;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control w-100" style="padding-left:34px;" placeholder="Search by company or owner name...">
                </div>
                <select name="plan" class="form-select flex-fill" style="min-width:120px;" onchange="this.form.submit()">
                    <option value="">All Plans</option>
                    @foreach($plans as $p)
                        <option value="{{ $p }}" {{ request('plan') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select flex-fill" style="min-width:120px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </form>
            {{-- Bulk form --}}
            <form method="POST" action="{{ route('host.companies.bulk') }}" id="bulkForm" class="d-flex gap-2 align-items-center m-0 flex-shrink-0">
                @csrf
                <select name="action" class="form-select" style="min-width:160px;">
                    <option value="">Bulk Action…</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="reactivate">Reactivate Selected</option>
                    <option value="delete">Delete Selected</option>
                    <option value="export">Export Selected as CSV</option>
                </select>
                <button type="submit" class="btn btn-outline-primary" onclick="return confirmBulk(event)">Apply</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Company Name</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    @php
                        $owner = $company->users->first();
                        $displayEmail = $company->admin_email
                            ?? ($owner ? $owner->email : null)
                            ?? $company->email
                            ?? \DB::table('users')->where('company_id', $company->id)->whereNotNull('email')->where('email','!=','')->value('email');
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="company-check" name="ids[]" value="{{ $company->id }}" form="bulkForm"></td>
                        <td style="color:#9ca3af;font-size:.78rem;">{{ $companies->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($company->logo)
                                    <img src="{{ asset($company->logo) }}" style="width:30px;height:30px;border-radius:8px;object-fit:contain;background:#f9fafb;padding:3px;">
                                @else
                                    <div style="width:30px;height:30px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.68rem;color:#1d4ed8;flex-shrink:0;">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </div>
                                @endif
                                <span style="font-weight:700;font-size:.85rem;color:#111827;">{{ $company->name }}</span>
                            </div>
                        </td>
                        <td style="font-size:.85rem;">{{ $owner->name ?? '—' }}</td>
                        <td style="font-size:.82rem;color:#6b7280;">{{ $displayEmail ?? '—' }}</td>
                        <td style="font-size:.82rem;color:#6b7280;">{{ $company->phone ?? '—' }}</td>
                        <td>
                            @if($company->subscription?->plan)
                                <span class="sa-badge sa-badge-blue">{{ $company->subscription->plan->name }}</span>
                            @else
                                <span class="sa-badge sa-badge-gray">—</span>
                            @endif
                        </td>
                        <td>
                            @if($company->status === 'suspended')
                                <span class="sa-badge sa-badge-red"><i class="bi bi-slash-circle"></i> Suspended</span>
                            @elseif($company->status === 'pending')
                                <span class="sa-badge sa-badge-blue"><i class="bi bi-hourglass-split"></i> Pending</span>
                            @else
                                <span class="sa-badge sa-badge-green"><i class="bi bi-check-circle"></i> Active</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:#6b7280;">{{ $company->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="sa-row-actions">
                                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="View"
                                        onclick="viewCompany({{ $company->id }})"><i class="bi bi-eye"></i></button>
                                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="Edit"
                                        onclick="editCompany({{ $company->id }}, '{{ addslashes($company->name) }}', '{{ addslashes($company->email) }}', '{{ addslashes($company->phone) }}', {{ $company->subscription->subscription_plan_id ?? 'null' }})"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="Manage Plan"
                                        onclick="managePlan({{ $company->id }}, '{{ addslashes($company->name) }}', {{ $company->subscription->subscription_plan_id ?? 'null' }})"><i class="bi bi-credit-card"></i></button>

                                <form method="POST" action="{{ route('host.companies.toggle-status', $company->id) }}" class="d-inline toggle-status-form">
                                    @csrf
                                    @method('PATCH')
                                    @if($company->status === 'suspended')
                                        <button type="button" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Reactivate"
                                            onclick="confirmToggle(this.closest('form'), 'Reactivate', '{{ addslashes($company->name) }}')"><i class="bi bi-play-circle"></i></button>
                                    @else
                                        <button type="button" class="sa-btn-icon warn" data-bs-toggle="tooltip" title="Suspend"
                                            onclick="confirmToggle(this.closest('form'), 'Suspend', '{{ addslashes($company->name) }}')"><i class="bi bi-pause-circle"></i></button>
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
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label fw-bold">Owner Email</label><input type="email" name="email" id="editEmail" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label fw-bold">Phone Number</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subscription Plan</label>
                            <select name="subscription_plan_id" id="editPlan" class="form-select">
                                <option value="">None</option>
                                @foreach($allPlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price, 0) }}/{{ $plan->billing_cycle }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Moving a company off a Free Trial plan here marks its subscription Active immediately — use this once payment has been confirmed.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Plan Modal --}}
    <div class="modal fade" id="managePlanModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="managePlanForm">
                @csrf
                @method('PATCH')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Change Plan — <span id="managePlanCompanyName"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Plan selection --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subscription Plan *</label>
                            <select name="subscription_plan_id" id="managePlanSelect" class="form-select" required onchange="updatePlanPrice(this)">
                                @foreach($allPlans as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">
                                        {{ $plan->name }} — ${{ number_format($plan->price, 0) }}/{{ $plan->billing_cycle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-3">
                        <p class="fw-bold mb-3" style="font-size:.8rem;color:var(--primary);text-transform:uppercase;letter-spacing:.05em;">
                            <i class="bi bi-cash-coin me-1"></i> Record Payment (optional)
                        </p>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Amount ($)</label>
                                <input type="number" step="0.01" min="0" name="payment_amount" id="managePlanAmount"
                                       class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Date</label>
                                <input type="date" name="payment_date" id="managePlanDate"
                                       class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="EVC Plus">EVC Plus</option>
                                    <option value="Card">Card</option>
                                    <option value="Manual">Manual / Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="Optional ref.">
                            </div>
                        </div>
                        <div class="form-text mt-1">
                            Leave Amount blank to only change the plan without recording a payment.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Plan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Company Modal --}}
    <div class="modal fade" id="addCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('host.companies.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Add Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label fw-bold">Company Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Owner Full Name *</label><input type="text" name="owner_name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Owner Email *</label><input type="email" name="owner_email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="mb-3"><label class="form-label fw-bold">Country</label><input type="text" name="country" class="form-control" value="Somalia"></div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subscription Plan</label>
                            <select name="subscription_plan_id" class="form-select">
                                <option value="">None</option>
                                @foreach($allPlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price, 0) }}/{{ $plan->billing_cycle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Company</button>
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
    fetch('/super_admin/companies/' + id + '/show').then(r => r.json()).then(c => {
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

function editCompany(id, name, email, phone, currentPlanId) {
    document.getElementById('editCompanyForm').action = '/super_admin/companies/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editPlan').value = currentPlanId ?? '';
    new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
}

function managePlan(id, name, currentPlanId) {
    document.getElementById('managePlanForm').action = '/super_admin/companies/' + id + '/plan';
    document.getElementById('managePlanCompanyName').textContent = name;
    const sel = document.getElementById('managePlanSelect');
    if (currentPlanId) sel.value = currentPlanId;
    // Reset payment fields each time the modal opens
    document.getElementById('managePlanAmount').value = '';
    document.getElementById('managePlanDate').value = '{{ now()->toDateString() }}';
    // Pre-fill amount with selected plan's price
    updatePlanPrice(sel);
    new bootstrap.Modal(document.getElementById('managePlanModal')).show();
}

function updatePlanPrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt ? opt.dataset.price : '';
    const amtInput = document.getElementById('managePlanAmount');
    if (price && parseFloat(price) > 0) amtInput.placeholder = parseFloat(price).toFixed(2);
}

function confirmToggle(form, action, name) {
    Swal.fire({
        title: action + ' ' + name + '?',
        text: action === 'Suspend'
            ? 'Users of this company will not be able to log in until it is reactivated.'
            : 'This company and its users will regain full access.',
        icon: action === 'Suspend' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, ' + action,
        confirmButtonColor: action === 'Suspend' ? '#f59e0b' : '#004161',
    }).then((result) => { if (result.isConfirmed) form.submit(); });
}

function deleteCompany(id, name) {
    Swal.fire({
        title: 'Delete ' + name + '?',
        html: 'This will permanently remove the company and <strong>all its data</strong> — products, sales, and users.<br><br>' +
              'Type <strong>' + name + '</strong> below to confirm:',
        input: 'text',
        inputPlaceholder: 'Type company name to confirm',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete Permanently',
        confirmButtonColor: '#e11d48',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) return 'Please type the company name.';
            if (value !== name) return 'Company name does not match. Try again.';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteCompanyForm');
            form.action = '/super_admin/companies/' + id;
            form.submit();
        }
    });
}

function confirmBulk(e) {
    e.preventDefault();
    const action = document.querySelector('#bulkForm select[name="action"]').value;
    const checked = document.querySelectorAll('.company-check:checked').length;
    if (!action) { Swal.fire({ icon: 'warning', title: 'No Action', text: 'Choose a bulk action first.' }); return false; }
    if (!checked) { Swal.fire({ icon: 'warning', title: 'None Selected', text: 'Select at least one company first.' }); return false; }
    if (action === 'export') { document.getElementById('bulkForm').submit(); return; }
    Swal.fire({
        title: action.charAt(0).toUpperCase() + action.slice(1) + ' ' + checked + ' companies?',
        text: 'This action will be applied to all selected companies.',
        icon: action === 'delete' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, ' + action,
        confirmButtonColor: action === 'delete' ? '#e11d48' : '#004161',
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('bulkForm').submit();
    });
}

document.getElementById('selectAll').addEventListener('change', e => {
    document.querySelectorAll('.company-check').forEach(cb => cb.checked = e.target.checked);
});
</script>
@endpush
