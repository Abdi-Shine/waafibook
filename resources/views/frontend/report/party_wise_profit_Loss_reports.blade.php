@extends('admin.admin_master')
@section('page_title', 'Party Profit & Loss')

@push('css')
@endpush

@section('admin')
@php
    $currencySymbols = ['SAR'=>'﷼','USD'=>'$','EUR'=>'€','GBP'=>'£','AED'=>'د.إ','KWD'=>'د.ك','QAR'=>'﷼','BHD'=>'BD','OMR'=>'﷼','JOD'=>'JD'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? 'SAR');
@endphp
<div class="report-premium-container"
     x-data="{ showClear: {{ ($filters['search'] || ($filters['from_date'] != now()->startOfYear()->format('Y-m-d')) || ($filters['to_date'] != now()->format('Y-m-d'))) ? 'true' : 'false' }} }">

    <!-- Header Section -->
    <div class="report-premium-header no-print">
        <div>
            <h1 class="report-premium-title">Party Wise Profit & Loss Report</h1>
            <p class="report-premium-subtitle">Profitability analysis by customer</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="report-premium-btn-outline">
                <i class="bi bi-printer text-sm"></i> PRINT
            </button>
            <a href="{{ route('reports.party_wise_profit_loss.excel') }}?{{ http_build_query($filters) }}" class="report-premium-btn-outline !bg-emerald-600 !text-white !border-emerald-600">
                <i class="bi bi-file-earmark-excel text-sm"></i> EXPORT EXCEL
            </a>
            <a href="{{ route('reports.party_wise_profit_loss.pdf') }}?{{ http_build_query($filters) }}" class="report-premium-btn-primary">
                <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="report-premium-stat-grid">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Sales</p>
                <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($totals['totalSales'], 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">All parties</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cart-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Profit</p>
                <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($totals['totalProfit'], 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">{{ $totals['overallMargin'] }}% margin</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Top Party</p>
                <h3 class="text-[18px] font-black text-primary truncate max-w-[160px]" title="{{ $totals['topParty'] }}">{{ $totals['topParty'] }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Highest profit contribution</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-trophy"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Parties</p>
                <h3 class="text-[18px] font-black text-primary">{{ $totals['activeParties'] }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">With transactions</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('reports.party_wise_profit_loss') }}" method="GET"
          class="report-premium-filter-bar no-print"
          x-data="{ showClear: false }"
          @change="showClear = true">

        <div class="report-premium-filter-group flex-1 min-w-[200px]">
            <span class="report-premium-filter-label">Search Party</span>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs text-primary"></i>
                <input type="text" name="search" value="{{ $filters['search'] }}"
                       placeholder="Search by name or phone..."
                       class="report-premium-filter-input !pl-9"
                       @input="showClear = true">
            </div>
        </div>

        <div class="report-premium-filter-group w-auto min-w-[160px]">
            <span class="report-premium-filter-label">Party Type</span>
            <select name="party_type" class="report-premium-filter-input">
                <option value="" {{ $filters['party_type'] === '' ? 'selected' : '' }}>All Parties</option>
                <option value="customer" {{ $filters['party_type'] === 'customer' ? 'selected' : '' }}>Registered Customers Only</option>
            </select>
        </div>

        <div class="report-premium-filter-group w-auto min-w-[140px]">
            <span class="report-premium-filter-label">From Date</span>
            <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="report-premium-filter-input">
        </div>

        <div class="report-premium-filter-group w-auto min-w-[140px]">
            <span class="report-premium-filter-label">To Date</span>
            <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="report-premium-filter-input">
        </div>

        <button type="submit" class="report-premium-btn-primary h-[38px] mt-auto">
            <i class="bi bi-funnel"></i> Generate
        </button>

        <a href="{{ route('reports.party_wise_profit_loss') }}"
           x-show="showClear || '{{ $filters['search'] }}' !== '' || '{{ $filters['from_date'] }}' !== '{{ now()->startOfYear()->format('Y-m-d') }}'"
           x-transition
           class="report-premium-btn-outline h-[38px] mt-auto text-gray-500">
            <i class="bi bi-x-lg text-xs"></i> Clear
        </a>
    </form>

    <!-- Table Section -->
    <div class="report-premium-card overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-brand-border bg-brand-bg/10 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Party Wise Profitability Analysis</h4>
            </div>
            <span class="report-premium-badge report-premium-badge-info !rounded-full italic font-black text-[9px] uppercase tracking-widest">
                {{ $totals['activeParties'] }} Records Found
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="text-center w-12">#</th>
                        <th>Party Name</th>
                        <th>Phone No.</th>
                        <th class="text-right">Total Sale Amount</th>
                        <th class="text-right">Profit (+) / Loss (-)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reportData as $key => $party)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-4 text-[11px] font-black text-gray-400 text-center italic leading-none">{{ str_pad($key + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-5 py-4">
                            <span class="text-xs font-black text-primary-dark uppercase tracking-tight">{{ $party['name'] }}</span>
                        </td>
                        <td class="px-5 py-4">
                            @if($party['phone'] !== '---')
                                <span class="text-[11px] text-gray-500 font-black">{{ $party['phone'] }}</span>
                            @else
                                <span class="text-[11px] text-gray-400 font-bold italic">N/A</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-xs font-black text-primary-dark font-mono">{{ $symbol }} {{ number_format($party['revenue'], 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if($party['profit'] >= 0)
                                <span class="report-premium-badge report-premium-badge-success italic !px-3 font-black font-mono">
                                    +{{ $symbol }} {{ number_format($party['profit'], 2) }}
                                </span>
                            @else
                                <span class="report-premium-badge report-premium-badge-danger italic !px-3 font-black font-mono">
                                    {{ $symbol }} {{ number_format($party['profit'], 2) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center">
                            <i class="bi bi-inbox text-3xl text-gray-300 block mb-2"></i>
                            <p class="text-xs text-gray-400 font-bold">No party data found for the selected period.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-primary/5">
                    <tr class="font-black text-primary-dark border-t-2 border-primary/20">
                        <td colspan="3" class="px-5 py-5 text-center">
                            <span class="text-[11px] font-black uppercase tracking-[0.2em] italic text-primary-dark opacity-70">Consolidated Profit Analysis</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none">Total Sale Amount</span>
                            <span class="text-[13px] font-black font-mono text-primary-dark">{{ $symbol }} {{ number_format($totals['totalSales'], 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right bg-accent/10">
                            <span class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none">Total Profit(+)/Loss(-)</span>
                            <span class="text-[15px] font-black font-mono text-accent italic">{{ $symbol }} {{ number_format($totals['totalProfit'], 2) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
