@extends('admin.admin_master')
@section('page_title', 'Parties')

@section('admin')

@php
    $symbol = '$';
    $ledgerCompany = $company ?? \App\Models\Company::find(auth()->user()->company_id ?? 0);
    $initialMobileView = $ledger ? 'detail' : 'list';
@endphp

<script>
    window.__ledger = {
        companyName:  @json($ledgerCompany->name ?? 'us'),
        parties:      @json($parties),
        selectedType: @json($selectedType),
        selectedId:   @json($selectedId ?? null),
        ledger:       @json($ledger),
        mobileView:   @json($initialMobileView),
    };
</script>

{{-- TEMPORARY diagnostic banner — remove once the blank-party-list issue is
     found. Shows any uncaught JS error directly on the page since the
     reporter can't reach browser DevTools on their phone. --}}
<div id="__debugErrorBanner" style="display:none; position:fixed; top:0; left:0; right:0; z-index:99999; background:#dc2626; color:#fff; padding:10px; font-size:12px; word-break:break-all;"></div>
<script>
    window.addEventListener('error', function (e) {
        var el = document.getElementById('__debugErrorBanner');
        if (!el) return;
        el.style.display = 'block';
        el.textContent = 'JS ERROR: ' + e.message + ' (' + (e.filename || '?') + ':' + (e.lineno || '?') + ':' + (e.colno || '?') + ')';
    });
    window.addEventListener('unhandledrejection', function (e) {
        var el = document.getElementById('__debugErrorBanner');
        if (!el) return;
        el.style.display = 'block';
        el.textContent = 'PROMISE ERROR: ' + (e.reason && e.reason.message ? e.reason.message : e.reason);
    });
</script>

<div class="min-h-screen bg-gray-100" x-data="{
    companyName: window.__ledger.companyName,
    parties:     window.__ledger.parties,
    search: '',
    typeFilter: 'all',
    txnSearch: '',
    selectedType: window.__ledger.selectedType,
    selectedId:   window.__ledger.selectedId,
    ledger:       window.__ledger.ledger,
    loading: false,
    mobileView:   window.__ledger.mobileView,

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
        // Keep the URL in sync with the open party. selectParty() only
        // flips client-side state, so without this the address bar stays on
        // whatever was last loaded — if Android reclaims the WebView while
        // it's backgrounded (e.g. handing off to WhatsApp) and restores it
        // later, the restore reloads that stale URL and land back on the
        // list/default party instead of the one actually being viewed.
        history.replaceState(null, '', '{{ url('/parties/ledger') }}?type=' + type + '&id=' + id);

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

    // wa.me links need the full international number. Party phone numbers
    // are usually saved locally (e.g. "612040858") without the +252 country
    // code, which makes WhatsApp/iOS reject the link outright. Assume the
    // Somalia country code for short, code-less numbers.
    normalizePhone(raw) {
        let digits = (raw || '').replace(/[^0-9]/g, '');
        if (!digits || digits.startsWith('252')) return digits;
        const trimmed = digits.replace(/^0+/, '');
        return trimmed.length <= 9 ? '252' + trimmed : digits;
    },

    // window.open('_blank') silently no-ops in a standalone/installed PWA (no
    // browser chrome to open a new tab into), and window.location.href
    // navigates the PWA's own page away so returning from WhatsApp reloads
    // the app from scratch instead of resuming where you were. A synthetic
    // link click lets the OS hand off to WhatsApp without either problem.
    openWhatsApp(url) {
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    },

    sendReminder() {
        if (!this.ledger) return;
        const phone = this.normalizePhone(this.ledger.party.phone);
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This party has no phone number saved. Please add one first.' });
            return;
        }
        const name = this.ledger.party.name;
        const amt  = parseFloat(this.ledger.party.amount).toFixed(2);
        const co   = this.companyName;
        const msg  = 'Dear ' + name + ',\n\nWe would like to kindly remind you that your outstanding balance with *' + co + '* is *$' + amt + '*.\n\nPlease arrange payment at your earliest convenience.\n\nThank you for your business!';
        this.openWhatsApp('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg));
    },

    sendStatement() {
        if (!this.ledger) return;
        const phone = this.normalizePhone(this.ledger.party.phone);
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This party has no phone number saved. Please add one first.' });
            return;
        }
        const name = this.ledger.party.name;
        const co   = this.companyName;
        const url  = this.ledger.party.statement_url;
        const msg  = 'Dear ' + name + ',\n\nPlease find your account statement with *' + co + '* at the link below:\n\n' + url + '\n\nThank you for your business!';
        this.openWhatsApp('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg));
    },

    shareTransaction(txn) {
        if (!this.ledger) return;
        const phone = this.normalizePhone(this.ledger.party.phone);
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This party has no phone number saved. Please add one first.' });
            return;
        }
        const name  = this.ledger.party.name;
        const co    = this.companyName;
        const total = parseFloat(txn.total).toFixed(2);

        let msg = 'Dear ' + name + ',\n\nHere are the details of your ' + txn.type
            + (txn.number ? ' (' + txn.number + ')' : '') + ' with *' + co + '*:\n\n'
            + 'Date: ' + txn.date + '\nTotal: $' + total;

        if (txn.balance != null) {
            msg += '\n' + (this.isPayment(txn.type) ? 'Unused' : 'Balance') + ': $' + parseFloat(txn.balance).toFixed(2);
        }
        if (txn.status) {
            msg += '\nStatus: ' + txn.status;
        }

        msg += '\n\nThank you for your business!';
        this.openWhatsApp('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg));
    },

    goToPayout() {
        if (!this.ledger) return;
        const isSupplier = this.ledger.party.type === 'supplier';
        const base = isSupplier ? '{{ route('view_payment_out') }}' : '{{ route('view_payment_in') }}';
        const param = isSupplier ? 'vendor_id' : 'customer_id';
        window.location.href = base + '?' + param + '=' + this.ledger.party.id;
    }
}">

    {{-- ── Party List View ── --}}
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

    {{-- ── Party Detail View ── --}}
    <div x-show="mobileView === 'detail'" x-cloak class="min-h-screen">

        <div x-show="loading" class="fixed inset-0 bg-white/80 z-50 flex items-center justify-center">
            <i class="bi bi-arrow-repeat animate-spin text-3xl text-primary"></i>
        </div>

        <template x-if="ledger">
            <div>
                {{-- Header --}}
                <div class="sticky top-0 z-20 bg-white border-b border-gray-100 shadow-sm px-4 py-3 flex items-center gap-2">
                    <button @click="mobileView = 'list'; history.replaceState(null, '', '{{ url('/parties/ledger') }}')"
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
                        <button @click="goToPayout()"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-primary/10 border border-primary/20 rounded-xl text-[13px] font-bold text-primary hover:bg-primary/20 transition-colors">
                            <i class="bi bi-cash-coin text-[15px]"></i> Payout
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
                            <div class="px-4 pt-4 pb-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-[13px] font-bold text-primary-dark" x-text="txn.type"></span>
                                    <span class="text-[13px] font-bold text-gray-400" x-text="txn.number ? '#' + txn.number : ''"></span>
                                </div>
                                <div class="flex items-center justify-between mt-1.5 pb-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                        :class="statusBadgeClass(txn.status)"
                                        x-text="txn.status"></span>
                                    <span class="text-[11px] text-gray-400" x-text="txn.date"></span>
                                </div>
                            </div>

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
                                    <button @click="shareTransaction(txn)"
                                        class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-green-600 rounded-lg transition-colors">
                                        <i class="bi bi-share text-[15px]"></i>
                                    </button>
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

@endsection
