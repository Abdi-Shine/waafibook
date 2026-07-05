@extends('admin.admin_master')
@section('page_title', 'Direct Expenses')


@php
    $symbol = $curr ?? '$';
@endphp

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="expenseManagement()">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Purchase Direct Expenses</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.view') }}" class="btn-premium-accent">Back to Bills</a>
            <button @click="openModal = 'new-expense'" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                <span>Add New Expense</span>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Expenses -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Expenses</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($expenses->sum('amount'), 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-wallet2 text-[10px]"></i> Life-time total
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>

        <!-- Pending Audit -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Audit</p>
                <h3 class="text-[18px] font-black text-primary">0</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Needs review</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-clock-history text-lg"></i>
            </div>
        </div>

        <!-- Approved Payouts -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Approved Payouts</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($expenses->sum('amount'), 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-check-all text-[10px]"></i> Cleared funds
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-shield-check text-lg"></i>
            </div>
        </div>

        <!-- Avg Daily Cost -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Avg Daily Cost</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($expenses->sum('amount') / max(1, date('d')), 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">This month cycle</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-graph-up-arrow text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

        <!-- Filters -->
        <form action="{{ route('purchase.expense') }}" method="GET"
              class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, bill # or supplier..."
                       class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <!-- Date From -->
            <div class="relative min-w-[160px]">
                <input type="date" name="from_date" value="{{ request('from_date') ?? date('Y-01-01') }}"
                       onchange="this.form.submit()"
                       class="w-full px-3 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>

            <!-- Date To -->
            <div class="relative min-w-[160px]">
                <input type="date" name="to_date" value="{{ request('to_date') ?? date('Y-m-d') }}"
                       onchange="this.form.submit()"
                       class="w-full px-3 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>

            <!-- Supplier -->
            <div class="relative min-w-[180px]">
                <select name="supplier_id" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->company_name ?? $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Branch -->
            <div class="relative min-w-[150px]">
                <select name="branch_id" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </form>

        <!-- Table Title -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Purchase Expense Records</h2>
            <span class="ml-auto text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $expenses->total() }} Records</span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Bill No.</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Expense Name</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Account</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Paid From</th>
                        <th class="px-5 py-4 text-right text-[12px] font-black text-primary-dark uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-4 text-center text-[12px] font-black text-primary-dark uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-center text-[12px] font-black text-primary-dark uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $key => $expense)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                {{ str_pad($expenses instanceof \Illuminate\Pagination\AbstractPaginator ? $expenses->firstItem() + $key : $key + 1, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                {{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') : 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <a href="{{ route('purchase.bill.show', $expense->purchase_id) }}" class="hover:text-primary transition-colors">
                                    #{{ $expense->purchase->bill_number ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">{{ $expense->expense_name }}</td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <a href="{{ route('account.ledger', ['account_id' => $expense->expense_account_id]) }}" class="hover:text-primary transition-colors">
                                    {{ $expense->account->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <a href="{{ route('account.ledger', ['account_id' => $expense->bank_account_id]) }}" class="hover:text-primary transition-colors">
                                    {{ $expense->bankAccount->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                $ {{ number_format($expense->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-accent/10 text-primary border border-accent/20 uppercase tracking-wider">PAID</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('purchase.expense.view', $expense->id) }}"
                                       class="btn-action-view"
                                       title="View Receipt"
                                       target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn-action-edit"
                                            @click="openEditModal(@js($expense))"
                                            title="Edit Expense">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn-action-delete"
                                            onclick="confirmDeleteExpense({{ $expense->id }}, '{{ addslashes($expense->expense_name) }}')"
                                            title="Delete Expense">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-expense-{{ $expense->id }}"
                                          action="{{ route('purchase.expense.destroy', $expense->id) }}"
                                          method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center text-gray-400 text-xs italic">
                                No purchase expenses recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($expenses instanceof \Illuminate\Pagination\AbstractPaginator && $expenses->count() > 0)
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $expenses->firstItem() }} to {{ $expenses->lastItem() }} of {{ $expenses->total() }} entries
            </p>
            <div class="flex items-center gap-1">
                @if($expenses->onFirstPage())
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                @else
                    <a href="{{ $expenses->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </a>
                @endif

                @foreach($expenses->links()->elements as $element)
                    @if(is_array($element))
                        @foreach($element as $page => $url)
                            @if($page == $expenses->currentPage())
                                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                            @else
                                <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm text-xs font-bold">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if($expenses->hasMorePages())
                    <a href="{{ $expenses->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </a>
                @else
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- New Expense Modal -->
    <div id="expenseModal"
         x-show="openModal === 'new-expense'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[100vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Add New Expense</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5">Fill in the required details below</p>
                        </div>
                    </div>
                    <button @click="openModal = null"
                            class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form action="{{ route('purchase.expense.store') }}" method="POST" id="expenseForm"
                  class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="px-6 py-6 flex-1 overflow-y-auto custom-scrollbar bg-white space-y-6">

                    <!-- Row 1: Expense Name, Linked Bill, Category -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="text" name="expense_name" required placeholder="Shipping Fee"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-tag absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Linked Bill <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                            <div class="relative">
                                <select name="purchase_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- No Linked Bill --</option>
                                    @foreach($bills as $bill)
                                        <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="expense_account_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT CATEGORY --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Amount, Date, Supplier -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount Paid <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-currency-dollar absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="date" name="expense_date" required
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all uppercase">
                                <i class="bi bi-calendar3 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Supplier <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                            <div class="relative">
                                <select name="supplier_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT SUPPLIER --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->company_name ?? $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Branch, Bank Account, Description -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                            <div class="relative">
                                <select name="branch_id" x-model="selectedBranch"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT BRANCH --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Bank Account <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="bank_account_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT BANK ACCOUNT --</option>
                                    <template x-for="account in filteredBankAccounts" :key="account.id">
                                        <option :value="account.id" x-text="account.name"></option>
                                    </template>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description & Notes</label>
                            <div class="relative">
                                <input type="text" name="description" placeholder="Details..."
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-pencil absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                    <button type="button" @click="openModal = null" class="btn-premium-accent">Cancel</button>
                    <button type="submit" class="btn-premium-primary">
                        <i class="bi bi-check2-circle"></i>
                        <span>Save Expense</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div id="viewExpenseModal"
         x-show="openModal === 'view-expense'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <!-- Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">Expense Details</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5" x-text="'#' + viewExpense.id"></p>
                        </div>
                    </div>
                    <button @click="openModal = null"
                            class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 flex-1 overflow-y-auto custom-scrollbar bg-white space-y-6">

                <!-- Row 1 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Expense Name</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.expense_name || '—'"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.expense_date || '—'"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Amount</p>
                        <p class="text-[13px] font-black text-primary" x-text="'$ ' + parseFloat(viewExpense.amount || 0).toLocaleString('en-US', {minimumFractionDigits:2})"></p>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Row 2 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-accent/10 text-primary border border-accent/20 uppercase tracking-wider">PAID</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Description</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.description || '—'"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Bill No.</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.purchase ? '#' + viewExpense.purchase.bill_number : '—'"></p>
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                <!-- Row 3 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Supplier</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.supplier ? (viewExpense.supplier.company_name || viewExpense.supplier.name) : '—'"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Branch</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.branch ? viewExpense.branch.name : '—'"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Paid From</p>
                        <p class="text-[13px] font-semibold text-primary-dark" x-text="viewExpense.bank_account ? viewExpense.bank_account.name : '—'"></p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-end shrink-0">
                <button type="button" @click="openModal = null" class="btn-premium-accent">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div id="editExpenseModal"
         x-show="openModal === 'edit-expense'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Edit Expense</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5">Update the expense details below</p>
                        </div>
                    </div>
                    <button @click="openModal = null"
                            class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form :action="`/purchase/expense/${editExpense.id}`" method="POST"
                  class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="px-6 py-6 flex-1 overflow-y-auto custom-scrollbar bg-white space-y-6">

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="text" name="expense_name" required x-model="editExpense.expense_name"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-tag absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Linked Bill <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="purchase_id" x-model="editExpense.purchase_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT BILL --</option>
                                    @foreach($bills as $bill)
                                        <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="expense_account_id" x-model="editExpense.expense_account_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT CATEGORY --</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount Paid <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="number" step="0.01" name="amount" required x-model="editExpense.amount"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-currency-dollar absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="date" name="expense_date" required x-model="editExpense.expense_date"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all uppercase">
                                <i class="bi bi-calendar3 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Supplier <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="supplier_id" x-model="editExpense.supplier_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT SUPPLIER --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->company_name ?? $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="branch_id" x-model="editExpense.branch_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT BRANCH --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Bank Account <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select required name="bank_account_id" x-model="editExpense.bank_account_id"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- SELECT BANK ACCOUNT --</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description & Notes</label>
                            <div class="relative">
                                <input type="text" name="description" x-model="editExpense.description"
                                       class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-pencil absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                    <button type="button" @click="openModal = null" class="btn-premium-accent">Cancel</button>
                    <button type="submit" class="btn-premium-primary">
                        <i class="bi bi-check2-circle"></i>
                        <span>Update Expense</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function confirmDeleteExpense(id, name) {
    Swal.fire({
        title: 'Delete Expense?',
        text: `Are you sure you want to delete "${name}"? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#004161',
        cancelButtonColor: '#99CC33',
        confirmButtonText: 'Yes, delete it!',
        customClass: {
            popup: 'rounded-[1.5rem]',
            confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
            cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-expense-' + id).submit();
        }
    });
}

function expenseManagement() {
    return {
        openModal: null,
        selectedBranch: '',
        allBankAccounts: @json($bankAccounts),
        editExpense: {},
        viewExpense: {},

        get filteredBankAccounts() {
            if (!this.selectedBranch) return this.allBankAccounts;
            return this.allBankAccounts.filter(acc => !acc.branch_id || acc.branch_id == this.selectedBranch);
        },

        openViewModal(expense) {
            this.viewExpense = { ...expense };
            this.openModal = 'view-expense';
        },

        openEditModal(expense) {
            this.editExpense = { ...expense };
            this.openModal = 'edit-expense';
        },

        init() {}
    }
}
</script>
@endsection
