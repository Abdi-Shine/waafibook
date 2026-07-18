@extends('admin.admin_master')
@section('page_title', 'Products')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    search: '',
    products: @js($products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->product_name,
        'code'     => $p->product_code,
        'category' => $p->category->name ?? null,
        'stock'    => $p->product_type === 'service' ? null : (float) ($p->stocks_sum_quantity ?? 0),
        'price'    => (float) $p->selling_price,
        'type'     => $p->product_type,
        'url'      => route('product.ledger', ['type' => $p->product_type, 'product_id' => $p->id]),
    ])),
    get filtered() {
        if (!this.search) return this.products;
        const q = this.search.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q));
    }
}">
    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-box-seam text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Items</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-x-circle text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Out of Stock</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['out_of_stock']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Stock Values</p>
            <p class="text-[16px] font-black text-primary">${{ number_format($stats['stock_value'], 0) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH PRODUCT"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
            <button x-show="search" @click="search = ''" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        <a href="{{ route('product.index', ['action' => 'create', 'desktop' => 1]) }}"
           class="flex items-center gap-1 px-3 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Add Product
        </a>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="product in filtered" :key="product.id">
            <a :href="product.url"
               class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                <div class="min-w-0 pr-3">
                    <p class="text-[15px] font-black text-text-primary leading-tight truncate" x-text="product.name.toUpperCase()"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="product.category || (product.type === 'service' ? 'Service' : 'Uncategorized')"></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[15px] font-black text-text-primary" x-text="'$ ' + parseFloat(product.price).toFixed(2)"></p>
                    <p class="text-xs font-bold mt-0.5 text-gray-400" x-text="product.stock === null ? 'Service' : 'Stock: ' + parseFloat(product.stock).toFixed(2)"></p>
                </div>
            </a>
        </template>
        <template x-if="!filtered.length">
            <div class="py-10 text-center">
                <i class="bi bi-box-seam text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold" x-text="search ? 'No items match your search' : 'No products yet'"></p>
            </div>
        </template>
    </div>
</div>
@endsection
