@extends('admin.admin_master')
@section('page_title', 'Sales Invoices')
@section('admin')



@php
    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
        'AUD' => 'A$', 'CAD' => 'C$', 'CHF' => 'Fr', 'CNY' => '¥',
        'INR' => '₹', 'MYR' => 'RM', 'SGD' => 'S$', 'AED' => 'د.إ',
        'SAR' => '﷼', 'NGN' => '₦', 'KES' => 'KSh', 'ZAR' => 'R',
    ];
    $symbol = $currencySymbols[$company->currency ?? 'USD'] ?? ($company->currency ?? '$');
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" spellcheck="false">

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Sales Invoices</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.invoice.create') }}" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                <span>New Invoice</span>
            </a>
        </div>
    </div>

    {{-- ── Statistics Cards ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Today's Sales -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Today's Sales</p>
                <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($todaySales, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-graph-up-arrow text-[10px]"></i> Daily total
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-receipt text-lg"></i>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Revenue</p>
                <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($totalRevenue, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-cash-stack text-[10px]"></i> Collected Revenue
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>

        <!-- Outstanding -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Outstanding</p>
                <h3 class="text-[18px] font-black text-red-500">{{ $symbol }} {{ number_format($totalOutstanding, 2) }}</h3>
                <p class="text-xs font-bold text-red-400 mt-1.5 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle text-[10px]"></i> Unpaid Balance
                </p>
            </div>
            <div class="w-11 h-11 bg-red-50 rounded-[0.6rem] flex items-center justify-center text-red-400 flex-shrink-0">
                <i class="bi bi-hourglass-split text-lg"></i>
            </div>
        </div>

        <!-- Total Invoices -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Invoices</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totalInvoices) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-file-earmark-text text-[10px]"></i> All time records
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-files text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        
        <!-- Filter Bar -->
        <form action="{{ route('sales.invoice.view') }}" method="GET"
              x-data="{ hasFilters: {{ request()->hasAny(['search', 'branch_id', 'status', 'date_from', 'date_to']) ? 'true' : 'false' }} }"
              class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">

            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <!-- Date From -->
            <div class="relative min-w-[160px]">
                <input type="date" name="date_from" value="{{ request('date_from', date('Y-01-01')) }}" onchange="this.form.submit()"
                    class="w-full pl-3 pr-3 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer">
            </div>

            <!-- Date To -->
            <div class="relative min-w-[160px]">
                <input type="date" name="date_to" value="{{ request('date_to', date('Y-m-d')) }}" onchange="this.form.submit()"
                    class="w-full pl-3 pr-3 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer">
            </div>

            <!-- Branch Filter -->
            <div class="relative min-w-[170px]">
                <select name="branch_id" onchange="this.form.submit()"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Status Filter -->
            <div class="relative min-w-[150px]">
                <select name="status" onchange="this.form.submit()"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Paid</option>
                    <option value="partial"   {{ request('status') == 'partial'    ? 'selected' : '' }}>Partial</option>
                    <option value="pending"   {{ request('status') == 'pending'    ? 'selected' : '' }}>Unpaid</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Clear Filters -->
            <button type="button" onclick="window.location.href='{{ route('sales.invoice.view') }}'"
                x-show="hasFilters"
                x-transition
                class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10"
                title="Clear All Filters">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </form>

        <!-- Table List Header -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Invoice List</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-12 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Invoice</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Branch</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Total</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Balance</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $key => $order)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($orders->firstItem() + $key, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $order->invoice_no }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                                    <span class="text-[10px] text-gray-400 font-medium">{{ $order->customer->phone ?? '' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $order->branch->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ \Carbon\Carbon::parse($order->invoice_date)->format('d M, Y') }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $symbol }} {{ number_format($order->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[12px] font-semibold {{ $order->due_amount > 0 ? 'text-red-500' : 'text-primary-dark' }}">
                                    {{ $symbol }} {{ number_format($order->due_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @php
                                    $statusClass = match($order->status) {
                                        'completed' => 'status-completed',
                                        'partial'   => 'status-warning',
                                        default     => 'status-danger',
                                    };
                                    $sLabel = match($order->status) {
                                        'completed' => 'PAID',
                                        'partial'   => 'PARTIAL',
                                        default     => 'UNPAID',
                                    };
                                @endphp
                                <span class="premium-badge {{ $statusClass }}">{{ $sLabel }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('sales.invoice.show', $order->id) }}" class="btn-action-view" title="View Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button onclick="sendWhatsAppInvoice('{{ $order->invoice_no }}', '{{ addslashes($order->customer->name ?? 'Walk-in Customer') }}', '{{ $order->customer->phone ?? '' }}', '{{ $symbol }} {{ number_format($order->total_amount, 2) }}', '{{ \App\Support\PublicUrl::temporarySigned('sales.invoice.public-pdf', now()->addDays(7), ['id' => $order->id]) }}')"
                                            class="w-8 h-8 rounded-lg bg-accent/10 text-accent hover:bg-accent hover:text-white transition-all duration-200 flex items-center justify-center text-sm shadow-sm" title="Send WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </button>
                                    <a href="{{ route('sales.invoice.edit', $order->id) }}" class="btn-action-edit" title="Edit Invoice">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('Sales & POS', 'delete'))
                                    <button onclick="confirmDelete({{ $order->id }})" class="btn-action-delete" title="Delete Invoice">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $order->id }}" action="{{ route('sales.invoice.delete', $order->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-300">
                                <i class="bi bi-inbox text-4xl"></i>
                                <span class="text-xs font-semibold text-gray-400">No invoices found</span>
                            </div>
                        </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} entries
            </p>
            <div class="flex items-center gap-1">
                @if ($orders->onFirstPage())
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </a>
                @endif

                @foreach ($orders->links()->elements as $element)
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $orders->currentPage())
                                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm text-xs font-bold">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </a>
                @else
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    deleteRecordWithPassword('{{ url('/sales/invoices') }}/' + id, 'this invoice', {
        title: 'Delete Invoice?',
        text: 'Are you sure you want to delete this invoice? This action will reverse customer balances and cannot be undone.'
    });
}

function sendWhatsAppInvoice(invoiceNo, customer, phone, total, pdfUrl) {
    let message = `*SALES INVOICE*\n\n`;
    message += `*Invoice:* ${invoiceNo}\n`;
    message += `*Customer:* ${customer}\n`;
    message += `*Total:* ${total}\n\n`;
    message += `📄 View Invoice: ${pdfUrl}\n\n`;
    message += `Thank you for your business!`;

    let url = '';
    if (phone) {
        const cleanPhone = phone.replace(/\D/g, '');
        url = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
    } else {
        url = `https://wa.me/?text=${encodeURIComponent(message)}`;
    }
    window.open(url, '_blank');
}
</script>

@endsection


