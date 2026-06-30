<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sale/Purchase By Item Category Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #004161; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; color: #004161; text-transform: uppercase; }
        .report-title { font-size: 14px; font-weight: bold; margin-top: 5px; color: #666; }
        .meta { margin-bottom: 20px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f8fafc; color: #004161; font-weight: bold; text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; font-size: 9px; color: #94a3b8; text-align: center; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
        .total-row td { border-top: 2px solid #004161; color: #004161; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? '' }}</div>
        <div class="report-title">Sale/Purchase By Item Category</div>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td><strong>Period:</strong> {{ date('d M, Y', strtotime($filters['from_date'])) }} - {{ date('d M, Y', strtotime($filters['to_date'])) }}</td>
                <td class="text-right"><strong>Generated:</strong> {{ date('d M, Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Category Name</th>
                <th class="text-center">Sale Qty</th>
                <th class="text-right">Sale Amount ({{ $company->currency ?? '$' }})</th>
                <th class="text-center">Purch Qty</th>
                <th class="text-right">Purch Amount ({{ $company->currency ?? '$' }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataset as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="text-center">{{ number_format($item->sale_qty) }}</td>
                <td class="text-right">{{ number_format($item->sale_amount, 2) }}</td>
                <td class="text-center">{{ number_format($item->purchase_qty) }}</td>
                <td class="text-right">{{ number_format($item->purchase_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>GRAND TOTAL</td>
                <td class="text-center">{{ number_format($totals->sale_qty) }}</td>
                <td class="text-right">{{ number_format($totals->sale_amount, 2) }}</td>
                <td class="text-center">{{ number_format($totals->purchase_qty) }}</td>
                <td class="text-right">{{ number_format($totals->purchase_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        © {{ date('Y') }} {{ $company->name ?? '' }} | System Generated Financial Intelligence Report
    </div>
</body>
</html>

