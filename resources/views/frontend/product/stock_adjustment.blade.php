@extends('admin.admin_master')
@section('page_title', 'Stock Adjustment')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="{
    openModal: false,
    method: 'addition',
    productId: '',
    branchId: '',
    quantity: '',
    date: new Date().toISOString().slice(0, 10),

    openFor(productId, branchId) {
        this.method = 'addition';
        this.productId = productId ?? '';
        this.branchId = branchId ?? '';
        this.quantity = '';
        this.date = new Date().toISOString().slice(0, 10);
        this.openModal = true;
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Stock Adjustment</h1>
            <p class="text-[13px] text-gray-400 font-medium mt-0.5">Reconcile stock levels across branches</p>
        </div>
        <button @click="openFor()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-full hover:bg-primary/90 transition-all shadow-sm text-[13px]">
            <i class="bi bi-sliders"></i> Adjust Stock
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">SKU Inventory</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['sku_inventory']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-box-seam text-[10px]"></i> Tracked lines
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-box-seam text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Aggregate Units</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['aggregate_units']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-stack text-[10px]"></i> Units on hand
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-stack text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Critical Stocks</p>
                <h3 class="text-[18px] font-black {{ $stats['critical_stocks'] > 0 ? 'text-red-500' : 'text-primary' }}">{{ number_format($stats['critical_stocks']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-exclamation-triangle text-[10px]"></i> 10 units or fewer
                </p>
            </div>
            <div class="w-11 h-11 bg-red-50 rounded-[0.6rem] flex items-center justify-center text-red-500 flex-shrink-0">
                <i class="bi bi-exclamation-triangle text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Storage</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['active_storage']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-shop text-[10px]"></i> Branches with stock
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-shop text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Search + Table -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden">
        <form action="{{ route('stock-adjustment.view') }}" method="GET" class="p-4 border-b border-gray-100">
            <div class="relative max-w-sm">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by product name or code..."
                       class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-background/60 border-b border-gray-100">
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Product</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Branch</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Quantity</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($stocks as $stock)
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-5 py-3.5 text-[13px] font-bold text-primary-dark">{{ $stock->product->product_name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-[13px] text-gray-700">{{ $stock->branch->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-[13px] font-bold text-right {{ $stock->quantity <= 10 ? 'text-red-500' : 'text-primary-dark' }}">
                            {{ number_format($stock->quantity) }}
                        </td>
                        <td class="px-5 py-3.5 text-[13px] text-gray-500">{{ $stock->updated_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <button @click="openFor({{ $stock->product_id }}, {{ $stock->branch_id ?? 'null' }})"
                                class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-primary hover:border-primary transition-all inline-flex items-center justify-center text-sm shadow-sm" title="Adjust">
                                <i class="bi bi-sliders"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-[13px] text-gray-400">No stock records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $stocks->links() }}
        </div>
        @endif
    </div>

    <!-- Adjust Stock Modal -->
    <div x-show="openModal" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.25rem] w-full max-w-md overflow-hidden shadow-2xl flex flex-col" @click.away="openModal = false">

            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Stock Adjustment</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5">Reconcile a stock level</p>
                        </div>
                    </div>
                    <button @click="openModal = false" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form action="{{ route('stock-adjustment.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="method = 'addition'"
                        :class="method === 'addition' ? 'bg-accent text-primary border-accent' : 'bg-gray-50 text-gray-500 border-gray-200'"
                        class="px-2 py-2.5 rounded-lg border text-[11px] font-bold uppercase tracking-wide transition-all">
                        <i class="bi bi-plus-circle"></i> Add
                    </button>
                    <button type="button" @click="method = 'deduction'"
                        :class="method === 'deduction' ? 'bg-red-500 text-white border-red-500' : 'bg-gray-50 text-gray-500 border-gray-200'"
                        class="px-2 py-2.5 rounded-lg border text-[11px] font-bold uppercase tracking-wide transition-all">
                        <i class="bi bi-dash-circle"></i> Reduce
                    </button>
                    <button type="button" @click="method = 'physical'"
                        :class="method === 'physical' ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-500 border-gray-200'"
                        class="px-2 py-2.5 rounded-lg border text-[11px] font-bold uppercase tracking-wide transition-all">
                        <i class="bi bi-clipboard-check"></i> Set Exact
                    </button>
                </div>
                <input type="hidden" name="method" :value="method">

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Adjustment Date <span class="text-primary">*</span></label>
                    <input type="date" name="date" x-model="date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Product <span class="text-primary">*</span></label>
                    <select name="product_id" x-model="productId" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-primary">*</span></label>
                    <select name="branch_id" x-model="branchId" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        <option value="">-- Select Branch --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                        <span x-text="method === 'physical' ? 'New Quantity' : 'Quantity'"></span> <span class="text-primary">*</span>
                    </label>
                    <input type="number" name="quantity" x-model="quantity" step="0.01" min="0" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="openModal = false"
                        class="px-5 py-2.5 text-gray-500 font-bold rounded-lg hover:bg-gray-100 transition-all text-[12px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[12px] uppercase tracking-wide flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
