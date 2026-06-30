<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party Wise Profit & Loss Report — {{ $company->name ?? 'Company' }}</title>
    <style>
        /* ── Force A4 Landscape in DomPDF ───────────────────────────────────── */
        @page { size: A4 landscape; margin: 10mm 12mm; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

        /* ── Header ─────────────────────────────────────────────────────────── */
        .header { padding: 14px 20px 12px; border-bottom: 2px solid #004161; display: flex; justify-content: space-between; align-items: flex-start; }
        .company-name { font-size: 16px; font-weight: 800; color: #004161; }
        .report-title { font-size: 11px; font-weight: 700; color: #64748b; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }
        .report-meta { text-align: right; }
        .report-meta p { font-size: 9px; color: #64748b; margin-top: 2px; }

        /* ── Filters bar ─────────────────────────────────────────────────────── */
        .filters-bar { padding: 6px 20px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; font-size: 8.5px; color: #64748b; }
        .filters-bar span { font-weight: 700; color: #004161; }

        /* ── Table — compact so many columns fit in landscape ───────────────── */
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        thead tr { background: #004161; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; font-weight: 800; color: #fff;
                   text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        thead th.right  { text-align: right; }
        thead th.center { text-align: center; }

        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 6px 8px; font-size: 9px; color: #334155; word-break: break-word; }
        tbody td.right  { text-align: right; white-space: nowrap; }
        tbody td.center { text-align: center; white-space: nowrap; }
        tbody td.bold   { font-weight: 700; color: #004161; }
        tbody td.green  { color: #10b981; font-weight: 700; }
        tbody td.red    { color: #ef4444; font-weight: 700; }

        /* ── Period Totals footer ────────────────────────────────────────────── */
        tfoot tr { background: #d1fae5; border-top: 2px solid #10b981; }
        tfoot td { padding: 7px 8px; font-size: 9px; font-weight: 800; color: #004161; white-space: nowrap; }
        tfoot td.right  { text-align: right; }
        tfoot td.center { text-align: center; }

        /* ── Page footer ─────────────────────────────────────────────────────── */
        .footer { padding: 8px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; margin-top: 4px; }

        /* ── Logo ────────────────────────────────────────────────────────────── */
        .logo-wrap { display: inline-block; vertical-align: middle; margin-right: 12px; }
        .logo-img  { height: 52px; width: 52px; border-radius: 8px; object-fit: contain; border: 1px solid #e2e8f0; }
        .logo-initial { width: 52px; height: 52px; border-radius: 8px; background: #004161;
                        color: #fff; font-size: 22px; font-weight: 900; text-align: center;
                        line-height: 52px; display: inline-block; vertical-align: middle; }
        .header-left { display: inline-block; vertical-align: middle; }
        .header-right { float: right; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

@php
    $currencySymbols = ['SAR'=>'SAR','USD'=>'$','EUR'=>'€','GBP'=>'£','AED'=>'AED','KWD'=>'KWD'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? 'SAR');

    $logoBase64 = null;
    if ($company && $company->logo) {
        $logoPath = $company->logo;
        if (!str_starts_with($logoPath, 'uploads/')) {
            $logoPath = 'uploads/company/' . $logoPath;
        }
        $fullLogoPath = public_path($logoPath);
        if (file_exists($fullLogoPath)) {
            $ext        = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
            $logoData   = file_get_contents($fullLogoPath);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode($logoData);
        }
    }
@endphp

    <!-- Header -->
    <div class="header clearfix">
        <div style="float: left;">
            @if($logoBase64)
                <span class="logo-wrap"><img src="{{ $logoBase64 }}" class="logo-img" alt="Logo"></span>
            @else
                <span class="logo-initial">{{ strtoupper(substr($company->name ?? 'C', 0, 1)) }}</span>
            @endif

            <span class="header-left">
                <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
                <div class="report-title">Party Wise Profit &amp; Loss Report</div>
            </span>
        </div>
        <div class="report-meta header-right">
            <p><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</p>
            <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }} — {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}</p>
            <p><strong>Total Parties:</strong> {{ $totals['activeParties'] }}</p>
        </div>
    </div>

    <!-- Filters bar -->
    <div class="filters-bar">
        Period: <span>{{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }}</span>
        to <span>{{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}</span>
        @if($filters['search'])
            &nbsp;|&nbsp; Search: <span>{{ $filters['search'] }}</span>
        @endif
        &nbsp;|&nbsp; Total Revenue: <span>{{ $symbol }} {{ number_format($totals['totalSales'], 2) }}</span>
        &nbsp;|&nbsp; Net Profit: <span>{{ $symbol }} {{ number_format($totals['totalProfit'], 2) }}</span>
        &nbsp;|&nbsp; Margin: <span>{{ $totals['overallMargin'] }}%</span>
    </div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th style="width:30px" class="center">#</th>
                <th>Party Name</th>
                <th>Phone No.</th>
                <th class="right">Total Sale Amount</th>
                <th class="right">COGS</th>
                <th class="right">Profit (+) / Loss (-)</th>
                <th class="right">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $key => $party)
            <tr>
                <td class="center">{{ str_pad($key + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="bold">{{ $party['name'] }}</td>
                <td>{{ $party['phone'] !== '---' ? $party['phone'] : '---' }}</td>
                <td class="right bold">{{ number_format($party['revenue'], 2) }}</td>
                <td class="right">{{ number_format($party['cogs'], 2) }}</td>
                <td class="right {{ $party['profit'] >= 0 ? 'green' : 'red' }}">
                    {{ $party['profit'] >= 0 ? '+' : '' }}{{ number_format($party['profit'], 2) }}
                </td>
                <td class="right {{ $party['margin'] >= 0 ? 'green' : 'red' }}">{{ $party['margin'] }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; padding: 20px; color: #94a3b8;">No party data found for the selected period.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="bold">GRAND TOTALS ({{ $symbol }})</td>
                <td class="right">{{ number_format($totals['totalSales'], 2) }}</td>
                <td class="right">{{ number_format($totals['totalCOGS'], 2) }}</td>
                <td class="right" style="color:#10b981;">
                    {{ $totals['totalProfit'] >= 0 ? '+' : '' }}{{ number_format($totals['totalProfit'], 2) }}
                </td>
                <td class="right" style="color:#10b981;">{{ $totals['overallMargin'] }}%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <div class="footer clearfix">
        <span style="float: left;">{{ $company->name ?? 'Company' }} — Confidential</span>
        <span style="float: right;">Generated by {{ $company->name ?? 'Waafibook' }}</span>
    </div>

</body>
</html>
