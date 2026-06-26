@extends('admin.admin_master')
@section('page_title', 'Parties Statement')



@section('admin')
<div class="report-premium-container"
     x-data="{
        searchTerm: '',
        partyFilter: '{{ $partyKey }}',
        pdfModal: false
     }">

    <!-- Page Header -->
    <div class="report-premium-header no-print">
        <div>
            <h1 class="report-premium-title">Party Statement</h1>
            <p class="report-premium-subtitle">Detailed transaction ledger for customers and suppliers</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="report-premium-btn-outline">
                <i class="bi bi-printer text-sm"></i> PRINT
            </button>
            <button class="report-premium-btn-outline">
                <i class="bi bi-file-earmark-excel text-sm"></i> EXCEL
            </button>
            <a href="{{ route('reports.party_statement.pdf', ['party' => $partyKey, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="report-premium-btn-primary">
                <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="report-premium-stat-grid">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Receivable</p>
                <h3 class="text-[18px] font-black text-primary">{{ $company->currency ?? 'SAR' }} {{ number_format($totalReceivable, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">To collect</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-arrow-down-circle"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Payable</p>
                <h3 class="text-[18px] font-black text-primary">{{ $company->currency ?? 'SAR' }} {{ number_format($totalPayable, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">To pay</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Transactions</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totalTransactions) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">This period</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Parties</p>
                <h3 class="text-[18px] font-black text-primary">{{ $activeParties }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Customers & Suppliers</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form class="report-premium-filter-bar no-print" method="GET" action="{{ route('reports.party_statement') }}">
        <div class="report-premium-filter-group flex-1 min-w-[200px]">
            <span class="report-premium-filter-label">Search Activity</span>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchTerm" placeholder="Search description or ID..." class="report-premium-filter-input !pl-9">
            </div>
        </div>
        <div class="report-premium-filter-group w-auto min-w-[150px]">
            <span class="report-premium-filter-label">From Date</span>
            <input type="date" name="from_date" value="{{ $fromDate }}" class="report-premium-filter-input">
        </div>
        <div class="report-premium-filter-group w-auto min-w-[150px]">
            <span class="report-premium-filter-label">To Date</span>
            <input type="date" name="to_date" value="{{ $toDate }}" class="report-premium-filter-input">
        </div>
        <div class="report-premium-filter-group w-auto min-w-[180px]">
            <span class="report-premium-filter-label">Select Party</span>
            <select name="party" x-model="partyFilter" class="report-premium-filter-input">
                <option value="">All Parties</option>
                <optgroup label="Customers">
                    @foreach($customers as $customer)
                        <option value="customer_{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Suppliers">
                    @foreach($suppliers as $supplier)
                        <option value="supplier_{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
        <button type="submit" class="report-premium-btn-primary h-[38px] mt-auto">
            <i class="bi bi-funnel"></i> Generate
        </button>
    </form>

    <!-- Table Section -->
    <div class="report-premium-card overflow-hidden mb-6">
        @php
            $currency = $company->currency ?? 'SAR';
            $lastBalance = $ledger->last()['balance'] ?? 0;
            $totalDebit = $ledger->sum('debit');
            $totalCredit = $ledger->sum('credit');
        @endphp
        <!-- Table Title Bar -->
        <div class="px-5 py-4 border-b border-brand-border bg-brand-bg/10 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Statement Ledger: {{ $party->name ?? 'All Parties' }}</h4>
            </div>
            @if($party)
            <div class="flex items-center gap-3">
                <span class="report-premium-badge report-premium-badge-info !rounded-full italic font-black uppercase tracking-widest text-[9px]">ACTIVE LEDGER</span>
                <span class="report-premium-badge report-premium-badge-warning !rounded-full font-black uppercase tracking-widest text-[9px]">{{ $lastBalance >= 0 ? 'DEBIT' : 'CREDIT' }} BAL: {{ $currency }} {{ number_format(abs($lastBalance), 2) }}</span>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="text-center w-12">#</th>
                        <th>Date</th>
                        <th>Activity/Description</th>
                        <th>Reference</th>
                        <th class="text-right">Debit (In)</th>
                        <th class="text-right">Credit (Out)</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ledger as $i => $row)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-4 text-[11px] font-black text-gray-400 text-center">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-5 py-4 text-[11px] font-bold text-gray-500">{{ $row['date']->format('d-M-Y') }}</td>
                        <td class="px-5 py-4 text-xs font-black text-primary-dark">{{ $row['description'] }}</td>
                        <td class="px-5 py-4 text-[11px] font-bold text-gray-400 italic">{{ $row['reference'] }}</td>
                        <td class="px-5 py-4 text-right text-[13px] font-black text-primary font-mono">{{ $row['debit'] > 0 ? $currency . ' ' . number_format($row['debit'], 2) : '---' }}</td>
                        <td class="px-5 py-4 text-right text-[13px] font-black text-accent font-mono">{{ $row['credit'] > 0 ? $currency . ' ' . number_format($row['credit'], 2) : '---' }}</td>
                        <td class="px-5 py-4 text-right text-[13px] font-black text-primary font-mono tracking-tighter">{{ $currency }} {{ number_format(abs($row['balance']), 2) }} <span class="text-[9px] opacity-70">{{ $row['balance'] >= 0 ? 'DR' : 'CR' }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-gray-400 text-sm font-semibold">
                            {{ $party ? 'No transactions for this party in the selected period.' : 'Select a party above to view their statement ledger.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ledger->isNotEmpty())
                <tfoot class="bg-primary/5">
                    <tr class="font-black text-primary-dark border-t-2 border-primary/20">
                        <td colspan="4" class="px-5 py-5 text-center">
                            <span class="text-[11px] font-black uppercase tracking-widest italic text-primary-dark">Period Activity Totals</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5 leading-none">Total Debits</span>
                            <span class="text-[14px] font-mono font-black text-primary italic leading-none">{{ $currency }} {{ number_format($totalDebit, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5 leading-none">Total Credits</span>
                            <span class="text-[14px] font-mono font-black text-accent italic leading-none">{{ $currency }} {{ number_format($totalCredit, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                             <span class="text-[9px] text-gray-400 block font-bold uppercase mb-0.5 leading-none">Net Balance</span>
                             <span class="text-[15px] font-mono font-black text-primary leading-none tracking-tighter">{{ $currency }} {{ number_format(abs($lastBalance), 2) }} {{ $lastBalance >= 0 ? 'DR' : 'CR' }}</span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>


</div>
@endsection


