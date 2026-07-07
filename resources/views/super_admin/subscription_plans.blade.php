@extends('super_admin.layouts.master')
@section('page_title', 'Subscription Plans')
@section('content')

</div>
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Subscription Plans</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Create and manage the subscription tiers available to tenant companies.</p>
    </div>
    <button class="btn btn-primary fw-bold px-4" id="addPlanBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Plan
    </button>
</div>

{{-- Summary stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="sa-stat text-center">
            <div class="sa-stat-val" style="color:var(--primary);">{{ $plans->count() }}</div>
            <div class="sa-stat-lbl">Total Plans</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat text-center">
            <div class="sa-stat-val" style="color:#16a34a;">{{ $plans->where('status','active')->count() }}</div>
            <div class="sa-stat-lbl">Active Plans</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat text-center">
            <div class="sa-stat-val" style="color:var(--accent);">${{ number_format($plans->where('status','active')->min('price'),0) }}</div>
            <div class="sa-stat-lbl">Starting From</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="sa-stat text-center">
            <div class="sa-stat-val" style="color:#d97706;">${{ number_format($plans->where('status','active')->max('price'),0) }}</div>
            <div class="sa-stat-lbl">Top Plan Price</div>
        </div>
    </div>
</div>

{{-- Plan cards --}}
<div class="row g-4 mb-4">
    @forelse($plans as $plan)
    <div class="col-md-4 col-lg-3">
        <div class="sa-card h-100 p-0 overflow-hidden"
             style="border-top:4px solid {{ $plan->is_popular ? 'var(--accent)' : 'var(--primary)' }};">

            {{-- Card header --}}
            <div class="px-4 pt-4 pb-3" style="background:{{ $plan->is_popular ? 'rgba(153,204,51,.07)' : '#fafafa' }}; border-bottom:1px solid #f3f4f6;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="fw-bold mb-0" style="color:var(--primary);">{{ $plan->name }}</h5>
                    @if($plan->is_popular)
                        <span class="sa-badge sa-badge-yellow"><i class="bi bi-star-fill"></i> Popular</span>
                    @endif
                </div>
                <div class="d-flex align-items-baseline gap-1">
                    <span style="font-size:1.8rem;font-weight:900;color:var(--primary);">${{ number_format($plan->price,0) }}</span>
                    <span style="color:#9ca3af;font-size:.8rem;">/ {{ $plan->billing_cycle }}</span>
                </div>
                @if($plan->description)
                    <p style="font-size:.78rem;color:#6b7280;margin:.4rem 0 0;">{{ $plan->description }}</p>
                @endif
            </div>

            {{-- Details --}}
            <div class="px-4 py-3">
                <ul class="list-unstyled mb-3" style="font-size:.82rem;">
                    <li class="mb-1"><i class="bi bi-people me-2" style="color:var(--primary);"></i>
                        {{ $plan->max_users >= 999 ? 'Unlimited' : 'Up to '.$plan->max_users }} users
                    </li>
                    <li class="mb-1"><i class="bi bi-hdd me-2" style="color:var(--primary);"></i>
                        {{ $plan->storage_limit_gb }}GB storage
                    </li>
                </ul>

                @if($plan->features && count($plan->features))
                <div class="mb-3">
                    @foreach($plan->features as $feature)
                        <div class="d-flex align-items-center gap-2 mb-1" style="font-size:.78rem;color:#374151;">
                            <i class="bi bi-check-circle-fill" style="color:var(--accent);font-size:.7rem;"></i>
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
                @endif

                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="sa-badge {{ $plan->status === 'active' ? 'sa-badge-green' : 'sa-badge-gray' }}">
                        {{ ucfirst($plan->status) }}
                    </span>
                    <span class="text-muted" style="font-size:.72rem;">{{ $plan->subscriptions_count ?? 0 }} subscribers</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-4 pb-4 d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm flex-fill btn-edit-plan fw-bold"
                        data-id="{{ $plan->id }}">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <form method="POST" action="{{ route('host.plans.destroy', $plan->id) }}"
                      onsubmit="return confirm('Delete {{ addslashes($plan->name) }} plan? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <i class="bi bi-tags fs-1 d-block mb-3" style="color:#e5e7eb;"></i>
        <p class="text-muted">No plans yet. Create your first subscription plan.</p>
    </div>
    @endforelse
</div>

{{-- Add/Edit Plan Modal --}}
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--primary);color:white;">
                <h5 class="modal-title" id="planModalTitle"><i class="bi bi-tags me-2"></i>Add Subscription Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="planForm" method="POST" action="{{ route('host.plans.store') }}">
                @csrf
                <span id="methodField"></span>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Plan Name *</label>
                            <input type="text" name="name" id="f_name" class="form-control" placeholder="e.g. Starter, Pro, Enterprise" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price ($) *</label>
                            <input type="number" name="price" id="f_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Billing Cycle *</label>
                            <select name="billing_cycle" id="f_billing_cycle" class="form-select" required>
                                <option value="7days">7 Days</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Max Users *</label>
                            <input type="number" name="max_users" id="f_max_users" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Storage (GB)</label>
                            <input type="number" name="storage_limit_gb" id="f_storage" class="form-control" min="1" value="2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" id="f_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" id="f_description" class="form-control" placeholder="Short description shown to tenants">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Features <small class="text-muted">(comma separated)</small></label>
                            <input type="text" name="features" id="f_features" class="form-control"
                                   placeholder="Inventory, Sales, POS, Reports, API Access">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_popular" id="f_is_popular" value="1">
                                <label class="form-check-label fw-semibold" for="f_is_popular">
                                    <i class="bi bi-star-fill text-warning me-1"></i> Mark as Popular (highlighted to tenants)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
var __plans = {
    @foreach($plans as $plan)
    "{{ $plan->id }}": {
        name:             @json($plan->name),
        price:            {{ (float) $plan->price }},
        billing_cycle:    @json($plan->billing_cycle),
        max_users:        {{ (int) $plan->max_users }},
        storage_limit_gb: {{ (int) ($plan->storage_limit_gb ?? 2) }},
        status:           @json($plan->status),
        description:      @json($plan->description ?? ''),
        features:         @json(is_array($plan->features) ? $plan->features : []),
        is_popular:       {{ $plan->is_popular ? 'true' : 'false' }}
    },
    @endforeach
};

function openPlanModal() {
    var el = document.getElementById('planModal');
    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
}

document.querySelectorAll('.btn-edit-plan').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id   = this.dataset.id;
        var plan = __plans[id] || {};

        document.getElementById('planModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Plan';
        document.getElementById('planForm').action = '/super_admin/plans/' + id;
        document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('f_name').value          = plan.name || '';
        document.getElementById('f_price').value         = plan.price || '';
        document.getElementById('f_billing_cycle').value = plan.billing_cycle || 'monthly';
        document.getElementById('f_max_users').value     = plan.max_users || '';
        document.getElementById('f_storage').value       = plan.storage_limit_gb || 2;
        document.getElementById('f_status').value        = plan.status || 'active';
        document.getElementById('f_description').value   = plan.description || '';
        document.getElementById('f_features').value      = Array.isArray(plan.features) ? plan.features.join(', ') : '';
        document.getElementById('f_is_popular').checked  = plan.is_popular === true;
        openPlanModal();
    });
});

document.getElementById('addPlanBtn').addEventListener('click', function () {
    document.getElementById('planModalTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Add Subscription Plan';
    document.getElementById('planForm').action = '{{ route("host.plans.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('planForm').reset();
    openPlanModal();
});
</script>
@endpush
@endsection
