@extends('admin.admin_master')
@section('page_title', 'Parties')
@section('admin')

@php
    $symbol = '$';
@endphp

<div class="h-[calc(100vh-5rem)] flex bg-background" x-data="{
    parties: @js($parties),
    search: '',
    typeFilter: 'all',
    txnSearch: '',
    selectedType: '{{ $selectedType }}',
    selectedId: {{ $selectedId ?? 'null' }},
    ledger: @js($ledger),
    loading: false,

    get filteredParties() {
        let list = this.parties;
        if (this.typeFilter !== 'all') list = list.filter(p => p.type === this.typeFilter);
        if (this.search) {
            const term = this.search.toLowerCase();
            list = list.filter(p => p.name.toLowerCase().includes(term));
        }
        return list;
    },

    get filteredTransactions() {
        if (!this.ledger) return [];
        if (!this.txnSearch) return this.ledger.transactions;
        const term = this.txnSearch.toLowerCase();
        return this.ledger.transactions.filter(t =>
            (t.type || '').toLowerCase().includes(term) ||
            String(t.number || '').toLowerCase().includes(term)
        );
    },

    async selectParty(type, id) {
        if (this.selectedType === type && this.selectedId === id) return;
        this.selectedType = type;
        this.selectedId = id;
        this.loading = true;
        try {
            const res = await fetch('{{ url('/parties') }}/' + type + '/' + id + '/ledger-data', { headers: { 'Accept': 'application/json' } });
            this.ledger = await res.json();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Could not load party ledger.' });
        } finally {
            this.loading = false;
        }
    },

    async refreshLedger() {
        if (!this.selectedType || this.selectedId === null) return;
        this.loading = true;
        try {
            const res = await fetch('{{ url('/parties') }}/' + this.selectedType + '/' + this.selectedId + '/ledger-data', { headers: { 'Accept': 'application/json' } });
            this.ledger = await res.json();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Could not refresh party ledger.' });
        } finally {
            this.loading = false;
        }
    },

    // Maps each transaction type to the route for the record actually
    // behind it, reusing each module's existing delete endpoint (with its
    // own balance/accounting reversal already built in) rather than
    // duplicating that logic here. Payment/Sale/Purchase routes differ by
    // party type since customers and suppliers use separate models.
    deleteTransaction(txn) {
        const isSupplier = this.ledger.party.type === 'supplier';
        let url = null;
        if (txn.type === 'Sale' && txn.id) {
            url = '{{ url('/sales/invoices') }}/' + txn.id;
        } else if (txn.type === 'Purchase' && txn.id) {
            url = '{{ url('/purchase/bills') }}/' + txn.id;
        } else if ((txn.type === 'Payment-In' || txn.type === 'Payment-Out') && txn.id) {
            url = isSupplier ? '{{ url('/payment-out/delete') }}/' + txn.id : '{{ url('/payment-in/delete') }}/' + txn.id;
        } else if (txn.type === 'Credit Note' && txn.id) {
            url = '{{ url('/sales-return') }}/' + txn.id;
        } else if (txn.type === 'Debit Note' && txn.id) {
            url = '{{ url('/purchase/returns') }}/' + txn.id;
        } else if (txn.type === 'Opening Balance') {
            url = '{{ url('/parties') }}/' + this.ledger.party.type + '/' + this.ledger.party.id + '/opening-balance';
        }
        if (!url) return;

        deleteRecordWithPassword(url, txn.type, {
            title: 'Delete Transaction?',
            text: `Are you sure you want to delete this ${txn.type.toLowerCase()}? This action cannot be undone.`,
            onSuccess: () => this.refreshLedger()
        });
    },

    exportCsv() {
        if (!this.ledger) return;
        const q = String.fromCharCode(34);
        let rows = [['Type', 'Number', 'Date', 'Total', 'Balance', 'Status']];
        this.filteredTransactions.forEach(t => {
            rows.push([t.type, t.number ?? '', t.date, t.total, t.balance, t.status ?? '']);
        });
        const csv = rows.map(r => r.map(v => q + String(v).split(q).join(q + q) + q).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = (this.ledger.party.name || 'party') + '-transactions.csv';
        link.click();
    }
}">
    <!-- Left Sidebar - Party List -->
    <div class="w-72 shrink-0 border-r border-gray-100 bg-white flex flex-col">
        <div class="p-4 flex items-center gap-2 border-b border-gray-100">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" x-model="search" placeholder="Search Party Name"
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
            </div>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false"
                    class="px-3 py-2 bg-accent text-primary font-bold rounded-[0.5rem] text-[12px] uppercase tracking-wide whitespace-nowrap hover:bg-accent/90 transition-all">
                    <i class="bi bi-plus-lg"></i> Add Party
                </button>
                <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden">
                    <a href="{{ route('customer.index') }}" class="block px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50">New Customer</a>
                    <a href="{{ route('supplier.index') }}" class="block px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 border-t border-gray-50">New Supplier</a>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-gray-100 bg-background/50">
            <button @click="typeFilter = 'all'" class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide transition-all" :class="typeFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">All</button>
            <button @click="typeFilter = 'customer'" class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide transition-all" :class="typeFilter === 'customer' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">Customers</button>
            <button @click="typeFilter = 'supplier'" class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide transition-all" :class="typeFilter === 'supplier' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">Suppliers</button>
        </div>

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-background/50">
            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Party Name</span>
            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Amount</span>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <template x-for="party in filteredParties" :key="party.type + '-' + party.id">
                <div @click="selectParty(party.type, party.id)"
                    class="flex items-center justify-between px-4 py-3 cursor-pointer border-b border-gray-50 hover:bg-gray-50 transition-colors"
                    :class="selectedType === party.type && selectedId === party.id ? 'bg-primary/5 border-l-[3px] border-l-primary' : ''">
                    <span class="text-[13px] font-semibold text-primary-dark truncate" :class="selectedType === party.type && selectedId === party.id ? 'font-bold' : ''" x-text="party.name"></span>
                    <span class="text-[13px] font-bold" :class="party.type === 'supplier' ? 'text-red-500' : 'text-accent'" x-text="parseFloat(party.amount).toFixed(2)"></span>
                </div>
            </template>
            <template x-if="!filteredParties.length">
                <p class="px-4 py-6 text-center text-[12px] text-gray-400">No parties found.</p>
            </template>
        </div>
    </div>

    <!-- Right Panel - Party Detail & Transactions -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <template x-if="loading">
            <div class="flex-1 flex items-center justify-center text-gray-400">
                <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
            </div>
        </template>

        <template x-if="!loading && !ledger">
            <div class="flex-1 flex items-center justify-center text-gray-400 flex-col gap-2">
                <i class="bi bi-people text-3xl"></i>
                <p class="text-[13px] font-semibold">No parties found. Add a customer or supplier to get started.</p>
            </div>
        </template>

        <template x-if="!loading && ledger">
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Party Header -->
                <div class="p-6 border-b border-gray-100 bg-white flex items-start justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-[18px] font-bold text-primary-dark flex items-center gap-2">
                            <span x-text="ledger.party.name"></span>
                            <a :href="ledger.party.type === 'supplier' ? '{{ route('supplier.index') }}' : '{{ route('customer.index') }}'" title="Manage Parties" class="text-gray-300 hover:text-primary transition-colors">
                                <i class="bi bi-pencil-square text-[14px]"></i>
                            </a>
                        </h1>
                        <p class="text-[12px] text-gray-400 mt-2">Phone Number</p>
                        <p class="text-[14px] font-semibold text-primary-dark" x-text="ledger.party.phone || '-'"></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a :href="'https://wa.me/' + (ledger.party.phone || '').replace(/[^0-9]/g, '')" target="_blank" title="WhatsApp"
                            x-show="ledger.party.phone"
                            class="w-9 h-9 flex items-center justify-center bg-green-50 text-green-600 rounded-full hover:bg-green-100 transition-all">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <a :href="ledger.party.type === 'supplier' ? '{{ url('/suppliers') }}/' + ledger.party.id + '/statement' : '{{ url('/customers') }}/' + ledger.party.id + '/statement'" title="View Full Statement"
                            class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-full hover:bg-primary/20 transition-all">
                            <i class="bi bi-file-earmark-text"></i>
                        </a>
                    </div>
                </div>

                <!-- Transactions -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-[13px] font-bold text-primary-dark uppercase tracking-wider">Transactions</h2>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" x-model="txnSearch" placeholder="Search transactions..."
                                    class="pl-9 pr-3 py-2 w-64 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                            <button @click="window.print()" title="Print"
                                class="w-9 h-9 flex items-center justify-center bg-white border border-gray-200 text-gray-600 rounded-[0.5rem] hover:bg-gray-50 transition-all">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button @click="exportCsv()" title="Export to CSV"
                                class="w-9 h-9 flex items-center justify-center bg-primary text-white rounded-[0.5rem] hover:bg-primary/90 transition-all">
                                <i class="bi bi-file-earmark-excel"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-background/60 border-b border-gray-100">
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Type</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Number</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Total</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Balance</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="txn in filteredTransactions" :key="txn.type + txn.number + txn.date">
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-5 py-3.5 text-[13px] font-semibold text-primary-dark">
                                            <span class="w-2 h-2 rounded-full inline-block mr-2" :class="txn.type_color"></span>
                                            <span x-text="txn.type"></span>
                                        </td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-500" x-text="txn.number ?? ''"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-500" x-text="txn.date"></td>
                                        <td class="px-5 py-3.5 text-[13px] font-semibold text-primary-dark text-right" x-text="'{{ $symbol }} ' + parseFloat(txn.total).toFixed(2)"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-700 text-right" x-text="'{{ $symbol }} ' + parseFloat(txn.balance).toFixed(2)"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-700" x-text="txn.status ?? ''"></td>
                                        <td class="px-5 py-3.5 text-right">
                                            <button @click="deleteTransaction(txn)" title="Delete Transaction"
                                                class="text-primary hover:text-red-500 transition-colors">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="!filteredTransactions.length">
                                    <tr><td colspan="7" class="px-5 py-10 text-center text-[13px] text-gray-400">No transactions found for this party.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@endsection
