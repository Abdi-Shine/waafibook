<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice_no }} — {{ $company->name ?? 'WaafiBook' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-background min-h-screen">

@php
    $symbol = '$'; // Force Dollar — matches sales_invoice_detail_pwa.blade.php
    $statusColor = match($order->status) {
        'completed' => 'bg-accent/10 text-accent',
        'partial' => 'bg-yellow-50 text-yellow-600',
        default => 'bg-red-50 text-red-500',
    };
    $statusLabel = match($order->status) {
        'completed' => 'Paid',
        'partial' => 'Partial',
        default => 'Unpaid',
    };
    $invLogoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? asset($company->logo)
        : asset('upload/waafibooklogo/waafibook_logo.jpg');
@endphp

<div class="pb-10 max-w-lg mx-auto">

    <div class="px-5 pt-6 pb-2 text-center">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Invoice From</p>
        <h1 class="text-[18px] font-black text-primary-dark mt-1">{{ $company->name ?? 'WaafiBook' }}</h1>
    </div>

    <div class="flex gap-2 px-5 pt-3">
        <a href="{{ $pdfUrl }}"
            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-white border border-gray-200 text-primary-dark font-bold rounded-xl text-[12px] shadow-sm">
            <i class="bi bi-download text-primary"></i> Download PDF
        </a>
    </div>

    {{-- Invoice Header --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-4 flex items-center justify-between bg-gradient-to-r from-primary to-primary/80">
            <div class="flex items-center gap-2.5 min-w-0">
                <img src="{{ $invLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5 shrink-0">
                <div class="min-w-0">
                    <h2 class="text-[13px] font-black text-white leading-tight truncate">{{ $company->name ?? '' }}</h2>
                    <p class="text-[10px] text-white/70 font-semibold truncate">{{ $company->phone ?? '' }}</p>
                </div>
            </div>
            <div class="text-right shrink-0 pl-2">
                <p class="text-[9px] font-black text-white/60 uppercase tracking-wider">Invoice</p>
                <p class="text-[13px] font-black text-white">{{ $order->invoice_no }}</p>
            </div>
        </div>

        <div class="px-4 py-4 border-b border-gray-100">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Bill To</p>
            <p class="text-[14px] font-black text-primary-dark leading-tight">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
            @if($order->customer?->phone)
                <p class="text-[11px] font-bold text-gray-400 mt-1">📞 {{ $order->customer->phone }}</p>
            @endif
            @if($order->customer?->email)
                <p class="text-[11px] font-bold text-gray-400">✉️ {{ $order->customer->email }}</p>
            @endif
        </div>

        <div class="px-4 py-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Invoice Details</p>
            <div class="space-y-1.5">
                <div class="flex justify-between text-[12px]">
                    <span class="text-gray-400 font-bold">Date:</span>
                    <span class="font-black text-primary-dark">{{ \Carbon\Carbon::parse($order->invoice_date)->format('d M, Y') }}</span>
                </div>
                <div class="flex justify-between text-[12px]">
                    <span class="text-gray-400 font-bold">Payment:</span>
                    <span class="font-black text-primary-dark">{{ $order->payment_method ?? 'Cash' }}</span>
                </div>
                <div class="flex justify-between items-center text-[12px]">
                    <span class="text-gray-400 font-bold">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Line Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left" style="min-width: 480px;">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-10 text-center border-r border-gray-100">#</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100" style="min-width: 160px;">Item</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-16 text-center border-r border-gray-100">Qty</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-24 text-right border-r border-gray-100">Price</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-24 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $i => $item)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-center border-r border-gray-100">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-3 py-3 border-r border-gray-100">
                                <p class="text-[13px] font-black text-primary-dark">{{ $item->product_name }}</p>
                                @if($item->product_code)
                                    <p class="text-[10px] text-gray-400 font-semibold">Code: {{ $item->product_code }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-center border-r border-gray-100">{{ $item->quantity }}</td>
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-right border-r border-gray-100">{{ $symbol }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-right">{{ $symbol }} {{ number_format($item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    @if($order->notes)
        <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Notes</p>
            <p class="text-[13px] font-bold text-gray-600 leading-relaxed whitespace-pre-line">{{ $order->notes }}</p>
        </div>
    @endif

    {{-- Summary --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-graph-up text-primary-dark text-sm"></i>
            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Summary</h2>
        </div>
        <div class="px-4 py-4 space-y-3">
            @if(($order->discount ?? 0) > 0)
                <div class="flex justify-between items-center text-[13px]">
                    <span class="font-bold text-gray-400">Discount</span>
                    <span class="font-black text-primary">- {{ $symbol }} {{ number_format($order->discount, 2) }}</span>
                </div>
            @endif
            @if(($order->tax ?? 0) > 0)
                <div class="flex justify-between items-center text-[13px]">
                    <span class="font-bold text-gray-400">Tax</span>
                    <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($order->tax, 2) }}</span>
                </div>
            @endif

            <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between">
                <span class="text-[11px] font-black text-white uppercase tracking-wider">Total</span>
                <span class="text-[16px] font-black text-accent">{{ $symbol }} {{ number_format($order->total_amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center text-[13px]">
                <span class="font-bold text-gray-400">Amount Paid</span>
                <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($order->paid_amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center px-3 py-2.5 rounded-lg {{ $order->due_amount > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
                <span class="font-black uppercase tracking-tight text-[11px] {{ $order->due_amount > 0 ? 'text-red-500' : 'text-gray-500' }}">Balance Due</span>
                <span class="font-black {{ $order->due_amount > 0 ? 'text-red-500' : 'text-primary-dark' }}">{{ $symbol }} {{ number_format($order->due_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <p class="text-center text-[11px] text-gray-400 mt-6 px-6">
        Thank you for your business!<br>
        This is a computer-generated invoice and does not require a signature.
    </p>

</div>
</body>
</html>
