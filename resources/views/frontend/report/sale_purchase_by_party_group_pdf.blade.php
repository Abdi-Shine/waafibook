<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale-Purchase by Party Group PDF — {{ $company->name ?? 'Company' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

        /* Header */
        .header { padding: 14px 20px 12px; border-bottom: 2px solid #004161; margin-bottom: 10px; }
        .logo-wrap { display: inline-block; vertical-align: middle; margin-right: 10px; }
        .logo-img { height: 45px; width: 45px; border-radius: 6px; object-fit: contain; vertical-align: middle; }
        .logo-initial { width: 45px; height: 45px; border-radius: 6px; background: #004161; color: #fff; font-size: 20px; font-weight: 900; text-align: center; line-height: 45px; display: inline-block; vertical-align: middle; }
        .company-name { font-size: 16px; font-weight: 800; color: #004161; display: inline-block; vertical-align: middle; }
        .report-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-top: 2px; padding-left: 55px; }
        .report-meta { float: right; text-align: right; }
        .report-meta p { font-size: 9px; color: #64748b; margin-top: 2px; }

        /* Stats Bar */
        .stats-bar { padding: 8px 20px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; font-size: 9px; margin-bottom: 15px; }
        .stats-bar span { font-weight: 700; color: #004161; margin-right: 15px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #004161; }
        thead th { padding: 8px 10px; text-align: left; font-size: 8px; font-weight: 800; color: #fff; text-transform: uppercase; }
        thead th.right { text-align: right; }
        
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 8px 10px; font-size: 9px; }
        tbody td.right { text-align: right; }
        tbody td.bold { font-weight: 700; color: #004161; }
        tbody td.green { color: #10b981; font-weight: 700; }
        tbody td.red { color: #ef4444; font-weight: 700; }

        /* Tfoot */
        tfoot tr { background: #d1fae5; border-top: 2px solid #10b981; }
        tfoot td { padding: 8px 10px; font-size: 10px; font-weight: 800; color: #004161; }
        tfoot td.right { text-align: right; }

        /* Footer */
        .footer { position: fixed; bottom: 0; width: 100%; padding: 10px 20px; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
@php
    $logoBase64 = null;
    if ($company && $company->logo) {
        $logoPath = $company->logo;
        if (!str_starts_with($logoPath, 'uploads/')) $logoPath = 'uploads/company/' . $logoPath;
        $fullLogoPath = public_path($logoPath);
        if (file_exists($fullLogoPath)) {
            $ext = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($fullLogoPath));
        }
    }
@endphp
    <div class="header clearfix">
        <div style="float: left;">
            @if($logoBase64)
                <span class="logo-wrap"><img src="{{ $logoBase64 }}" class="logo-img" alt="Logo"></span>
            @else
                <span class="logo-initial">{{ strtoupper(substr($company->name ?? 'C', 0, 1)) }}</span>
            @endif
            <span class="company-name">{{ $company->name ?? 'Company' }}</span>
            <span class="report-title">Sale-Purchase by Party Group Analysis</span>
        </div>
        <div class="report-meta">
            <p><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</p>
            <p><strong>Currency:</strong> {{ $company->currency ?? 'SAR' }}</p>
        </div>
    </div>

    <div class="stats-bar">
        Total Group Sales: <span>{{ $company->currency ?? 'SAR' }} 507,850.00</span>
        Total Group Purchases: <span>{{ $company->currency ?? 'SAR' }} 652,000.00</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th>Group Name</th>
                <th class="right">Aggregated Sales</th>
                <th class="right">Aggregated Purchases</th>
                <th class="right">Net Position</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>01</td>
                <td class="bold">Main Distributors</td>
                <td class="right green">156,450.00</td>
                <td class="right red">45,000.00</td>
                <td class="right bold">111,450.00</td>
            </tr>
            <tr>
                <td>02</td>
                <td class="bold">Retail Partners</td>
                <td class="right green">89,000.00</td>
                <td class="right red">12,000.00</td>
                <td class="right bold">77,000.00</td>
            </tr>
            <tr>
                <td>03</td>
                <td class="bold">Wholesale Clients</td>
                <td class="right green">245,000.00</td>
                <td class="right red">180,000.00</td>
                <td class="right bold">65,000.00</td>
            </tr>
            <tr>
                <td>04</td>
                <td class="bold">International Suppliers</td>
                <td class="right">---</td>
                <td class="right red">320,000.00</td>
                <td class="right red">-320,000.00</td>
            </tr>
            <tr>
                <td>05</td>
                <td class="bold">Local Manufacturers</td>
                <td class="right green">5,000.00</td>
                <td class="right red">95,000.00</td>
                <td class="right red">-90,000.00</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">GRAND TOTALS ({{ $company->currency ?? 'SAR' }})</td>
                <td class="right">507,850.00</td>
                <td class="right">652,000.00</td>
                <td class="right red">-144,150.00 Net</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer clearfix">
        <span style="float: left;">{{ $company->name ?? 'Company' }} — Group Hierarchy Analysis</span>
        <span style="float: right;">Generated by {{ $company->name ?? 'Waafibook' }}</span>
    </div>
</body>
</html>

