@extends('super_admin.layouts.master')
@section('page_title', 'Payments')
@section('content')

</div>

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="font-weight:800;color:#111827;margin:0;">Payments</h4>
        <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">All subscription payment transactions.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.5rem 1rem;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-currency-dollar" style="color:#15803d;font-size:1rem;"></i>
            <span style="font-size:.72rem;color:#15803d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Total Revenue</span>
            <span style="font-size:1.1rem;font-weight:800;color:#15803d;">${{ number_format($totalRevenue, 2) }}</span>
        </div>
        <button type="button" class="btn btn-primary fw-bold"
                style="background:var(--primary);border-color:var(--primary);"
                data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
            <i class="bi bi-plus-lg"></i> Record Payment
        </button>
    </div>
</div>

@php
    $pendingCount = $payments->getCollection()->where('status','pending')->count();
@endphp

{{-- Pending alert banner (no separate table — merged below) --}}
@if($pendingCount)
<div class="mb-3 d-flex align-items-center gap-2 px-4 py-3"
     style="background:#fffbeb;border:1.5px solid #f59e0b;border-radius:10px;">
    <i class="bi bi-hourglass-split" style="color:#d97706;font-size:1rem;"></i>
    <span style="color:#92400e;font-size:.88rem;font-weight:700;">
        {{ $pendingCount }} payment{{ $pendingCount !== 1 ? 's' : '' }} awaiting approval —
        <a href="?status=pending" style="color:#b45309;text-decoration:underline;">view pending</a>
    </span>
</div>
@endif

{{-- Single unified table with status filter tabs --}}
<div class="sa-card">
    {{-- Tab filter bar --}}
    <div class="px-4 pt-3 pb-0 d-flex gap-1 border-bottom" style="background:#fafafa;">
        @php
            $currentStatus = request('status', '');
            $tabs = [
                '' => ['label' => 'All', 'icon' => 'bi-list-ul'],
                'pending'   => ['label' => 'Pending',   'icon' => 'bi-hourglass-split'],
                'completed' => ['label' => 'Completed', 'icon' => 'bi-check-circle'],
                'failed'    => ['label' => 'Failed',    'icon' => 'bi-x-circle'],
            ];
        @endphp
        @foreach($tabs as $val => $tab)
        <a href="?status={{ $val }}"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;font-size:.8rem;font-weight:700;text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;
                  {{ $currentStatus === $val
                      ? 'background:white;border-color:#e5e7eb;border-bottom-color:white;color:var(--primary);margin-bottom:-1px;'
                      : 'color:#6b7280;' }}">
            <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
        </a>
        @endforeach
    </div>

    <div class="table-responsive">
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
                    $isPending = $pmt->status === 'pending';
                @endphp
                <tr style="{{ $isPending ? 'background:#fffdf0;' : '' }}">
                    <td>
                        <div class="fw-semibold">{{ $pmt->subscription->company->name ?? '—' }}</div>
                    </td>
                    <td>
                        <span class="sa-badge sa-badge-blue">{{ $pmt->subscription->plan->name ?? '—' }}</span>
                    </td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;">
                        ${{ number_format($pmt->amount, 2) }}
                    </td>
                    <td style="font-size:.8rem;color:#6b7280;white-space:nowrap;">
                        {{ $pmt->payment_date ? \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') : '—' }}
                    </td>
                    <td style="font-size:.83rem;">{{ $pmt->payment_method ?? '—' }}</td>
                    <td>
                        @if($pmt->transaction_id)
                            <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:.78rem;">{{ $pmt->transaction_id }}</code>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><span class="sa-badge {{ $badgeClass }}">{{ ucfirst($pmt->status) }}</span></td>
                    <td>
                        <div class="sa-row-actions">
                            @if($isPending)
                            <form method="POST" action="{{ route('host.payments.mark-paid', $pmt->id) }}" class="d-inline"
                                  onsubmit="return confirm('Approve this payment and activate the subscription for {{ addslashes($pmt->subscription->company->name ?? '') }}?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Approve & Activate">
                                    <i class="bi bi-check2-circle"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('host.payments.destroy', $pmt->id) }}" class="d-inline"
                                  onsubmit="return confirm('Permanently delete this payment record?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="sa-btn-icon danger" data-bs-toggle="tooltip" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5" style="color:#9ca3af;">
                        <i class="bi bi-receipt" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        No payments found{{ $currentStatus ? ' with status "' . $currentStatus . '"' : '' }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-4 py-3 border-top" style="background:#fafafa;">
        {{ $payments->appends(request()->query())->links() }}
    </div>
    @endif
</div>

{{-- Record Payment Modal --}}
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('host.payments.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background:var(--primary);color:white;">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Record Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
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
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Amount *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="EVC Plus">EVC Plus</option>
                                <option value="Card">Card</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Optional reference">
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
