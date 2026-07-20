@extends('admin.admin_master')
@section('page_title', 'Sales Invoices')

@php
    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
        'AUD' => 'A$', 'CAD' => 'C$', 'CHF' => 'Fr', 'CNY' => '¥',
        'INR' => '₹', 'MYR' => 'RM', 'SGD' => 'S$', 'AED' => 'د.إ',
        'SAR' => '﷼', 'NGN' => '₦', 'KES' => 'KSh', 'ZAR' => 'R',
    ];
    $symbol = $currencySymbols[$company->currency ?? 'USD'] ?? ($company->currency ?? '$');
    $canDelete = auth()->user()->hasPermission('Sales & POS', 'delete');
@endphp

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    sendWhatsApp(invoiceNo, customer, phone, total, pdfUrl, companyName, date, companyPhone, companyEmail) {
        let message = `*${companyName}*\n\n`;
        message += `*Qaansheegta Iibka*\n`;
        message += `${total}\n`;
        message += `taariikhda ${date}\n\n`;
        message += `Salaam ${customer},\n`;
        message += `Kani waa qaansheegtaada ${invoiceNo}. Halkan ka eeg: ${pdfUrl}\n\n`;
        message += `Mahadsanid,\n${companyName}`;
        if (companyPhone) message += `\n${companyPhone}`;
        if (companyEmail) message += `\n${companyEmail}`;

        let url = '';
        if (phone) {
            const cleanPhone = phone.replace(/\D/g, '');
            url = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
        } else {
            url = `https://wa.me/?text=${encodeURIComponent(message)}`;
        }
        window.open(url, '_blank');
    },
    deleteInvoice(id) {
        deleteRecordWithPassword('{{ url('/sales/invoices') }}/' + id, 'this invoice', {
            title: 'Delete Invoice?',
            text: 'Are you sure you want to delete this invoice? This action will reverse customer balances and cannot be undone.'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Sales Invoices</h1>
        <a href="{{ route('sales.invoice.create') }}"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Invoice
        </a>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-receipt text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Today's Sales</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($todaySales, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Revenue</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($totalRevenue, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-400 mb-2">
                <i class="bi bi-hourglass-split text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Outstanding</p>
            <p class="text-[16px] font-black text-red-500">{{ $symbol }} {{ number_format($totalOutstanding, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-files text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Invoices</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($totalInvoices) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.invoice.view') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH INVOICES"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="status" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Paid</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Unpaid</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Invoice List</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($orders as $order)
            @php
                $statusColor = match($order->status) {
                    'completed' => 'bg-accent/10 text-accent',
                    'partial' => 'bg-yellow-50 text-yellow-600',
                    default => 'bg-red-50 text-red-500',
                };
                $sLabel = match($order->status) {
                    'completed' => 'Paid',
                    'partial' => 'Partial',
                    default => 'Unpaid',
                };
            @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $order->invoice_no }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $symbol }} {{ number_format($order->total_amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ \Carbon\Carbon::parse($order->invoice_date)->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor }}">{{ $sLabel }}</span>
                        @if($order->due_amount > 0)
                            <span class="text-xs text-red-500 font-semibold">Bal: {{ $symbol }}{{ number_format($order->due_amount, 2) }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('sales.invoice.show', $order->id) }}"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        <button type="button"
                            @click="sendWhatsApp('{{ $order->invoice_no }}', '{{ addslashes($order->customer->name ?? 'Walk-in Customer') }}', '{{ $order->customer->phone ?? '' }}', '{{ $symbol }} {{ number_format($order->total_amount, 2) }}', '{{ \App\Support\PublicUrl::temporarySigned('sales.invoice.public-pdf', now()->addDays(7), ['id' => $order->id]) }}', '{{ addslashes($company->name ?? 'Waafibook') }}', '{{ \Carbon\Carbon::parse($order->invoice_date)->format('jS F Y') }}', '{{ $company->phone ?? '' }}', '{{ $company->email ?? '' }}')"
                            class="w-7 h-7 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent active:bg-accent/20">
                            <i class="bi bi-whatsapp text-xs"></i>
                        </button>
                        <a href="{{ route('sales.invoice.edit', $order->id) }}"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </a>
                        @if($canDelete)
                            <button type="button" @click="deleteInvoice({{ $order->id }})"
                                class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                                <i class="bi bi-trash text-xs"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No invoices found.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
