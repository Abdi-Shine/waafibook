@extends('admin.admin_master')
@section('page_title', 'Account Management')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{ searchTerm: '' }" x-cloak>

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Account Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openDepositModal()" class="flex items-center gap-2 px-4 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                <i class="bi bi-arrow-down-short text-lg"></i> Deposit
            </button>
            <button onclick="openWithdrawModal()" class="flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] shadow-sm">
                <i class="bi bi-arrow-up-short text-lg"></i> Withdraw
            </button>
        </div>
    </div>

    <!-- Stats Grid (5 cols) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Balance</p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($totalBalance, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-check2-circle"></i> {{ $activeAccounts }} Active Wallets
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Deposits <span class="text-[10px] opacity-60">MTD</span></p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($depositsAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-arrow-down-short"></i> {{ $depositsCount }} Transactions
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-box-arrow-in-down text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Withdrawals <span class="text-[10px] opacity-60">MTD</span></p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($withdrawalsAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-arrow-up-short"></i> {{ $withdrawalsCount }} Transactions
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-box-arrow-up text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Transfers <span class="text-[10px] opacity-60">MTD</span></p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($transfersAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-shuffle text-[10px]"></i> {{ $transfersCount }} Movements
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-shuffle text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Adjustments</p>
                <h3 class="text-[18px] font-black text-primary">{{ $companyCurrency }} {{ number_format($adjustmentsAmount, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-sliders text-[10px]"></i> Corrections
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-sliders text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <!-- Filter Bar -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm group-focus-within:text-primary transition-colors"></i>
                <input type="text" x-model="searchTerm" placeholder="Search transactions..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <div class="relative min-w-[150px]">
                <select onchange="filterTransactions()" id="accountFilter"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Accounts</option>
                    @foreach($bankAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
            </div>

            <div class="relative min-w-[150px]">
                <select onchange="filterTransactions()" id="typeFilter"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Types</option>
                    <option value="deposits" {{ request('type') == 'deposits' ? 'selected' : '' }}>Deposits</option>
                    <option value="withdrawals" {{ request('type') == 'withdrawals' ? 'selected' : '' }}>Withdrawals</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
            </div>
        </div>

        <!-- Table Title Bar -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary text-sm"></i>
            <h2 class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Transaction Ledger</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Classification</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Target Ledger</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Fund Impact</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Account Matrix</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $trx)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group" x-show="searchTerm === '' || '{{ strtolower($trx->reference) }}'.includes(searchTerm.toLowerCase())">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                @if(str_starts_with($trx->reference, 'DEP-'))
                                    Deposit
                                @elseif(str_starts_with($trx->reference, 'WTH-'))
                                    Withdraw
                                @elseif(str_starts_with($trx->reference, 'TRF-'))
                                    Transfer
                                @else
                                    Adjust
                                @endif
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                @php
                                    $bankItem = $trx->items->first(function($item) use ($bankAccounts) {
                                        return $bankAccounts->contains('id', $item->account_id);
                                    });
                                @endphp
                                @if($bankItem)
                                    {{ $bankItem->account->name }}
                                @else
                                    <span class="text-gray-400 italic">Multi-Ledger Offset</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-right">
                                @php
                                    $isNegative = str_starts_with($trx->reference, 'WTH-') || str_starts_with($trx->reference, 'ADJ-');
                                @endphp
                                <span class="font-black {{ $isNegative ? 'text-primary' : 'text-primary-dark' }}">
                                    {{ $isNegative ? '-' : '+' }}{{ $companyCurrency }}{{ number_format($trx->total_amount, 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                @php
                                    $catItem = $trx->items->first(function($item) use ($bankAccounts) {
                                        return !$bankAccounts->contains('id', $item->account_id);
                                    });
                                @endphp
                                @if($catItem)
                                    {{ $catItem->account->name }}
                                @else
                                    <span class="text-gray-300 italic">N/A</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button class="btn-action-view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(auth()->user() && in_array(strtolower(trim((string) auth()->user()->role)), ['admin', 'super admin']))
                                    <button type="button" class="btn-action-edit" title="Edit"
                                        onclick="openEditTransactionModal({{ $trx->id }}, {{ $trx->total_amount }}, '{{ $trx->date->format('Y-m-d') }}', '{{ addslashes($trx->description ?? '') }}', {{ $catItem->account_id ?? 'null' }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn-action-delete" title="Delete"
                                        onclick="deleteTransaction({{ $trx->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center text-gray-400">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                <i class="bi bi-safe text-2xl text-accent"></i>
                            </div>
                            <p class="text-[13px] font-bold uppercase tracking-widest text-gray-400">No transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages() || $transactions->total() > 0)
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} entries
            </div>
            <div class="flex items-center gap-2">
                @if ($transactions->onFirstPage())
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </button>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </a>
                @endif

                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">
                    {{ $transactions->currentPage() }}
                </button>

                @if ($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </a>
                @else
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </button>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Deposit Modal -->
<div id="depositModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-in fade-in duration-300">
    <div class="bg-white rounded-[1.5rem] w-full max-w-2xl shadow-2xl overflow-hidden relative animate-in zoom-in duration-300">
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-white text-lg shadow-inner backdrop-blur-md">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-base font-bold text-white tracking-tight uppercase">Record Vault Deposit</h2>
                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest">Inward Fund Registry Allocation</p>
                    </div>
                </div>
                <button onclick="closeDepositModal()" class="w-7 h-7 bg-white/10 border border-white/10 text-white rounded-md hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-[10px]"></i>
                </button>
            </div>
        </div>
        <form action="{{ route('bank.transaction.deposit') }}" method="POST" class="p-8">
            @csrf
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Target Bank Asset</label>
                        <select name="bank_account_id" required class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" data-balance="{{ number_format($acc->balance, 2) }}">{{ $acc->name }} ({{ number_format($acc->balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Deposit Category</label>
                        <select name="category_id" required class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            @foreach($accountCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Fund Amount</label>
                        <input type="number" step="0.01" name="amount" required class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Entry Date</label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Descriptive Narrative</label>
                    <input type="text" name="notes" placeholder="Detailed objective of deposit..." class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-8">
                <button type="button" onclick="closeDepositModal()" class="flex-1 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Decline</button>
                <button type="submit" class="flex-[2] px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center justify-center gap-2">
                    <i class="bi bi-shield-check text-sm"></i> Commit Deposit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-in fade-in duration-300">
    <div class="bg-white rounded-[1.5rem] w-full max-w-2xl shadow-2xl overflow-hidden relative animate-in zoom-in duration-300">
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-white text-lg shadow-inner backdrop-blur-md">
                        <i class="bi bi-box-arrow-up"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-base font-bold text-white tracking-tight uppercase">Record Withdrawal</h2>
                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest">Outward Fund Registry</p>
                    </div>
                </div>
                <button onclick="closeWithdrawModal()" class="w-7 h-7 bg-white/10 border border-white/10 text-white rounded-md hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-[10px]"></i>
                </button>
            </div>
        </div>
        <form action="{{ route('bank.transaction.withdraw') }}" method="POST" class="p-8">
            @csrf
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Source Bank Account</label>
                        <select name="bank_account_id" required class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" data-balance="{{ number_format($acc->balance, 2) }}">{{ $acc->name }} ({{ number_format($acc->balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Category</label>
                        <select name="category_id" required class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            @foreach($accountCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date</label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Paid To</label>
                    <input type="text" name="paid_to" placeholder="Vendor / Recipient name..." class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Notes</label>
                    <input type="text" name="notes" placeholder="Reason for withdrawal..." class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-8">
                <button type="button" onclick="closeWithdrawModal()" class="flex-1 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Cancel</button>
                <button type="submit" class="flex-[2] px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] shadow-sm flex items-center justify-center gap-2">
                    <i class="bi bi-box-arrow-up text-sm"></i> Confirm Withdrawal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editTransactionModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md animate-in fade-in duration-300">
    <div class="bg-white rounded-[1.5rem] w-full max-w-lg shadow-2xl overflow-hidden relative animate-in zoom-in duration-300">
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-white text-lg shadow-inner backdrop-blur-md">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <h2 class="text-base font-bold text-white tracking-tight uppercase">Edit Transaction</h2>
                </div>
                <button type="button" onclick="closeEditTransactionModal()" class="w-7 h-7 bg-white/10 border border-white/10 text-white rounded-md hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-[10px]"></i>
                </button>
            </div>
        </div>
        <form id="editTransactionForm" method="POST" class="p-8">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount</label>
                        <input type="number" step="0.01" name="amount" id="editTrxAmount" required class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date</label>
                        <input type="date" name="date" id="editTrxDate" required class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Deposit Category</label>
                    <select name="category_id" id="editTrxCategory" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">— No change —</option>
                        @foreach($accountCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description</label>
                    <input type="text" name="description" id="editTrxDescription" class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-8">
                <button type="button" onclick="closeEditTransactionModal()" class="flex-1 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Cancel</button>
                <button type="submit" class="flex-[2] px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] shadow-sm flex items-center justify-center gap-2">
                    <i class="bi bi-check2 text-sm"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden delete form, reused for every row -->
<form id="deleteTransactionForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" name="password">
</form>

<script>
    function openDepositModal() { document.getElementById('depositModal').classList.remove('hidden'); }
    function closeDepositModal() { document.getElementById('depositModal').classList.add('hidden'); }
    function openWithdrawModal() { document.getElementById('withdrawModal').classList.remove('hidden'); }
    function closeWithdrawModal() { document.getElementById('withdrawModal').classList.add('hidden'); }

    function openEditTransactionModal(id, amount, date, description, categoryId) {
        document.getElementById('editTransactionForm').action = '/account-management/transaction/' + id;
        document.getElementById('editTrxAmount').value = amount;
        document.getElementById('editTrxDate').value = date;
        document.getElementById('editTrxDescription').value = description;
        document.getElementById('editTrxCategory').value = categoryId ?? '';
        document.getElementById('editTransactionModal').classList.remove('hidden');
    }
    function closeEditTransactionModal() { document.getElementById('editTransactionModal').classList.add('hidden'); }

    function deleteTransaction(id) {
        Swal.fire({
            title: 'Delete this transaction?',
            text: 'This reverses the journal entry and account balances. This cannot be undone.',
            icon: 'warning',
            input: 'password',
            inputPlaceholder: 'Enter your admin password to confirm',
            inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            inputValidator: (value) => {
                if (!value) return 'Password is required to delete this transaction.';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteTransactionForm');
                form.action = '/account-management/transaction/' + id;
                form.querySelector('input[name="password"]').value = result.value;
                form.submit();
            }
        });
    }

    function filterTransactions() {
        const accId = document.getElementById('accountFilter').value;
        const type = document.getElementById('typeFilter').value;
        const url = new URL(window.location.href);
        if(accId) url.searchParams.set('account_id', accId); else url.searchParams.delete('account_id');
        if(type) url.searchParams.set('type', type); else url.searchParams.delete('type');
        window.location.href = url.toString();
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDepositModal();
            closeWithdrawModal();
        }
    });
</script>

@endsection
