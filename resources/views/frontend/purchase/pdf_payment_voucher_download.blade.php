<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Voucher - {{ $payment->voucher_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #fff;
            padding: 14mm 16mm;
        }
        .title-row {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            padding: 10px 0 14px;
            border-bottom: 1px solid #ccc;
            margin-bottom: 0;
        }
        .outer-box { border: 1px solid #ccc; width: 100%; }
        .row-border-top { border-top: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 14px; vertical-align: top; }
        .label-bg { background: #f0f0f0; font-weight: bold; padding: 6px 14px; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .green { color: #16a34a; }
        .yellow { color: #ca8a04; }
        .small { font-size: 11px; color: #555; }
        .sig-box { border: 1px solid #ccc; padding: 12px 14px; }
        .sig-line { border-top: 1px solid #ccc; margin-top: 55px; padding-top: 6px; text-align: center; font-size: 11px; color: #555; }
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

    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');

    $logoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? public_path($company->logo)
        : public_path('upload/waafibooklogo/waafibook_logo.jpg');
    $logoExt  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'jpg');
    $logoB64  = file_exists($logoPath)
        ? 'data:image/' . $logoExt . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

{{-- Title --}}
<div class="title-row">Payment Voucher</div>

<div class="outer-box">

    {{-- Company --}}
    <table>
        <tr>
            <td style="padding:12px 14px;">
                @if($logoB64)
                    <img src="{{ $logoB64 }}"
                         style="width:60px;height:60px;object-fit:contain;border:1px dashed #bbb;border-radius:4px;vertical-align:middle;margin-right:10px;">
                @endif
                <span style="display:inline-block;vertical-align:middle;">
                    <div style="font-size:20px;font-weight:bold;">{{ $company->name ?? '' }}</div>
                    <div class="small" style="margin-top:4px;">Phone: {{ $company->phone ?? '' }}</div>
                </span>
            </td>
        </tr>
    </table>

    {{-- Pay To / Voucher Details --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:0;border-right:1px solid #ccc;">
                <div class="label-bg">Pay To (Supplier):</div>
                <div style="padding:8px 14px;font-weight:bold;font-size:14px;">{{ strtoupper($payment->supplier->name ?? 'N/A') }}</div>
                <div style="padding:0 14px 4px;" class="small">Phone: {{ $payment->supplier->phone ?? 'N/A' }}</div>
                <div style="padding:0 14px 8px;" class="small">Method: {{ $payment->payment_method ?? 'Cash' }}</div>
            </td>
            <td width="50%" style="padding:0;">
                <div class="label-bg">Voucher Details:</div>
                <div style="padding:8px 14px;">
                    <div>Voucher No.: &nbsp;<strong>{{ $payment->voucher_no }}</strong></div>
                    <div style="margin-top:4px;">Date: &nbsp;<strong>{{ date('d/m/Y', strtotime($payment->payment_date)) }}</strong></div>
                    <div style="margin-top:4px;">Status: &nbsp;<strong class="{{ $payment->status === 'completed' ? 'green' : 'yellow' }}">{{ ucfirst($payment->status) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Amount Paid --}}
    <table class="row-border-top">
        <tr>
            <td style="width:50%;">Amount Paid</td>
            <td style="width:10%;text-align:center;">:</td>
            <td style="width:40%;text-align:right;" class="bold">{{ $symbol }} {{ number_format($payment->amount, 2) }}</td>
        </tr>
    </table>

    {{-- Amount in Words label --}}
    <table class="row-border-top">
        <tr>
            <td class="label-bg"><strong>Amount in Words:</strong></td>
        </tr>
    </table>

    {{-- Amount in Words value --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 14px;color:#004161;">{{ $amountWords }}</td>
        </tr>
    </table>

    @if($payment->notes)
    {{-- Notes label --}}
    <table class="row-border-top">
        <tr>
            <td class="label-bg"><strong>Notes:</strong></td>
        </tr>
    </table>
    {{-- Notes value --}}
    <table class="row-border-top">
        <tr>
            <td style="padding:8px 14px;">{{ $payment->notes }}</td>
        </tr>
    </table>
    @endif

    {{-- Authorized Signatory --}}
    <table class="row-border-top">
        <tr>
            <td width="50%" style="padding:16px;"></td>
            <td width="50%" style="padding:12px;">
                <div class="sig-box">
                    <div class="bold" style="margin-bottom:0;">For {{ $company->name ?? '' }}:</div>
                    <div class="sig-line">Authorized Signatory</div>
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
