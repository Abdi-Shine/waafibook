@extends('admin.admin_master')
@section('page_title', 'Item Ledger')
@section('admin')

@php
    $symbol = '$';
@endphp

@if($selectedId && $ledger)
    {{-- DETAIL SCREEN --}}
    <div class="flex flex-col bg-gray-50 min-h-screen pb-24" x-data="{
        txnSearch: '',
        ledger: @js($ledger),
        get filteredTransactions() {
            if (!this.txnSearch) return this.ledger.transactions;
            const term = this.txnSearch.toLowerCase();
            return this.ledger.transactions.filter(t =>
                (t.type || '').toLowerCase().includes(term) ||
                (t.name || '').toLowerCase().includes(term) ||
                String(t.ref || '').toLowerCase().includes(term)
            );
        },
        deleteProductItem(id, name) {
            deleteRecordWithPassword('{{ url('/products/delete') }}/' + id, name, {
                title: 'Delete Item?',
                text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
                onBlocked: () => Swal.fire({ icon:'error', title:'Cannot Delete', text:'This product is used in transactions.', confirmButtonColor:'#004161' }),
                onSuccess: () => { window.location = '{{ route('product.ledger', ['type' => $type ?? 'product']) }}'; }
            });
        },
        deleteTransaction(txn) {
            let url = null;
            if (txn.type === 'Sale' && txn.ref) url = '{{ url('/sales/invoices') }}/' + txn.ref;
            else if (txn.type === 'Purchase Order' && txn.ref) url = '{{ url('/purchase/bills') }}/' + txn.ref;
            else if (txn.type === 'Opening Stock') url = '{{ url('/products') }}/{{ $selectedId }}/opening-stock';
            if (!url) return;
            deleteRecordWithPassword(url, txn.type, {
                title: 'Delete Transaction?',
                text: `Delete this ${txn.type.toLowerCase()}? This cannot be undone.`,
                onSuccess: () => window.location.reload()
            });
        }
    }">

        {{-- Top bar --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-100 sticky top-0 z-10">
            <a href="{{ route('product.ledger', ['type' => $type ?? 'product']) }}"
               class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-700 shrink-0">
                <i class="bi bi-arrow-left text-base"></i>
            </a>
            <h1 class="flex-1 text-[16px] font-bold text-gray-900 uppercase truncate">
                {{ $ledger['product']['name'] }}
            </h1>
            <a href="{{ route('product.index', ['action' => 'edit', 'id' => $selectedId]) }}"
               class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 shrink-0">
                <i class="bi bi-pencil text-sm"></i>
            </a>
            <button @click="deleteProductItem({{ $selectedId }}, {{ Js::from($ledger['product']['name']) }})"
                class="w-9 h-9 flex items-center justify-center rounded-full bg-red-50 text-red-400 shrink-0">
                <i class="bi bi-trash3 text-sm"></i>
            </button>
        </div>

        {{-- Price cards row --}}
        <div class="grid grid-cols-2 gap-3 px-4 pt-4">
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Sale Price</p>
                <p class="text-[18px] font-black text-accent">{{ $symbol }} {{ number_format($ledger['product']['selling_price'], 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Purchase Price</p>
                <p class="text-[18px] font-black text-accent">{{ $symbol }} {{ number_format($ledger['product']['purchase_price'], 2) }}</p>
            </div>
        </div>

        {{-- Adjust Item button --}}
        <div class="px-4 pt-3">
            <a href="{{ route('stock-adjustment.view') }}"
               class="flex items-center justify-center gap-2 w-full py-3 bg-primary text-white font-bold rounded-2xl text-[13px] uppercase tracking-wide shadow-sm active:opacity-80 transition-opacity">
                <i class="bi bi-sliders text-base"></i> Adjust Item
            </a>
        </div>

        {{-- Stock info --}}
        <div class="px-4 pt-3">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex justify-between items-center">
                <div>
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Stock Quantity</p>
                    <p class="text-[16px] font-black text-gray-900 mt-0.5">{{ $ledger['product']['stock_quantity'] }}</p>
                </div>
                <div class="w-px h-10 bg-gray-100"></div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Stock Value</p>
                    <p class="text-[16px] font-black text-gray-900 mt-0.5">{{ $symbol }} {{ number_format($ledger['product']['stock_value'], 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="px-4 pt-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-[13px] font-black text-gray-700 uppercase tracking-wider">Transactions</h2>
            </div>

            <div class="relative mb-3">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-sm"></i>
                <input type="text" x-model="txnSearch" placeholder="Search transactions..."
                    class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-[13px] text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
            </div>

            <div class="space-y-2">
                <template x-for="txn in filteredTransactions" :key="txn.type + txn.ref + txn.date">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full shrink-0 mt-0.5" :class="txn.type_color"></span>
                                    <span class="text-[14px] font-bold text-gray-900" x-text="txn.type"></span>
                                    <span class="text-[12px] text-gray-400 font-medium" x-text="txn.ref ? '#' + txn.ref : ''"></span>
                                </div>
                                <p class="text-[12px] text-gray-500 mt-1 ml-4 truncate" x-text="txn.name"></p>
                            </div>
                            <button @click="deleteTransaction(txn)" class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full bg-red-50 text-red-400">
                                <i class="bi bi-trash3 text-xs"></i>
                            </button>
                        </div>
                        <div class="mt-3 flex items-center gap-3 flex-wrap text-[12px]">
                            <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-lg font-medium" x-text="txn.date"></span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg font-bold" x-text="'Qty: ' + txn.quantity"></span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg font-bold" x-text="txn.price !== null ? '{{ $symbol }} ' + parseFloat(txn.price).toFixed(2) : '-'"></span>
                            <template x-if="txn.status">
                                <span class="px-2 py-1 bg-accent/10 text-primary rounded-lg font-bold" x-text="txn.status"></span>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="!filteredTransactions.length">
                    <p class="py-16 text-center text-[13px] text-gray-400">No transactions found.</p>
                </template>
            </div>
        </div>

    </div>
@else
    {{-- LIST SCREEN --}}
    <div x-data="{
        products: @js($products->map(fn($p) => [
            'id'             => $p->id,
            'name'           => $p->product_name,
            'category'       => $p->category->name ?? null,
            'selling_price'  => $p->selling_price,
            'purchase_price' => $p->purchase_price,
            'quantity'       => $p->stocks_sum_quantity ?? 0,
        ])),
        search: '',
        get filteredProducts() {
            if (!this.search) return this.products;
            const term = this.search.toLowerCase();
            return this.products.filter(p => p.name.toLowerCase().includes(term));
        }
    }" class="flex flex-col bg-gray-50 min-h-screen pb-24">

        {{-- Tabs --}}
        <div class="bg-white border-b border-gray-100 px-4">
            @include('frontend.product.partials.product_tabs', ['active' => ($type ?? 'product') === 'service' ? 'services' : 'products'])
        </div>

        {{-- Search --}}
        <div class="px-4 py-3 bg-white border-b border-gray-100">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-base"></i>
                <input type="text" x-model="search" placeholder="Search Items by Name or Code"
                    class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-[14px] text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
            </div>
        </div>

        {{-- Product Cards --}}
        <div class="flex-1 divide-y divide-gray-100 bg-white">
            <template x-for="product in filteredProducts" :key="product.id">
                <a :href="'{{ route('product.ledger', ['type' => $type ?? 'product']) }}' + '&product_id=' + product.id"
                    class="flex items-start gap-3 px-4 py-4 active:bg-gray-50 transition-colors">

                    <div class="flex-1 min-w-0">
                        {{-- Name --}}
                        <p class="text-[15px] font-bold text-gray-900 leading-tight" x-text="product.name"></p>

                        {{-- Category badge --}}
                        <template x-if="product.category">
                            <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-bold uppercase tracking-wide" x-text="product.category"></span>
                        </template>

                        {{-- Price + Stock row --}}
                        <div class="flex items-center gap-4 mt-2.5">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Sale Price</p>
                                <p class="text-[13px] font-bold text-gray-800" x-text="'$ ' + parseFloat(product.selling_price).toFixed(2)"></p>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">Purchase Price</p>
                                <p class="text-[13px] font-bold text-gray-800" x-text="'$ ' + parseFloat(product.purchase_price).toFixed(2)"></p>
                            </div>
                            <div class="w-px h-8 bg-gray-200"></div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wide">In Stock</p>
                                <p class="text-[13px] font-bold text-accent" x-text="parseFloat(product.quantity).toFixed(2)"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Share icon --}}
                    <span class="shrink-0 mt-1 w-8 h-8 flex items-center justify-center text-gray-300">
                        <i class="bi bi-share text-base"></i>
                    </span>
                </a>
            </template>

            <template x-if="!filteredProducts.length">
                <p class="px-4 py-16 text-center text-[13px] text-gray-400">No items found.</p>
            </template>
        </div>

        {{-- Floating Add Product button --}}
        <div class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50">
            <a href="{{ route('product.index', ['action' => 'create']) }}"
                class="flex items-center gap-2 px-6 py-3.5 bg-accent text-primary font-bold rounded-full shadow-xl text-[14px] active:scale-95 transition-transform">
                <i class="bi bi-plus-lg text-lg"></i> Add Product
            </a>
        </div>

    </div>
@endif

@endsection
