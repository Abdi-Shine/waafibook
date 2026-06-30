<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Details Ledger — {{ $company->name ?? '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; line-height: 1.4; background: #fff; }
        
        /* Header */
        .header { border-bottom: 2px solid #004161; padding-bottom: 15px; margin-bottom: 20px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 60%; }
        .header-right { display: table-cell; vertical-align: top; width: 40%; text-align: right; }
        
        .company-name { font-size: 18px; font-weight: bold; color: #004161; margin-bottom: 2px; text-transform: uppercase; }
        .company-info { font-size: 9px; color: #666; }
        
        .report-title { font-size: 16px; font-weight: bold; color: #004161; margin-bottom: 5px; }
        .report-meta { font-size: 9px; color: #666; }
        
        /* Item Info Gradient Card */
        .item-card { 
            background: #004161; 
            color: #fff; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px;
        }
        .item-name { font-size: 16px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; }
        .item-details { width: 100%; border-collapse: collapse; }
        .item-details td { font-size: 8px; padding: 2px 0; }
        .label { opacity: 0.7; text-transform: uppercase; font-size: 7px; }
        .value { font-weight: bold; font-size: 10px; }

        /* Stats Row */
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        .stat-box { border: 1px solid #eee; padding: 10px; border-radius: 6px; background: #fbfbfb; }
        .stat-label { font-size: 7px; color: #666; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; display: block; }
        .stat-value { font-size: 12px; font-weight: bold; color: #004161; }

        /* Table */
        .table-container { width: 100%; border: 1px solid #E5E7EB; border-radius: 4px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F8FAFC; color: #475569; font-weight: bold; font-size: 8px; text-transform: uppercase; text-align: left; padding: 8px 6px; border-bottom: 1px solid #E5E7EB; }
        td { padding: 8px 6px; font-size: 9px; border-bottom: 1px solid #F1F5F9; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-accent { color: #10B981; }
        .text-danger { color: #EF4444; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        
        .closing-col { background: #F8FAFC; font-weight: bold; color: #004161; }
        .type-badge { font-size: 7px; padding: 2px 4px; border-radius: 3px; font-weight: bold; }

        /* Footer */
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
                <img src="{{ $logoBase64 }}" style="max-height: 40px; margin-bottom: 5px;">
            @endif
            <div class="company-name">{{ $company->name ?? '' }}</div>
            <div class="company-info">
                {{ $company->address ?? '' }} | Phone: {{ $company->phone ?? '' }}
            </div>
        </div>
        <div class="header-right">
            <h1 class="report-title">Item Details Report</h1>
            <div class="report-meta">
                Period: <strong>{{ date('M d, Y', strtotime($filters['from_date'])) }} - {{ date('M d, Y', strtotime($filters['to_date'])) }}</strong><br>
                Product: <strong>{{ $selectedProduct->product_name }}</strong>
            </div>
        </div>
    </div>

    <!-- Product Details Card -->
    <div class="item-card">
        <div class="item-name">{{ $selectedProduct->product_name }}</div>
        <table class="item-details">
            <tr>
                <td width="20%"><span class="label">SKU:</span> <span class="value">{{ $selectedProduct->product_code ?? 'N/A' }}</span></td>
                <td width="25%"><span class="label">Category:</span> <span class="value">{{ $selectedProduct->category->name ?? 'Uncategorized' }}</span></td>
                <td width="25%"><span class="label">Opening Stock:</span> <span class="value">{{ number_format($totals->opening) }} Units</span></td>
                <td width="30%"><span class="label">Closing Stock:</span> <span class="value">{{ number_format($totals->closing) }} Units</span></td>
            </tr>
        </table>
    </div>

    <!-- Summary Stats -->
    <table class="stats-table">
        <tr>
            <td class="stat-box">
                <span class="stat-label">Total Purchases (+)</span>
                <div class="stat-value text-accent">+{{ number_format($totals->purchases) }}</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box">
                <span class="stat-label">Total Sales (-)</span>
                <div class="stat-value text-danger">-{{ number_format($totals->sales) }}</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box">
                <span class="stat-label">Adjustments</span>
                <div class="stat-value">{{ $totals->adjustments >= 0 ? '+' : '' }}{{ number_format($totals->adjustments) }}</div>
            </td>
            <td style="width: 10px;"></td>
            <td class="stat-box" style="background: #F1F5F9;">
                <span class="stat-label">Net Movement</span>
                <div class="stat-value" style="color: #99CC33;">{{ ($totals->closing - $totals->opening) >= 0 ? '+' : '' }}{{ number_format($totals->closing - $totals->opening) }}</div>
            </td>
        </tr>
    </table>

    <!-- Ledger Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">Date</th>
                    <th class="text-center" style="width: 60px;">Type</th>
                    <th class="text-right">Purchase Qty (+)</th>
                    <th class="text-right">Sale Qty (-)</th>
                    <th class="text-right">Adjustment</th>
                    <th class="text-right" style="width: 80px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #F8FAFC; font-style: italic;">
                    <td colspan="5" style="color: #666;">Opening Balance Carryover</td>
                    <td class="text-right font-bold">{{ number_format($totals->opening) }}</td>
                </tr>
                @foreach($dataset as $row)
                <tr>
                    <td>{{ date('d/m/Y', strtotime($row->date)) }}</td>
                    <td class="text-center">
                        <span class="type-badge" style="background: {{ $row->type == 'Sale' ? '#fff3cd' : ($row->type == 'Purchase' ? '#d1ecf1' : '#e2e3e5') }}; color: {{ $row->type == 'Sale' ? '#856404' : ($row->type == 'Purchase' ? '#0c5460' : '#383d41') }};">
                            {{ strtoupper($row->type) }}
                        </span>
                    </td>
                    <td class="text-right text-accent">{{ $row->type == 'Purchase' ? number_format(abs($row->quantity)) : '0' }}</td>
                    <td class="text-right text-danger">{{ $row->type == 'Sale' ? number_format(abs($row->quantity)) : '0' }}</td>
                    <td class="text-right" style="color: #666;">{{ !in_array($row->type, ['Sale', 'Purchase']) ? ($row->quantity >= 0 ? '+' : '') . number_format($row->quantity) : '0' }}</td>
                    <td class="text-right closing-col">{{ number_format($row->closing) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background: #F8FAFC; border-top: 2px solid #004161;">
                <tr>
                    <td colspan="2" class="font-bold">TOTAL PERIODS</td>
                    <td class="text-right font-black text-accent">{{ number_format($totals->purchases) }}</td>
                    <td class="text-right font-black text-danger">{{ number_format($totals->sales) }}</td>
                    <td class="text-right font-bold">{{ $totals->adjustments >= 0 ? '+' : '' }}{{ number_format($totals->adjustments) }}</td>
                    <td class="text-right font-black" style="color: #004161; font-size: 11px;">{{ number_format($totals->closing) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        Generated by Horntech LTD | {{ date('M d, Y') }} | Page 1
    </div>
</body>
</html>

