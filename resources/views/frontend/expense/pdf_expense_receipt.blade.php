<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Expense Receipt – {{ $expense->expense_name ?? 'EXP-'.$expense->id }}</title>
    <style>
        @page { margin: 0; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #2d3748;
            background: #ffffff;
        }

        /* ── Header ── */
        .header-band {
            background: #004161;
            padding: 24px 36px 20px;
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; width: 55%; vertical-align: middle; }
        .header-right { display: table-cell; width: 45%; text-align: right; vertical-align: middle; }
        .company-logo { height: 52px; width: auto; border-radius: 8px; }
        .company-name-text {
            font-size: 20px; font-weight: 900;
            color: #ffffff; margin: 0;
            display: inline-block; vertical-align: middle; margin-left: 12px;
        }
        .company-contact {
            font-size: 9px; color: rgba(255,255,255,0.55);
            margin-top: 4px;
        }
        .doc-type-label {
            font-size: 9px; font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase;
            color: #99CC33; margin-bottom: 4px;
        }
        .doc-type-title {
            font-size: 22px; font-weight: 900; color: #ffffff;
            letter-spacing: -0.5px;
        }
        .doc-type-no {
            font-size: 10px; color: rgba(255,255,255,0.5); margin-top: 3px;
        }

        /* ── Accent bar ── */
        .accent-bar {
            background: #99CC33; height: 4px; width: 100%;
        }

        /* ── Body ── */
        .body-wrap { padding: 28px 36px; }

        /* ── Meta row ── */
        .meta-row { display: table; width: 100%; margin-bottom: 24px; }
        .meta-left  { display: table-cell; width: 55%; vertical-align: top; }
        .meta-right { display: table-cell; width: 45%; text-align: right; vertical-align: top; }

        .expense-title {
            font-size: 18px; font-weight: 900;
            color: #004161; margin-bottom: 5px;
        }
        .expense-desc {
            font-size: 11px; color: #64748b;
            margin-bottom: 8px; font-style: italic;
        }
        .category-chip {
            display: inline-block;
            background: rgba(0,65,97,0.08);
            color: #004161;
            font-size: 9px; font-weight: 800;
            padding: 3px 10px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 1px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            display: inline-block;
            min-width: 200px;
            text-align: left;
        }
        .info-row { display: table; width: 100%; padding: 5px 0; border-bottom: 1px solid #edf2f7; }
        .info-row:last-child { border-bottom: none; }
        .info-lbl { display: table-cell; font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; width: 45%; }
        .info-val { display: table-cell; font-size: 11px; font-weight: 900; color: #004161; text-align: right; }
        .status-approved { color: #10b981; }
        .status-pending  { color: #f59e0b; }

        /* ── Amount card ── */
        .amount-card {
            background: #004161;
            border-radius: 14px;
            padding: 22px 28px;
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .amount-card-left  { display: table-cell; width: 60%; vertical-align: middle; }
        .amount-card-right { display: table-cell; width: 40%; text-align: right; vertical-align: middle; }
        .amount-label {
            font-size: 9px; font-weight: 800;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 6px;
        }
        .amount-value {
            font-size: 36px; font-weight: 900;
            color: #99CC33; line-height: 1;
        }
        .amount-note { font-size: 9px; color: rgba(255,255,255,0.35); margin-top: 6px; }
        .paid-from-lbl { font-size: 9px; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .paid-from-val { font-size: 13px; font-weight: 800; color: #ffffff; }

        /* ── Divider ── */
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 4px 0 20px; }

        /* ── Summary grid ── */
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .summary-table td { padding: 10px 14px; }
        .summary-table tr { border-bottom: 1px solid #f1f5f9; }
        .summary-table tr:last-child { border-bottom: none; }
        .summary-lbl { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; width: 35%; }
        .summary-val { font-size: 11px; font-weight: 800; color: #1e293b; }
        .summary-val-green { color: #10b981; }
        .summary-wrap {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .summary-header {
            background: rgba(0,65,97,0.05);
            padding: 9px 14px;
            font-size: 9px; font-weight: 900;
            color: #004161; text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* ── Footer section ── */
        .footer-grid { display: table; width: 100%; margin-top: 10px; }
        .footer-left  { display: table-cell; width: 50%; vertical-align: bottom; }
        .footer-right { display: table-cell; width: 50%; text-align: right; vertical-align: bottom; }

        .thanks-heading {
            font-size: 16px; font-weight: 900;
            color: #004161; margin-bottom: 6px;
        }
        .disclaimer {
            font-size: 9px; color: #94a3b8;
            line-height: 1.6; max-width: 280px; margin-left: auto;
        }
        .disclaimer strong { color: #004161; }

        .sig-line {
            border-top: 1px solid #cbd5e0;
            width: 160px; margin-bottom: 6px; margin-top: 40px;
        }
        .sig-label {
            font-size: 8px; font-weight: 700;
            color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;
        }

        /* ── Doc footer strip ── */
        .doc-footer-strip {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 10px 36px;
            text-align: center;
            font-size: 8px; color: #94a3b8;
            position: fixed; bottom: 0; left: 0; right: 0;
        }
        .doc-footer-strip strong { color: #004161; }
    </style>
</head>
<body>
@php
    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
    $symbol = $currencySymbols[$company_profile->currency ?? ''] ?? ($company_profile->currency ?? '$');

    // Base64 logo for dompdf
    $logoPath = null;
    if (!empty($company_profile->logo) && file_exists(public_path($company_profile->logo))) {
        $logoPath = public_path($company_profile->logo);
    } elseif (file_exists(public_path('upload/waafibooklogo/waafibook_logo.jpg'))) {
        $logoPath = public_path('upload/waafibooklogo/waafibook_logo.jpg');
    }
    $logoSrc = $logoPath
        ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $expDate    = \Carbon\Carbon::parse($expense->expense_date);
    $statusText = ucfirst(strtolower($expense->status ?? 'Pending'));
    $isApproved = strtolower($expense->status ?? '') === 'approved';
    $voucherNo  = str_pad($expense->id, 4, '0', STR_PAD_LEFT);
@endphp

{{-- ── Header ── --}}
<div class="header-band">
    <div class="header-left">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" class="company-logo" alt="{{ $company_profile->name ?? '' }}">
        @endif
        <div style="display:inline-block; vertical-align:middle; margin-left:12px;">
            <div style="font-size:18px; font-weight:900; color:#fff;">{{ $company_profile->name ?? 'WaafiBook' }}</div>
            <div class="company-contact">
                @if($company_profile->phone ?? '') Phone: {{ $company_profile->phone }} @endif
                @if(($company_profile->phone ?? '') && ($company_profile->email ?? '')) &bull; @endif
                @if($company_profile->email ?? '') Email: {{ $company_profile->email }} @endif
            </div>
        </div>
    </div>
    <div class="header-right">
        <div class="doc-type-label">WaafiBook</div>
        <div class="doc-type-title">Expense Receipt</div>
        <div class="doc-type-no">Voucher #EXP-{{ $voucherNo }}</div>
    </div>
</div>
<div class="accent-bar"></div>

{{-- ── Body ── --}}
<div class="body-wrap">

    {{-- Meta row: expense info + info box --}}
    <div class="meta-row">
        <div class="meta-left">
            <div class="expense-title">{{ $expense->expense_name }}</div>
            @if($expense->description)
                <div class="expense-desc">{{ $expense->description }}</div>
            @endif
            <div class="category-chip">
                {{ $expense->account->name ?? 'General Expense' }}
            </div>
        </div>
        <div class="meta-right">
            <div class="info-box">
                <div class="info-row">
                    <div class="info-lbl">Date</div>
                    <div class="info-val">{{ $expDate->format('d M Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-lbl">Voucher #</div>
                    <div class="info-val">#EXP-{{ $voucherNo }}</div>
                </div>
                <div class="info-row">
                    <div class="info-lbl">Status</div>
                    <div class="info-val {{ $isApproved ? 'status-approved' : 'status-pending' }}">
                        {{ $statusText }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Amount card --}}
    <div class="amount-card">
        <div class="amount-card-left">
            <div class="amount-label">Total Expense Amount</div>
            <div class="amount-value">{{ $symbol }}{{ number_format($expense->amount, 2) }}</div>
            <div class="amount-note">Paid on {{ $expDate->format('l, d F Y') }}</div>
        </div>
        <div class="amount-card-right">
            <div class="paid-from-lbl">Paid From</div>
            <div class="paid-from-val">{{ $expense->bankAccount->name ?? ($expense->payment_method ?? 'Cash on Hand') }}</div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="summary-wrap">
        <div class="summary-header">Expense Summary</div>
        <table class="summary-table">
            <tr>
                <td class="summary-lbl">Expense Name</td>
                <td class="summary-val">{{ $expense->expense_name }}</td>
            </tr>
            <tr>
                <td class="summary-lbl">Category</td>
                <td class="summary-val">{{ $expense->account->name ?? 'General' }}</td>
            </tr>
            <tr>
                <td class="summary-lbl">Paid From</td>
                <td class="summary-val">{{ $expense->bankAccount->name ?? ($expense->payment_method ?? 'Cash on Hand') }}</td>
            </tr>
            <tr>
                <td class="summary-lbl">Date</td>
                <td class="summary-val">{{ $expDate->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="summary-lbl">Recorded By</td>
                <td class="summary-val">{{ $expense->createdBy->name ?? 'System' }}</td>
            </tr>
            <tr>
                <td class="summary-lbl">Approval Status</td>
                <td class="summary-val {{ $isApproved ? 'summary-val-green' : '' }}">{{ $statusText }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <hr class="divider">
    <div class="footer-grid">
        <div class="footer-left">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
        <div class="footer-right">
            <div class="thanks-heading">Thank you for your business!</div>
            <div class="disclaimer">
                This is a computer-generated document and does not require a physical signature.<br>
                For queries contact <strong>{{ $company_profile->email ?? '' }}</strong>
                @if($company_profile->phone ?? '') or call <strong>{{ $company_profile->phone }}</strong>@endif
            </div>
        </div>
    </div>

</div>

{{-- Bottom strip --}}
<div class="doc-footer-strip">
    <strong>{{ $company_profile->name ?? 'WaafiBook' }}</strong> &bull;
    Voucher #EXP-{{ $voucherNo }} &bull;
    Generated {{ now()->format('d M Y, h:i A') }} &bull;
    Powered by <strong>WaafiBook</strong>
</div>

</body>
</html>
