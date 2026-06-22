@extends('admin.admin_master')
@section('page_title', 'Cash Management')

@push('css')
@endpush

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" 
    x-data="{ 
        showModal: false,
        searchTerm: '',
        statusFilter: '',
        typeFilter: ''
    }" x-cloak>
    
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Branch Cash Operations</h1>
        </div>
    </div>

    <!-- Stats Cards (Matching Customer Design) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Liquidity</p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($totalCash, 0) }}</h3>
                <p class="text-xs text-accent mt-1.5 flex items-center gap-1 font-medium"><i class="bi bi-dot text-xl animate-pulse"></i> Real-time Active</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-bank text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Receivables</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($receivables, 0) }}</h3>
                <p class="text-xs text-gray-400 mt-1.5 font-medium">Global AR Assets</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Payables</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($payables, 0) }}</h3>
                <p class="text-xs text-primary mt-1.5 font-medium">Pending Liabilities</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-truck text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Branches</p>
                <h3 class="text-[18px] font-black text-primary">{{ $activeBranches }} Units</h3>
                <p class="text-xs text-accent mt-1.5 font-medium flex items-center gap-1">
                    <i class="bi bi-check2-circle"></i> Operational
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-diagram-3 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Filters (Matching Customer Design) -->
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <div class="relative group min-w-[300px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" placeholder="Search operational units, codes, or branch entities..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <div class="relative min-w-[160px]">
                <select x-model="statusFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <div class="relative min-w-[160px]">
                <select x-model="typeFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Types</option>
                    <option value="main">Main Branch</option>
                    <option value="sub">Sub-Branch</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </div>

        <!-- Branch List -->
        <div class="animate-in fade-in duration-500">
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Branch List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider">Branch Identity</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Cash & Bank</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Receivables</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Payables</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Expenses</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Returns</th>
                            <th class="px-5 py-4 text-[10px] font-black text-primary-dark uppercase tracking-wider text-right">Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $grandCash = 0; $grandReceivables = 0; $grandPayables = 0;
                            $grandExpenses = 0; $grandPurchases = 0; $grandSales = 0;
                        @endphp
                        @foreach($branches as $branch)
                        @php
                            $bAccs = $branch->accounts;
                            $bCash = $bAccs->whereIn('type', ['bank', 'cash'])->sum('balance');
                            // Customers/suppliers aren't tracked per branch, so when there's a single
                            // branch its row can safely show the real company-wide totals; with
                            // multiple branches this falls back to the (less precise) ledger figures.
                            $bReceivables = $branches->count() === 1 ? $receivables : $bAccs->where('category', 'assets')->filter(fn($a) => str_contains(strtolower($a->name), 'receivable'))->sum('balance');
                            $bPayables = $branches->count() === 1 ? $payables : $bAccs->where('category', 'liabilities')->filter(fn($a) => str_contains(strtolower($a->name), 'payable'))->sum('balance');
                            $bExpenses = $bAccs->where('category', 'expenses')->sum('balance');
                            $bPurchases = $bAccs->where('name', 'like', '%Purchase%')->sum('balance'); // Fallback to name search for purchases
                            $bSales = $bAccs->where('category', 'revenue')->sum('balance');

                            $grandCash += $bCash; $grandReceivables += $bReceivables; $grandPayables += $bPayables;
                            $grandExpenses += $bExpenses; $grandPurchases += $bPurchases; $grandSales += $bSales;

                            $icons = ['bi-building', 'bi-shop', 'bi-house-heart', 'bi-briefcase'];
                            $bgColors = ['bg-primary/10 text-primary', 'bg-accent/10 text-accent', 'bg-primary/10 text-primary', 'bg-primary/10 text-primary'];
                            $idx = $loop->index % 4;
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                            x-show="(searchTerm === '' || '{{ strtolower($branch->name) }} {{ strtolower($branch->district ?? '') }}'.includes(searchTerm.toLowerCase())) &&
                                    (statusFilter === '' || '{{ $branch->is_active ? 'active' : 'inactive' }}' === statusFilter) &&
                                    (typeFilter === '' || '{{ strtolower($branch->level ?? '') }}' === typeFilter)">
                            <td class="px-5 py-4 text-[11px] font-bold text-gray-400 text-center italic">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <div>
                                    <div class="text-[13px] font-bold text-primary">{{ $branch->name }}</div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">{{ $branch->district ?? 'Main District' }}</div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-primary">
                                {{ $companyCurrency }} {{ number_format($bCash, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-primary">
                                {{ number_format($bReceivables, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-primary">
                                {{ number_format($bPayables, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-primary">
                                {{ number_format($bExpenses, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-primary">
                                {{ number_format($bPurchases, 2) }}
                            </td>
                            <td class="px-5 py-4 text-right text-[13px] font-black text-accent">
                                {{ number_format($bSales, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-white border-t-2 border-primary/10 text-primary-dark font-black uppercase tracking-wider text-[11px]">
                        <tr>
                            <td colspan="2" class="px-5 py-5 text-sm uppercase">Total All Branches</td>
                            <td class="px-5 py-5 text-right font-black">{{ $companyCurrency }} {{ number_format($grandCash, 2) }}</td>
                            <td class="px-5 py-5 text-right font-black text-primary">{{ number_format($receivables, 2) }}</td>
                            <td class="px-5 py-5 text-right font-black text-primary">{{ number_format($payables, 2) }}</td>
                            <td class="px-5 py-5 text-right font-black text-primary">{{ number_format($grandExpenses, 2) }}</td>
                            <td class="px-5 py-5 text-right font-black">{{ number_format($grandPurchases, 2) }}</td>
                            <td class="px-5 py-5 text-right font-black text-accent">{{ number_format($grandSales, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                    Showing 1 to {{ $branches->count() }} of {{ $branches->count() }} entries
                </div>
                <div class="flex items-center gap-1">
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-50 text-gray-400 border border-gray-200 cursor-not-allowed" disabled>
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </button>
                    <span class="w-7 h-7 flex items-center justify-center rounded bg-[#004161] text-white font-bold text-[11px] border border-[#004161] shadow-sm shadow-[#004161]/20">1</span>
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-white text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200 font-bold text-[11px]">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Assignments Modal -->
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
        
        <div class="bg-white rounded-[1.25rem] w-full max-w-xl shadow-2xl overflow-hidden relative animate-in zoom-in duration-300" @click.away="showModal = false">
            
            <!-- Modal Header (Matching Customer Design) -->
            <div class="px-6 py-6 bg-[#004161] relative overflow-hidden">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold text-white tracking-tight uppercase">Ledger Matrix Assignment</h2>
                            <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-0.5">Initialize segregated branch accounting</p>
                        </div>
                    </div>
                    <button @click="showModal = false" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <form action="{{ route('account.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <!-- Branch Selection -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Target Operational Center <span class="text-primary">*</span></label>
                        <select name="branch_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#004161]/10 focus:border-[#004161] outline-none transition-all appearance-none cursor-pointer">
                            <option value="">-- SELECT CLASSIFIED BRANCH --</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->code }} | {{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type & Category Dual -->
                    <div class="grid grid-cols-2 gap-5 pt-4 border-t border-gray-100">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Type</label>
                            <select name="type" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#004161]/10 focus:border-[#004161] outline-none transition-all appearance-none cursor-pointer">
                                <option value="bank">Bank Account</option>
                                <option value="cash">Cash on Hand</option>
                                <option value="receivables">Receivables</option>
                                <option value="payables">Payables</option>
                                <option value="operating">Operating Expense</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category</label>
                            <select name="category" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#004161]/10 focus:border-[#004161] outline-none transition-all appearance-none cursor-pointer">
                                <option value="assets">Assets</option>
                                <option value="liabilities">Liabilities</option>
                                <option value="expenses">Expenses</option>
                                <option value="revenue">Revenue</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Internal Code</label>
                            <input type="text" name="code" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-mono font-bold text-[#004161] outline-none">
                        </div>
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Descriptive Title</label>
                            <input type="text" name="name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 bg-[#004161] text-white font-semibold rounded-lg hover:bg-[#004161]/95 transition-all text-[13px] shadow-sm">
                            Cancel
                        </button>
                        <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-[#A4D65E] text-[#004161] font-bold rounded-lg hover:bg-[#97c454] transition-all text-[13px] shadow-sm">
                            <i class="bi bi-check2-circle text-base"></i>
                            Establish Ledger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

