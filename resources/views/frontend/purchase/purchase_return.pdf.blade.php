@extends('admin.admin_master')
@section('page_title', 'Purchase Return Receipt')

@push('css')
<style>
    /* ── Wrapper ── */
    .receipt-wrapper { width: 100%; background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; padding: 40px; }

    /* ── Header ── */
    .r-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .r-logo { height: 55px; }
    .r-logo-placeholder { width: 55px; height: 55px; background: #004161; border-radius: 8px; }
    .r-header-right { text-align: right; }
    .r-company-name { font-size: 22px; font-weight: 800; color: #004161; margin: 0; }
    .r-company-contact { font-size: 11px; color: #64748b; margin-top: 4px; }

    /* ── Section title ── */
    .r-section-title { font-size: 11px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
    .r-section-title-spaced { margin-bottom: 10px; }

    /* ── Details row ── */
    .r-details-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .r-payee-name { font-size: 20px; font-weight: 800; color: #004161; margin: 0 0 4px 0; }
    .r-payee-id { font-size: 11px; color: #64748b; margin-bottom: 2px; }

    /* ── Info box ── */
    .r-info-box { background: #f8fafc; border-radius: 12px; padding: 20px; min-width: 220px; }
    .r-info-box table { width: 100%; border-collapse: collapse; }
    .r-info-label { font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; padding: 4px 12px 4px 0; text-align: left; white-space: nowrap; }
    .r-info-value { font-size: 11px; font-weight: bold; color: #334155; padding: 4px 0; text-align: right; }
    .r-status-paid     { color: #10b981; }
    .r-status-pending  { color: #f59e0b; }
    .r-status-rejected { color: #ef4444; }

    /* ── Transaction table ── */
    table.r-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.r-table th { background: #f8fafc; padding: 10px 12px; text-align: left; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; }
    table.r-table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #334155; }
    .r-col-date        { width: 12%; }
    .r-col-transaction { width: 12%; }
    .r-col-description { width: 22%; }
    .r-col-item        { width: 20%; }
    .r-col-payment     { width: 16%; }
    .r-col-status      { width: 8%; }
    .r-col-total       { width: 10%; text-align: right; }
    .r-badge { background: #e2e8f0; color: #64748b; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .r-description-cell { color: #64748b; font-style: italic; }
    .r-center { text-align: center; }
    .r-amount-cell { text-align: right; font-weight: bold; }
    .r-totals-row td { padding: 16px 12px; border-bottom: none; font-weight: 800; color: #004161; }
    .r-totals-label { text-align: right; font-size: 11px; font-weight: 800; color: #004161; }
    .r-total-amount { text-align: right; font-size: 16px; color: #004161; }

    /* ── Bottom grid ── */
    .r-bottom-grid { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; gap: 24px; }
    .r-summary-card { background: #f8fafc; border-radius: 12px; padding: 20px; flex: 1; }
    .r-summary-item { padding: 8px 0; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
    .r-summary-item:last-child { border-bottom: none; }
    .r-summary-label { font-size: 12px; color: #64748b; }
    .r-summary-val { font-weight: 900; color: #004161; font-size: 12px; }

    /* ── Footer ── */
    .r-footer-thanks { text-align: right; flex: 1; }
    .r-thanks-bold { font-size: 18px; font-weight: 900; color: #004161; margin-bottom: 8px; }
    .r-disclaimer { font-size: 11px; color: #94a3b8; line-height: 1.6; }
    .r-contact-link { color: #004161; }

    /* Hide mobile bottom nav on this receipt page */
    .bottom-nav, nav.bottom-nav { display: none !important; }

    @media print {
        /* Remove admin chrome from document flow */
        #sidebar, header, .no-print { display: none !important; }

        /* Kill Tailwind's min-h-screen on every element that carries it */
        .min-h-screen { min-height: 0 !important; height: auto !important; }

        /* Reset all layout containers */
        html, body { height: auto !important; background: white !important; margin: 0 !important; padding: 0 !important; }
        .main-content { margin-left: 0 !important; height: auto !important; overflow: visible !important; }
        .main-content > div { padding: 0 !important; background: white !important; }

        /* Clean receipt card */
        .receipt-wrapper {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 12px !important;
            width: 100% !important;
        }
    }
</style>
@endpush

@section('admin')
@php
    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
    $reasonLabels = [
        'damaged'   => 'Physical Damage on Arrival',
        'technical' => 'Technical Malfunction / Defect',
        'wrong_sku' => 'SKU Mismatch (Wrong Item)',
        'quality'   => 'Quality Control Violation',
    ];
    $statusClass = match($return->status ?? 'approved') {
        'approved', 'refunded' => 'r-status-paid',
        'rejected'             => 'r-status-rejected',
        default                => 'r-status-pending',
    };
@endphp

<div class="px-4 py-6 md:px-6 md:py-8 bg-background min-h-screen font-inter">

    {{-- Top action bar --}}
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-[20px] font-bold text-primary-dark">Purchase Return Receipt</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.returns') }}" class="btn-premium-accent">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('purchase.return.pdf', $return->id) }}" class="btn-premium-primary" target="_blank">
                <i class="bi bi-file-earmark-arrow-down"></i> Download PDF
            </a>
            <button onclick="window.print()" class="btn-premium-primary">
                <i class="bi bi-printer"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <div class="receipt-wrapper">

        {{-- ── HEADER ── --}}
        <div class="r-header">
            <div>
                @if(isset($company) && $company->logo && file_exists(public_path($company->logo)))
                    <img src="{{ asset($company->logo) }}" class="r-logo" alt="Logo">
                @else
                    <div class="r-logo-placeholder"></div>
                @endif
            </div>
            <div class="r-header-right">
                <h1 class="r-company-name">{{ $company->company_name ?? $company->name ?? '' }}</h1>
                <div class="r-company-contact">
                    Phone: {{ $company->phone ?? '' }} &nbsp;&bull;&nbsp; Email: {{ $company->email ?? '' }}
                </div>
            </div>
        </div>

        {{-- ── RETURN DETAILS ── --}}
        <div class="r-details-row">
            <div>
                <div class="r-section-title">RETURN DETAILS</div>
                <h2 class="r-payee-name">{{ $return->supplier->name ?? 'N/A' }}</h2>
                <div class="r-payee-id">Reference Bill: {{ $return->bill->bill_number ?? 'N/A' }}</div>
                <div class="r-payee-id">Reference ID: #{{ $return->return_number }}</div>
                <div class="r-payee-id">Branch: {{ $return->branch->name ?? 'Main Branch' }}</div>
            </div>
            <div class="r-info-box">
                <table>
                    <tr>
                        <td class="r-info-label">Return Date</td>
                        <td class="r-info-value">{{ \Carbon\Carbon::parse($return->return_date)->format('M j, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="r-info-label">Voucher #</td>
                        <td class="r-info-value">#{{ $return->return_number }}</td>
                    </tr>
                    <tr>
                        <td class="r-info-label">Status</td>
                        <td class="r-info-value {{ $statusClass }}">{{ strtoupper($return->status ?? 'APPROVED') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ── TRANSACTION HISTORY ── --}}
        <div class="r-section-title">TRANSACTION HISTORY</div>
        <table class="r-table">
            <thead>
                <tr>
                    <th class="r-col-date">Date</th>
                    <th class="r-col-transaction">Transaction</th>
                    <th class="r-col-description">Description</th>
                    <th class="r-col-item">Item Details</th>
                    <th class="r-col-payment">Reference Bill</th>
                    <th class="r-col-status">Status</th>
                    <th class="r-col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($return->items as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($return->return_date)->format('M d, Y') }}</td>
                    <td><span class="r-badge">RETURN</span></td>
                    <td class="r-description-cell">{{ $reasonLabels[$return->reason] ?? ucfirst(str_replace('_', ' ', $return->reason ?? '')) }}</td>
                    <td>{{ $item->product->product_name ?? $item->product->name ?? $item->product_name ?? 'Product' }}</td>
                    <td>{{ $return->bill->bill_number ?? 'N/A' }}</td>
                    <td class="r-center">{{ strtoupper($return->status ?? 'APPROVED') }}</td>
                    <td class="r-amount-cell">{{ $symbol }}{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:#718096;">No items found.</td>
                </tr>
                @endforelse
                <tr class="r-totals-row">
                    <td colspan="6" class="r-totals-label">TOTALS:</td>
                    <td class="r-total-amount">{{ $symbol }}{{ number_format($return->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ── RETURN SUMMARY + FOOTER ── --}}
        <div class="r-bottom-grid">
            <div class="r-summary-card">
                <div class="r-section-title r-section-title-spaced">&#x1F4CB; RETURN SUMMARY</div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Return Reason</span>
                    <span class="r-summary-val">{{ $reasonLabels[$return->reason] ?? ucfirst(str_replace('_', ' ', $return->reason ?? 'N/A')) }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Subtotal</span>
                    <span class="r-summary-val">{{ $symbol }}{{ number_format($return->subtotal ?? $return->total_amount, 2) }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Tax / VAT</span>
                    <span class="r-summary-val">{{ $symbol }}{{ number_format($return->tax ?? 0, 2) }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Total Credit</span>
                    <span class="r-summary-val">{{ $symbol }}{{ number_format($return->total_amount, 2) }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Recorded By</span>
                    <span class="r-summary-val">{{ $return->user->name ?? 'System' }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Approval Status</span>
                    <span class="r-summary-val {{ $statusClass }}">{{ ucfirst($return->status ?? 'Approved') }}</span>
                </div>
            </div>
            <div class="r-footer-thanks">
                <div class="r-thanks-bold">Thank you for your business!</div>
                <p class="r-disclaimer">
                    This is a computer-generated return receipt and does not require a signature.<br>
                    For queries, contact us at
                    <strong class="r-contact-link">{{ $company->email ?? '' }}</strong>
                    or call <strong class="r-contact-link">{{ $company->phone ?? '' }}</strong>
                </p>
            </div>
        </div>

    </div>{{-- end receipt-wrapper --}}
</div>
@endsection
