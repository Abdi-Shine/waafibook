@extends('admin.admin_master')
@section('page_title', 'Item Ledger')
@section('admin')

@php
    $symbol = '$';
@endphp

<div class="px-4 pt-6 md:px-8 bg-white">
    @include('frontend.product.partials.product_tabs', ['active' => ($type ?? 'product') === 'service' ? 'services' : 'products'])
</div>

<div class="h-[calc(100vh-9.5rem)] flex bg-background" x-data="{
    products: @js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->product_name, 'quantity' => $p->stocks_sum_quantity ?? 0])),
    search: '',
    txnSearch: '',
    selectedId: {{ $selectedId ?? 'null' }},
    ledger: @js($ledger),
    loading: false,

    get filteredProducts() {
        if (!this.search) return this.products;
        const term = this.search.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(term));
    },

    get filteredTransactions() {
        if (!this.ledger) return [];
        if (!this.txnSearch) return this.ledger.transactions;
        const term = this.txnSearch.toLowerCase();
        return this.ledger.transactions.filter(t =>
            (t.type || '').toLowerCase().includes(term) ||
            (t.name || '').toLowerCase().includes(term) ||
            String(t.ref || '').toLowerCase().includes(term)
        );
    },

    async selectProduct(id) {
        if (this.selectedId === id) return;
        this.selectedId = id;
        this.loading = true;
        try {
            const res = await fetch('{{ url('/products') }}/' + id + '/ledger-data', { headers: { 'Accept': 'application/json' } });
            this.ledger = await res.json();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Could not load item ledger.' });
        } finally {
            this.loading = false;
        }
    },

    async refreshLedger() {
        if (this.selectedId === null) return;
        this.loading = true;
        try {
            const res = await fetch('{{ url('/products') }}/' + this.selectedId + '/ledger-data', { headers: { 'Accept': 'application/json' } });
            this.ledger = await res.json();
            const p = this.products.find(p => p.id === this.selectedId);
            if (p) p.quantity = this.ledger.product.stock_quantity;
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Could not refresh item ledger.' });
        } finally {
            this.loading = false;
        }
    },

    // Main delete icon: deletes the item itself, blocked server-side (with
    // an explanatory message) if it's already used in any transaction.
    deleteProductItem(id, name) {
        deleteRecordWithPassword('{{ url('/products/delete') }}/' + id, name, {
            title: 'Delete Item?',
            text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
            onBlocked: () => Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: 'This product cannot be deleted as it is already used in transactions. Please delete all related transactions before deleting this product.',
                confirmButtonColor: '#004161'
            })
        });
    },

    // Transaction delete icon: deletes the underlying sale/purchase record
    // itself (password-confirmed), then refreshes this item's ledger in
    // place. Opening Stock deletes the journal entry created alongside the
    // product and backs the quantity it represented out of current stock.
    deleteTransaction(txn) {
        let url = null;
        if (txn.type === 'Sale' && txn.ref) {
            url = '{{ url('/sales/invoices') }}/' + txn.ref;
        } else if (txn.type === 'Purchase Order' && txn.ref) {
            url = '{{ url('/purchase/bills') }}/' + txn.ref;
        } else if (txn.type === 'Opening Stock') {
            url = '{{ url('/products') }}/' + this.selectedId + '/opening-stock';
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
        let rows = [['Type', 'Invoice/Ref', 'Name', 'Date', 'Quantity', 'Price/Unit', 'Status']];
        this.filteredTransactions.forEach(t => {
            rows.push([t.type, t.ref ?? '', t.name, t.date, t.quantity, t.price ?? '', t.status ?? '']);
        });
        const csv = rows.map(r => r.map(v => q + String(v).split(q).join(q + q) + q).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = (this.ledger.product.name || 'item') + '-transactions.csv';
        link.click();
    }
}">
    <!-- Left Sidebar - Item List -->
    <div class="w-72 shrink-0 border-r border-gray-100 bg-white flex flex-col">
        <div class="p-4 flex items-center gap-2 border-b border-gray-100">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" x-model="search" placeholder="Search items..."
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
            </div>
            <a href="{{ route('product.index', ['action' => 'create']) }}" title="Add Product"
                class="px-3 py-2 bg-accent text-primary font-bold rounded-[0.5rem] text-[12px] uppercase tracking-wide whitespace-nowrap hover:bg-accent/90 transition-all">
                <i class="bi bi-plus-lg"></i> Add Product
            </a>
        </div>

        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-background/50">
            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Item</span>
            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Quantity</span>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <template x-for="product in filteredProducts" :key="product.id">
                <div @click="selectProduct(product.id)"
                    class="flex items-center justify-between px-4 py-3 cursor-pointer border-b border-gray-50 hover:bg-gray-50 transition-colors group"
                    :class="selectedId === product.id ? 'bg-primary/5 border-l-[3px] border-l-primary' : ''">
                    <span class="text-[13px] font-semibold text-primary-dark truncate" :class="selectedId === product.id ? 'font-bold' : ''" x-text="product.name"></span>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[13px] font-bold text-accent" x-text="product.quantity"></span>
                        <button @click.stop="deleteProductItem(product.id, product.name)" title="Delete Item"
                            class="opacity-0 group-hover:opacity-100 text-primary hover:text-red-500 transition-all">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            </template>
            <template x-if="!filteredProducts.length">
                <p class="px-4 py-6 text-center text-[12px] text-gray-400">No items found.</p>
            </template>
        </div>
    </div>

    <!-- Right Panel - Item Detail & Transactions -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <template x-if="loading">
            <div class="flex-1 flex items-center justify-center text-gray-400">
                <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
            </div>
        </template>

        <template x-if="!loading && !ledger">
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-3 px-6 text-center">
                <i class="bi bi-inboxes text-4xl text-gray-300"></i>
                <p class="text-[13px] font-semibold" x-text="products.length ? 'Select an item to view its ledger' : 'No items yet — add one to get started'"></p>
            </div>
        </template>

        <template x-if="!loading && ledger">
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Item Header -->
                <div class="p-6 border-b border-gray-100 bg-white flex items-start justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-[20px] font-bold text-primary-dark uppercase tracking-wide flex items-center gap-2">
                            <span x-text="ledger.product.name"></span>
                            <a :href="'{{ route('product.index') }}'" title="Manage Items" class="text-gray-300 hover:text-primary transition-colors">
                                <i class="bi bi-arrow-up-right text-[14px]"></i>
                            </a>
                        </h1>
                        <div class="flex items-center gap-6 mt-3">
                            <p class="text-[13px] text-gray-500">SALE PRICE:
                                <span class="text-accent font-bold" x-text="'{{ $symbol }} ' + parseFloat(ledger.product.selling_price).toFixed(2)"></span>
                            </p>
                            <p class="text-[13px] text-gray-500">PURCHASE PRICE:
                                <span class="text-accent font-bold" x-text="'{{ $symbol }} ' + parseFloat(ledger.product.purchase_price).toFixed(2)"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-3">
                        <a href="{{ route('stock-adjustment.view') }}"
                            class="flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] text-[12px] uppercase tracking-wide hover:bg-primary/90 transition-all">
                            <i class="bi bi-sliders"></i> Adjust Item
                        </a>
                        <div class="text-right">
                            <p class="text-[13px] text-gray-500">STOCK QUANTITY: <span class="font-bold text-primary-dark" x-text="ledger.product.stock_quantity"></span></p>
                            <p class="text-[13px] text-gray-500">STOCK VALUE:
                                <span class="font-bold text-primary-dark" x-text="'{{ $symbol }} ' + parseFloat(ledger.product.stock_value).toFixed(2)"></span>
                            </p>
                        </div>
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
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Invoice/Ref</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Name</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Quantity</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Price/Unit</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="txn in filteredTransactions" :key="txn.type + txn.ref + txn.date">
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-5 py-3.5 text-[13px] font-semibold text-primary-dark">
                                            <span class="w-2 h-2 rounded-full inline-block mr-2" :class="txn.type_color"></span>
                                            <span x-text="txn.type"></span>
                                        </td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-500" x-text="txn.ref ?? ''"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-700" x-text="txn.name"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-500" x-text="txn.date"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-700 text-right" x-text="txn.quantity"></td>
                                        <td class="px-5 py-3.5 text-[13px] text-gray-700 text-right" x-text="txn.price !== null ? '{{ $symbol }} ' + parseFloat(txn.price).toFixed(2) : '-'"></td>
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
                                    <tr><td colspan="8" class="px-5 py-10 text-center text-[13px] text-gray-400">No transactions found for this item.</td></tr>
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
