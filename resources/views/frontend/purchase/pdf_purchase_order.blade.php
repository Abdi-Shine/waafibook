<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $po->po_number }}</title>
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
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 14px;
            color: #1a1a2e;
        }
        .outer-box { border: 1px solid #999; width: 100%; }
        .row-border-top { border-top: 1px solid #999; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 12px; vertical-align: top; }
        th { background: #e8e8e8; font-weight: bold; text-align: left; padding: 7px 12px; font-size: 12px; }
        .label-cell { background: #e8e8e8; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .items-thead th { border-bottom: 1px solid #999; }
        .items-row td { border-bottom: 1px solid #ddd; }
        .items-total td { border-top: 1px solid #999; font-weight: bold; background: #f5f5f5; }
        .section-label { background: #e8e8e8; font-weight: bold; padding: 6px 12px; }
        .section-value { padding: 8px 12px; }
    </style>
</head>
<body>

@php
    /* Amount in words helper */
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

    $total   = (float) $po->total_amount;
    $dollars = (int) floor($total);
    $cents   = (int) round(($total - $dollars) * 100);
    $amountWords = trim($numToWords($dollars)) . ' Dollar' . ($dollars !== 1 ? 's' : '');
    if ($cents > 0) {
        $amountWords .= ' and ' . trim($numToWords($cents)) . ' Cent' . ($cents !== 1 ? 's' : '');
    }
    $amountWords .= ' only';

    /* Logo */
    $logoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? public_path($company->logo)
        : public_path('upload/waafibooklogo/waafibook_logo.jpg');
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'jpg');
    $logoB64 = file_exists($logoPath)
        ? 'data:image/' . $logoExt . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

<h1 class="page-title">Purchase Order</h1>

<div class="outer-box">

    {{-- Company Header --}}
    <table>
        <tr>
            <td style="padding:12px 16px;">
                @if($logoB64)
                    <img src="{{ $logoB64 }}"
                         style="width:64px;height:64px;object-fit:contain;border:1px dashed #bbb;border-radius:4px;vertical-align:middle;margin-right:12px;">
                @endif
                <span style="display:inline-block;vertical-align:middle;">
                    <div style="font-size:22px;font-weight:bold;color:#1a1a2e;">{{ $company->name ?? '' }}</div>
                    <div style="font-size:12px;color:#555;margin-top:4px;">Phone: &nbsp;<strong>{{ $company->phone ?? '' }}</strong></div>
                </span>
            </td>
        </tr>
    </table>

    {{-- Order To / Order Details --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:0;border-right:1px solid #999;">
                <div class="section-label">Order To:</div>
                <div style="padding:8px 12px;font-weight:bold;">{{ $po->supplier->name ?? 'N/A' }}</div>
                @if(!empty($po->supplier->phone))
                <div style="padding:0 12px 8px;font-size:12px;color:#444;">{{ $po->supplier->phone }}</div>
                @endif
            </td>
            <td width="50%" style="padding:0;">
                <div class="section-label">Order Details:</div>
                <div style="padding:8px 12px;">
                    <div>Order No.: &nbsp;<strong>{{ $po->po_number }}</strong></div>
                    <div style="margin-top:4px;">Date: &nbsp;<strong>{{ date('m/d/Y', strtotime($po->order_date)) }}</strong></div>
                    <div style="margin-top:4px;">Due Date: &nbsp;<strong>{{ $po->expected_delivery ? date('m/d/Y', strtotime($po->expected_delivery)) : date('m/d/Y', strtotime($po->order_date)) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="row-border-top">
        <thead class="items-thead">
            <tr>
                <th style="width:2.5rem;">#</th>
                <th>Item name</th>
                <th class="text-right" style="width:8rem;">Quantity</th>
                <th class="text-right" style="width:9rem;">Price/ Unit($)</th>
                <th class="text-right" style="width:9rem;">Amount($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $idx => $item)
            <tr class="items-row">
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->product->product_name ?? ($item->product_name ?? '—') }}</td>
                <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                <td class="text-right">$ {{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">$ {{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="items-total">
                <td></td>
                <td class="font-bold">Total</td>
                <td class="text-right">{{ number_format($po->items->sum('quantity'), 0) }}</td>
                <td></td>
                <td class="text-right">$ {{ number_format($po->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Sub Total --}}
    <table class="row-border-top">
        <tr>
            <td style="width:50%;">Sub Total</td>
            <td style="width:10%;text-align:center;">:</td>
            <td style="width:40%;text-align:right;">$ {{ number_format($po->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Total --}}
    <table class="row-border-top">
        <tr>
            <td style="width:50%;font-weight:bold;">Total</td>
            <td style="width:10%;text-align:center;">:</td>
            <td style="width:40%;text-align:right;font-weight:bold;">$ {{ number_format($po->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Amount in Words Label --}}
    <table class="row-border-top">
        <tr>
            <td class="label-cell"><strong>Order Amount in Words:</strong></td>
        </tr>
    </table>

    {{-- Amount in Words Value --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 12px;color:#004161;">{{ $amountWords }}</td>
        </tr>
    </table>

    {{-- Advance --}}
    <table class="row-border-top">
        <tr>
            <td style="width:50%;">Advance</td>
            <td style="width:10%;text-align:center;">:</td>
            <td style="width:40%;text-align:right;">$ 0.00</td>
        </tr>
    </table>

    {{-- Balance --}}
    <table class="row-border-top">
        <tr>
            <td style="width:50%;font-weight:bold;">Balance</td>
            <td style="width:10%;text-align:center;">:</td>
            <td style="width:40%;text-align:right;font-weight:bold;">$ {{ number_format($po->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Terms & Conditions Label --}}
    <table class="row-border-top">
        <tr>
            <td class="label-cell"><strong>Terms &amp; Conditions:</strong></td>
        </tr>
    </table>

    {{-- Terms & Conditions Value --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 12px;">{{ $po->notes ?: 'Thanks for doing business with us!' }}</td>
        </tr>
    </table>

    {{-- Authorized Signatory --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:16px;"></td>
            <td width="50%" style="padding:12px;">
                <div style="border:1px solid #999;padding:12px 14px;">
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
