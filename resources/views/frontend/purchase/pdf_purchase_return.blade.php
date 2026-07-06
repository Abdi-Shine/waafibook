<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Return - {{ $return->return_number }}</title>
    <style>
        {!! file_get_contents(public_path('frontend/assets/css/receipt-pdf.css')) !!}
    </style>
    <style>
        /* ── Single-page A4 portrait layout ── */
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body          { padding: 0; font-size: 10.5px; line-height: 1.4; }

        /* Header */
        .header       { margin-bottom: 10px; }
        .logo-img     { height: 40px; margin-bottom: 4px; }
        .company-name { font-size: 16px; }
        .company-sub  { font-size: 9px; margin-top: 2px; }
        .doc-title    { font-size: 22px; }
        .doc-number   { font-size: 10px; margin-top: 3px; }
        .doc-badge    { margin-top: 4px; }

        /* Dividers */
        .divider       { margin-bottom: 10px; }
        .divider-light { margin: 10px 0; }

        /* Meta bar */
        .meta-box  { padding: 10px 12px; margin-bottom: 12px; }
        .meta-item { padding: 8px 12px; }
        .meta-val  { font-size: 12px; margin-top: 2px; }

        /* Supplier / doc info */
        .info-grid  { margin-bottom: 12px; }
        .info-name  { font-size: 12px; margin-bottom: 3px; }
        .info-detail{ font-size: 9.5px; line-height: 1.4; }

        /* Items table */
        .section-title    { margin-bottom: 8px; }
        table.items th    { padding: 8px 10px; font-size: 8.5px; }
        table.items td    { padding: 7px 10px; font-size: 10.5px; }
        table.items       { margin-bottom: 12px; }

        /* Totals */
        .totals-wrap      { margin-top: 10px; }
        .totals-table td  { padding: 4px 0; font-size: 10.5px; }
        .totals-table .grand td,
        .totals-table tr.grand-total td { font-size: 13px; padding-top: 6px; }

        /* Reason box */
        .reason-box   { padding: 8px 12px; margin-top: 8px; }
        .reason-title { font-size: 8.5px; margin-bottom: 4px; }
        .reason-text  { font-size: 10.5px; }

        /* Signatures — fixed to bottom of page */
        .signature-table {
            position: fixed;
            bottom: 22mm;
            left: 0; right: 0;
            width: 100%;
            margin: 0;
        }
        .signature-line  { margin-top: 24px; margin-bottom: 8px; }
        .signature-name  { font-size: 12px; }
        .signature-role  { font-size: 9px; }

        /* Footer — fixed to very bottom */
        .footer-meta {
            position: fixed;
            bottom: 4mm;
            left: 0; right: 0;
            margin: 0;
            padding-top: 6px;
            font-size: 9px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك'];
        $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
        $reasonLabels = [
            'damaged'   => 'Physical Damage on Arrival',
            'technical' => 'Technical Malfunction / Defect',
            'wrong_sku' => 'SKU Mismatch (Wrong Item)',
            'quality'   => 'Quality Control Violation',
        ];
    @endphp

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($company && $company->logo && file_exists(public_path($company->logo)))
                <img src="{{ public_path($company->logo) }}" class="logo-img" alt="Logo">
            @endif
            <div class="company-name">{{ $company->name ?? 'Company' }}</div>
            <div class="company-sub">
                {{ $company->address ?? '' }}<br>
                @if($company->phone) Tel: {{ $company->phone }} @endif
                @if($company->email) &nbsp;|&nbsp; {{ $company->email }} @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">DEBIT NOTE</div>
            <div class="doc-number">{{ $return->return_number }}</div>
            <div class="doc-badge {{ $return->status === 'approved' ? 'approved' : '' }}">
                {{ strtoupper($return->status ?? 'Approved') }}
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- Meta Bar --}}
    <div class="meta-box">
        <div class="meta-item">
            <div class="meta-label">Return Date</div>
            <div class="meta-val">{{ \Carbon\Carbon::parse($return->return_date)->format('d M Y') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Ref. Bill No.</div>
            <div class="meta-val">{{ $return->bill->bill_number ?? 'N/A' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Branch</div>
            <div class="meta-val">{{ $return->branch->name ?? '-' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Processed By</div>
            <div class="meta-val">{{ $return->user->name ?? 'System' }}</div>
        </div>
    </div>

    {{-- Supplier Info --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="section-label">Return To (Supplier)</div>
            <div class="info-name">{{ $return->supplier->name ?? 'N/A' }}</div>
            <div class="info-detail">
                @if($return->supplier)
                    Code: {{ $return->supplier->supplier_code ?? '-' }}<br>
                    Phone: {{ $return->supplier->phone ?? '-' }}<br>
                    Email: {{ $return->supplier->email ?? '-' }}
                @endif
            </div>
        </div>
        <div class="info-col right">
            <div class="section-label">Document Info</div>
            <div class="info-name">{{ $return->return_number }}</div>
            <div class="info-detail">
                Generated: {{ now()->format('d M Y H:i') }}<br>
                Status: <strong>{{ ucfirst($return->status ?? 'approved') }}</strong>
            </div>
        </div>
    </div>

    <hr class="divider-light">

    {{-- Items Table --}}
    <div class="section-title">Returned Items</div>
    <table class="items">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-product">Product / Description</th>
                <th class="center col-15">Orig. Bill Qty</th>
                <th class="center col-15">Return Qty</th>
                <th class="right col-12">Unit Price</th>
                <th class="right col-13">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($return->items as $index => $item)
            <tr>
                <td class="gray">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product->product_name ?? $item->product_name ?? 'Product' }}</strong><br>
                    <span class="product-code">{{ $item->product->product_code ?? '' }}</span>
                </td>
                <td class="center gray">{{ number_format($item->original_qty ?? 0, 0) }}</td>
                <td class="center td-red">{{ number_format($item->quantity, 0) }}</td>
                <td class="right">{{ $symbol }} {{ number_format($item->unit_price, 2) }}</td>
                <td class="right td-primary">{{ $symbol }} {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="td-empty">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals + Reason --}}
    <div class="totals-wrap">
        <div class="totals-left">
            @if($return->reason)
            <div class="reason-box">
                <div class="reason-title">Return Reason</div>
                <div class="reason-text">{{ $reasonLabels[$return->reason] ?? ucfirst(str_replace('_', ' ', $return->reason)) }}</div>
            </div>
            @endif
            @if($return->notes)
            <div class="confirmation-box" style="margin-top: 12px;">
                <div class="confirmation-title">Notes</div>
                <div class="confirmation-text">{{ $return->notes }}</div>
            </div>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="val">{{ $symbol }} {{ number_format($return->subtotal ?? $return->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax / VAT</td>
                    <td class="val">{{ $symbol }} {{ number_format($return->tax ?? 0, 2) }}</td>
                </tr>
                <tr class="grand">
                    <td class="label">Total Credit</td>
                    <td class="val">{{ $symbol }} {{ number_format($return->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-name uppercase">{{ $return->user->name ?? 'ACCOUNTANT' }}</div>
                <div class="signature-role">Prepared By</div>
            </td>
            <td width="10%"></td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-name uppercase">_________________</div>
                <div class="signature-role">Manager Approval</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer-meta">
        <strong>{{ $company->name ?? '' }}</strong> &mdash;
        System-generated document. No physical signature required.<br>
        For queries: {{ $company->email ?? '' }} &nbsp;|&nbsp; {{ $company->phone ?? '' }}
        &nbsp;&mdash;&nbsp; Generated on {{ now()->format('d M Y, H:i') }}
    </div>

</body>
</html>

