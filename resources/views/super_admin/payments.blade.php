@extends('super_admin.layouts.master')
@section('page_title', 'Payments')
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Payments</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">All subscription payment transactions.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="sa-stat d-flex align-items-center gap-3 px-4 py-3" style="margin-bottom:0;">
            <div class="sa-stat-icon" style="background:rgba(153,204,51,.15);color:#5a7a1a;margin-bottom:0;">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div>
                <div class="sa-stat-lbl">Total Revenue</div>
                <div class="sa-stat-val" style="color:#5a7a1a;">${{ number_format($totalRevenue, 2) }}</div>
            </div>
        </div>
        <button type="button" class="btn btn-primary fw-bold" style="background:var(--primary);border-color:var(--primary);" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
            <i class="bi bi-plus-lg"></i> Record Payment
        </button>
    </div>
</div>

{{-- ── Pending Approval Requests ──────────────────────────────── --}}
@php $pendingPayments = $payments->getCollection()->where('status','pending'); @endphp
@if($pendingPayments->count())
<div class="mb-4" style="border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
    <div style="background:#fffbeb;padding:12px 20px;border-bottom:1px solid #fde68a;display:flex;align-items:center;gap:10px;">
        <i class="bi bi-hourglass-split" style="color:#d97706;font-size:1.1rem;"></i>
        <strong style="color:#92400e;font-size:.9rem;">Pending Payment Requests — {{ $pendingPayments->count() }} awaiting approval</strong>
    </div>
    <table class="sa-table" style="margin:0;">
        <thead>
            <tr>
                <th>Company</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction Ref</th>
                <th>Submitted</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingPayments as $pmt)
            <tr style="background:#fffbeb;">
                <td><strong>{{ $pmt->subscription->company->name ?? '—' }}</strong></td>
                <td><span class="sa-badge sa-badge-blue">{{ $pmt->subscription->plan->name ?? '—' }}</span></td>
                <td><strong style="color:#15803d;font-size:1rem;">${{ number_format($pmt->amount, 2) }}</strong></td>
                <td>{{ $pmt->payment_method ?? '—' }}</td>
                <td><code style="background:#fef3c7;padding:2px 6px;border-radius:4px;font-size:.8rem;">{{ $pmt->transaction_id ?? '—' }}</code></td>
                <td style="color:#6b7280;font-size:.8rem;">{{ \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') }}</td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:6px;justify-content:center;">
                        <form method="POST" action="{{ route('host.payments.mark-paid', $pmt->id) }}" class="d-inline"
                              onsubmit="return confirm('Approve this payment and activate the subscription for {{ addslashes($pmt->subscription->company->name ?? '') }}?');">
                            @csrf @method('PATCH')
                            <button type="submit" style="background:#15803d;color:#fff;border:none;padding:6px 16px;border-radius:6px;font-weight:800;font-size:.8rem;cursor:pointer;">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('host.payments.destroy', $pmt->id) }}" class="d-inline"
                              onsubmit="return confirm('Reject and delete this payment request?');">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-weight:800;font-size:.8rem;cursor:pointer;">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── All Payments ────────────────────────────────────────────── --}}
<div class="sa-card">
    <table class="sa-table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $pmt)
            @php
                $badgeClass = match($pmt->status) {
                    'completed' => 'sa-badge-green',
                    'pending'   => 'sa-badge-yellow',
                    'failed'    => 'sa-badge-red',
                    default     => 'sa-badge-gray',
                };
            @endphp
            <tr>
                <td class="fw-semibold">{{ $pmt->subscription->company->name ?? '—' }}</td>
                <td>{{ $pmt->subscription->plan->name ?? '—' }}</td>
                <td class="fw-bold">${{ number_format($pmt->amount, 2) }}</td>
                <td>{{ $pmt->payment_date ? \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') : '—' }}</td>
                <td>{{ $pmt->payment_method ?? '—' }}</td>
                <td><code>{{ $pmt->transaction_id ?? '—' }}</code></td>
                <td><span class="sa-badge {{ $badgeClass }}">{{ ucfirst($pmt->status) }}</span></td>
                <td>
                    <div class="sa-row-actions">
                        @if($pmt->status !== 'completed')
                        <form method="POST" action="{{ route('host.payments.mark-paid', $pmt->id) }}" class="d-inline"
                              onsubmit="return confirm('Approve this payment and activate the subscription?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Approve & Activate"><i class="bi bi-check2-circle"></i></button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('host.payments.destroy', $pmt->id) }}" class="d-inline"
                              onsubmit="return confirm('Permanently delete this payment record?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-5 text-muted">No payments recorded yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payments->hasPages())
<div class="mt-4 d-flex justify-content-center">{{ $payments->links() }}</div>
@endif

{{-- Record Payment Modal --}}
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('host.payments.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company / Subscription *</label>
                        <select name="subscription_id" class="form-select" required>
                            <option value="">Select a subscription…</option>
                            @foreach($subscriptions as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->company->name ?? '—' }} — {{ $sub->plan->name ?? 'No Plan' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold">Amount *</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-bold">Payment Date *</label><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="EVC Plus">EVC Plus</option>
                            <option value="Card">Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold">Transaction ID</label><input type="text" name="transaction_id" class="form-control" placeholder="Optional reference"></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
