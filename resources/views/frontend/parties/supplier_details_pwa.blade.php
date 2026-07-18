@extends('admin.admin_master')
@section('page_title', 'Suppliers')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    search: '',
    suppliers: @js($suppliers->map(fn($s) => [
        'id'      => $s->id,
        'name'    => $s->name,
        'amount'  => abs($s->amount_balance),
        'date'    => $s->latest_date ? \Carbon\Carbon::parse($s->latest_date)->format('d M Y') : '—',
        'label'   => $s->amount_balance > 0 ? 'You\'ll Pay' : ($s->amount_balance < 0 ? 'You\'ll Get' : 'Settled'),
        'labelColor' => $s->amount_balance > 0 ? 'text-red-500' : ($s->amount_balance < 0 ? 'text-accent' : 'text-gray-400'),
        'url'     => route('parties.ledger', ['type' => 'supplier', 'id' => $s->id]),
    ])),
    get filtered() {
        if (!this.search) return this.suppliers;
        const q = this.search.toLowerCase();
        return this.suppliers.filter(s => s.name.toLowerCase().includes(q));
    }
}">
    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH PARTY"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
            <button x-show="search" @click="search = ''" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        <a href="{{ route('supplier.index') }}?reopen_create=1"
           class="flex items-center gap-1 px-3 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Party
        </a>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="supplier in filtered" :key="supplier.id">
            <a :href="supplier.url"
               class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                <div class="min-w-0 pr-3">
                    <p class="text-[15px] font-black text-text-primary leading-tight truncate" x-text="supplier.name.toUpperCase()"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="supplier.date"></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[15px] font-black text-text-primary" x-text="'$ ' + parseFloat(supplier.amount).toFixed(2)"></p>
                    <p class="text-xs font-bold mt-0.5" :class="supplier.labelColor" x-text="supplier.label"></p>
                </div>
            </a>
        </template>
        <template x-if="!filtered.length">
            <div class="py-10 text-center">
                <i class="bi bi-truck text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold" x-text="search ? 'No suppliers match your search' : 'No suppliers yet'"></p>
            </div>
        </template>
    </div>
</div>
@endsection
