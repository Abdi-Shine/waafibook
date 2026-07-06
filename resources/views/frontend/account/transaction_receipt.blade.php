@extends('admin.admin_master')
@section('page_title', 'Transaction Receipt')

@push('css')
<style>
    .receipt-wrapper { width: 100%; background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; padding: 40px; }

    .r-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .r-logo { height: 55px; }
    .r-logo-placeholder { width: 55px; height: 55px; background: #004161; border-radius: 8px; }
    .r-header-right { text-align: right; }
    .r-company-name { font-size: 22px; font-weight: 800; color: #004161; margin: 0; }
    .r-company-contact { font-size: 11px; color: #64748b; margin-top: 4px; }

    .r-section-title { font-size: 11px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
    .r-section-title-spaced { margin-bottom: 10px; }

    .r-details-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .r-payee-name { font-size: 20px; font-weight: 800; color: #004161; margin: 0 0 4px 0; }
    .r-payee-id { font-size: 11px; color: #64748b; margin-bottom: 2px; }

    .r-info-box { background: #f8fafc; border-radius: 12px; padding: 20px; min-width: 220px; }
    .r-info-box table { width: 100%; border-collapse: collapse; }
    .r-info-label { font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; padding: 4px 12px 4px 0; text-align: left; white-space: nowrap; }
    .r-info-value { font-size: 11px; font-weight: bold; color: #334155; padding: 4px 0; text-align: right; }
    .r-status-paid { color: #10b981; }

    table.r-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.r-table th { background: #f8fafc; padding: 10px 12px; text-align: left; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; }
    table.r-table td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 11px; color: #334155; }
    .r-badge-dep { background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .r-badge-wth { background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .r-amount-cell { text-align: right; font-weight: bold; }
    .r-totals-row td { padding: 16px 12px; border-bottom: none; font-weight: 800; color: #004161; }
    .r-totals-label { text-align: right; font-size: 11px; font-weight: 800; color: #004161; }
    .r-total-amount { text-align: right; font-size: 16px; color: #004161; }

    .r-bottom-grid { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; gap: 24px; }
    .r-summary-card { background: #f8fafc; border-radius: 12px; padding: 20px; flex: 1; }
    .r-summary-item { padding: 8px 0; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
    .r-summary-item:last-child { border-bottom: none; }
    .r-summary-label { font-size: 12px; color: #64748b; }
    .r-summary-val { font-weight: 900; color: #004161; font-size: 12px; }

    .r-footer-thanks { text-align: right; flex: 1; }
    .r-thanks-bold { font-size: 18px; font-weight: 900; color: #004161; margin-bottom: 8px; }
    .r-disclaimer { font-size: 11px; color: #94a3b8; line-height: 1.6; }
    .r-contact-link { color: #004161; }

    .bottom-nav, nav.bottom-nav { display: none !important; }

    @media print {
        #sidebar, header, .no-print { display: none !important; }
        .min-h-screen { min-height: 0 !important; height: auto !important; }
        html, body { height: auto !important; background: white !important; margin: 0 !important; padding: 0 !important; }
        .main-content { margin-left: 0 !important; height: auto !important; overflow: visible !important; }
        .main-content > div { padding: 0 !important; background: white !important; }
        .receipt-wrapper { border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 12px !important; width: 100% !important; }
    }
</style>
@endpush

@section('admin')
@php
    $isDeposit    = str_starts_with($entry->reference, 'DEP-');
    $isWithdrawal = str_starts_with($entry->reference, 'WTH-');
    $typeLabel    = $isDeposit ? 'Deposit' : ($isWithdrawal ? 'Withdrawal' : 'Transaction');

    $currencyMap  = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
    $symbol       = $currencyMap[$company->currency ?? ''] ?? ($company->currency ?? '$');
@endphp

<div class="px-4 py-6 md:px-6 md:py-8 bg-background min-h-screen font-inter">

    {{-- Action bar --}}
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-[20px] font-bold text-primary-dark">{{ $typeLabel }} Receipt</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('account_management.index') }}" class="btn-premium-accent">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="btn-premium-primary">
                <i class="bi bi-printer"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <div class="receipt-wrapper">

        {{-- HEADER --}}
        <div class="r-header">
            <div>
                @php
                    $logoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
                        ? asset($company->logo)
                        : asset('upload/waafibooklogo/waafibook_logo.jpg');
                @endphp
                <img src="{{ $logoSrc }}" class="r-logo" alt="{{ $company->name ?? 'Logo' }}">
            </div>
            <div class="r-header-right">
                <h1 class="r-company-name">{{ $company->name ?? $company->company_name ?? '' }}</h1>
                <div class="r-company-contact">
                    Phone: {{ $company->phone ?? '' }} &nbsp;&bull;&nbsp; Email: {{ $company->email ?? '' }}
                </div>
            </div>
        </div>

        {{-- TRANSACTION DETAILS --}}
        <div class="r-details-row">
            <div>
                <div class="r-section-title">{{ strtoupper($typeLabel) }} DETAILS</div>
                <h2 class="r-payee-name">{{ $party ?? ($isDeposit ? 'Unknown Sender' : 'Unknown Recipient') }}</h2>
                <div class="r-payee-id">Account: {{ $bankItem?->account->name ?? 'N/A' }}</div>
                <div class="r-payee-id">Reference: {{ $entry->reference }}</div>
                @if($notes)
                <div class="r-payee-id">Notes: {{ $notes }}</div>
                @endif
            </div>
            <div class="r-info-box">
                <table>
                    <tr>
                        <td class="r-info-label">Date</td>
                        <td class="r-info-value">{{ \Carbon\Carbon::parse($entry->date)->format('M j, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="r-info-label">Voucher #</td>
                        <td class="r-info-value">#{{ $entry->id }}</td>
                    </tr>
                    <tr>
                        <td class="r-info-label">Status</td>
                        <td class="r-info-value r-status-paid">POSTED</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- TRANSACTION HISTORY --}}
        <div class="r-section-title">TRANSACTION HISTORY</div>
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width:14%">Date</th>
                    <th style="width:12%">Type</th>
                    <th style="width:28%">Description</th>
                    <th style="width:20%">Account</th>
                    <th style="width:16%">Category</th>
                    <th style="width:10%; text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('M d, Y') }}</td>
                    <td>
                        @if($isDeposit)
                            <span class="r-badge-dep">DEPOSIT</span>
                        @else
                            <span class="r-badge-wth">WITHDRAW</span>
                        @endif
                    </td>
                    <td>{{ $notes ?? $entry->description ?? '—' }}</td>
                    <td>{{ $bankItem?->account->name ?? '—' }}</td>
                    <td>{{ $catItem?->account->name ?? '—' }}</td>
                    <td class="r-amount-cell">{{ $symbol }}{{ number_format($entry->total_amount, 2) }}</td>
                </tr>
                <tr class="r-totals-row">
                    <td colspan="5" class="r-totals-label">TOTAL:</td>
                    <td class="r-total-amount">{{ $symbol }}{{ number_format($entry->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- SUMMARY + FOOTER --}}
        <div class="r-bottom-grid">
            <div class="r-summary-card">
                <div class="r-section-title r-section-title-spaced">TRANSACTION SUMMARY</div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Transaction Type</span>
                    <span class="r-summary-val">{{ $typeLabel }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">{{ $isDeposit ? 'Received From' : 'Paid To' }}</span>
                    <span class="r-summary-val">{{ $party ?? '—' }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Account</span>
                    <span class="r-summary-val">{{ $bankItem?->account->name ?? '—' }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Category</span>
                    <span class="r-summary-val">{{ $catItem?->account->name ?? '—' }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Total Amount</span>
                    <span class="r-summary-val">{{ $symbol }}{{ number_format($entry->total_amount, 2) }}</span>
                </div>
                <div class="r-summary-item">
                    <span class="r-summary-label">Status</span>
                    <span class="r-summary-val r-status-paid">Posted</span>
                </div>
            </div>
            <div class="r-footer-thanks">
                <div class="r-thanks-bold">Thank you for your business!</div>
                <p class="r-disclaimer">
                    This is a computer-generated receipt and does not require a signature.<br>
                    For queries, contact us at
                    <strong class="r-contact-link">{{ $company->email ?? '' }}</strong>
                    or call <strong class="r-contact-link">{{ $company->phone ?? '' }}</strong>
                </p>
            </div>
        </div>

    </div>{{-- end receipt-wrapper --}}
</div>
@endsection
