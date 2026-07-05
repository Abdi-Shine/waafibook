<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note - {{ $return->credit_note_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #fff;
        }
        h1.page-title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 18px;
            color: #1a1a2e;
        }
        .outer-box { border: 1px solid #666; width: 100%; }
        .row-border-top { border-top: 1px solid #666; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f5f5; font-weight: bold; text-align: left; padding: 8px 12px; font-size: 12px; }
        td { padding: 8px 12px; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .items-thead th { border-bottom: 1px solid #999; }
        .items-row td { border-bottom: 1px solid #eee; }
        .items-total td { border-top: 1px solid #999; font-weight: bold; background: #f5f5f5; }
    </style>
</head>
<body>

@php
    $numToWords = function(int $n) use (&$numToWords): string {
        if ($n === 0) return '';
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                 'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                 'Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $w = '';
        if ($n >= 1000000) { $w .= $numToWords(intdiv($n, 1000000)) . ' Million '; $n %= 1000000; }
        if ($n >= 1000)    { $w .= $numToWords(intdiv($n, 1000)) . ' Thousand '; $n %= 1000; }
        if ($n >= 100)     { $w .= $ones[intdiv($n, 100)] . ' Hundred '; $n %= 100; }
        if ($n >= 20)      { $w .= $tens[intdiv($n, 10)] . ' '; $n %= 10; }
        if ($n > 0)        { $w .= $ones[$n] . ' '; }
        return $w;
    };

    $dollars     = (int) floor((float) $return->amount);
    $cents       = (int) round(((float) $return->amount - $dollars) * 100);
    $amountWords = trim($numToWords($dollars)) . ' Dollar' . ($dollars !== 1 ? 's' : '');
    if ($cents > 0) {
        $amountWords .= ' and ' . trim($numToWords($cents)) . ' Cent' . ($cents !== 1 ? 's' : '');
    }
    $amountWords .= ' only';

    $logoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? public_path($company->logo)
        : public_path('upload/waafibooklogo/waafibook_logo.jpg');
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'jpg');
    $logoB64 = file_exists($logoPath)
        ? 'data:image/' . $logoExt . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

<h1 class="page-title">Credit Note</h1>

<div class="outer-box">

    {{-- Company --}}
    <table>
        <tr>
            <td style="padding:12px 16px;">
                @if($logoB64)
                    <img src="{{ $logoB64 }}"
                         style="width:60px;height:60px;object-fit:contain;border:1px dashed #bbb;border-radius:4px;vertical-align:middle;margin-right:12px;">
                @endif
                <span style="display:inline-block;vertical-align:middle;">
                    <div style="font-size:20px;font-weight:bold;color:#1a1a2e;">{{ $company->name ?? '' }}</div>
                    <div style="font-size:12px;color:#555;margin-top:4px;">Phone:&nbsp;&nbsp;{{ $company->phone ?? '' }}</div>
                </span>
            </td>
        </tr>
    </table>

    {{-- Return From / Return Details --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:12px 16px;border-right:1px solid #666;">
                <div style="font-weight:bold;margin-bottom:8px;">Return From:</div>
                <div style="font-weight:bold;font-size:15px;">{{ strtoupper($return->customer->name ?? 'WALK-IN') }}</div>
                <div style="margin-top:5px;color:#444;">Contact No:&nbsp;&nbsp;<strong>{{ $return->customer->phone ?? 'N/A' }}</strong></div>
            </td>
            <td width="50%" style="padding:12px 16px;">
                <div style="font-weight:bold;margin-bottom:8px;">Return Details:</div>
                <div>Credit Note No.:&nbsp;&nbsp;<strong>{{ $return->credit_note_no }}</strong></div>
                <div style="margin-top:5px;">Date:&nbsp;&nbsp;<strong>{{ date('d/m/Y', strtotime($return->return_date)) }}</strong></div>
                @if($return->invoice)
                <div style="margin-top:5px;">Invoice No.:&nbsp;&nbsp;<strong>{{ $return->invoice->invoice_no ?? '' }}</strong></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    @if($return->items->count() > 0)
    <table class="row-border-top">
        <thead class="items-thead">
            <tr>
                <th style="width:2.5rem;">#</th>
                <th>Item Name</th>
                <th class="text-right" style="width:6rem;">Qty</th>
                <th class="text-right" style="width:8rem;">Unit Price</th>
                <th class="text-right" style="width:8rem;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $idx => $item)
            <tr class="items-row">
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->product->product_name ?? '—' }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">$ {{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">$ {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="items-total">
                <td colspan="2" class="font-bold">Total</td>
                <td class="text-right">{{ number_format($return->items->sum('quantity'), 2) }}</td>
                <td></td>
                <td class="text-right">$ {{ number_format($return->amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Sub Total / Total --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 16px;width:50%;">Sub Total</td>
            <td style="padding:8px 16px;text-align:center;width:10%;">:</td>
            <td style="padding:8px 16px;text-align:right;width:40%;">$ {{ number_format($return->amount, 2) }}</td>
        </tr>
    </table>
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 16px;width:50%;font-weight:bold;">Total</td>
            <td style="padding:8px 16px;text-align:center;width:10%;">:</td>
            <td style="padding:8px 16px;text-align:right;width:40%;font-weight:bold;">$ {{ number_format($return->amount, 2) }}</td>
        </tr>
    </table>

    {{-- Amount in Words label --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 16px;font-weight:bold;background:#f5f5f5;"><strong>Amount in Words:</strong></td>
        </tr>
    </table>

    {{-- Amount in Words value --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:10px 16px;">{{ $amountWords }}</td>
        </tr>
    </table>

    {{-- Paid / Balance --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 16px;width:50%;">Paid</td>
            <td style="padding:8px 16px;text-align:center;width:10%;">:</td>
            <td style="padding:8px 16px;text-align:right;width:40%;">$ 0.00</td>
        </tr>
    </table>
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 16px;width:50%;font-weight:bold;">Balance</td>
            <td style="padding:8px 16px;text-align:center;width:10%;">:</td>
            <td style="padding:8px 16px;text-align:right;width:40%;font-weight:bold;">$ {{ number_format($return->amount, 2) }}</td>
        </tr>
    </table>

    {{-- Authorized Signatory --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:16px;"></td>
            <td width="50%" style="padding:16px;">
                <div style="border:1px solid #666;padding:12px 14px;">
                    <div style="font-weight:bold;margin-bottom:65px;">For {{ $company->name ?? '' }}:</div>
                    <div style="text-align:center;font-size:11px;color:#555;border-top:1px solid #ccc;padding-top:6px;">
                        Authorized Signatory
                    </div>
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
