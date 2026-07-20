@extends('admin.admin_master')
@section('page_title', 'Low Stock Alerts')

@section('admin')
<div class="pb-28 bg-background min-h-screen">

    <div class="px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Low Stock Alerts</h1>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-graph-down-arrow text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Alerts</p>
            <p class="text-[16px] font-black text-primary">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-shield-exclamation text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Critical</p>
            <p class="text-[16px] font-black text-primary">{{ $stats['critical'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-megaphone text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Warning</p>
            <p class="text-[16px] font-black text-primary">{{ $stats['warning'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-building-up text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Branches</p>
            <p class="text-[16px] font-black text-primary">{{ $stats['branches'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('low-stock.view') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH PRODUCTS"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="threat_level" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Levels</option>
                <option value="critical" {{ request('threat_level') == 'critical' ? 'selected' : '' }}>Critical</option>
                <option value="warning" {{ request('threat_level') == 'warning' ? 'selected' : '' }}>Warning</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Alert Registry</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($lowStockProducts as $stock)
            @php $isCritical = $stock->quantity <= 5; @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $stock->product_name }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $stock->branch_name ?? 'Primary Vault' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $stock->quantity }} Units</p>
                        <p class="text-xs text-text-secondary mt-0.5">Min: {{ $stock->low_stock_threshold ?? 10 }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $isCritical ? 'bg-red-50 text-red-500' : 'bg-accent/10 text-accent' }}">
                        {{ $isCritical ? 'Critical' : 'Warning' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-shield-check text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No stock alerts found.</p>
            </div>
        @endforelse
    </div>

    @if($lowStockProducts->hasPages())
        <div class="flex items-center justify-between px-5 py-4">
            <div class="text-[11px] text-gray-400">
                {{ $lowStockProducts->firstItem() ?? 0 }}–{{ $lowStockProducts->lastItem() ?? 0 }} of {{ $lowStockProducts->total() }}
            </div>
            <div class="flex items-center gap-2">
                @if($lowStockProducts->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-left text-[10px]"></i></span>
                @else
                    <a href="{{ $lowStockProducts->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-left text-[10px]"></i></a>
                @endif
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs">{{ $lowStockProducts->currentPage() }}</span>
                @if($lowStockProducts->hasMorePages())
                    <a href="{{ $lowStockProducts->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-right text-[10px]"></i></a>
                @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-right text-[10px]"></i></span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
