@extends('admin.admin_master')
@section('page_title', 'Bill-wise Profit')



@section('admin')
    <div class="report-premium-container" x-data="{
            searchTerm: @js(request('search', '')),
            branchFilter: @js(request('branch_id', '')),
            customerFilter: @js(request('customer_id', '')),
            marginFilter: @js(request('margin_filter', '')),
            pdfModal: false,
            cols: {
                date: true, invoice_no: true, party_name: true, revenue: true,
                cost: true, expenses: true, profit: true, margin: true, grade: true
            },
            exportType: 'pdf',
            buildPdfUrl() {
                let base = '{{ route('reports.bill_wise_profit.pdf') }}';
                let params = new URLSearchParams(@js(request()->query()));
                // Sync current x-data filters
                if(this.searchTerm) params.set('search', this.searchTerm);
                if(this.branchFilter) params.set('branch_id', this.branchFilter);
                if(this.customerFilter) params.set('customer_id', this.customerFilter);
                if(this.marginFilter) params.set('margin_filter', this.marginFilter);

                return base + '?' + params.toString();
            }
         }">

        <!-- Header Section -->
        <div class="report-premium-header no-print">
            <div>
                <h1 class="report-premium-title">Bill-Wise Profit Report</h1>
                <p class="report-premium-subtitle">Analysis of profitability on a per-invoice basis</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="report-premium-btn-outline">
                    <i class="bi bi-printer text-sm"></i> PRINT
                </button>
                <button @click="exportType = 'pdf'; pdfModal = true" class="report-premium-btn-primary">
                    <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
                </button>
            </div>
        </div>

        <!-- PDF Column Selection Modal -->
        <div x-show="pdfModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0,0,0,0.45);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5" @click.outside="pdfModal = false">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[16px] font-black text-primary-dark">Export PDF — Options</h3>
                    <button @click="pdfModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <p class="text-[12px] text-gray-500 mb-4">The report will be generated based on your current filters.</p>

                <div class="flex justify-end gap-2">
                    <a :href="buildPdfUrl()" target="_blank" @click="pdfModal = false"
                        class="flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-bold rounded-full transition-all shadow-md text-sm">
                        <i class="bi bi-file-earmark-pdf"></i>
                        Generate PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <!-- Stats Cards -->
        <div class="report-premium-stat-grid">
            <div
                class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Revenue</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totals->revenue, 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Gross Proceeds</p>
                </div>
                <div
                    class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Net Profit</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totals->profit, 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">After Costs & Exp.</p>
                </div>
                <div
                    class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Avg Margin</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totals->avgMargin, 1) }}%</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Efficiency Rate</p>
                </div>
                <div
                    class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-percent"></i>
                </div>
            </div>
            <div
                class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Best Margin</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totals->bestMargin, 1) }}%</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">TOP TRANSACTION</p>
                </div>
                <div
                    class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-trophy"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <!-- Filter Bar -->
        <form action="{{ route('reports.bill_wise_profit') }}" method="GET" class="report-premium-filter-bar no-print">
            <div class="report-premium-filter-group flex-1 min-w-[200px]">
                <span class="report-premium-filter-label">Search Invoice</span>
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="search" x-model="searchTerm" placeholder="Invoice #..."
                        class="report-premium-filter-input !pl-9">
                </div>
            </div>
            <div class="report-premium-filter-group w-auto min-w-[140px]">
                <span class="report-premium-filter-label">From Date</span>
                <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="report-premium-filter-input">
            </div>
            <div class="report-premium-filter-group w-auto min-w-[140px]">
                <span class="report-premium-filter-label">To Date</span>
                <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="report-premium-filter-input">
            </div>
            <div class="report-premium-filter-group w-auto min-w-[160px]">
                <span class="report-premium-filter-label">Customer</span>
                <select name="customer_id" x-model="customerFilter" class="report-premium-filter-input">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="report-premium-filter-group w-auto min-w-[140px]">
                <span class="report-premium-filter-label">Margin Category</span>
                <select name="margin_filter" x-model="marginFilter" class="report-premium-filter-input">
                    <option value="">All Margins</option>
                    <option value="high">High (>35%)</option>
                    <option value="medium">Medium (15-35%)</option>
                    <option value="low">Low (&lt;15%)</option>
                </select>
            </div>
            <div class="report-premium-filter-group w-auto min-w-[140px]">
                <span class="report-premium-filter-label">Branch</span>
                <select name="branch_id" x-model="branchFilter" class="report-premium-filter-input">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
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
                    <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Invoice Profitability
                        Matrix</h4>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="report-premium-badge report-premium-badge-info !rounded-full italic font-black uppercase tracking-widest text-[9px]">{{ count($reportData) }}
                        Transactions Evaluated</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="report-premium-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Customer Name</th>
                            <th class="text-right">Sale Amount</th>
                            <th class="text-right">Cost Amount</th>
                            <th class="text-right">Profit/Loss</th>
                            <th class="text-right">Overall Margin</th>
                            <th class="text-center">Rank</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $row)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-[12px] font-black text-gray-500 uppercase italic">
                                    {{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-[13px] font-black text-primary uppercase tracking-tight">
                                    {{ $row->invoice_no }}</td>
                                <td class="px-6 py-4 text-[13px] font-black text-primary-dark uppercase tracking-tighter">
                                    {{ $row->customer }}</td>
                                <td class="px-6 py-4 text-[13px] font-black font-mono text-gray-900 text-right">
                                    {{ number_format($row->revenue, 2) }}</td>
                                <td class="px-6 py-4 text-[13px] font-black font-mono text-gray-400 text-right">
                                    {{ number_format($row->cost, 2) }}</td>
                                <td
                                    class="px-6 py-4 text-[13px] font-black font-mono text-right {{ $row->profit >= 0 ? 'text-accent' : 'text-primary' }}">
                                    {{ number_format($row->profit, 2) }}
                                </td>
                                <td class="px-6 py-4 text-[13px] font-black font-mono text-gray-900 text-right">
                                    {{ number_format($row->margin, 1) }}%</td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $badgeType = match ($row->grade) {
                                            'High' => 'success',
                                            'Medium' => 'info',
                                            default => 'error'
                                        };
                                    @endphp
                                    <span
                                        class="report-premium-badge report-premium-badge-{{ $badgeType }} !rounded-full italic font-black text-[9px] uppercase tracking-widest">{{ $row->grade }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="bi bi-inbox text-3xl text-gray-200"></i>
                                        </div>
                                        <p class="text-sm font-black text-gray-400 uppercase tracking-widest italic">No
                                            transactions found for current filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($reportData->count() > 0)
                        <tfoot class="bg-primary/5">
                            <tr class="font-black text-primary-dark border-t-2 border-primary/20">
                                <td colspan="3" class="px-6 py-5 text-center">
                                    <span
                                        class="text-[11px] font-black uppercase tracking-[0.2em] italic text-primary-dark opacity-70">Consolidated
                                        Performance Analytics</span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <span
                                        class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none text-right">Total
                                        Rev.</span>
                                    <span
                                        class="text-[14px] font-mono text-primary italic leading-none">{{ number_format($totals->revenue, 2) }}</span>
                                </td>
                                <td class="px-6 py-5 text-right border-l border-gray-100/50">
                                    <span
                                        class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none text-right">Net
                                        COGS</span>
                                    <span
                                        class="text-[14px] font-mono text-gray-400 italic leading-none">{{ number_format($totals->cost, 2) }}</span>
                                </td>
                                <td class="px-6 py-5 text-right border-l border-gray-100/50 bg-brand-bg/5">
                                    <span
                                        class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none text-right">Total
                                        P&L</span>
                                    <span
                                        class="text-[14px] font-mono text-accent italic leading-none">{{ number_format($totals->profit, 2) }}</span>
                                </td>
                                <td class="px-6 py-5 text-right border-l border-gray-100/50">
                                    <span
                                        class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none text-right">Avg
                                        Rate</span>
                                    <span
                                        class="text-[14px] font-mono text-gray-900 italic leading-none">{{ number_format($totals->avgMargin, 1) }}%</span>
                                </td>
                                <td class="bg-primary/5"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
@endsection