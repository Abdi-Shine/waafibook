@extends('admin.admin_master')
@section('page_title', 'Parties')
@section('admin')

@php
    $symbol = '$';
    $initialMobileView = $ledger ? 'detail' : 'list';
    $companyName = $company->name ?? 'us';
@endphp

<div x-data="{
    companyName: @js($companyName),
    parties: @js($parties),
    search: '',
    typeFilter: 'all',
    txnSearch: '',
    selectedType: '{{ $selectedType }}',
    selectedId: {{ $selectedId ?? 'null' }},
    ledger: @js($ledger),
    loading: false,
    mobileView: '{{ $initialMobileView }}',

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
        if (this.selectedType === type && this.selectedId === id && this.ledger) {
            this.mobileView = 'detail';
            return;
        }
        this.selectedType = type;
        this.selectedId = id;
        this.loading = true;
        try {
            const res = await fetch('{{ url('/parties') }}/' + type + '/' + id + '/ledger-data', { headers: { 'Accept': 'application/json' } });
            this.ledger = await res.json();
            this.mobileView = 'detail';
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
            text: 'Are you sure you want to delete this ' + txn.type.toLowerCase() + '? This action cannot be undone.',
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
    },

    statusBadgeClass(status) {
        const s = (status || '').toLowerCase();
        if (s === 'unpaid') return 'bg-amber-100 text-amber-700';
        if (s === 'paid') return 'bg-emerald-100 text-emerald-700';
        if (s === 'partial') return 'bg-orange-100 text-orange-600';
        if (s === 'unused') return 'bg-blue-100 text-blue-600';
        if (s === 'draft') return 'bg-gray-100 text-gray-500';
        return 'bg-gray-100 text-gray-600';
    },

    isPayment(type) {
        return type === 'Payment-In' || type === 'Payment-Out';
    },

    sendReminder() {
        if (!this.ledger) return;
        const phone = (this.ledger.party.phone || '').replace(/[^0-9]/g, '');
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This party has no phone number saved. Please add one first.' });
            return;
        }
        const name = this.ledger.party.name;
        const amt  = parseFloat(this.ledger.party.amount).toFixed(2);
        const co   = this.companyName;
        const msg  = 'Dear ' + name + ',\n\nWe would like to kindly remind you that your outstanding balance with *' + co + '* is *$' + amt + '*.\n\nPlease arrange payment at your earliest convenience.\n\nThank you for your business!';
        window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank');
    },

    sendStatement() {
        if (!this.ledger) return;
        const phone = (this.ledger.party.phone || '').replace(/[^0-9]/g, '');
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This party has no phone number saved. Please add one first.' });
            return;
        }
        const name = this.ledger.party.name;
        const co   = this.companyName;
        const url  = this.ledger.party.statement_url;
        const msg  = 'Dear ' + name + ',\n\nPlease find your account statement with *' + co + '* at the link below:\n\n' + url + '\n\nThank you for your business!';
        window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank');
    }
}">

    {{-- ═══════════════════════════ MOBILE LAYOUT ═══════════════════════════ --}}
    <div class="lg:hidden min-h-screen bg-gray-100">

        {{-- ── Mobile: Party List View ── --}}
        <div x-show="mobileView === 'list'" x-cloak>
            <div class="sticky top-0 z-20 bg-white border-b border-gray-100 shadow-sm px-4 pt-3 pb-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5">
                        <i class="bi bi-search text-gray-400 text-sm"></i>
                        <input type="text" x-model="search" placeholder="Search Party Name"
                            class="flex-1 text-[14px] outline-none bg-transparent text-gray-700 font-medium placeholder-gray-400">
                    </div>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="h-10 px-3.5 bg-accent text-primary font-black rounded-xl text-[12px] uppercase tracking-wide whitespace-nowrap">
                            <i class="bi bi-plus-lg"></i> New
                        </button>
                        <div x-show="open" x-cloak x-transition
                            class="absolute right-0 mt-1.5 w-40 bg-white rounded-xl shadow-lg border border-gray-100 z-30 overflow-hidden">
                            <a href="{{ route('customer.index') }}"
                                class="block px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50">New Customer</a>
                            <a href="{{ route('supplier.index') }}"
                                class="block px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 border-t border-gray-50">New Supplier</a>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-2.5 pb-1">
                    <button @click="typeFilter = 'all'"
                        class="px-3.5 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wide transition-all"
                        :class="typeFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">All</button>
                    <button @click="typeFilter = 'customer'"
                        class="px-3.5 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wide transition-all"
                        :class="typeFilter === 'customer' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">Customers</button>
                    <button @click="typeFilter = 'supplier'"
                        class="px-3.5 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wide transition-all"
                        :class="typeFilter === 'supplier' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-500'">Suppliers</button>
                </div>
            </div>

            <div class="px-4 pt-3 pb-24 space-y-2">
                <template x-for="party in filteredParties" :key="party.type + '-' + party.id">
                    <div @click="selectParty(party.type, party.id)"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5 flex items-center justify-between cursor-pointer active:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-[15px] text-white shrink-0"
                                :class="party.type === 'customer' ? 'bg-emerald-500' : 'bg-primary'">
                                <span x-text="party.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-primary-dark leading-snug" x-text="party.name"></p>
                                <p class="text-[11px] font-semibold mt-0.5 capitalize"
                                    :class="party.type === 'customer' ? 'text-emerald-500' : 'text-primary'"
                                    x-text="party.type"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[14px] font-bold"
                                :class="party.type === 'customer' ? 'text-emerald-500' : 'text-red-500'"
                                x-text="'$ ' + parseFloat(party.amount).toFixed(2)"></p>
                            <p class="text-[11px] text-gray-400 mt-0.5"
                                x-text="party.type === 'customer' ? 'Receivable' : 'Payable'"></p>
                        </div>
                    </div>
                </template>
                <template x-if="!filteredParties.length">
                    <div class="text-center py-16 text-gray-400">
                        <i class="bi bi-people text-4xl block mb-2"></i>
                        <p class="text-[13px] font-semibold">No parties found</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Mobile: Party Detail View ── --}}
        <div x-show="mobileView === 'detail'" x-cloak class="min-h-screen">

            {{-- Loading overlay --}}
            <div x-show="loading" class="fixed inset-0 bg-white/80 z-50 flex items-center justify-center">
                <i class="bi bi-arrow-repeat animate-spin text-3xl text-primary"></i>
            </div>

            <template x-if="ledger">
                <div>
                    {{-- Header --}}
                    <div class="sticky top-0 z-20 bg-white border-b border-gray-100 shadow-sm px-4 py-3 flex items-center gap-2">
                        <button @click="mobileView = 'list'"
                            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors shrink-0">
                            <i class="bi bi-arrow-left text-primary-dark text-lg"></i>
                        </button>
                        <h1 class="flex-1 text-[17px] font-black text-primary-dark">Party Details</h1>
                        <a :href="ledger.party.type === 'supplier' ? '{{ route('supplier.index') }}' : '{{ route('customer.index') }}'"
                            class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors shrink-0">
                            <i class="bi bi-pencil text-gray-400"></i>
                        </a>
                        <div x-data="{ menuOpen: false }" class="relative shrink-0">
                            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false"
                                class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
                                <i class="bi bi-three-dots-vertical text-gray-400"></i>
                            </button>
                            <div x-show="menuOpen" x-cloak x-transition
                                class="absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-30 overflow-hidden">
                                <button @click="exportCsv(); menuOpen = false"
                                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 flex items-center gap-2">
                                    <i class="bi bi-file-earmark-excel text-gray-400"></i> Export CSV
                                </button>
                                <button @click="window.print(); menuOpen = false"
                                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 border-t border-gray-50 flex items-center gap-2">
                                    <i class="bi bi-printer text-gray-400"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Party Info Card --}}
                    <div class="mx-4 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center font-black text-[18px] text-white shrink-0"
                                    :class="ledger.party.type === 'customer' ? 'bg-emerald-500' : 'bg-primary'">
                                    <span x-text="(ledger.party.name || '?').charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[15px] font-black text-primary-dark truncate" x-text="ledger.party.name"></p>
                                    <p class="text-[12px] text-gray-400 mt-1 flex items-center gap-1.5">
                                        <i class="bi bi-telephone text-[11px]"></i>
                                        <span x-text="ledger.party.phone || 'No phone number'"></span>
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        <template x-if="!ledger.party.credit_limit">
                                            <span>No Credit Limit Set</span>
                                        </template>
                                        <template x-if="ledger.party.credit_limit">
                                            <span>Credit Limit: $ <span x-text="parseFloat(ledger.party.credit_limit).toFixed(2)"></span></span>
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black"
                                    :class="ledger.party.type === 'customer' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
                                    x-text="ledger.party.type === 'customer' ? 'Receivable' : 'Payable'"></span>
                                <p class="text-[17px] font-black mt-1.5"
                                    :class="ledger.party.type === 'customer' ? 'text-emerald-600' : 'text-red-500'"
                                    x-text="'$ ' + parseFloat(ledger.party.amount).toFixed(2)"></p>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-4">
                            <button @click="sendReminder()"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[13px] font-bold text-primary-dark hover:bg-gray-100 transition-colors">
                                <i class="bi bi-whatsapp text-green-500 text-[15px]"></i> Send Reminder
                            </button>
                            <button @click="sendStatement()"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[13px] font-bold text-primary-dark hover:bg-gray-100 transition-colors">
                                <i class="bi bi-whatsapp text-green-500 text-[15px]"></i> Send Statement
                            </button>
                        </div>
                    </div>

                    {{-- Search Transactions --}}
                    <div class="px-4 mt-3">
                        <div class="bg-gray-100 flex items-center gap-2 px-3 py-2.5 rounded-xl">
                            <i class="bi bi-search text-gray-400 text-sm"></i>
                            <input type="text" x-model="txnSearch" placeholder="Search Transactions"
                                class="flex-1 text-[14px] text-gray-700 outline-none border-none ring-0 font-medium placeholder-gray-400 bg-transparent">
                            <button class="w-7 h-7 flex items-center justify-center text-gray-500">
                                <i class="bi bi-funnel text-[14px]"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Transaction Cards --}}
                    <div class="px-4 mt-3 pb-24 space-y-3">
                        <template x-for="txn in filteredTransactions" :key="txn.type + txn.number + txn.date">
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                {{-- Top: type name + number --}}
                                <div class="px-4 pt-4 pb-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[13px] font-bold text-primary-dark" x-text="txn.type"></span>
                                        <span class="text-[13px] font-bold text-gray-400" x-text="txn.number ? '#' + txn.number : ''"></span>
                                    </div>
                                    {{-- Status badge + date --}}
                                    <div class="flex items-center justify-between mt-1.5 pb-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                            :class="statusBadgeClass(txn.status)"
                                            x-text="txn.status"></span>
                                        <span class="text-[11px] text-gray-400" x-text="txn.date"></span>
                                    </div>
                                </div>

                                {{-- Amounts + actions --}}
                                <div class="border-t border-gray-100 px-4 py-3 flex items-center gap-6">
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total</p>
                                        <p class="text-[14px] font-black text-primary-dark mt-0.5"
                                            x-text="'$ ' + parseFloat(txn.total).toFixed(2)"></p>
                                    </div>
                                    <div x-show="txn.balance != null">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"
                                            x-text="isPayment(txn.type) ? 'Unused' : 'Balance'"></p>
                                        <p class="text-[14px] font-black text-primary-dark mt-0.5"
                                            x-text="'$ ' + parseFloat(txn.balance).toFixed(2)"></p>
                                    </div>

                                    <div class="ml-auto flex items-center gap-0.5">
                                        <button @click="window.print()"
                                            class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-primary rounded-lg transition-colors">
                                            <i class="bi bi-printer text-[16px]"></i>
                                        </button>
                                        <a :href="'https://wa.me/' + (ledger.party.phone || '').replace(/[^0-9]/g, '')" target="_blank"
                                            class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-green-600 rounded-lg transition-colors">
                                            <i class="bi bi-share text-[15px]"></i>
                                        </a>
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click="open = !open" @click.away="open = false"
                                                class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-primary-dark rounded-lg transition-colors">
                                                <i class="bi bi-three-dots-vertical text-[15px]"></i>
                                            </button>
                                            <div x-show="open" x-cloak x-transition
                                                class="absolute right-0 bottom-full mb-1 w-36 bg-white rounded-xl shadow-lg border border-gray-100 z-20 overflow-hidden">
                                                <button @click="deleteTransaction(txn); open = false"
                                                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-red-500 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="bi bi-trash3"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!filteredTransactions.length && !loading">
                            <div class="text-center py-16 text-gray-400">
                                <i class="bi bi-inbox text-4xl block mb-2"></i>
                                <p class="text-[13px] font-semibold">No transactions found</p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══════════════════════════ DESKTOP LAYOUT ═══════════════════════════ --}}
    <div class="hidden lg:flex h-[calc(100vh-5rem)] bg-background">

        {{-- Left Sidebar - Party List --}}
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

        {{-- Right Panel - Party Detail & Transactions --}}
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

</div>

@endsection
