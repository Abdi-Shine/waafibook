@extends('super_admin.layouts.master')
@section('page_title', 'Subscriptions')
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Subscription &amp; Billing</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Revenue, renewals, and payment status across every active subscription.</p>
    </div>
    <span class="sa-badge sa-badge-blue">{{ $subscriptions->total() }} total</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="sa-stat">
            <span class="sa-pill">Monthly</span>
            <div class="sa-stat-icon" style="background:rgba(0,65,97,.08);color:#004161;"><i class="bi bi-cash-coin"></i></div>
            <div class="sa-stat-val" style="color:#004161;">${{ number_format($totalMrr, 0) }}</div>
            <div class="sa-stat-lbl">Total MRR</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-stat">
            <span class="sa-pill">Payments</span>
            <div class="sa-stat-icon" style="background:rgba(225,29,72,.1);color:#dc2626;"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="sa-stat-val" style="color:#dc2626;">{{ $overduePayments }}</div>
            <div class="sa-stat-lbl">Overdue Payments</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="sa-stat">
            <span class="sa-pill">This Month</span>
            <div class="sa-stat-icon" style="background:rgba(245,158,11,.12);color:#d97706;"><i class="bi bi-calendar-event"></i></div>
            <div class="sa-stat-val" style="color:#d97706;">{{ $expiringThisMonth }}</div>
            <div class="sa-stat-lbl">Expiring This Month</div>
        </div>
    </div>
</div>

<div class="sa-card">
    <table class="sa-table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Expiry Date</th>
                <th>Auto Renew</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $sub)
            @php
                $badgeClass = match($sub->status) {
                    'active'    => 'sa-badge-green',
                    'trial'     => 'sa-badge-blue',
                    'expired'   => 'sa-badge-red',
                    'cancelled' => 'sa-badge-gray',
                    default     => 'sa-badge-gray',
                };
                $expiring = $sub->status === 'active'
                    && $sub->expiry_date
                    && \Carbon\Carbon::parse($sub->expiry_date)->diffInDays(now()) <= 7
                    && \Carbon\Carbon::parse($sub->expiry_date)->isFuture();
            @endphp
            <tr>
                <td class="fw-semibold">{{ $sub->company->name ?? '—' }}</td>
                <td>{{ $sub->plan->name ?? 'No Plan' }}</td>
                <td><span class="sa-badge {{ $badgeClass }}">{{ ucfirst($sub->status) }}</span></td>
                <td>{{ $sub->start_date ? \Carbon\Carbon::parse($sub->start_date)->format('d M Y') : '—' }}</td>
                <td>
                    {{ $sub->expiry_date ? \Carbon\Carbon::parse($sub->expiry_date)->format('d M Y') : '—' }}
                    @if($expiring)
                        <span class="sa-badge sa-badge-yellow ms-1">Expiring Soon</span>
                    @endif
                </td>
                <td>
                    @if($sub->auto_renew)
                        <span class="sa-badge sa-badge-green"><i class="bi bi-check"></i> Yes</span>
                    @else
                        <span class="sa-badge sa-badge-gray">No</span>
                    @endif
                </td>
                <td>
                    @php $lastPmt = $sub->payments->where('status','completed')->sortByDesc('payment_date')->first(); @endphp
                    <div class="fw-semibold" style="font-size:.85rem;">${{ number_format($sub->plan->price ?? 0, 2) }}</div>
                    @if($lastPmt)
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $lastPmt->payment_method }} · {{ \Carbon\Carbon::parse($lastPmt->payment_date)->format('d M Y') }}</div>
                    @else
                        <div style="font-size:.72rem;color:#9ca3af;">No payment recorded</div>
                    @endif
                </td>
                <td>
                    <div class="sa-row-actions">
                        {{-- Change Plan --}}
                        <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="Change Plan"
                                onclick="changePlan({{ $sub->company_id }}, '{{ addslashes($sub->company->name ?? '') }}', {{ $sub->subscription_plan_id ?? 'null' }})">
                            <i class="bi bi-credit-card"></i>
                        </button>
                        @if($sub->status !== 'cancelled')
                        <form method="POST" action="{{ route('host.subscriptions.cancel', $sub->id) }}" class="d-inline"
                              onsubmit="return confirm('{{ addslashes($sub->company->name ?? '') }} will lose access to paid features at the end of the billing period. Continue?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="sa-btn-icon warn" data-bs-toggle="tooltip" title="Cancel Subscription"><i class="bi bi-x-circle"></i></button>
                        </form>
                        @endif
                        <a href="{{ route('host.subscriptions.invoice', $sub->id) }}" target="_blank"
                           class="sa-btn-icon" data-bs-toggle="tooltip" title="View Invoice">
                            <i class="bi bi-receipt"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-5 text-muted">No subscriptions found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($subscriptions->hasPages())
<div class="mt-4 d-flex justify-content-center">{{ $subscriptions->links() }}</div>
@endif

{{-- Change Plan Modal --}}
<div class="modal fade" id="changePlanModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="changePlanForm">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header" style="background:var(--primary);color:white;">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Change Plan — <span id="changePlanCompanyName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subscription Plan *</label>
                        <select name="subscription_plan_id" id="changePlanSelect" class="form-select" required onchange="updateChangePlanPrice(this)">
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
                            <input type="number" step="0.01" min="0" name="payment_amount" id="changePlanAmount" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Date</label>
                            <input type="date" name="payment_date" id="changePlanDate" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="row g-3">
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
                    <div class="form-text mt-2">Leave Amount blank to only change the plan without recording a payment.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save Plan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
function changePlan(companyId, name, currentPlanId) {
    document.getElementById('changePlanForm').action = '/super_admin/companies/' + companyId + '/plan';
    document.getElementById('changePlanCompanyName').textContent = name;
    const sel = document.getElementById('changePlanSelect');
    if (currentPlanId) sel.value = currentPlanId;
    document.getElementById('changePlanAmount').value = '';
    document.getElementById('changePlanDate').value = '{{ now()->toDateString() }}';
    updateChangePlanPrice(sel);
    new bootstrap.Modal(document.getElementById('changePlanModal')).show();
}
function updateChangePlanPrice(sel) {
    const price = sel.options[sel.selectedIndex]?.dataset?.price;
    const inp = document.getElementById('changePlanAmount');
    if (price && parseFloat(price) > 0) inp.placeholder = parseFloat(price).toFixed(2);
}
</script>
@endpush

@endsection
