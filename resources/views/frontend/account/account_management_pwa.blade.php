@extends('admin.admin_master')
@section('page_title', 'Account Management')

@php
    $isAdmin = auth()->user() && in_array(strtolower(trim((string) auth()->user()->role)), ['admin', 'super admin']);
@endphp

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    activeModal: null,
    saving: false,
    editForm: { id: null, amount: '', date: '', description: '', category_id: '' },
    openEditModal(trx) {
        this.editForm = {
            id: trx.id,
            amount: trx.amount,
            date: trx.date,
            description: trx.description || '',
            category_id: trx.category_id ?? '',
        };
        this.activeModal = 'edit';
    },
    deleteTransaction(id) {
        Swal.fire({
            title: 'Delete this transaction?',
            text: 'This reverses the journal entry and account balances. This cannot be undone.',
            icon: 'warning',
            input: 'password',
            inputPlaceholder: 'Enter your admin password to confirm',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            inputValidator: (value) => { if (!value) return 'Password is required.'; }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteTransactionForm');
                form.action = '{{ url('/account-management/transaction') }}/' + id;
                form.querySelector('input[name=\'password\']').value = result.value;
                form.submit();
            }
        });
    }
}" x-cloak>

    <div class="px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark mb-3">Account Management</h1>
        <div class="grid grid-cols-2 gap-3">
            <button @click="activeModal = 'deposit'"
                class="flex items-center justify-center gap-1.5 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px]">
                <i class="bi bi-arrow-down-short text-base"></i> Deposit
            </button>
            <button @click="activeModal = 'withdraw'"
                class="flex items-center justify-center gap-1.5 py-2.5 bg-primary text-white font-bold rounded-xl text-[13px]">
                <i class="bi bi-arrow-up-short text-base"></i> Withdraw
            </button>
        </div>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Balance</p>
            <p class="text-[16px] font-black text-primary">{{ $companyCurrency }} {{ number_format($totalBalance, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-box-arrow-in-down text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Deposits (MTD)</p>
            <p class="text-[16px] font-black text-primary">{{ $companyCurrency }} {{ number_format($depositsAmount, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-box-arrow-up text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Withdrawals (MTD)</p>
            <p class="text-[16px] font-black text-primary">{{ $companyCurrency }} {{ number_format($withdrawalsAmount, 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('account_management.index') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH TRANSACTIONS"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="type" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Types</option>
                <option value="deposits" {{ request('type') == 'deposits' ? 'selected' : '' }}>Deposits</option>
                <option value="withdrawals" {{ request('type') == 'withdrawals' ? 'selected' : '' }}>Withdrawals</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Transactions</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($transactions as $trx)
            @php
                $bankItem = $trx->items->first(fn($i) => $bankAccounts->contains('id', $i->account_id));
                $catItem  = $trx->items->first(fn($i) => !$bankAccounts->contains('id', $i->account_id));
                $displayAccount = $bankItem ?? $trx->items->first();
                $isDebit  = str_starts_with($trx->reference, 'WTH-') || str_starts_with($trx->reference, 'ADJ-');
                $type = match(true) {
                    str_starts_with($trx->reference, 'DEP-') => 'Deposit',
                    str_starts_with($trx->reference, 'WTH-') => 'Withdraw',
                    str_starts_with($trx->reference, 'TRF-') => 'Transfer',
                    default => 'Adjust',
                };
                $desc = $trx->description ?? '';
                $party = '—';
                if (preg_match('/^Deposit from (.+?) :/i', $desc, $m)) {
                    $party = trim($m[1]);
                } elseif (preg_match('/^Withdrawal to (.+?) :/i', $desc, $m)) {
                    $party = trim($m[1]);
                }
                $editPayload = [
                    'id' => $trx->id,
                    'amount' => (float) $trx->total_amount,
                    'date' => $trx->date->format('Y-m-d'),
                    'description' => $trx->description,
                    'category_id' => $catItem->account_id ?? null,
                ];
            @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight">{{ $type }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $displayAccount?->account->name ?? '—' }} <span class="mx-0.5">·</span> {{ $party }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black {{ $isDebit ? 'text-red-500' : 'text-accent' }}">{{ $isDebit ? '-' : '+' }}{{ $companyCurrency }}{{ number_format($trx->total_amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $trx->date->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-2.5">
                    <a href="{{ route('bank.transaction.receipt', $trx->id) }}"
                        class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                        <i class="bi bi-eye text-xs"></i>
                    </a>
                    @if($isAdmin)
                        <button type="button" @click="openEditModal(@js($editPayload))"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteTransaction({{ $trx->id }})"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-safe text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No transactions found.</p>
            </div>
        @endforelse
    </div>

    @if($transactions->hasPages())
        <div class="flex items-center justify-between px-5 py-4">
            <div class="text-[11px] text-gray-400">
                {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}
            </div>
            <div class="flex items-center gap-2">
                @if($transactions->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-left text-[10px]"></i></span>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-left text-[10px]"></i></a>
                @endif
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs">{{ $transactions->currentPage() }}</span>
                @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-right text-[10px]"></i></a>
                @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-right text-[10px]"></i></span>
                @endif
            </div>
        </div>
    @endif

    {{-- Deposit — mobile bottom sheet --}}
    <div x-show="activeModal === 'deposit'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'deposit'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Record Deposit</h2>
                <button @click="activeModal = null" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form action="{{ route('bank.transaction.deposit') }}" method="POST" class="p-5 flex flex-col gap-4">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Deposit To</label>
                    <div class="relative">
                        <select name="bank_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format($acc->balance, 2) }})</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Source <span class="text-gray-400 font-normal normal-case">(where money comes from)</span></label>
                    <div class="relative">
                        <select name="category_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($depositCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Received From</label>
                    <input type="text" name="received_from" placeholder="Customer / sender name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $companyCurrency }}</span>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Notes</label>
                    <input type="text" name="notes" placeholder="Notes (optional)"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" @click="saving = true"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Deposit'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Withdraw — mobile bottom sheet --}}
    <div x-show="activeModal === 'withdraw'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'withdraw'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Record Withdrawal</h2>
                <button @click="activeModal = null" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form action="{{ route('bank.transaction.withdraw') }}" method="POST" class="p-5 flex flex-col gap-4">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Withdraw From</label>
                    <div class="relative">
                        <select name="bank_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format($acc->balance, 2) }})</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Expense Category <span class="text-gray-400 font-normal normal-case">(what it pays for)</span></label>
                    <div class="relative">
                        <select name="category_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($withdrawalCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $companyCurrency }}</span>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Paid To</label>
                    <input type="text" name="paid_to" placeholder="Vendor / Recipient name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Notes</label>
                    <input type="text" name="notes" placeholder="Reason for withdrawal"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" @click="saving = true"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Withdrawal'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($isAdmin)
    {{-- Edit Transaction — mobile bottom sheet --}}
    <div x-show="activeModal === 'edit'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'edit'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Edit Transaction</h2>
                <button @click="activeModal = null" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form :action="'{{ url('/account-management/transaction') }}/' + editForm.id" method="POST" class="p-5 flex flex-col gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                    <input type="number" step="0.01" name="amount" x-model="editForm.amount" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" x-model="editForm.date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category</label>
                    <div class="relative">
                        <select name="category_id" x-model="editForm.category_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">— No change —</option>
                            @foreach($accountCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description</label>
                    <input type="text" name="description" x-model="editForm.description"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2">
                        <i class="bi bi-check2-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete form --}}
    <form id="deleteTransactionForm" method="POST" class="hidden">
        @csrf @method('DELETE')
        <input type="hidden" name="password">
    </form>
    @endif
</div>
@endsection
