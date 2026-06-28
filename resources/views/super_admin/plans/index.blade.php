@extends('super_admin.layouts.master')
@section('page_title', 'Pricing Plans')
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pricing Plans</h4>
        <p class="text-muted mb-0">Manage subscription tiers available to tenants</p>
    </div>
    <button class="btn btn-sm text-white fw-semibold px-4"
            style="background:var(--primary);border-radius:8px;"
            id="addPlanBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Plan
    </button>
</div>

<div class="row g-4 mb-4">
    @forelse($plans as $plan)
    <div class="col-md-4">
        <div class="sa-card h-100 p-4"
             style="border-top:3px solid {{ $plan->is_popular ? 'var(--accent)' : 'var(--primary)' }};">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h5 class="fw-bold mb-0" style="color:var(--primary);">{{ $plan->name }}</h5>
                    @if($plan->is_popular)
                        <span class="sa-badge sa-badge-yellow mt-1">
                            <i class="bi bi-star-fill"></i> Popular
                        </span>
                    @endif
                </div>
                <span class="sa-badge {{ $plan->status === 'active' ? 'sa-badge-green' : 'sa-badge-gray' }}">
                    {{ ucfirst($plan->status) }}
                </span>
            </div>

            <div class="mb-3">
                <span class="fw-bold" style="font-size:1.75rem;color:var(--primary);">
                    ${{ number_format($plan->price, 2) }}
                </span>
                <span class="text-muted">/ {{ $plan->billing_cycle }}</span>
            </div>

            <ul class="list-unstyled text-muted mb-3" style="font-size:.85rem;">
                <li><i class="bi bi-people me-2 text-primary"></i>Up to {{ $plan->max_users }} users</li>
                <li><i class="bi bi-hdd me-2 text-primary"></i>{{ $plan->storage_limit_gb }}GB storage</li>
                @if($plan->description)
                    <li class="mt-1">{{ $plan->description }}</li>
                @endif
            </ul>

            @if($plan->features && count($plan->features))
            <div class="mb-3">
                @foreach($plan->features as $feature)
                    <span class="sa-badge sa-badge-blue me-1 mb-1">{{ $feature }}</span>
                @endforeach
            </div>
            @endif

            <div class="d-flex gap-2 mt-auto pt-2 border-top">
                <button class="btn btn-sm btn-outline-primary flex-fill btn-edit-plan"
                        data-id="{{ $plan->id }}"
                        data-plan="{{ htmlspecialchars(json_encode($plan), ENT_QUOTES, 'UTF-8') }}">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <form method="POST" action="{{ route('host.plans.destroy', $plan->id) }}"
                      onsubmit="return confirm('Delete this plan?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
        No plans yet. Create your first plan.
    </div>
    @endforelse
</div>

{{-- Add/Edit Plan Modal --}}
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--primary);color:white;">
                <h5 class="modal-title" id="planModalTitle">Add Pricing Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="planForm" method="POST" action="{{ route('host.plans.store') }}">
                @csrf
                <span id="methodField"></span>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Plan Name *</label>
                            <input type="text" name="name" id="f_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Price *</label>
                            <input type="number" name="price" id="f_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Billing Cycle *</label>
                            <select name="billing_cycle" id="f_billing_cycle" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Max Users *</label>
                            <input type="number" name="max_users" id="f_max_users" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Storage (GB)</label>
                            <input type="number" name="storage_limit_gb" id="f_storage" class="form-control" min="1" value="2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="f_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <input type="text" name="description" id="f_description" class="form-control" placeholder="Short description">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Features <small class="text-muted">(comma separated)</small>
                            </label>
                            <input type="text" name="features" id="f_features" class="form-control"
                                   placeholder="Inventory, Sales, HR, Accounting">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_popular"
                                       id="f_is_popular" value="1">
                                <label class="form-check-label fw-semibold" for="f_is_popular">
                                    Mark as Popular
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:var(--primary);">
                        <i class="bi bi-check-lg me-1"></i> Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    function openPlanModal() {
        var el = document.getElementById('planModal');
        var modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        modal.show();
    }

    // Edit buttons
    document.querySelectorAll('.btn-edit-plan').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id   = this.getAttribute('data-id');
            var plan = {};
            try { plan = JSON.parse(this.getAttribute('data-plan')); } catch(e) {}

            document.getElementById('planModalTitle').textContent = 'Edit Plan';
            document.getElementById('planForm').action = '{{ url("host/plans") }}/' + id;
            document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('f_name').value          = plan.name || '';
            document.getElementById('f_price').value         = plan.price || '';
            document.getElementById('f_billing_cycle').value = plan.billing_cycle || 'monthly';
            document.getElementById('f_max_users').value     = plan.max_users || '';
            document.getElementById('f_storage').value       = plan.storage_limit_gb || 2;
            document.getElementById('f_status').value        = plan.status || 'active';
            document.getElementById('f_description').value   = plan.description || '';
            document.getElementById('f_features').value      = Array.isArray(plan.features) ? plan.features.join(', ') : (plan.features || '');
            document.getElementById('f_is_popular').checked  = plan.is_popular == 1 || plan.is_popular === true;

            openPlanModal();
        });
    });

    // Add Plan button
    document.getElementById('addPlanBtn').addEventListener('click', function () {
        document.getElementById('planModalTitle').textContent = 'Add Pricing Plan';
        document.getElementById('planForm').action = '{{ route("host.plans.store") }}';
        document.getElementById('methodField').innerHTML = '';
        document.getElementById('planForm').reset();
        openPlanModal();
    });
});
</script>
@endpush
@endsection
