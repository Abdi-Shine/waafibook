@extends('admin.admin_master')
@section('page_title', 'Invoice Detail')

@php
    $symbol = '$'; // Force Dollar — matches desktop sales_invoice_detail.blade.php
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

@section('admin')
<div class="pb-28 bg-background min-h-screen">

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('sales.invoice.view') }}"
                class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 bg-white shadow-sm shrink-0">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-[16px] font-black text-primary-dark truncate">{{ $order->invoice_no }}</h1>
                <p class="text-[11px] font-bold text-gray-400">Invoice Detail</p>
            </div>
        </div>
    </div>

    <div class="flex gap-2 px-5 pt-3">
        <a href="{{ route('sales.invoice.pdf', $order->id) }}" target="_blank"
            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-white border border-gray-200 text-primary-dark font-bold rounded-xl text-[12px]">
            <i class="bi bi-file-earmark-pdf text-primary"></i> Download PDF
        </a>
        <a href="{{ route('sales.invoice.edit', $order->id) }}"
            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-primary text-white font-bold rounded-xl text-[12px]">
            <i class="bi bi-pencil"></i> Edit Invoice
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
                    <span class="text-gray-400 font-bold">Branch:</span>
                    <span class="font-black text-primary-dark">{{ $order->branch->name ?? '—' }}</span>
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
            <table class="w-full text-left" style="min-width: 560px;">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-10 text-center border-r border-gray-100">#</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100" style="min-width: 180px;">Item</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-16 text-center border-r border-gray-100">Qty</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-20 text-center border-r border-gray-100">Unit</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-24 text-right border-r border-gray-100">Price</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-20 text-right border-r border-gray-100">Discount</th>
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
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-center border-r border-gray-100">{{ $item->unit ?? '—' }}</td>
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-right border-r border-gray-100">{{ $symbol }} {{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-3 text-[12px] font-black text-primary-dark text-right border-r border-gray-100">
                                {{ $item->discount > 0 ? $symbol . ' ' . number_format($item->discount, 2) : '—' }}
                            </td>
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

    {{-- Record Info --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-info-circle text-primary-dark text-sm"></i>
            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Record Info</h2>
        </div>
        <div class="px-4 py-4 space-y-3">
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Created By</span>
                <span class="font-black text-primary-dark">{{ $order->creator->name ?? 'System' }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Created On</span>
                <span class="font-black text-primary-dark">{{ $order->created_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Last Updated</span>
                <span class="font-black text-primary-dark">{{ $order->updated_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Items Count</span>
                <span class="font-black text-primary-dark">{{ $order->items->count() }} item(s)</span>
            </div>
        </div>
    </div>
</div>
@endsection
