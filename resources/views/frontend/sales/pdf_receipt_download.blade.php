<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $payment->receipt_no ?? $payment->id }}</title>
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

    $dollars     = (int) floor((float) $payment->amount);
    $cents       = (int) round(((float) $payment->amount - $dollars) * 100);
    $amountWords = trim($numToWords($dollars)) . ' Dollar' . ($dollars !== 1 ? 's' : '');
    if ($cents > 0) {
        $amountWords .= ' and ' . trim($numToWords($cents)) . ' Cent' . ($cents !== 1 ? 's' : '');
    }
    $amountWords .= ' only';

    $symbol = '$';

    $logoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? public_path($company->logo)
        : public_path('upload/waafibooklogo/waafibook_logo.jpg');
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'jpg');
    $logoB64 = file_exists($logoPath)
        ? 'data:image/' . $logoExt . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

<h1 class="page-title">Payment Receipt</h1>

<div class="outer-box">

    {{-- Company --}}
    <table>
        <tr>
            <td style="padding:12px 16px;">
                @if($logoB64)
                    <img src="{{ $logoB64 }}"
                         style="width:64px;height:64px;object-fit:contain;border:1px dashed #bbb;border-radius:6px;vertical-align:middle;margin-right:14px;">
                @endif
                <span style="display:inline-block;vertical-align:middle;">
                    <div style="font-size:22px;font-weight:bold;color:#1a1a2e;">{{ $company->name ?? '' }}</div>
                    <div style="font-size:12px;color:#555;margin-top:5px;">Phone:&nbsp;&nbsp;{{ $company->phone ?? '' }}</div>
                </span>
            </td>
        </tr>
    </table>

    {{-- Received From / Receipt Details --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:12px 16px;vertical-align:top;border-right:1px solid #666;">
                <div style="font-weight:bold;margin-bottom:8px;">Received From:</div>
                <div style="font-weight:bold;font-size:15px;">{{ strtoupper($payment->customer->name ?? 'WALK-IN') }}</div>
                <div style="margin-top:5px;color:#444;">Contact No:&nbsp;&nbsp;<strong>{{ $payment->customer->phone ?? 'N/A' }}</strong></div>
            </td>
            <td width="50%" style="padding:12px 16px;vertical-align:top;">
                <div style="font-weight:bold;margin-bottom:8px;">Receipt Details:</div>
                <div>Receipt No.:&nbsp;&nbsp;<strong>{{ $payment->id }}</strong></div>
                <div style="margin-top:5px;">Date:&nbsp;&nbsp;<strong>{{ date('d/m/Y', strtotime($payment->payment_date)) }}</strong></div>
            </td>
        </tr>
    </table>

    {{-- Received Amount --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:10px 16px;width:50%;">Received</td>
            <td style="padding:10px 16px;text-align:center;width:10%;">:</td>
            <td style="padding:10px 16px;text-align:right;font-weight:bold;width:40%;">{{ $symbol }}&nbsp;{{ number_format($payment->amount, 2) }}</td>
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
