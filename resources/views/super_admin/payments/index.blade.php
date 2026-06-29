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
    <div class="sa-stat d-flex align-items-center gap-3 px-4 py-3" style="margin-bottom:0;">
        <div class="sa-stat-icon" style="background:rgba(153,204,51,.15);color:#5a7a1a;margin-bottom:0;">
            <i class="bi bi-currency-dollar"></i>
        </div>
        <div>
            <div class="sa-stat-lbl">Total Revenue</div>
            <div class="sa-stat-val" style="color:#5a7a1a;">${{ number_format($totalRevenue, 2) }}</div>
        </div>
    </div>
</div>

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
                    @if($pmt->status !== 'completed')
                    <form method="POST" action="{{ route('host.payments.mark-paid', $pmt->id) }}" class="d-inline"
                          onsubmit="return confirm('Mark this payment as paid?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="sa-btn-icon ok" data-bs-toggle="tooltip" title="Mark as Paid"><i class="bi bi-check2-circle"></i></button>
                    </form>
                    @endif
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
@endsection
