<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice_no }} — {{ $company->name ?? 'WaafiBook' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-white min-h-screen">

@php
    $symbol = '$'; // Force Dollar — matches sales_invoice_detail_pwa.blade.php
    $invLogoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? asset($company->logo)
        : asset('upload/waafibooklogo/waafibook_logo.jpg');
    $subTotal = $order->items->sum('total_price');
@endphp

<div class="pb-10 max-w-lg mx-auto px-5">

    {{-- Company Header --}}
    <div class="flex items-start justify-between pt-6">
        <div class="flex items-start gap-2.5 min-w-0">
            <img src="{{ $invLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}" class="h-9 w-9 rounded-md object-contain shrink-0">
            <div class="min-w-0">
                <h2 class="text-[13px] font-black text-gray-900 leading-tight">{{ $company->name ?? '' }}</h2>
                <p class="text-[11px] text-gray-400 font-semibold mt-0.5">{{ $company->phone ?? '' }}</p>
            </div>
        </div>
        <a href="{{ $pdfUrl }}" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 shrink-0">
            <i class="bi bi-download text-sm"></i>
        </a>
    </div>

    <h1 class="text-center text-[18px] font-black text-gray-900 mt-4">Invoice</h1>

    {{-- Bill To / Invoice Details --}}
    <div class="flex justify-between gap-4 mt-5">
        <div class="min-w-0">
            <p class="text-[11px] text-gray-400 font-semibold">Bill To:</p>
            <p class="text-[13px] font-black text-gray-900 mt-1">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
            @if($order->customer?->phone)
                <p class="text-[11px] text-gray-400 font-semibold mt-0.5">{{ $order->customer->phone }}</p>
            @endif
        </div>
        <div class="text-right shrink-0">
            <p class="text-[11px] text-gray-400 font-semibold">Invoice No.</p>
            <p class="text-[13px] font-black text-gray-900 mt-1">{{ $order->invoice_no }}</p>
            <p class="text-[11px] text-gray-400 font-semibold mt-2">Date: <span class="text-gray-900 font-bold">{{ \Carbon\Carbon::parse($order->invoice_date)->format('d M, Y') }}</span></p>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="mt-5 rounded-xl border border-gray-200 overflow-hidden">
        @foreach($order->items as $item)
            <div class="px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                <p class="text-[13px] font-black text-primary mb-2">{{ $item->product_name }}</p>
                <div class="grid grid-cols-4 gap-2">
                    <div>
                        <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide">Quantity</p>
                        <p class="text-[12px] font-bold text-gray-800 mt-0.5">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} {{ $item->unit ?? 'Unit' }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide">Price/Unit</p>
                        <p class="text-[12px] font-bold text-gray-800 mt-0.5">{{ $symbol }}{{ number_format($item->unit_price, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide">Tax</p>
                        <p class="text-[12px] font-bold text-gray-800 mt-0.5">--</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide">Amount</p>
                        <p class="text-[12px] font-bold text-gray-800 mt-0.5">{{ $symbol }}{{ number_format($item->total_price, 2) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pricing / Breakup --}}
    <div class="mt-4 rounded-xl border border-gray-200 p-4">
        <p class="text-[13px] font-black text-gray-900 mb-3">Pricing / Breakup</p>
        <div class="space-y-2.5">
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-gray-400 font-semibold">Sub Total</span>
                <span class="font-bold text-gray-800">{{ $symbol }}{{ number_format($subTotal, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-primary font-black">Total Amount</span>
                <span class="font-black text-primary">{{ $symbol }}{{ number_format($order->total_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-gray-400 font-semibold">Received Amount</span>
                <span class="font-bold text-gray-800">{{ $symbol }}{{ number_format($order->paid_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-gray-400 font-semibold">Transaction Balance</span>
                <span class="font-bold text-gray-800">{{ $symbol }}{{ number_format($order->due_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-gray-400 font-semibold">Previous Balance</span>
                <span class="font-bold text-gray-800">{{ $symbol }}{{ number_format($previousBalance, 2) }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="text-gray-400 font-semibold">Current Balance</span>
                <span class="font-bold text-gray-800">{{ $symbol }}{{ number_format($currentBalance, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($order->notes)
        <p class="text-[12px] text-gray-600 font-medium mt-4">{{ $order->notes }}</p>
    @endif

    <div class="border-t border-gray-100 mt-6 pt-3">
        <p class="text-[11px] text-gray-400">
            <span class="font-black text-gray-600">Terms &amp; Conditions</span> : Thanks for doing business with us!
        </p>
    </div>

</div>
</body>
</html>
