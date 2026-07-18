@extends('admin.admin_master')
@section('page_title', 'Cash On Hand')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    txnSearch: '',
    typeFilter: 'all',
    activeModal: null,
    adjustType: 'increase',
    saving: false,
    transactions: @js($transactions),

    get types() {
        return [...new Set(this.transactions.map(t => t.type))].sort();
    },

    get filteredTransactions() {
        let list = this.transactions;
        if (this.typeFilter !== 'all') list = list.filter(t => t.type === this.typeFilter);
        if (this.txnSearch) {
            const term = this.txnSearch.toLowerCase();
            list = list.filter(t => (t.type + ' ' + t.name).toLowerCase().includes(term));
        }
        return list;
    },

    openAdjustModal() {
        this.adjustType = 'increase';
        this.activeModal = 'adjust-modal';
        this.$nextTick(() => document.getElementById('adjustForm').reset());
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <div class="flex items-center gap-2 min-w-0">
            <h1 class="text-[16px] font-black text-primary-dark whitespace-nowrap">Cash</h1>
            <span class="text-[16px] font-black whitespace-nowrap {{ $cashBalance < 0 ? 'text-red-500' : 'text-primary' }}">
                {{ $companyCurrency }}{{ number_format($cashBalance, $cashBalance == (int) $cashBalance ? 0 : 2) }}
            </span>
        </div>
        <button @click="openAdjustModal()"
            class="flex items-center gap-1 px-3 py-2 bg-primary text-white font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-sliders text-sm"></i> Adjust Cash
        </button>
    </div>

    @if(!$cashAccount)
        <div class="mx-5 mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400">
            <i class="bi bi-cash-coin text-3xl mb-2 block text-gray-300"></i>
            <p class="text-[13px] font-semibold">No Cash on Hand account found for your branch.</p>
        </div>
    @else

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="txnSearch" placeholder="SEARCH TRANSACTIONS"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
            <button x-show="txnSearch" @click="txnSearch = ''" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        <div class="relative shrink-0">
            <select x-model="typeFilter"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[110px]">
                <option value="all">All Types</option>
                <template x-for="t in types" :key="t">
                    <option :value="t" x-text="t"></option>
                </template>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </div>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Transactions</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="txn in filteredTransactions" :key="txn.sort_id">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="min-w-0 pr-3">
                    <p class="text-[14px] font-black text-text-primary leading-tight" x-text="txn.type"></p>
                    <p class="text-xs text-text-secondary mt-0.5 truncate" x-text="txn.name"></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[14px] font-black" :class="txn.direction === 'in' ? 'text-accent' : 'text-red-500'"
                        x-text="'{{ $companyCurrency }} ' + Math.round(txn.amount)"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="txn.date"></p>
                </div>
            </div>
        </template>
        <template x-if="!filteredTransactions.length">
            <div class="py-10 text-center">
                <i class="bi bi-receipt text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No transactions found.</p>
            </div>
        </template>
    </div>
    @endif

    {{-- Adjust Cash — mobile bottom sheet --}}
    <div x-show="activeModal === 'adjust-modal'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'adjust-modal'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Adjust Cash</h2>
                <button @click="activeModal = null" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form id="adjustForm" action="{{ route('bank.transaction.adjustment') }}" method="POST" class="p-5 flex flex-col gap-4">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $cashAccount->id ?? '' }}">

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Adjustment Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="adjustType = 'increase'"
                            :class="adjustType === 'increase' ? 'bg-accent text-primary border-accent' : 'bg-gray-50 text-gray-500 border-gray-200'"
                            class="px-4 py-2.5 rounded-lg border text-[13px] font-bold uppercase tracking-wide transition-all">
                            <i class="bi bi-plus-circle"></i> Increase
                        </button>
                        <button type="button" @click="adjustType = 'decrease'"
                            :class="adjustType === 'decrease' ? 'bg-red-500 text-white border-red-500' : 'bg-gray-50 text-gray-500 border-gray-200'"
                            class="px-4 py-2.5 rounded-lg border text-[13px] font-bold uppercase tracking-wide transition-all">
                            <i class="bi bi-dash-circle"></i> Decrease
                        </button>
                    </div>
                    <input type="hidden" name="type" :value="adjustType">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $companyCurrency }}</span>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Reason</label>
                    <textarea name="reason" rows="2" placeholder="Why is this adjustment being made..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
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
                        <span x-text="saving ? 'Saving...' : 'Save Adjustment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
