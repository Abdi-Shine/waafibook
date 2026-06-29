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
                <td>{{ $sub->payment_method ?? '—' }}</td>
                <td>
                    <div class="sa-row-actions">
                        @if($sub->status !== 'cancelled')
                        <form method="POST" action="{{ route('host.subscriptions.cancel', $sub->id) }}" class="d-inline"
                              onsubmit="return confirm('{{ addslashes($sub->company->name ?? '') }} will lose access to paid features at the end of the billing period. Continue?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="sa-btn-icon warn" data-bs-toggle="tooltip" title="Cancel Subscription"><i class="bi bi-x-circle"></i></button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('host.subscriptions.send-invoice', $sub->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="sa-btn-icon" data-bs-toggle="tooltip" title="Send Invoice"><i class="bi bi-receipt"></i></button>
                        </form>
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
@endsection
