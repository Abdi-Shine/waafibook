@extends('admin.admin_master')
@section('page_title', 'Invoice Detail')
@section('admin')

@php
    $currencySymbols = [
        'USD' => '$', 'SAR' => 'SAR', 'SOS' => 'SOS', 'EUR' => '€', 'GBP' => '£',
    ];
    $symbol = '$'; // Force Dollar

    $statusColor = match($order->status) {
        'completed' => 'bg-accent/10 text-accent border-accent/20',
        'partial'   => 'bg-primary/10 text-primary border-primary/20',
        default     => 'bg-primary/10 text-primary border-primary/20',
    };
    $statusLabel = match($order->status) {
        'completed' => 'PAID',
        'partial'   => 'PARTIAL',
        default     => 'UNPAID',
    };
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.invoice.view') }}"
               class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all bg-white shadow-sm">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-[20px] font-black text-primary-dark tracking-tight">{{ $order->invoice_no }}</h1>
                <p class="text-[11px] font-bold text-gray-400">Invoice Detail</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.invoice.pdf', $order->id) }}" target="_blank"
               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-bold rounded-lg hover:bg-gray-50 transition-all text-xs shadow-sm">
                <i class="bi bi-file-earmark-pdf text-primary"></i> Download PDF
            </a>
            <a href="{{ route('sales.invoice.edit', $order->id) }}"
               class="flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-xs shadow-sm">
                <i class="bi bi-pencil"></i> Edit Invoice
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Left Column: Invoice Card ─────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Invoice Header --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                {{-- Brand Header Bar --}}
                <div class="px-6 py-5 flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-primary to-primary/80">
                    <div class="flex items-center gap-3">
                        @php
                            $invLogoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
                                ? asset($company->logo)
                                : asset('upload/waafibooklogo/waafibook_logo.jpg');
                        @endphp
                        <img src="{{ $invLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}" class="h-10 w-10 rounded-lg object-contain bg-white p-0.5">
                        <div>
                            <h2 class="text-[15px] font-black text-white leading-tight">{{ $company->name ?? '' }}</h2>
                            <p class="text-[10px] text-white/70 font-semibold">{{ $company->phone ?? '' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-white/60 uppercase tracking-wider">Invoice</p>
                        <p class="text-[16px] font-black text-white">{{ $order->invoice_no }}</p>
                    </div>
                </div>

                {{-- Customer + Invoice Meta --}}
                <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100">
                    <div class="px-6 py-5">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Bill To</p>
                        <p class="text-[14px] font-black text-primary-dark leading-tight">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
                        @if($order->customer?->phone)
                        <p class="text-[11px] font-bold text-gray-400 mt-1">📞 {{ $order->customer->phone }}</p>
                        @endif
                        @if($order->customer?->email)
                        <p class="text-[11px] font-bold text-gray-400">✉️ {{ $order->customer->email }}</p>
                        @endif
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Invoice Details</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-bold">Date:</span>
                                <span class="font-black text-primary-dark">{{ \Carbon\Carbon::parse($order->invoice_date)->format('d M, Y') }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-bold">Branch:</span>
                                <span class="font-black text-primary-dark">{{ $order->branch->name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-bold">Payment:</span>
                                <span class="font-black text-primary-dark">{{ $order->payment_method ?? 'Cash' }}</span>
                            </div>
                            <div class="flex justify-between text-[11px]">
                                <span class="text-gray-400 font-bold">Status:</span>
                                <span class="px-2 py-0.5 rounded-full border text-[10px] font-black {{ $statusColor }}">{{ $statusLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <i class="bi bi-list-ul text-primary text-sm"></i>
                    <h3 class="text-xs font-black text-primary-dark uppercase tracking-wider">Line Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider">#</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider">Item</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center">Qty</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center">Unit</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Unit Price</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Discount</th>
                                <th class="px-5 py-3 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($order->items as $i => $item)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-4 text-[12px] font-black text-primary-dark">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[12px] font-black text-primary-dark">{{ $item->product_name }}</span>
                                        @if($item->product_code)
                                        <span class="text-[10px] font-bold text-gray-400">Code: {{ $item->product_code }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center text-[12px] font-black text-primary-dark">{{ $item->quantity }}</td>
                                <td class="px-5 py-3.5 text-center text-[12px] font-black text-primary-dark">{{ $item->unit ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-right text-[12px] font-black text-primary-dark">{{ $symbol }} {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-5 py-3.5 text-right text-[12px] font-black text-primary-dark">
                                    {{ $item->discount > 0 ? $symbol . ' ' . number_format($item->discount, 2) : '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-[12px] font-black text-primary-dark">{{ $symbol }} {{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            @if($order->notes)
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Notes</p>
                <p class="text-[12px] font-bold text-gray-600 leading-relaxed">{{ $order->notes }}</p>
            </div>
            @endif
        </div>

        {{-- ── Right Column: Summary ─────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Financial Summary --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <i class="bi bi-graph-up text-primary text-sm"></i>
                    <h3 class="text-xs font-black text-primary-dark uppercase tracking-wider">Summary</h3>
                </div>
                <div class="px-5 pb-5 space-y-3">
                    @if(($order->discount ?? 0) > 0)
                    <div class="flex justify-between items-center text-[12px] pt-5">
                        <span class="font-bold text-gray-400">Discount</span>
                        <span class="font-black text-primary">- {{ $symbol }} {{ number_format($order->discount, 2) }}</span>
                    </div>
                    @endif
                    @if(($order->tax ?? 0) > 0)
                    <div class="flex justify-between items-center text-[12px] pt-5">
                        <span class="font-bold text-gray-400">Tax</span>
                        <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($order->tax, 2) }}</span>
                    </div>
                    @endif
                    <div class="pt-3">
                        <div class="bg-primary rounded-lg px-4 py-3 flex justify-between items-center">
                            <span class="text-[13px] font-black text-white uppercase tracking-wider">Total</span>
                            <span class="text-[18px] font-black text-accent">{{ $symbol }} {{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-[12px] pt-1">
                        <span class="font-bold text-gray-400">Amount Paid</span>
                        <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($order->paid_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[12px] bg-{{ $order->due_amount > 0 ? 'rose' : 'gray' }}-50 rounded-lg px-3 py-2.5">
                        <span class="font-black text-{{ $order->due_amount > 0 ? 'rose-600' : 'gray-500' }} uppercase tracking-tight text-[11px]">Balance Due</span>
                        <span class="font-black text-{{ $order->due_amount > 0 ? 'rose-600' : 'primary-dark' }}">{{ $symbol }} {{ number_format($order->due_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Meta Info --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <i class="bi bi-info-circle text-primary text-sm"></i>
                    <h3 class="text-xs font-black text-primary-dark uppercase tracking-wider">Record Info</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="font-bold text-gray-400">Created By</span>
                        <span class="font-black text-primary-dark">{{ $order->creator->name ?? 'System' }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="font-bold text-gray-400">Created On</span>
                        <span class="font-black text-primary-dark">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="font-bold text-gray-400">Last Updated</span>
                        <span class="font-black text-primary-dark">{{ $order->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="font-bold text-gray-400">Items Count</span>
                        <span class="font-black text-primary-dark">{{ $order->items->count() }} item(s)</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 space-y-2">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-3">Quick Actions</p>
                <a href="{{ route('sales.invoice.pdf', $order->id) }}" target="_blank"
                   class="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-primary/10 hover:border-sky-200 transition-all group w-full">
                    <i class="bi bi-file-earmark-pdf text-primary text-base"></i>
                    <span class="text-[12px] font-black text-primary-dark group-hover:text-primary transition-colors">Download PDF Invoice</span>
                </a>
                <a href="{{ route('sales.invoice.edit', $order->id) }}"
                   class="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-primary/10 hover:border-indigo-200 transition-all group w-full">
                    <i class="bi bi-pencil text-primary text-base"></i>
                    <span class="text-[12px] font-black text-primary-dark group-hover:text-primary transition-colors">Edit This Invoice</span>
                </a>
                <a href="{{ route('sales.invoice.view') }}"
                   class="flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-primary/5 hover:border-primary/20 transition-all group w-full">
                    <i class="bi bi-list-ul text-primary text-base"></i>
                    <span class="text-[12px] font-black text-primary-dark group-hover:text-primary transition-colors">Back to Invoices</span>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

