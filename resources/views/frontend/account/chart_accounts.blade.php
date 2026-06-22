@extends('admin.admin_master')
@section('page_title', 'Chart of Accounts')

@push('css')
@endpush

@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="accountManager" x-cloak>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        @if(session('success'))
            <div class="fixed top-4 right-4 z-[100] bg-accent/10 text-white px-6 py-3 rounded-lg shadow-lg border border-accent/20 animate-in slide-in-from-right-full duration-300">
                <div class="flex items-center gap-3">
                    <i class="bi bi-check-circle-fill text-xl"></i>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="fixed top-4 right-4 z-[100] bg-primary/10 text-white px-6 py-3 rounded-lg shadow-lg border border-primary/20 animate-in slide-in-from-right-full duration-300">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                        <span class="font-bold text-sm text-white">Validation Errors:</span>
                    </div>
                    <ul class="text-[10px] list-disc list-inside mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Financial Registry</span>
                <i class="bi bi-chevron-right text-[10px] text-gray-300"></i>
                <span class="text-[10px] font-black text-accent uppercase tracking-widest">General Ledger</span>
            </div>
            <h1 class="text-[22px] font-bold text-primary-dark tracking-tight">Chart of Accounts</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="syncData()" :disabled="isSyncing" class="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-primary-dark font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all shadow-sm text-sm disabled:opacity-50">
                <i class="bi bi-arrow-repeat" :class="isSyncing ? 'animate-spin' : ''"></i>
                <span x-text="isSyncing ? 'Syncing...' : 'Re-calculate Balances'"></span>
            </button>
            <button @click="openCreateModal()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                Add New Account
            </button>
        </div>
    </div>

    <!-- Financial Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <!-- Assets -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Assets</p>
                <h3 class="text-[18px] font-black text-primary tracking-tight">{{ $company->currency ?? '$' }} {{ number_format($totalAssets, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    Liquid & Fixed
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-bank2 text-lg"></i>
            </div>
        </div>

        <!-- Liabilities -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Liabilities</p>
                <h3 class="text-[18px] font-black text-primary tracking-tight">{{ $company->currency ?? '$' }} {{ number_format($totalLiabilities, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    Pending Debts
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-credit-card-2-front text-lg"></i>
            </div>
        </div>

        <!-- Equity -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Equity</p>
                <h3 class="text-[18px] font-black text-primary tracking-tight">{{ $company->currency ?? '$' }} {{ number_format($totalEquity, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    Retained Earnings
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-pie-chart text-lg"></i>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Revenue</p>
                <h3 class="text-[18px] font-black text-primary tracking-tight">{{ $company->currency ?? '$' }} {{ number_format($totalRevenue, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    Sales & Services
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-cash-stack text-lg"></i>
            </div>
        </div>

        <!-- Expenses -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Expenses</p>
                <h3 class="text-[18px] font-black text-primary tracking-tight">{{ $company->currency ?? '$' }} {{ number_format($totalExpenses, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    Operating Costs
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-receipt text-lg"></i>
            </div>
        </div>
    </div>



    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" placeholder="Search accounts, codes, or branches..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>
            

            
            <!-- Status -->
            <div class="relative min-w-[150px]">
                <select x-model="selectedStatus" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
            
            <!-- Apply Button -->
            <div class="min-w-[120px]">
                <button @click="syncData()" :disabled="isSyncing" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border border-primary text-primary font-bold rounded-[0.5rem] hover:bg-primary hover:text-white transition-all text-[13px] shadow-sm disabled:opacity-50">
                    <i class="bi bi-funnel" x-show="!isSyncing"></i>
                    <i class="bi bi-arrow-repeat animate-spin" x-show="isSyncing"></i>
                    <span x-text="isSyncing ? 'Syncing...' : 'Apply Filters'"></span>
                </button>
            </div>
        </div>

        <!-- Table Title -->
        <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-gray-50/30">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Accounting Chart Registry</h2>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-3 py-1 bg-white border border-gray-200 text-[10px] font-bold text-gray-500 rounded hover:bg-gray-50 uppercase tracking-widest flex items-center gap-1.5 transition-all">
                    <i class="bi bi-printer text-xs"></i> Print
                </button>
                <a href="{{ route('account.export') }}" class="px-3 py-1 bg-white border border-gray-200 text-[10px] font-bold text-gray-500 rounded hover:bg-gray-50 uppercase tracking-widest flex items-center gap-1.5 transition-all">
                    <i class="bi bi-file-earmark-excel text-xs text-accent"></i> Export
                </a>
            </div>
        </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Code</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Account Name</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Category</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Type</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Balance</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $groups = [
                                'assets'      => ['label' => 'Assets',      'icon' => 'bi-bank2',               'color' => 'text-blue-600',   'bg' => 'bg-blue-50'],
                                'liabilities' => ['label' => 'Liabilities', 'icon' => 'bi-credit-card-2-front', 'color' => 'text-red-600',    'bg' => 'bg-red-50'],
                                'equity'      => ['label' => 'Equity',      'icon' => 'bi-pie-chart',           'color' => 'text-purple-600', 'bg' => 'bg-purple-50'],
                                'revenue'     => ['label' => 'Revenue',     'icon' => 'bi-cash-stack',          'color' => 'text-green-600',  'bg' => 'bg-green-50'],
                                'expenses'    => ['label' => 'Expenses',    'icon' => 'bi-receipt',             'color' => 'text-orange-600', 'bg' => 'bg-orange-50'],
                            ];
                            $grouped = $allAccounts->groupBy('category');
                        @endphp

                        @foreach($groups as $catKey => $catMeta)
                            @if($grouped->has($catKey))
                                {{-- Group Header Row --}}
                                <tr class="border-t-2 border-gray-200">
                                    <td colspan="7" class="px-5 py-2.5 {{ $catMeta['bg'] }}">
                                        <div class="flex items-center gap-2">
                                            <i class="bi {{ $catMeta['icon'] }} {{ $catMeta['color'] }} text-sm"></i>
                                            <span class="text-[11px] font-black {{ $catMeta['color'] }} uppercase tracking-widest">{{ $catMeta['label'] }}</span>
                                            <span class="text-[10px] font-bold text-gray-400">({{ $grouped[$catKey]->count() }} accounts)</span>
                                        </div>
                                    </td>
                                </tr>
                                {{-- Account Rows --}}
                                @foreach($grouped[$catKey] as $account)
                                <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                                    x-show="shouldShowRow(@js($account->id), @js(strtolower($account->name)), @js(strtolower($account->code)), @js($account->is_active ? '1' : '0'))">
                                    <td class="px-5 py-3.5 pl-8">
                                        <span class="text-xs font-bold text-primary border-b border-primary/20 tracking-tighter">{{ $account->code }}</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-xs font-bold text-primary-dark uppercase leading-tight">{{ $account->name }}</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-[10px] font-black {{ $catMeta['color'] }} uppercase tracking-wider">{{ $account->category }}</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-xs font-bold text-primary-dark capitalize leading-tight">{{ $account->type }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs font-bold text-primary-dark tracking-tight">
                                        {{ $account->currency ?? ($company->currency ?? 'USD') }} {{ number_format($account->balance, 2) }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if($account->is_active)
                                        <span class="text-xs font-bold text-green-600">Active</span>
                                        @else
                                        <span class="text-xs font-bold text-gray-400">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('account.ledger', ['account_id' => $account->id]) }}"
                                               class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm"
                                               title="View Ledger">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
                                            <button @click="openEditModal('{{ $account->id }}')"
                                                    class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm"
                                                    title="Edit Account">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            {{-- Lock / Unlock toggle instead of Delete --}}
                                            <form action="{{ route('account.toggle-status', $account->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 transition-all flex items-center justify-center text-xs shadow-sm
                                                               {{ $account->is_active ? 'text-gray-400 hover:text-amber-500 hover:border-amber-300 hover:bg-amber-50' : 'text-amber-500 border-amber-300 bg-amber-50 hover:text-green-600 hover:border-green-300 hover:bg-green-50' }}"
                                                        title="{{ $account->is_active ? 'Lock Account' : 'Unlock Account' }}">
                                                    <i class="bi {{ $account->is_active ? 'bi-unlock' : 'bi-lock-fill' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($allAccounts->count() > 0)
            <!-- Pagination (Matching Customer Style) -->
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                    SHOWING 1 TO {{ $allAccounts->count() }} ENTRIES
                </div>
                <div class="flex items-center gap-1">
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 text-gray-400 border border-gray-200"><i class="bi bi-chevron-left text-[10px]"></i></button>
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-primary text-white font-bold text-[11px]">1</button>
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 text-gray-400 border border-gray-200"><i class="bi bi-chevron-right text-[10px]"></i></button>
                </div>
            </div>
            @endif
        </div>

    <!-- ADD/EDIT MODAL -->
    <div x-show="activeModal === 'addAccountModal'" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        
        <div class="bg-white rounded-[1rem] w-full max-w-3xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="closeModal()">
            
            <!-- Modal Header (Customer Style) -->
            <div class="px-5 py-4 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-white text-lg shadow-inner backdrop-blur-md">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-base font-bold text-white tracking-tight" x-text="isEditMode ? 'Modify Account Detail' : 'Add New Chart Account'"></h2>
                        </div>
                    </div>
                    
                    <button @click="closeModal()" 
                        class="w-7 h-7 bg-white/10 border border-white/10 text-white rounded-md hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-[10px]"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content -->
            <div class="px-5 py-4 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <form id="accountForm" method="POST" :action="formAction">
                    @csrf
                    <template x-if="isEditMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-4">



                        <!-- ═══════ GROUP 2: Account Classification ═══════ -->
                        <div class="p-4 bg-gray-50/50 border border-gray-200 rounded-xl space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-primary/10 rounded-md flex items-center justify-center">
                                    <i class="bi bi-diagram-3 text-primary text-[10px]"></i>
                                </div>
                                <span class="text-[10px] font-black text-primary-dark uppercase tracking-widest">Account Classification</span>
                            </div>

                            <!-- Row 3: Account Code | Display Name | Category -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Account Code <span class="text-primary">*</span></label>
                                    <div class="relative group">
                                        <input type="text" name="code" id="acc_code" required placeholder="e.g., 1021" x-model="selectedAccount.code"
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-mono font-bold text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all"
                                            :class="isCodeTaken ? 'border-primary/20 ring-2 ring-rose-500/10' : ''">
                                        <i class="bi bi-hash absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    </div>
                                    <template x-if="isCodeTaken">
                                        <p class="text-[9px] text-primary font-bold uppercase tracking-tight mt-1 flex items-center gap-1 animate-pulse">
                                            <i class="bi bi-exclamation-circle-fill"></i> This code is already in use by <span x-text="takenCodeName"></span>
                                        </p>
                                    </template>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Display Name <span class="text-primary">*</span></label>
                                    <div class="relative group">
                                        <input type="text" name="name" id="acc_name" required placeholder="Main Operational Bank" x-model="selectedAccount.name"
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-person-badge absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Category</label>
                                    <select name="category" id="acc_category" required x-model="selectedAccount.category"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none cursor-pointer transition-all">
                                        <option value="assets">Assets (Debit)</option>
                                        <option value="liabilities">Liabilities (Credit)</option>
                                        <option value="equity">Equity (Credit)</option>
                                        <option value="revenue">Revenue (Credit)</option>
                                        <option value="expenses">Expenses (Debit)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 4: Internal Type | Parent Account | Balance -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-3 border-t border-gray-100">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Internal Type</label>
                                    <select name="type" id="acc_type" required x-model="selectedAccount.type"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none cursor-pointer transition-all">
                                        <option value="">Select Type</option>
                                        <option value="parent">Parent Account</option>
                                        <option value="bank">Bank Account</option>
                                        <option value="cash">Cash on Hand</option>
                                        <option value="receivables">Receivables</option>
                                        <option value="inventory">Inventory</option>
                                        <option value="fixed">Fixed Assets</option>
                                        <option value="payables">Payables</option>
                                        <option value="loans">Loans</option>
                                        <option value="capital">Capital</option>
                                        <option value="revenue">Revenue</option>
                                        <option value="direct">Direct Costs</option>
                                        <option value="operating">Operating Expense</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Parent Account</label>
                                    <select name="parent_id" id="acc_parent" x-model="selectedAccount.parent_id"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none cursor-pointer transition-all">
                                        <option value="">None (Main Account)</option>
                                        @foreach($parentAccounts as $parent)
                                            <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Initial Balance</label>
                                    <input type="number" step="0.01" name="balance" id="acc_balance" placeholder="0.00" x-model="selectedAccount.balance"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 text-right focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                </div>
                            </div>

                            <!-- Row 5: Status Toggle -->
                            <div class="pt-3">
                                <div @click="selectedAccount.is_active = !selectedAccount.is_active" 
                                    class="flex items-center justify-between px-3 py-1.5 bg-white border border-gray-200 rounded-lg cursor-pointer hover:bg-primary/5 transition-all">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 bg-primary/5 rounded-md flex items-center justify-center text-primary border border-primary/5">
                                            <i class="bi bi-shield-check text-sm"></i>
                                        </div>
                                        <div class="flex flex-col text-left">
                                            <span class="text-[10px] font-black text-primary-dark uppercase tracking-tight" x-text="selectedAccount.is_active ? 'Active' : 'Inactive'"></span>
                                            <span class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">Transaction logs enabled</span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="is_active" :value="selectedAccount.is_active ? 1 : 0">
                                    <div class="relative h-6 w-11 shrink-0 rounded-full transition-all shadow-inner"
                                        :class="selectedAccount.is_active ? 'bg-accent' : 'bg-gray-200'">
                                        <div class="absolute top-[2px] h-5 w-5 rounded-full bg-white transition-all shadow-sm"
                                            :class="selectedAccount.is_active ? 'left-[22px]' : 'left-[2px]'"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- Modal Footer (Customer Style) -->
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button type="button" @click="closeModal()" 
                    class="px-4 py-2 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-xs">
                    Cancel Window
                </button>
                <button type="submit" form="accountForm" :disabled="isCodeTaken"
                    class="flex items-center gap-2 px-5 py-2 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-xs shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="bi bi-check2-circle text-base"></i>
                    <span x-text="isEditMode ? 'Update Account' : 'Commit New Account'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Import Data Modal (Matching Customer Style) -->
    <div x-show="activeModal === 'importDataModal'" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
        
        <div class="bg-white rounded-[1.5rem] w-full max-w-lg overflow-hidden shadow-2xl flex flex-col relative animate-in zoom-in duration-300" @click.away="closeModal()">
            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-white text-lg shadow-inner backdrop-blur-md">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-base font-bold text-white tracking-tight">Import Chart of Accounts</h2>
                            <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest">Bulk upload financial accounts via CSV</p>
                        </div>
                    </div>
                    <button @click="closeModal()" class="w-7 h-7 bg-white/10 border border-white/10 text-white rounded-md hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-[10px]"></i>
                    </button>
                </div>
                <!-- Decoration -->
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-accent/20 rounded-full blur-3xl opacity-50"></div>
            </div>

            <div class="p-8 bg-white">
                <form action="{{ route('account.import') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <!-- File Upload Box -->
                    <div class="group relative py-12 border-2 border-dashed border-gray-200 rounded-2xl hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer flex flex-col items-center justify-center bg-gray-50/50">
                        <input type="file" name="file" class="absolute inset-0 opacity-0 cursor-pointer" required>
                        <i class="bi bi-file-earmark-spreadsheet text-4xl text-gray-300 group-hover:text-primary transition-colors mb-3"></i>
                        <span class="text-xs font-bold text-gray-400 group-hover:text-primary-dark uppercase tracking-widest">Select Importable File</span>
                    </div>

                    <!-- Warning/Info Box -->
                    <div class="bg-primary/10 border border-primary/20 rounded-xl p-4 flex gap-3">
                        <i class="bi bi-info-circle text-primary mt-0.5"></i>
                        <div class="flex flex-col text-left">
                            <span class="text-[11px] font-black text-primary uppercase tracking-tight">Data Integrity Check</span>
                            <p class="text-[10px] text-primary font-medium leading-relaxed mt-1">File must include: Code, Name, category (assets/liabilities), and type (bank/cash).</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button type="button" @click="closeModal()" class="flex-1 py-3.5 bg-gray-100 text-gray-500 font-bold rounded-xl hover:bg-gray-200 transition-all text-[11px] uppercase tracking-widest">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl hover:bg-accent/90 transition-all text-[11px] uppercase tracking-widest shadow-lg shadow-accent/20 flex items-center justify-center gap-2">
                            <i class="bi bi-check2-all"></i>
                            Start Import
                        </button>
                    </div>
                </form>
                
                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-center">
                    <a href="{{ route('account.download-template') }}" class="text-[10px] font-black text-primary hover:text-accent transition-colors uppercase tracking-widest flex items-center gap-2 group">
                        <i class="bi bi-download group-hover:animate-bounce"></i>
                        Get CSV Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('accountManager', () => ({
        activeModal: null,
        isEditMode: false,
        searchTerm: '',
        isSyncing: false,
        selectedBank: '',
        selectedStatus: '',
        accounts: @js($allAccounts),
        selectedAccount: {
            id: '', code: '', name: '', category: 'assets', type: 'operating', parent_id: '', balance: 0,
            description: '', is_active: true, currency: '{{ $company->currency ?? 'USD' }}'
        },

        get formAction() {
            return this.isEditMode ? '/accounts/' + this.selectedAccount.id : '{{ route('account.store') }}';
        },

        get isCodeTaken() {
            if (!this.selectedAccount.code || this.isEditMode) return false;
            return this.accounts.some(a => a.code.toString() === this.selectedAccount.code.toString());
        },

        get takenCodeName() {
            const acc = this.accounts.find(a => a.code.toString() === this.selectedAccount.code.toString());
            return acc ? acc.name : '';
        },

        openCreateModal() {
            this.isEditMode = false;
            this.selectedAccount = { 
                id: '', code: '', name: '', category: 'assets', type: 'operating', parent_id: '', balance: 0, 
                description: '', is_active: true, currency: '{{ $company->currency ?? 'USD' }}'
            };
            this.activeModal = 'addAccountModal';
        },

        openEditModal(id) {
            this.isEditMode = true;
            let account = this.accounts.find(a => a.id == id);
            if (!account) return;
            
            this.selectedAccount = { ...account };
            this.activeModal = 'addAccountModal';
        },



        closeModal() {
            this.activeModal = null;
        },

        async syncData() {
            this.isSyncing = true;
            try {
                const response = await fetch('{{ route('account.recalculate-balances') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                window.location.reload();
            } catch (error) {
                console.error('Sync failed:', error);
                this.isSyncing = false;
            }
        },

        openImportModal() {
            this.activeModal = 'importDataModal';
        },



        confirmDelete(id, name) {
            deleteRecordWithPassword('{{ url('/accounts') }}/' + id, name, {
                title: 'Delete Account?',
                text: `Are you sure you want to delete '${name}'?`
            });
        },

        shouldShowRow(id, name, code, isActive) {
            const matchesSearch = this.searchTerm === '' || 
                                 name.toLowerCase().includes(this.searchTerm.toLowerCase()) || 
                                 code.toLowerCase().includes(this.searchTerm.toLowerCase());
            const matchesStatus = this.selectedStatus === '' || isActive.toString() === this.selectedStatus;
            
            return matchesSearch && matchesStatus;
        }
    }));
});
</script>
@endpush
@endsection


