@extends('admin.admin_master')
@section('page_title', 'Cash In Hand')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{
    activeModal: null,
    txnSearch: '',
    typeFilter: 'all',
    sortField: 'date',
    sortDirection: 'desc',
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
        const field = this.sortField;
        const dir = this.sortDirection === 'asc' ? 1 : -1;
        return [...list].sort((a, b) => {
            let av = field === 'date' ? a.sort_date : a[field];
            let bv = field === 'date' ? b.sort_date : b[field];
            if (typeof av === 'string') { av = av.toLowerCase(); bv = bv.toLowerCase(); }
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    },

    toggleSort(field) {
        if (this.sortField === field) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortDirection = 'asc';
        }
    },

    openAdjustModal() {
        this.adjustType = 'increase';
        this.activeModal = 'adjust-modal';
        this.$nextTick(() => document.getElementById('adjustForm').reset());
    }
}" x-cloak>

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex items-center gap-3">
            <h1 class="text-[22px] font-bold text-primary-dark">Cash In Hand</h1>
            <span class="text-[20px] font-bold {{ $cashBalance < 0 ? 'text-red-500' : 'text-primary-dark' }}">
                {{ $companyCurrency }} {{ number_format($cashBalance, $cashBalance == (int) $cashBalance ? 0 : 2) }}
            </span>
        </div>
        <button @click="openAdjustModal()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-full hover:bg-primary/90 transition-all shadow-sm text-[13px]">
            <i class="bi bi-sliders"></i> Adjust Cash
        </button>
    </div>

    @if(!$cashAccount)
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm p-10 text-center text-gray-400">
            <i class="bi bi-cash-coin text-3xl mb-2 block text-gray-300"></i>
            No Cash on Hand account found for your branch. Set one up in Chart of Accounts first.
        </div>
    @else
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100">
            <h2 class="text-[15px] font-bold text-primary-dark">Transactions</h2>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="txnSearch" placeholder="Search transactions..."
                        class="pl-9 pr-3 py-2 w-64 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
                <div class="relative">
                    <select x-model="typeFilter" style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                        class="pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        <option value="all">All Types</option>
                        <template x-for="t in types" :key="t">
                            <option :value="t" x-text="t"></option>
                        </template>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-background/60 border-b border-gray-100">
                        <th @click="toggleSort('type')" class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider cursor-pointer select-none hover:text-primary transition-colors">
                            Type
                            <i class="bi ms-1" :class="sortField === 'type' ? (sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-caret-down text-gray-300'"></i>
                        </th>
                        <th @click="toggleSort('name')" class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider cursor-pointer select-none hover:text-primary transition-colors">
                            Name
                            <i class="bi ms-1" :class="sortField === 'name' ? (sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-caret-down text-gray-300'"></i>
                        </th>
                        <th @click="toggleSort('date')" class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider cursor-pointer select-none hover:text-primary transition-colors">
                            Date
                            <i class="bi ms-1" :class="sortField === 'date' ? (sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-caret-down text-gray-300'"></i>
                        </th>
                        <th @click="toggleSort('amount')" class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right cursor-pointer select-none hover:text-primary transition-colors">
                            Amount
                            <i class="bi ms-1" :class="sortField === 'amount' ? (sortDirection === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-caret-down text-gray-300'"></i>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="txn in filteredTransactions" :key="txn.sort_id">
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-3.5 text-[13px] font-bold text-primary-dark" x-text="txn.type"></td>
                            <td class="px-5 py-3.5 text-[13px] text-gray-700" x-text="txn.name"></td>
                            <td class="px-5 py-3.5 text-[13px] text-gray-500" x-text="txn.date"></td>
                            <td class="px-5 py-3.5 text-[13px] font-bold text-right"
                                :class="txn.direction === 'in' ? 'text-accent' : 'text-red-500'"
                                x-text="'{{ $companyCurrency }} ' + Math.round(txn.amount)"></td>
                        </tr>
                    </template>
                    <template x-if="!filteredTransactions.length">
                        <tr><td colspan="4" class="px-5 py-10 text-center text-[13px] text-gray-400">No transactions found.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Adjust Cash Modal -->
    <div x-show="activeModal === 'adjust-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-md overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Adjust Cash</h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Correct the Cash in Hand balance</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form id="adjustForm" action="{{ route('bank.transaction.adjustment') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $cashAccount->id ?? '' }}">

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Adjustment Type</label>
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

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $companyCurrency }}</span>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" required value="{{ now()->format('Y-m-d') }}"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reason</label>
                    <textarea name="reason" rows="2" placeholder="Why is this adjustment being made..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="activeModal = null"
                        class="px-5 py-2.5 text-gray-500 font-bold rounded-lg hover:bg-gray-100 transition-all text-[12px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" @click="saving = true"
                        class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[12px] uppercase tracking-wide flex items-center gap-2">
                        <i class="bi" :class="saving ? 'bi-hourglass-split animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Adjustment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
