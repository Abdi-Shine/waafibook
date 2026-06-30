<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party Statement PDF — {{ $company->name ?? 'Company' }}</title>
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

    <!-- Header -->
    <div class="header clearfix">
        <div style="float: left;">
            @php
                $logoBase64  = null;
                if ($company && $company->logo) {
                    $logoPath = $company->logo;
                    if (!str_starts_with($logoPath, 'uploads/')) {
                        $logoPath = 'uploads/company/' . $logoPath;
                    }
                    $fullLogoPath = public_path($logoPath);
                    if (file_exists($fullLogoPath)) {
                        $ext         = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                        $logoData    = file_get_contents($fullLogoPath);
                        $logoBase64  = 'data:image/' . $ext . ';base64,' . base64_encode($logoData);
                    }
                }
            @endphp

            @if($logoBase64)
                <span class="logo-wrap"><img src="{{ $logoBase64 }}" class="logo-img" alt="Logo"></span>
            @else
                <span class="logo-initial">{{ strtoupper(substr($company->name ?? 'C', 0, 1)) }}</span>
            @endif

            <span class="header-left">
                <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
                <div class="report-title">Party Statement Report</div>
            </span>
        </div>
        @php
            $currency = $company->currency ?? 'SAR';
            $lastBalance = $ledger->last()['balance'] ?? 0;
            $totalDebit = $ledger->sum('debit');
            $totalCredit = $ledger->sum('credit');
        @endphp
        <div class="report-meta header-right">
            <p><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</p>
            <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('M d Y') }} — {{ \Carbon\Carbon::parse($toDate)->format('M d Y') }}</p>
            <p><strong>Active Transactions:</strong> {{ $totalTransactions }}</p>
        </div>
    </div>


    <!-- Status bar applied -->
    <div class="filters-bar clearfix">
        <div style="float: left;">
            Party: <span>{{ $party->name ?? 'All Parties' }}</span> &nbsp;|&nbsp; Ledger Statement
        </div>
        <div style="float: right;">
            Net Balance: <span>{{ $currency }} {{ number_format(abs($lastBalance), 2) }} {{ $lastBalance >= 0 ? 'DR' : 'CR' }}</span>
        </div>
    </div>


    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th style="width:30px" class="center">#</th>
                <th>Date</th>
                <th>Activity / Description</th>
                <th>Ref ID</th>
                <th class="right">Debit (In)</th>
                <th class="right">Credit (Out)</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $i => $row)
            <tr>
                <td class="center">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="bold">{{ $row['date']->format('d-M-Y') }}</td>
                <td class="bold">{{ $row['description'] }}</td>
                <td>{{ $row['reference'] }}</td>
                <td class="right red">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '---' }}</td>
                <td class="right green">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '---' }}</td>
                <td class="right bold">{{ number_format(abs($row['balance']), 2) }} {{ $row['balance'] >= 0 ? 'DR' : 'CR' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="center">{{ $party ?? false ? 'No transactions for this party in the selected period.' : 'Select a party to view their statement ledger.' }}</td>
            </tr>
            @endforelse
        </tbody>
        @if($ledger->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4" class="bold">PERIOD ACTIVITY TOTALS</td>
                <td class="right" style="color:#ef4444;">{{ number_format($totalDebit, 2) }}</td>
                <td class="right" style="color:#10b981;">{{ number_format($totalCredit, 2) }}</td>
                <td class="right">{{ number_format(abs($lastBalance), 2) }} {{ $lastBalance >= 0 ? 'DR' : 'CR' }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Footer -->
    <div class="footer clearfix">
        <span style="float: left;">{{ $company->name ?? 'Company' }} — Confidential</span>
        <span style="float: right;">Generated by {{ $company->name ?? 'Waafibook' }}</span>
    </div>

</body>
</html>

