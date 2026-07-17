@extends('admin.admin_master')
@section('page_title', 'Reports')

@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Reports & Analytics</h1>
            <p class="text-[12px] text-gray-500 font-medium mt-1">Comprehensive business intelligence and fiscal oversight</p>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Sales</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totalSales, 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1"><i class="bi bi-arrow-up text-accent"></i> All time</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cart-check text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Purchases</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totalPurchases, 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Inventory cost</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-truck text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Net Profit</p>
                <h3 class="text-[18px] font-black {{ $netProfit >= 0 ? 'text-accent' : 'text-red-500' }}">{{ number_format($netProfit, 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1"><i class="bi bi-graph-up"></i> Operational</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up-arrow text-lg"></i>
            </div>
        </div>
    </div>

    {{-- ── FINANCIAL REPORTS ────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-primary rounded-full"></div>
            <h2 class="text-[11px] font-black text-primary uppercase tracking-[0.15em]">Financial Reports</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

            <a href="{{ route('reports.profit_loss') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-primary/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-200">
                    <i class="bi bi-graph-up-arrow text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Profit & Loss</h4>
                <p class="text-[10px] text-gray-400 mt-1">Income vs expenses</p>
            </a>

            <a href="{{ route('reports.balance_sheet') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-primary/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-200">
                    <i class="bi bi-briefcase text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Balance Sheet</h4>
                <p class="text-[10px] text-gray-400 mt-1">Assets & liabilities</p>
            </a>

            <a href="{{ route('reports.trial_balance') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-primary/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-200">
                    <i class="bi bi-layout-three-columns text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Trial Balance</h4>
                <p class="text-[10px] text-gray-400 mt-1">Debit vs credit</p>
            </a>

            <a href="{{ route('reports.cash_flow') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-primary/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-200">
                    <i class="bi bi-cash-stack text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Cash Flow</h4>
                <p class="text-[10px] text-gray-400 mt-1">Cash in & out</p>
            </a>

            <a href="{{ route('reports.transaction') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-primary/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-200">
                    <i class="bi bi-clipboard-data text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Transactions</h4>
                <p class="text-[10px] text-gray-400 mt-1">All ledger entries</p>
            </a>

        </div>
    </div>

    {{-- ── SALES REPORTS ────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-accent rounded-full"></div>
            <h2 class="text-[11px] font-black text-primary uppercase tracking-[0.15em]">Sales Reports</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

            <a href="{{ route('reports.sales') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-accent/20 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-accent/10 text-accent group-hover:bg-accent group-hover:text-white transition-all duration-200">
                    <i class="bi bi-cart-check text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Sales Report</h4>
                <p class="text-[10px] text-gray-400 mt-1">Invoice & revenue</p>
            </a>

        </div>
    </div>

    {{-- ── PURCHASE REPORTS ─────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-blue-500 rounded-full"></div>
            <h2 class="text-[11px] font-black text-primary uppercase tracking-[0.15em]">Purchase Reports</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

            <a href="{{ route('reports.purchases') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-blue-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-truck text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Purchase Analysis</h4>
                <p class="text-[10px] text-gray-400 mt-1">Supplier & cost</p>
            </a>

            <a href="{{ route('reports.expense_item_report') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-blue-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-receipt text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Expense Items</h4>
                <p class="text-[10px] text-gray-400 mt-1">Item-level expenses</p>
            </a>

        </div>
    </div>

    {{-- ── INVENTORY REPORTS ────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-orange-400 rounded-full"></div>
            <h2 class="text-[11px] font-black text-primary uppercase tracking-[0.15em]">Inventory Reports</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

            <a href="{{ route('reports.summary_stock') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-orange-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-orange-50 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-box-fill text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Stock Summary</h4>
                <p class="text-[10px] text-gray-400 mt-1">Current stock levels</p>
            </a>

            <a href="{{ route('reports.stock_details') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-orange-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-orange-50 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-ui-checks text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Stock Details</h4>
                <p class="text-[10px] text-gray-400 mt-1">Per-product stock</p>
            </a>

        </div>
    </div>

    {{-- ── PARTY REPORTS ────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-purple-500 rounded-full"></div>
            <h2 class="text-[11px] font-black text-primary uppercase tracking-[0.15em]">Party Reports</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

            <a href="{{ route('reports.party_statement') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-purple-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-person-lines-fill text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Party Statement</h4>
                <p class="text-[10px] text-gray-400 mt-1">Customer/supplier ledger</p>
            </a>

            <a href="{{ route('reports.sales_purchase_by_party') }}" class="group bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex flex-col items-center text-center hover:shadow-md hover:border-purple-200 hover:-translate-y-0.5 transition-all duration-200">
                <div class="w-12 h-12 rounded-[0.75rem] flex items-center justify-center mb-3 bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all duration-200">
                    <i class="bi bi-arrow-left-right text-xl"></i>
                </div>
                <h4 class="text-[12px] font-black text-primary-dark">Party Trade</h4>
                <p class="text-[10px] text-gray-400 mt-1">Sales & purchases by party</p>
            </a>

        </div>
    </div>

</div>

@endsection
