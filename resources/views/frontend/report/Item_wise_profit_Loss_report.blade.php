@extends('admin.admin_master')
@section('page_title', 'Item Profit & Loss')

@push('css')

@endpush

@section('admin')
<div class="report-premium-container">

    <!-- Page Header -->
    <div class="report-premium-header no-print">
        <div>
            <h1 class="report-premium-title">Item Wise Profit & Loss Report</h1>
            <p class="report-premium-subtitle">Profitability breakdown per inventory item for the selected period</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="report-premium-btn-outline">
                <i class="bi bi-printer text-sm"></i> PRINT
            </button>
            <a href="{{ route('reports.item_wise_profit_loss.pdf', ['from_date' => $filters['from_date'], 'to_date' => $filters['to_date']]) }}" class="report-premium-btn-primary">
                <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="report-premium-stat-grid !grid-cols-5">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Items</p>
                <h3 class="text-[18px] font-black text-primary">{{ $totals->count }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Total Products</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Sale Qty</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totals->saleQty) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Units Sold</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cart-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Sales</p>
                <h3 class="text-[18px] font-black text-primary">{{ ($company->currency ?? 'SAR') }} {{ number_format($totals->saleAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Revenue</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Purchases</p>
                <h3 class="text-[18px] font-black text-primary">{{ ($company->currency ?? 'SAR') }} {{ number_format($totals->purchaseAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Cost</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cart3"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Net Profit</p>
                <h3 class="text-[18px] font-black text-primary">
                    {{ ($company->currency ?? 'SAR') }} {{ number_format(abs($totals->netProfit), 2) }}
                </h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    {{ $totals->netProfit >= 0 ? 'Profit' : 'Loss' }}
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-{{ $totals->netProfit >= 0 ? 'graph-up-arrow' : 'graph-down-arrow' }}"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('reports.item_wise_profit_loss') }}" class="report-premium-filter-bar no-print">
        <div class="report-premium-filter-group w-auto min-w-[150px]">
            <span class="report-premium-filter-label">From Date</span>
            <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="report-premium-filter-input">
        </div>
        <div class="report-premium-filter-group w-auto min-w-[150px]">
            <span class="report-premium-filter-label">To Date</span>
            <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="report-premium-filter-input">
        </div>
        <div class="report-premium-filter-group flex-1 min-w-[200px]">
            <span class="report-premium-filter-label">Search Item</span>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by item name or code..." class="report-premium-filter-input !pl-9">
            </div>
        </div>
        <button type="submit" class="report-premium-btn-primary h-[38px] mt-auto">
            <i class="bi bi-funnel"></i> Generate
        </button>
    </form>

    <!-- Table Section -->
    <div class="report-premium-card overflow-hidden mb-6">
        <!-- Table Title Bar -->
        <div class="px-5 py-4 border-b border-brand-border bg-brand-bg/10 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Item Wise Profit & Loss Detail</h4>
            </div>
            <span class="report-premium-badge report-premium-badge-info !rounded-full italic font-black uppercase text-[9px] tracking-widest px-3">
                {{ $totals->count }} Items — {{ $filters['from_date'] }} to {{ $filters['to_date'] }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="text-center w-12">#</th>
                        <th>Item Name</th>
                        <th class="text-right">Sale Qty</th>
                        <th class="text-right">Sale Amount</th>
                        <th class="text-right">Purchase Qty</th>
                        <th class="text-right">Purchase Amount</th>
                        <th class="text-right">Net Profit / Loss</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-4 text-[11px] font-black text-gray-400 text-center">{{ $loop->iteration }}</td>
                        <td class="px-5 py-4">
                            <span class="text-xs font-black text-primary-dark">{{ $item->name }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black font-mono {{ $item->saleQty > 0 ? 'text-primary' : 'text-gray-300' }}">
                                {{ $item->saleQty > 0 ? number_format($item->saleQty) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black font-mono {{ $item->saleAmount > 0 ? 'text-accent' : 'text-gray-300' }}">
                                {{ $item->saleAmount > 0 ? number_format($item->saleAmount, 2) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black font-mono {{ $item->purchaseQty > 0 ? 'text-primary' : 'text-gray-300' }}">
                                {{ $item->purchaseQty > 0 ? number_format($item->purchaseQty) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black font-mono {{ $item->purchaseAmount > 0 ? 'text-primary' : 'text-gray-300' }}">
                                {{ $item->purchaseAmount > 0 ? number_format($item->purchaseAmount, 2) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @php $profit = $item->netProfit; @endphp
                            <span class="report-premium-badge {{ $profit > 0 ? 'report-premium-badge-success' : ($profit < 0 ? 'report-premium-badge-error' : 'report-premium-badge-info') }} italic !px-3">
                                <i class="bi bi-{{ $profit > 0 ? 'arrow-up' : ($profit < 0 ? 'arrow-down' : 'dash') }} text-[10px]"></i>
                                {{ number_format(abs($profit), 2) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($items->isNotEmpty())
                <tfoot class="bg-primary/5">
                    <tr class="font-black text-primary-dark border-t-2 border-primary/20">
                        <td colspan="3" class="px-5 py-5 text-center">
                            <span class="text-[11px] font-black uppercase tracking-widest italic text-primary-dark">Grand Totals</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5">Sale Qty</span>
                            <span class="text-[13px] font-mono font-black text-primary">{{ number_format($totals->saleQty) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5">Sale Amount</span>
                            <span class="text-[13px] font-mono font-black text-accent">{{ ($company->currency ?? 'SAR') }} {{ number_format($totals->saleAmount, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5">Purch Qty</span>
                            <span class="text-[13px] font-mono font-black text-primary">{{ number_format($totals->purchaseQty) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5">Purch Amount</span>
                            <span class="text-[13px] font-mono font-black text-primary">{{ ($company->currency ?? 'SAR') }} {{ number_format($totals->purchaseAmount, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5">Net Profit/Loss</span>
                            <span class="text-[14px] font-mono font-black {{ $totals->netProfit >= 0 ? 'text-accent' : 'text-primary' }}">
                                {{ ($company->currency ?? 'SAR') }} {{ number_format($totals->netProfit, 2) }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection

