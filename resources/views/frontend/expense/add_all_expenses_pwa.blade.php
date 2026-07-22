@extends('admin.admin_master')
@section('page_title', 'Expenses')

@section('admin')
@php
    $totalThisMonth = $expenses->where('expense_date', '>=', now()->startOfMonth()->toDateString())->sum('amount');
    $approvedSum    = $expenses->where('status', 'Approved')->sum('amount');
    $pendingCount   = $expenses->where('status', 'Pending')->count();
    $cashOnHandAccount = $bankAccounts->first(fn($a) => stripos($a->name, 'Cash on Hand') !== false)
        ?? $bankAccounts->first(fn($a) => ($a->type ?? '') === 'cash')
        ?? $bankAccounts->first();
@endphp

<div class="pb-28 bg-background min-h-screen" x-data="{
    showAddModal: false,
    showEditModal: false,
    saving: false,
    searchQuery: '',
    filterCategory: '',
    filterStatus: '',
    editData: {},

    matchExpense(name, desc, accountId, status) {
        const q = this.searchQuery.toLowerCase();
        if (q && !name.toLowerCase().includes(q) && !desc.toLowerCase().includes(q)) return false;
        if (this.filterCategory && accountId != this.filterCategory) return false;
        if (this.filterStatus && status.toLowerCase() !== this.filterStatus.toLowerCase()) return false;
        return true;
    },
    openEditModal(expense) {
        this.editData = { ...expense };
        this.showEditModal = true;
    },
    deleteExpense(id, name) {
        deleteRecordWithPassword('{{ url('/expenses/delete') }}/' + id, name, {
            title: 'Delete Expense?',
            text: 'Are you sure? This will also remove the associated journal entry. This action cannot be undone.'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Expenses</h1>
        <button @click="showAddModal = true"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Add Expense
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">This Month</p>
            <p class="text-[16px] font-black text-primary">{{ $curr }} {{ number_format($totalThisMonth, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-check-circle text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Approved</p>
            <p class="text-[16px] font-black text-primary">{{ $curr }} {{ number_format($approvedSum, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-hourglass-split text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Pending</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($pendingCount) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="searchQuery" placeholder="SEARCH EXPENSES"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select x-model="filterStatus"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </div>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Expense Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($expenses as $expense)
            @php $editPayload = $expense->toArray(); @endphp
            <div x-show="matchExpense('{{ addslashes($expense->expense_name) }}', '{{ addslashes($expense->description) }}', '{{ $expense->expense_account_id }}', '{{ $expense->status }}')"
                class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $expense->expense_name }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $expense->account->name ?? 'Uncategorised' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $curr }} {{ number_format($expense->amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') : '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    @if(strtolower($expense->status) === 'approved')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-accent/10 text-accent">Approved</span>
                    @elseif(strtolower($expense->status) === 'pending')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 text-gray-400">Pending</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 text-gray-400">{{ $expense->status }}</span>
                    @endif
                    <div class="flex items-center gap-2">
                        <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        <button type="button" @click="openEditModal(@js($editPayload))"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteExpense({{ $expense->id }}, '{{ addslashes($expense->expense_name) }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-receipt text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No expenses recorded yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Add Expense — mobile bottom sheet --}}
    <div x-show="showAddModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddModal = false">
        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Add New Expense</h2>
            </div>

            <form action="{{ route('expenses.store') }}" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                <input type="hidden" name="payment_method" value="Cash">

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Expense Name <span class="text-primary">*</span></label>
                    <input type="text" name="expense_name" required placeholder="e.g., Office Rent"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                            <input type="number" step="0.01" min="0" name="amount" required placeholder="0.00"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                        <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="expense_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Category --</option>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Paid From <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="bank_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ ($cashOnHandAccount && $cashOnHandAccount->id == $account->id) ? 'selected' : '' }}>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description <span class="text-primary">*</span></label>
                    <textarea name="description" required rows="3" placeholder="Enter expense details or notes..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Expense'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Expense — mobile bottom sheet --}}
    <div x-show="showEditModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showEditModal = false">
        <div x-show="showEditModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Edit Expense Record</h2>
            </div>

            <form :action="'{{ url('/expenses/update') }}/' + editData.id" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                <input type="hidden" name="payment_method" value="Cash">

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Expense Name <span class="text-primary">*</span></label>
                    <input type="text" name="expense_name" x-model="editData.expense_name" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                            <input type="number" step="0.01" min="0" name="amount" x-model="editData.amount" required
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                        <input type="date" name="expense_date" x-model="editData.expense_date" required
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="expense_account_id" x-model="editData.expense_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Paid From <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="bank_account_id" x-model="editData.bank_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description <span class="text-primary">*</span></label>
                    <textarea name="description" x-model="editData.description" required rows="3"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showEditModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Update Expense'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
