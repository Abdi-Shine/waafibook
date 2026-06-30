<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Low Stock Summary PDF — {{ $company->name ?? 'Company' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        body { font-size: 11px; color: #333; line-height: 1.4; background: #fff; }
        .header { border-bottom: 2px solid #004161; padding-bottom: 15px; margin-bottom: 25px; display: table; width: 100%; position: relative; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; }
        .header-right { display: table-cell; vertical-align: middle; width: 35%; text-align: right; }
        
        .logo-wrap { display: inline-block; vertical-align: middle; margin-right: 15px; }
        .logo-img { height: 50px; width: 50px; border-radius: 6px; object-fit: contain; }
        .logo-initial { display: inline-block; width: 50px; height: 50px; border-radius: 6px; background: #004161; color: #fff; font-size: 24px; font-weight: 900; text-align: center; line-height: 50px; vertical-align: middle; margin-right: 15px; }
        
        .company-details { display: inline-block; vertical-align: middle; }
        .company-name { font-size: 22px; font-weight: bold; color: #004161; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
        .company-info { font-size: 10px; color: #666; margin-bottom: 8px; }
        .report-title { font-size: 16px; font-weight: bold; color: #004161; text-transform: uppercase; background: #f4f6f9; display: inline-block; padding: 5px 15px; border-radius: 4px; border: 1px solid #e0e6ed; }
        
        .date { font-size: 10px; color: #666; text-align: right; line-height: 1.5; }
        
        .summary-boxes { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 20px; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center; width: 25%; }
        .box-title { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 5px; }
        .box-value { font-size: 16px; font-weight: bold; color: #0f172a; }
        .box-value.critical { color: #e11d48; }
        .box-value.low { color: #d97706; }
        .box-value.warning { color: #0284c7; }
        .box-value.money { color: #16a34a; }

        .section-title { font-size: 12px; font-weight: bold; color: #0f172a; margin-bottom: 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f1f5f9; color: #334155; font-size: 9px; font-weight: bold; text-transform: uppercase; border: 1px solid #cbd5e1; padding: 8px 6px; text-align: left; }
        th.text-center { text-align: center; }
        th.text-right { text-align: right; }
        td { border: 1px solid #cbd5e1; padding: 7px 6px; font-size: 10px; color: #1e293b; vertical-align: middle; }
        td.text-center { text-align: center; }
        td.text-right { text-align: right; }
        
        tr:nth-child(even) { background-color: #f8fafc; }
        tr.critical td { background-color: #fff1f2; }
        tr.low td { background-color: #fffbeb; }

        .label { display: inline-block; padding: 3px 6px; font-size: 9px; font-weight: bold; border-radius: 3px; color: #fff; }
        .label-critical { background-color: #e11d48; }
        .label-low { background-color: #f59e0b; }
        .label-warning { background-color: #3b82f6; }

        .progress-container { width: 100%; background: #e2e8f0; border-radius: 2px; height: 6px; margin-top: 4px; overflow: hidden; }
        .progress-bar { height: 100%; }
        .progress-bar.critical { background-color: #e11d48; }
        .progress-bar.low { background-color: #f59e0b; }
        .progress-bar.warning { background-color: #3b82f6; }

        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a2b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
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

    <div class="header">
        <div class="header-left">
            @if($logoBase64)
                <span class="logo-wrap"><img src="{{ $logoBase64 }}" class="logo-img" alt="Logo"></span>
            @else
                <span class="logo-initial">{{ strtoupper(substr($company->name ?? 'C', 0, 1)) }}</span>
            @endif
            <div class="company-details">
                <div class="company-name">{{ $company->name ?? '' }}</div>
                <div class="company-info">
                    {{ $company->company_address ?? 'Company Address' }} | 
                    Tel: {{ $company->mobile_no ?? 'N/A' }} | 
                    Email: {{ $company->email ?? 'N/A' }}
                </div>
                <div class="report-title">Low Stock Summary Report</div>
            </div>
        </div>
        <div class="header-right">
            <div class="date">
                <strong>Date:</strong> {{ date('d M, Y') }}<br>
                <strong>Time:</strong> {{ date('h:i A') }}
            </div>
        </div>
    </div>



    <div class="section-title">Low Stock Items Details</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%" class="text-center">#</th>
                <th style="width: 25%">Item Name & Code</th>
                <th style="width: 15%">Category</th>
                <th style="width: 10%" class="text-right">Min Qty</th>
                <th style="width: 10%" class="text-right">Stock Qty</th>
                <th style="width: 15%" class="text-right">Stock Value ({{ $company->currency ?? 'SAR' }})</th>
                <th style="width: 20%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataset as $product)
                <tr class="{{ $product->stock_status === 'critical' ? 'critical' : ($product->stock_status === 'low' ? 'low' : '') }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $product->product_name }}</strong><br>
                        <span style="font-size: 9px; color: #64748b; font-family: monospace;">{{ $product->product_code }}</span>
                    </td>
                    <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                    <td class="text-right">{{ number_format($product->min_stock) }}</td>
                    <td class="text-right font-bold" style="font-weight: bold; color: {{ $product->stock_status == 'critical' ? '#e11d48' : ($product->stock_status == 'low' ? '#d97706' : '#0284c7') }}">
                        {{ number_format($product->current_stock) }}
                    </td>
                    <td class="text-right">{{ number_format($product->stock_value, 2) }}</td>
                    <td>
                        @if($product->stock_status === 'critical')
                            <span class="label label-critical">Critical - {{ $product->percentage }}%</span>
                            <div class="progress-container"><div class="progress-bar critical" style="width: {{ min(100, $product->percentage) }}%"></div></div>
                        @elseif($product->stock_status === 'low')
                            <span class="label label-low">Low - {{ $product->percentage }}%</span>
                            <div class="progress-container"><div class="progress-bar low" style="width: {{ min(100, $product->percentage) }}%"></div></div>
                        @else
                            <span class="label label-warning">Warning - {{ $product->percentage }}%</span>
                            <div class="progress-container"><div class="progress-bar warning" style="width: {{ min(100, $product->percentage) }}%"></div></div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">No low stock items found matching your criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>



    <div class="footer">
        Horntech LTD &bull; Generated by Administrator &bull; Page 1
    </div>
</body>
</html>

