@extends('admin.admin_master')
@section('page_title', 'Item Ledger')
@section('admin')

@php
    $symbol = '$';
@endphp

@if($selectedId && $ledger)
    {{-- DETAIL SCREEN --}}
    <div x-data="{
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
                onBlocked: () => Swal.fire({
                    icon: 'error',
                    title: 'Cannot Delete',
                    text: 'This product cannot be deleted as it is already used in transactions. Please delete all related transactions before deleting this product.',
                    confirmButtonColor: '#004161'
                }),
                onSuccess: () => { window.location = '{{ route('product.ledger', ['type' => $type ?? 'product']) }}'; }
            });
        },

        deleteTransaction(txn) {
            let url = null;
            if (txn.type === 'Sale' && txn.ref) {
                url = '{{ url('/sales/invoices') }}/' + txn.ref;
            } else if (txn.type === 'Purchase Order' && txn.ref) {
                url = '{{ url('/purchase/bills') }}/' + txn.ref;
            } else if (txn.type === 'Opening Stock') {
                url = '{{ url('/products') }}/{{ $selectedId }}/opening-stock';
            }
            if (!url) return;

            deleteRecordWithPassword(url, txn.type, {
                title: 'Delete Transaction?',
                text: `Are you sure you want to delete this ${txn.type.toLowerCase()}? This action cannot be undone.`,
                onSuccess: () => window.location.reload()
            });
        }
    }">
        <div class="p-4 flex items-center gap-3 bg-white border-b border-gray-100">
            <a href="{{ route('product.ledger', ['type' => $type ?? 'product']) }}" class="w-8 h-8 flex items-center justify-center text-primary-dark shrink-0">
                <i class="bi bi-arrow-left text-lg"></i>
            </a>
            <h1 class="text-[15px] font-bold text-primary-dark uppercase tracking-wide truncate">{{ $ledger['product']['name'] }}</h1>
            <button @click="deleteProductItem({{ $selectedId }}, {{ Js::from($ledger['product']['name']) }})" title="Delete Item" class="ml-auto w-8 h-8 flex items-center justify-center text-primary hover:text-red-500 transition-colors shrink-0">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="p-4 bg-white border-b border-gray-100">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Sale Price</p>
                    <p class="text-[14px] font-bold text-accent">{{ $symbol }} {{ number_format($ledger['product']['selling_price'], 2) }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Purchase Price</p>
                    <p class="text-[14px] font-bold text-accent">{{ $symbol }} {{ number_format($ledger['product']['purchase_price'], 2) }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Stock Quantity</p>
                    <p class="text-[14px] font-bold text-primary-dark">{{ $ledger['product']['stock_quantity'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">Stock Value</p>
                    <p class="text-[14px] font-bold text-primary-dark">{{ $symbol }} {{ number_format($ledger['product']['stock_value'], 2) }}</p>
                </div>
            </div>

            <a href="{{ route('stock-adjustment.view') }}"
                class="mt-4 flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] text-[12px] uppercase tracking-wide hover:bg-primary/90 transition-all">
                <i class="bi bi-sliders"></i> Adjust Item
            </a>
        </div>

        <div class="p-4">
            <div class="relative mb-3">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" x-model="txnSearch" placeholder="Search transactions..."
                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
            </div>

            <div class="space-y-2">
                <template x-for="txn in filteredTransactions" :key="txn.type + txn.ref + txn.date">
                    <div class="bg-white rounded-[0.75rem] border border-gray-200/80 p-3.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" :class="txn.type_color"></span>
                                <span class="text-[13px] font-bold text-primary-dark truncate" x-text="txn.type"></span>
                                <span class="text-[12px] text-gray-400" x-text="txn.ref ? '#' + txn.ref : ''"></span>
                            </div>
                            <button @click="deleteTransaction(txn)" title="Delete Transaction" class="shrink-0 text-primary hover:text-red-500 transition-colors">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                        <p class="text-[12px] text-gray-500 mt-1 truncate" x-text="txn.name"></p>
                        <div class="flex items-center justify-between mt-2 text-[12px]">
                            <span class="text-gray-400" x-text="txn.date"></span>
                            <span class="text-gray-700" x-text="'Qty: ' + txn.quantity"></span>
                            <span class="text-gray-700" x-text="txn.price !== null ? '{{ $symbol }} ' + parseFloat(txn.price).toFixed(2) : '-'"></span>
                            <span class="font-bold text-primary-dark" x-text="txn.status ?? ''"></span>
                        </div>
                    </div>
                </template>
                <template x-if="!filteredTransactions.length">
                    <p class="py-10 text-center text-[13px] text-gray-400">No transactions found for this item.</p>
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
                class="flex items-center gap-2 px-6 py-3.5 bg-red-500 text-white font-bold rounded-full shadow-xl text-[14px] active:scale-95 transition-transform">
                <i class="bi bi-plus-lg text-lg"></i> Add Product
            </a>
        </div>

    </div>
@endif

@endsection
