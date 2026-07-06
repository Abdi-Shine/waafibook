<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Expense Receipt</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1e293b; background: #fff; line-height: 1.5; padding: 16mm 18mm; }

        .logo-img { height: 50px; }
        .company-name    { font-size: 21px; font-weight: 800; color: #004161; }
        .company-contact { font-size: 10px; color: #64748b; margin-top: 3px; }

        .section-label { font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 7px; }
        .payee-name    { font-size: 19px; font-weight: 800; color: #004161; margin-bottom: 4px; }
        .payee-id      { font-size: 10px; color: #64748b; margin-bottom: 2px; }

        .info-label { font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; padding: 5px 16px 5px 0; white-space: nowrap; }
        .info-value { font-size: 11px; font-weight: 700; color: #334155; padding: 5px 0; text-align: right; white-space: nowrap; }
        .status-paid { color: #10b981; font-weight: 800; }

        .section-title { font-size: 10px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 0.5px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th { padding: 9px 10px; text-align: left; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-size: 9px; font-weight: 800; color: #004161; text-transform: uppercase; background-color: #f8fafc; }
        table.items td { padding: 11px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; color: #334155; }
        .col-date { width: 13%; }
        .col-txn  { width: 12%; }
        .col-desc { width: 22%; }
        .col-item { width: 18%; }
        .col-pay  { width: 17%; }
        .col-stat { width: 8%; text-align: center; }
        .col-tot  { width: 10%; text-align: right; }
        .badge { background-color: #e2e8f0; color: #64748b; padding: 2px 6px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .italic { color: #64748b; font-style: italic; }
        .right  { text-align: right; }
        .bold   { font-weight: 800; color: #004161; }
        .totals-row td { padding: 11px 10px; border-bottom: none; font-weight: 800; color: #004161; }

        .sum-label { font-size: 11px; color: #64748b; padding: 7px 10px 7px 0; }
        .sum-val   { font-size: 11px; font-weight: 800; color: #004161; text-align: right; padding: 7px 0; }

        .thanks-bold  { font-size: 17px; font-weight: 900; color: #004161; margin-bottom: 8px; }
        .disclaimer   { font-size: 10px; color: #94a3b8; line-height: 1.7; }
        .contact-link { color: #004161; font-weight: 700; }
    </style>
</head>
<body>
@php
    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
    $symbol = $currencySymbols[$company_profile->currency ?? ''] ?? ($company_profile->currency ?? '$');
@endphp

{{-- ── HEADER ── --}}
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="40%" valign="middle" style="padding-bottom:14px;">
            @if($company_profile && $company_profile->logo && file_exists(public_path($company_profile->logo)))
                <img src="{{ public_path($company_profile->logo) }}" class="logo-img" alt="Logo">
            @endif
        </td>
        <td width="60%" valign="middle" align="right" style="padding-bottom:14px;">
            <div class="company-name">{{ $company_profile->name ?? 'Company' }}</div>
            <div class="company-contact">Phone: {{ $company_profile->phone ?? '' }} &nbsp;&bull;&nbsp; Email: {{ $company_profile->email ?? '' }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom: 2px solid #e2e8f0; padding: 0; font-size:0;">&nbsp;</td>
    </tr>
    <tr><td colspan="2" style="padding-bottom:16px; font-size:0;"></td></tr>
</table>

{{-- ── EXPENSE DETAILS ── --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
    <tr>
        <td width="52%" valign="top">
            <div class="section-label">Expense Details</div>
            <div class="payee-name">{{ $expense->expense_name }}</div>
            <div class="payee-id">Supplier: {{ $expense->supplier->name ?? 'N/A' }}</div>
            <div class="payee-id">Reference ID: #EXP-{{ $expense->id }}</div>
            <div class="payee-id">Branch: {{ $expense->branch->name ?? 'Main Branch' }}</div>
        </td>
        <td width="3%"></td>
        {{-- Info box: background + padding live directly on this <td> for DomPDF --}}
        <td width="45%" valign="top" style="background-color:#f8fafc; padding:16px 18px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-label">Receipt Date</td>
                    <td class="info-value">{{ \Carbon\Carbon::parse($expense->expense_date)->format('M j, Y') }}</td>
                </tr>
                <tr>
                    <td class="info-label">Voucher #</td>
                    <td class="info-value">#{{ $expense->id }}</td>
                </tr>
                <tr>
                    <td class="info-label">Status</td>
                    <td class="info-value status-paid">PAID</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Light divider --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
    <tr><td style="border-bottom:1px solid #f1f5f9; font-size:0;">&nbsp;</td></tr>
</table>

{{-- ── TRANSACTION HISTORY ── --}}
<div class="section-title" style="margin-bottom:8px;">Transaction History</div>
<table class="items">
    <thead>
        <tr>
            <th class="col-date">Date</th>
            <th class="col-txn">Transaction</th>
            <th class="col-desc">Description</th>
            <th class="col-item">Item Details</th>
            <th class="col-pay">Payment Info</th>
            <th class="col-stat">Status</th>
            <th class="col-tot">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('M d, Y') }}</td>
            <td><span class="badge">EXPENSE</span></td>
            <td class="italic">{{ $expense->description ?? '-' }}</td>
            <td>{{ $expense->account->name ?? '-' }}</td>
            <td>{{ $expense->bankAccount->name ?? 'N/A' }}</td>
            <td style="text-align:center;">PAID</td>
            <td class="right bold">{{ $symbol }}{{ number_format($expense->amount, 2) }}</td>
        </tr>
        <tr class="totals-row">
            <td colspan="6" class="right bold" style="font-size:10px;">TOTALS:</td>
            <td class="right bold" style="font-size:14px;">{{ $symbol }}{{ number_format($expense->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- ── ACCOUNT SUMMARY + FOOTER ── --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;">
    <tr>
        {{-- Summary card: background lives on the <td> --}}
        <td width="47%" valign="top" style="background-color:#f8fafc; padding:14px 16px;">
            <div class="section-title" style="margin-bottom:10px;">ACCOUNT SUMMARY</div>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="sum-label" style="border-bottom:1px solid #e2e8f0;">Expense Category</td>
                    <td class="sum-val"   style="border-bottom:1px solid #e2e8f0;">{{ $expense->account->name ?? 'General' }}</td>
                </tr>
                <tr>
                    <td class="sum-label" style="border-bottom:1px solid #e2e8f0;">Recorded By</td>
                    <td class="sum-val"   style="border-bottom:1px solid #e2e8f0;">{{ ($expense->creator ?? $expense->createdBy)->name ?? 'System' }}</td>
                </tr>
                <tr>
                    <td class="sum-label">Approval Status</td>
                    <td class="sum-val status-paid">Approved</td>
                </tr>
            </table>
        </td>
        <td width="6%"></td>
        <td width="47%" valign="top" align="right" style="padding-top:8px;">
            <div class="thanks-bold">Thank you for your business!</div>
            <p class="disclaimer">
                This is a computer-generated receipt and does not require a signature.<br>
                For queries, contact us at
                <strong class="contact-link">{{ $company_profile->email ?? '' }}</strong>
                or call <strong class="contact-link">{{ $company_profile->phone ?? '' }}</strong>
            </p>
        </td>
    </tr>
</table>

</body>
</html>
