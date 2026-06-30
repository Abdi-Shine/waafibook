<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Details Report — {{ $company->name ?? 'Company' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; line-height: 1.4; background: #fff; }
        
        .header { border-bottom: 2px solid #004161; padding-bottom: 15px; margin-bottom: 25px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        
        .logo-box { width: 45px; height: 45px; background: #004161; color: #fff; line-height: 45px; text-align: center; border-radius: 8px; font-size: 20px; font-weight: bold; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: bold; color: #004161; margin-bottom: 2px; text-transform: uppercase; }
        .company-info { font-size: 9px; color: #666; }
        
        .report-title { font-size: 16px; font-weight: bold; color: #004161; margin-bottom: 5px; }
        .report-meta { font-size: 9px; color: #666; }
        .report-meta strong { color: #333; }

        .summary-stats { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .stat-box { width: 25%; padding: 12px; border: 1px solid #eee; border-radius: 8px; background: #F9FAFB; }
        .stat-label { font-size: 8px; font-weight: bold; color: #6B7280; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .stat-value { font-size: 14px; font-weight: bold; color: #004161; }
        .stat-sub { font-size: 8px; color: #9CA3AF; margin-top: 2px; }

        .table-container { width: 100%; border: 1px solid #E5E7EB; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { background: #F8FAFC; color: #475569; font-weight: bold; font-size: 8px; text-transform: uppercase; text-align: left; padding: 10px 8px; border-bottom: 1px solid #E5E7EB; }
        td { padding: 8px; font-size: 9px; border-bottom: 1px solid #F1F5F9; vertical-align: middle; word-wrap: break-word; }
        
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .text-accent { color: #10B981; }
        .text-rose { color: #EF4444; }
        .text-primary { color: #004161; }
        
        .category-text { font-size: 8px; color: #94A3B8; display: block; }
        .qty-badge { font-family: monospace; font-weight: bold; }
        
        .footer { position: fixed; bottom: -5mm; left: 0; right: 0; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('uploads/company/' . ($company->logo ?? ''));
        $logoBase64 = '';
        if ($company && $company->logo && file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . $logoData;
        }
    @endphp

    <div class="header">
        <div class="header-left">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 50px; margin-bottom: 10px;">
            @else
                <div class="logo-box">{{ substr($company->name ?? 'B', 0, 1) }}</div>
            @endif
            <div class="company-name">{{ $company->name ?? '' }}</div>
            <div class="company-info">
                {{ $company->address ?? 'Main Branch' }}<br>
                Phone: {{ $company->phone ?? 'N/A' }} | Email: {{ $company->email ?? 'N/A' }}
            </div>
        </div>
        <div class="header-right">
            <h1 class="report-title">Stock Details Report</h1>
            <div class="report-meta">
                Period: <strong>{{ date('M d, Y', strtotime($filters['from_date'])) }} - {{ date('M d, Y', strtotime($filters['to_date'])) }}</strong><br>
                Generated: <strong>{{ now()->format('M d, Y h:i A') }}</strong>
            </div>
        </div>
    </div>

    <table class="summary-stats">
        <tr>
            <td class="stat-box">
                <span class="stat-label">Beginning Quantity</span>
                <div class="stat-value">{{ number_format($totals->beginningQty) }}</div>
                <div class="stat-sub">Stock at start</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box">
                <span class="stat-label">Total Qty In</span>
                <div class="stat-value text-accent">+{{ number_format($totals->qtyIn) }}</div>
                <div class="stat-sub">Purchased/Received</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box">
                <span class="stat-label">Total Qty Out</span>
                <div class="stat-value text-rose">-{{ number_format($totals->qtyOut) }}</div>
                <div class="stat-sub">Sold/Issued</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box" style="background: #ECFDF5; border-color: #A7F3D0;">
                <span class="stat-label">Closing Quantity</span>
                <div class="stat-value text-primary">{{ number_format($totals->closingQty) }}</div>
                <div class="stat-sub">Stocks at end</div>
            </td>
        </tr>
    </table>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Product Details</th>
                    <th class="text-right" style="width: 70px;">Beg Qty</th>
                    <th class="text-right" style="width: 60px;">Qty In</th>
                    <th class="text-right" style="width: 90px;">Purch Amount</th>
                    <th class="text-right" style="width: 60px;">Qty Out</th>
                    <th class="text-right" style="width: 90px;">Sale Amount</th>
                    <th class="text-right" style="width: 70px;">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dataset as $row)
                <tr>
                    <td style="text-align: center; color: #94A3B8;">{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-bold">{{ $row->name }}</div>
                        <span class="category-text">{{ $row->category }}</span>
                    </td>
                    <td class="text-right qty-badge" style="color: #64748B;">{{ number_format($row->beginningQty) }}</td>
                    <td class="text-right qty-badge text-accent">+{{ number_format($row->qtyIn) }}</td>
                    <td class="text-right">{{ number_format($row->purchaseAmount, 2) }}</td>
                    <td class="text-right qty-badge text-rose">-{{ number_format($row->qtyOut) }}</td>
                    <td class="text-right">{{ number_format($row->saleAmount, 2) }}</td>
                    <td class="text-right font-black text-primary bg-slate-50">{{ number_format($row->closingQty) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background: #F8FAFC;">
                <tr>
                    <td colspan="2" class="text-right font-bold" style="padding: 10px;">GRAND TOTALS</td>
                    <td class="text-right font-black" style="color: #475569;">{{ number_format($totals->beginningQty) }}</td>
                    <td class="text-right font-black text-accent">+{{ number_format($totals->qtyIn) }}</td>
                    <td class="text-right font-black">{{ number_format($totals->purchaseAmount, 2) }}</td>
                    <td class="text-right font-black text-rose">-{{ number_format($totals->qtyOut) }}</td>
                    <td class="text-right font-black">{{ number_format($totals->saleAmount, 2) }}</td>
                    <td class="text-right font-black text-primary">{{ number_format($totals->closingQty) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ $company->name ?? '' }} | Stock Movement Details Report | Page 1
    </div>
</body>
</html>

