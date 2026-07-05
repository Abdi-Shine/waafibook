@extends('admin.admin_master')
@section('page_title', 'Journal Entry')



@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen overflow-x-hidden" x-data="{
    activeModal: null,
    searchTerm: '',
    statusFilter: '',
    entryRows: [
        { account_id: '', description: '', debit: 0, credit: 0 },
        { account_id: '', description: '', debit: 0, credit: 0 }
    ],

    addRow() {
        this.entryRows.push({ account_id: '', description: '', debit: 0, credit: 0 });
    },

    removeRow(index) {
        if (this.entryRows.length > 2) {
            this.entryRows.splice(index, 1);
        }
    },

    get totalDebit() {
        return this.entryRows.reduce((sum, row) => sum + parseFloat(row.debit || 0), 0);
    },

    get totalCredit() {
        return this.entryRows.reduce((sum, row) => sum + parseFloat(row.credit || 0), 0);
    },

    get isBalanced() {
        return Math.abs(this.totalDebit - this.totalCredit) < 0.01 && this.totalDebit > 0;
    },

    openCreateModal() {
        this.entryRows = [
            { account_id: '', description: '', debit: 0, credit: 0 },
            { account_id: '', description: '', debit: 0, credit: 0 }
        ];
        this.activeModal = 'journal-modal';
    },

    confirmDelete(id, num) {
        deleteRecordWithPassword('{{ url('/journal-entries') }}/' + id, num, {
            title: 'Delete Journal Entry?',
            text: `Are you sure you want to delete ${num}?`
        });
    }
}" x-cloak>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Financial Journal Ledger</h1>
        </div>

    </div>

    <!-- Stats Cards (Matching Customer Design) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Entries</p>
                <h3 class="text-[18px] font-black text-primary">{{ $entries->count() }}</h3>
                <p class="text-xs text-accent mt-1.5 flex items-center gap-1 font-medium"><i class="bi bi-journal-text"></i> Recorded Ledger</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-journal-richtext text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Posted Entries</p>
                <h3 class="text-[18px] font-black text-primary">{{ $entries->where('status', 'posted')->count() }}</h3>
                <p class="text-xs text-accent mt-1.5 font-medium flex items-center gap-1"><i class="bi bi-check2-circle"></i> Finalized</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-check2-all text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Open Drafts</p>
                <h3 class="text-[18px] font-black text-primary">{{ $entries->where('status', 'draft')->count() }}</h3>
                <p class="text-xs text-primary mt-1.5 font-medium">Pending Review</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-pencil-square text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Volume</p>
                <h3 class="text-[18px] font-black text-primary">$ {{ number_format($entries->sum('total_amount'), 2) }}</h3>
                <p class="text-xs text-accent mt-1.5 font-medium">Global Value</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-graph-up text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm overflow-hidden mb-8">

        <!-- Filters (Matching Customer Design) -->
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <div class="relative group min-w-[300px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" placeholder="Search by entry #, reference, or description..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <div class="relative min-w-[160px]">
                <select x-model="statusFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="posted">Posted</option>
                    <option value="draft">Draft</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </div>

        <!-- Journal Table -->
        <div class="animate-in fade-in duration-500">
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Journal Entry List</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center italic">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Entry Detail</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Reference</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Debit Balance</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Credit Balance</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($entries as $entry)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                            x-show="(searchTerm === '' || '{{ strtolower($entry->entry_number) }} {{ strtolower($entry->description) }} {{ strtolower($entry->reference) }}'.includes(searchTerm.toLowerCase())) &&
                                    (statusFilter === '' || '{{ $entry->status }}' === statusFilter)">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center italic">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold text-primary-dark">{{ $entry->entry_number }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">{{ date('d M, Y', strtotime($entry->date)) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $entry->reference }}</span>
                                    <span class="text-[10px] text-gray-400 truncate max-w-[180px]">{{ $entry->description }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-black text-accent">$ {{ number_format($entry->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-black text-primary">$ {{ number_format($entry->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                @if($entry->status == 'posted')
                                    <span class="text-[11px] font-bold text-accent">Posted</span>
                                @elseif($entry->status == 'pending')
                                    <span class="text-[11px] font-bold text-primary">Pending</span>
                                @else
                                    <span class="text-[11px] font-bold text-gray-400">Draft</span>
                                @endif
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                    Showing 1 to {{ $entries->count() }} of {{ $entries->count() }} entries
                </div>
                <div class="flex items-center gap-1">
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-50 text-gray-400 border border-gray-200 cursor-not-allowed" disabled>
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </button>
                    <span class="w-7 h-7 flex items-center justify-center rounded bg-primary text-white font-bold text-[11px] border border-primary shadow-sm shadow-primary/20">1</span>
                    <button class="w-7 h-7 flex items-center justify-center rounded bg-white text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200 font-bold text-[11px]">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Create Journal Entry Modal -->
    <div x-show="activeModal === 'journal-modal'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-primary/40 backdrop-blur-md">

        <div class="bg-white rounded-[1.5rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col border border-white/10">
            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary text-xl">
                            <i class="bi bi-journal-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-white uppercase tracking-tighter">New Journal Entry</h2>
                            <p class="text-[9px] text-white/60 font-bold uppercase tracking-widest mt-0.5">Double-entry bookkeeping record</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-9 h-9 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-8 py-8 overflow-y-auto custom-scrollbar flex-grow bg-slate-50/30">
                <form id="journalForm" action="{{ route('journal.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Entry Date</label>
                            <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-primary focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">General Description</label>
                            <input type="text" name="description" required placeholder="e.g., Office Rent for March"
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-primary focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all">
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Entry Items (Double Entry)</h3>
                            <button type="button" @click="addRow()" class="text-[10px] font-black text-primary uppercase hover:text-primary transition-colors flex items-center gap-1.5">
                                <i class="bi bi-plus-circle-fill"></i> Add Line
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(row, index) in entryRows" :key="index">
                                <div class="grid grid-cols-12 gap-3 items-start animate-in fade-in slide-in-from-right-2 duration-300">
                                    <div class="col-span-12 md:col-span-4">
                                        <select :name="'items[' + index + '][account_id]'" x-model="row.account_id" required
                                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-primary focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">[{{ $acc->code }}] {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-4">
                                        <input type="text" :name="'items[' + index + '][description]'" x-model="row.description" placeholder="Line description..."
                                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-primary focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all">
                                    </div>
                                    <div class="col-span-5 md:col-span-2 relative">
                                        <input type="number" :name="'items[' + index + '][debit]'" x-model="row.debit" step="0.01" min="0" placeholder="0.00"
                                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-accent focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all text-right">
                                    </div>
                                    <div class="col-span-5 md:col-span-2 relative">
                                        <input type="number" :name="'items[' + index + '][credit]'" x-model="row.credit" step="0.01" min="0" placeholder="0.00"
                                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-primary focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none shadow-sm transition-all text-right">
                                    </div>
                                    <div class="col-span-2 md:col-span-1 flex justify-end pt-1.5">
                                        <button type="button" @click="removeRow(index)" class="text-slate-300 hover:text-primary transition-colors">
                                            <i class="bi bi-dash-circle-fill text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Balance Summary -->
                    <div class="mt-8 p-5 bg-white border border-slate-200 rounded-[1rem] flex flex-wrap justify-between items-center gap-6">
                        <div class="flex items-center gap-8">
                            <div class="text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Debit</p>
                                <p class="text-sm font-black text-accent">$ <span x-text="totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                            </div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Credit</p>
                                <p class="text-sm font-black text-primary">$ <span x-text="totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                                :class="isBalanced ? 'bg-accent/10 text-accent' : 'bg-primary/10 text-primary'">
                                <i :class="isBalanced ? 'bi bi-shield-check' : 'bi bi-shield-slash'"></i>
                                <span x-text="isBalanced ? 'ENTRY BALANCED' : 'OUT BY $ ' + Math.abs(totalDebit - totalCredit).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sticky Footer -->
            <div class="px-8 py-5 bg-white border-t border-slate-100 flex justify-end gap-3 shrink-0">
                <button type="button" @click="activeModal = null" class="px-6 py-3 bg-accent/20 text-primary font-bold rounded-xl hover:bg-accent/30 transition-all text-xs uppercase tracking-widest">
                    Dismiss
                </button>
                <button type="submit" form="journalForm" :disabled="!isBalanced"
                    :class="isBalanced ? '' : 'bg-slate-200 cursor-not-allowed text-slate-400'"
                    class="px-8 py-3 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all text-xs uppercase tracking-widest disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    Post Journal Entry
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


