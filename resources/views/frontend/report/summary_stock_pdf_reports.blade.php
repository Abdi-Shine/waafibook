<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary Stock PDF — {{ $company->name ?? 'Company' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

        .header { padding: 14px 20px 12px; border-bottom: 2px solid #004161; margin-bottom: 10px; }
        .logo-wrap { display: inline-block; vertical-align: middle; margin-right: 10px; }
        .logo-img { height: 45px; width: 45px; border-radius: 6px; object-fit: contain; vertical-align: middle; }
        .logo-initial { width: 45px; height: 45px; border-radius: 6px; background: #004161; color: #fff; font-size: 20px; font-weight: 900; text-align: center; line-height: 45px; display: inline-block; vertical-align: middle; }
        .company-name { font-size: 16px; font-weight: 800; color: #004161; display: inline-block; vertical-align: middle; }
        .report-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-top: 2px; padding-left: 55px; }
        .report-meta { float: right; text-align: right; }
        .report-meta p { font-size: 9px; color: #64748b; margin-top: 2px; }

        .stats-bar { padding: 8px 20px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; font-size: 9px; margin-bottom: 15px; }
        .stats-bar span { font-weight: 700; color: #004161; margin-right: 15px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #004161; }
        thead th { padding: 8px 10px; text-align: left; font-size: 8px; font-weight: 800; color: #fff; text-transform: uppercase; }
        thead th.right  { text-align: right; }
        thead th.center { text-align: center; }

        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 8px 10px; font-size: 9px; }
        tbody td.right  { text-align: right; }
        tbody td.center { text-align: center; }
        tbody td.bold   { font-weight: 700; color: #004161; }
        tbody td.green  { color: #10b981; font-weight: 700; }
        tbody td.red    { color: #ef4444; font-weight: 700; }
        tbody td.teal   { color: #0d9488; font-weight: 700; }

        tfoot tr { background: #d1fae5; border-top: 2px solid #10b981; }
        tfoot td { padding: 8px 10px; font-size: 10px; font-weight: 800; color: #004161; }
        tfoot td.right { text-align: right; }

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
            <span class="report-title">Summary Stock Report</span>
        </div>
        <div class="report-meta">
            <p><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</p>
            <p><strong>Currency:</strong> {{ $company->currency ?? 'SAR' }}</p>
        </div>
    </div>

    @php
        $totalQty = $products->sum('qty');
        $totalValue = $products->sum(fn($p) => $p['purchasePrice'] * $p['qty']);
        $totalRevenue = $products->sum(fn($p) => $p['salePrice'] * $p['qty']);
    @endphp

    <div class="stats-bar clearfix">
        Total Items: <span>{{ $products->count() }}</span>
        Total Stock Qty: <span>{{ number_format($totalQty) }} units</span>
        Stock Value: <span>{{ $company->currency ?? 'SAR' }} {{ number_format($totalValue, 2) }}</span>
        Potential Revenue: <span>{{ $company->currency ?? 'SAR' }} {{ number_format($totalRevenue, 2) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:35px;">#</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="right">Sale Price</th>
                <th class="right">Purchase Price</th>
                <th class="center">Stock Qty</th>
                <th class="center">Status</th>
                <th class="right">Stock Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $i => $p)
                @php
                    $statusClass = $p['qty'] <= 0 ? 'red' : ($p['qty'] < 5 ? 'red' : ($p['qty'] <= 20 ? 'teal' : 'green'));
                    $statusLabel = $p['qty'] <= 0 ? 'Out of Stock' : ($p['qty'] < 5 ? 'Low Stock' : ($p['qty'] <= 20 ? 'Medium' : 'Healthy'));
                @endphp
                <tr>
                    <td>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td class="bold">{{ $p['name'] }}</td>
                    <td>{{ $p['category'] }}</td>
                    <td class="right green">{{ number_format($p['salePrice'], 2) }}</td>
                    <td class="right red">{{ number_format($p['purchasePrice'], 2) }}</td>
                    <td class="center {{ $statusClass }}">{{ $p['qty'] }}</td>
                    <td class="center {{ $statusClass }}">{{ $statusLabel }}</td>
                    <td class="right">{{ number_format($p['purchasePrice'] * $p['qty'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">No products found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">TOTALS ({{ $company->currency ?? 'SAR' }})</td>
                <td class="right">{{ number_format($totalValue, 2) }} purchase</td>
                <td class="right">{{ number_format($totalQty) }} units</td>
                <td></td>
                <td class="right">{{ number_format($totalValue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer clearfix">
        <span style="float: left;">{{ $company->name ?? 'Company' }} — Summary Stock Report</span>
        <span style="float: right;">Page 1 of 1</span>
    </div>
</body>
</html>

