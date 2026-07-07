@extends('super_admin.layouts.master')
@section('page_title', 'Expired Companies')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 style="font-weight:800;color:#111827;font-size:1.4rem;margin:0;">Expired Companies</h2>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Companies whose subscriptions have expired and need renewal.</p>
    </div>
    <a href="{{ route('host.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:rgba(220,38,38,.1);color:#dc2626;"><i class="bi bi-calendar-x"></i></div>
            <div class="sa-stat-val" style="color:#dc2626;">{{ $companies->total() }}</div>
            <div class="sa-stat-lbl">Total Expired</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:rgba(245,158,11,.12);color:#d97706;"><i class="bi bi-clock-history"></i></div>
            <div class="sa-stat-val" style="color:#d97706;">
                {{ $companies->filter(fn($c) => $c->subscription && $c->subscription->expiry_date && now()->diffInDays($c->subscription->expiry_date, false) >= -30)->count() }}
            </div>
            <div class="sa-stat-lbl">Expired ≤ 30 Days</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-stat">
            <div class="sa-stat-icon" style="background:rgba(0,65,97,.08);color:#004161;"><i class="bi bi-arrow-repeat"></i></div>
            <div class="sa-stat-val" style="color:#004161;">{{ $allPlans->count() }}</div>
            <div class="sa-stat-lbl">Available Plans</div>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="sa-card mb-3">
    <div class="sa-card-head"><h6>Filter</h6></div>
    <div class="p-3">
        <form method="GET" action="{{ route('host.companies.expired') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="Search company name or email…">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm px-4">Search</button>
            </div>
            @if(request('search'))
            <div class="col-auto">
                <a href="{{ route('host.companies.expired') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="sa-card">
    <div class="sa-card-head">
        <h6>Expired Subscriptions <span class="badge bg-danger ms-2">{{ $companies->total() }}</span></h6>
    </div>
    <div class="table-responsive">
        <table class="sa-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Expiry Date</th>
                    <th>Days Expired</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $i => $company)
                @php
                    $sub      = $company->subscription;
                    $expiry   = $sub?->expiry_date ? \Carbon\Carbon::parse($sub->expiry_date) : null;
                    $daysAgo  = $expiry ? (int) $expiry->diffInDays(now()) : null;
                @endphp
                <tr>
                    <td style="color:#9ca3af;font-size:.8rem;">{{ $companies->firstItem() + $i }}</td>
                    <td style="font-weight:600;color:#111827;">{{ $company->name }}</td>
                    <td style="color:#6b7280;font-size:.85rem;">{{ $company->email ?? '—' }}</td>
                    <td>
                        @if($sub?->plan)
                            <span class="sa-badge sa-badge-blue">{{ $sub->plan->name }}</span>
                        @else
                            <span class="sa-badge sa-badge-gray">No Plan</span>
                        @endif
                    </td>
                    <td style="color:#dc2626;font-size:.85rem;font-weight:600;">
                        {{ $expiry ? $expiry->format('d M Y') : '—' }}
                    </td>
                    <td>
                        @if($daysAgo !== null)
                            <span class="badge" style="background:{{ $daysAgo <= 7 ? 'rgba(245,158,11,.15);color:#d97706' : 'rgba(220,38,38,.1);color:#dc2626' }}; font-size:.78rem; padding:4px 8px; border-radius:6px;">
                                {{ $daysAgo }} day{{ $daysAgo != 1 ? 's' : '' }} ago
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#renewModal"
                                data-id="{{ $company->id }}"
                                data-name="{{ $company->name }}"
                                data-plan="{{ $sub?->subscription_plan_id ?? '' }}">
                            <i class="bi bi-arrow-repeat me-1"></i> Renew
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color:#9ca3af;">
                        <i class="bi bi-check-circle" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#22c55e;"></i>
                        No expired companies found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($companies->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span style="font-size:.8rem;color:#6b7280;">
            Showing {{ $companies->firstItem() }}–{{ $companies->lastItem() }} of {{ $companies->total() }}
        </span>
        {{ $companies->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Renew Plan Modal --}}
<div class="modal fade" id="renewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Renew Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="renewForm" method="POST" action="">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <p class="mb-3" style="color:#374151;">Renewing subscription for <strong id="renewCompanyName"></strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subscription Plan</label>
                        <select name="subscription_plan_id" class="form-select" required>
                            <option value="">— Select plan —</option>
                            @foreach($allPlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="payment_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Manual">Manual</option>
                                <option value="EVC Plus">EVC Plus</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i> Renew Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
document.getElementById('renewModal').addEventListener('show.bs.modal', function (e) {
    const btn  = e.relatedTarget;
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const plan = btn.dataset.plan;

    document.getElementById('renewCompanyName').textContent = name;
    document.getElementById('renewForm').action = `/super_admin/companies/${id}/plan`;

    const sel = this.querySelector('select[name="subscription_plan_id"]');
    if (plan) sel.value = plan;
});
</script>
@endpush
