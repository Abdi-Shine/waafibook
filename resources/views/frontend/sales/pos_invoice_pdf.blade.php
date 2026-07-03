<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #222; font-size: 12px; margin: 24px; }
        .title { text-align: center; font-size: 26px; font-weight: bold; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        .outer { border: 1px solid #333; }
        .header-row td { border: 1px solid #333; padding: 10px 12px; vertical-align: top; }
        .logo-cell { width: 90px; }
        .logo-cell img { width: 70px; height: 70px; object-fit: contain; }
        .company-name { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .company-phone { font-size: 12px; }
        .section-label { font-weight: bold; background: #f1f1f1; padding: 6px 12px; border: 1px solid #333; border-bottom: none; font-size: 12px; }
        .section-value { padding: 8px 12px; border: 1px solid #333; border-top: none; font-size: 12px; }
        .two-col { width: 100%; }
        .two-col td { width: 50%; vertical-align: top; padding: 0; }
        .items-table th, .items-table td { border: 1px solid #333; padding: 6px 10px; font-size: 12px; }
        .items-table th { background: #f1f1f1; text-align: left; }
        .items-table .num { width: 28px; text-align: center; }
        .items-table .qty, .items-table .price, .items-table .amount { text-align: right; }
        .totals-row td { border: 1px solid #333; padding: 6px 10px; font-size: 12px; }
        .totals-row .label-col { text-align: left; }
        .totals-row .value-col { text-align: right; }
        .bold { font-weight: bold; }
        .summary-row td { padding: 4px 12px; font-size: 12px; }
        .summary-row .colon { width: 20px; text-align: left; }
        .summary-row .value { text-align: right; }
        .words-box { border: 1px solid #333; }
        .words-box .label { font-weight: bold; background: #f1f1f1; padding: 6px 12px; border-bottom: 1px solid #333; }
        .words-box .value { padding: 8px 12px; }
        .terms-box { border: 1px solid #333; margin-top: 14px; }
        .terms-box .label { font-weight: bold; background: #f1f1f1; padding: 6px 12px; border-bottom: 1px solid #333; }
        .terms-box .value { padding: 8px 12px; }
        .sign-table { margin-top: 14px; }
        .sign-table td { vertical-align: bottom; }
        .sign-box { border: 1px solid #333; height: 90px; padding: 8px 12px; }
        .sign-box .label { font-weight: bold; background: #f1f1f1; margin: -8px -12px 8px -12px; padding: 6px 12px; border-bottom: 1px solid #333; }
        .sign-box .signatory { text-align: center; margin-top: 40px; font-size: 12px; }
    </style>
</head>
<body>

<div class="title">Invoice</div>

<table class="outer">
    <tr class="header-row">
        <td class="logo-cell">
            @php
                $posLogoSrc = null;
                if (!empty($company->logo) && file_exists(public_path($company->logo))) {
                    $posLogoSrc = public_path($company->logo);
                } elseif (file_exists(public_path('upload/waafibooklogo/waafibook_logo.jpg'))) {
                    $posLogoSrc = public_path('upload/waafibooklogo/waafibook_logo.jpg');
                }
            @endphp
            @if($posLogoSrc)
                <img src="{{ $posLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}">
            @endif
        </td>
        <td>
            <div class="company-name">{{ $company->name ?? 'Your Company' }}</div>
            @if($company && $company->phone)
                <div class="company-phone">Phone: <strong>{{ $company->phone }}</strong></div>
            @endif
        </td>
    </tr>
</table>

<table class="two-col">
    <tr>
        <td style="padding-right: 6px;">
            <div class="section-label">Bill To:</div>
            <div class="section-value">{{ $order->customer->name ?? 'Cash Sale' }}</div>
        </td>
        <td style="padding-left: 6px;">
            <div class="section-label">Invoice Details:</div>
            <div class="section-value">
                Invoice No.: <strong>{{ $order->invoice_no }}</strong><br>
                Date: <strong>{{ \Carbon\Carbon::parse($order->invoice_date)->format('d/m/Y') }}</strong>
            </div>
        </td>
    </tr>
</table>

<table class="items-table" style="margin-top: 14px;">
    <thead>
        <tr>
            <th class="num">#</th>
            <th>Item name</th>
            <th class="qty">Quantity</th>
            <th class="price">Price/ Unit($)</th>
            <th class="amount">Amount($)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>{{ $item->product_name }}</td>
            <td class="qty">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="price">$ {{ number_format($item->unit_price, 2) }}</td>
            <td class="amount">$ {{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
        <tr class="bold">
            <td></td>
            <td>Total</td>
            <td class="qty">{{ rtrim(rtrim(number_format($order->items->sum('quantity'), 2), '0'), '.') }}</td>
            <td></td>
            <td class="amount">$ {{ number_format($order->subtotal, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals-row" style="margin-top: -1px;">
    <tr>
        <td class="label-col" style="width: 70%;">Sub Total</td>
        <td class="label-col" style="width: 5%;">:</td>
        <td class="value-col bold">$ {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if($order->discount > 0)
    <tr>
        <td class="label-col">Discount</td>
        <td class="label-col">:</td>
        <td class="value-col">- $ {{ number_format($order->discount, 2) }}</td>
    </tr>
    @endif
    <tr class="bold">
        <td class="label-col">Total</td>
        <td class="label-col">:</td>
        <td class="value-col">$ {{ number_format($order->total_amount, 2) }}</td>
    </tr>
</table>

<div class="words-box" style="margin-top: -1px;">
    <div class="label">Invoice Amount in Words:</div>
    <div class="value">{{ \App\Http\Controllers\SalesController::numberToWords($order->total_amount) }}</div>
</div>

<table class="totals-row" style="margin-top: 8px; border: none;">
    <tr>
        <td class="label-col" style="width: 70%; border: none;">Received</td>
        <td class="label-col" style="width: 5%; border: none;">:</td>
        <td class="value-col" style="border: none;">$ {{ number_format($order->paid_amount, 2) }}</td>
    </tr>
    <tr>
        <td class="label-col" style="border: none;">Balance</td>
        <td class="label-col" style="border: none;">:</td>
        <td class="value-col" style="border: none;">$ {{ number_format($order->due_amount, 2) }}</td>
    </tr>
</table>

<div class="terms-box">
    <div class="label">Terms &amp; Conditions:</div>
    <div class="value">{{ $order->notes ?: 'Thanks for doing business with us!' }}</div>
</div>

<table class="sign-table">
    <tr>
        <td style="width: 55%;"></td>
        <td style="width: 45%;">
            <div class="sign-box">
                <div class="label">For {{ $company->name ?? 'Your Company' }}:</div>
                <div class="signatory">Authorized Signatory</div>
            </div>
        </td>
    </tr>
</table>

</body>
</html>