@extends('admin.admin_master')
@section('page_title', 'Stock Summary')

@push('css')

@endpush

@section('admin')
<div class="report-premium-container"
     x-data="{
        searchTerm: '',
        categoryFilter: 'All',
        stockFilter: 'All',
        products: {{ \Illuminate\Support\Js::from($products) }},
        get filtered() {
            return this.products.filter(p => {
                const matchSearch = p.name.toLowerCase().includes(this.searchTerm.toLowerCase());
                const matchCat = this.categoryFilter === 'All' || p.category === this.categoryFilter;
                const matchStock = this.stockFilter === 'All' ||
                    (this.stockFilter === 'Low' && p.qty < 5) ||
                    (this.stockFilter === 'Medium' && p.qty >= 5 && p.qty <= 20) ||
                    (this.stockFilter === 'High' && p.qty > 20);
                return matchSearch && matchCat && matchStock;
            });
        },
        stockValue(p)   { return p.purchasePrice * p.qty; },
        totalQty()      { return this.filtered.reduce((s,p) => s + p.qty, 0); },
        totalValue()    { return this.filtered.reduce((s,p) => s + p.purchasePrice * p.qty, 0); },
        totalRevenue()  { return this.filtered.reduce((s,p) => s + p.salePrice * p.qty, 0); },
        stockClass(qty) { return qty < 5 ? 'text-primary' : qty <= 20 ? 'text-primary' : 'text-accent'; },
        stockLabel(qty) { return qty === 0 ? 'Out of Stock' : qty < 5 ? 'Low Stock' : qty <= 20 ? 'Medium' : 'Healthy'; },
        stockBadge(qty) { return qty === 0 ? 'report-premium-badge-error' : qty < 5 ? 'report-premium-badge-warning' : qty <= 20 ? 'report-premium-badge-info' : 'report-premium-badge-success'; }
     }">

    <!-- Page Header -->
    <div class="report-premium-header no-print">
        <div>
            <h1 class="report-premium-title">Summary Stock Report</h1>
            <p class="report-premium-subtitle">Current inventory levels with purchase & sale value overview</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="report-premium-btn-outline">
                <i class="bi bi-printer text-sm"></i> PRINT
            </button>
            <button class="report-premium-btn-outline">
                <i class="bi bi-file-earmark-excel text-sm"></i> EXCEL
            </button>
            <a href="{{ route('reports.summary_stock.pdf') }}" class="report-premium-btn-primary">
                <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="report-premium-stat-grid">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total SKUs</p>
                <h3 class="text-[18px] font-black text-primary" x-text="filtered.length"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Unique items</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Stock Quantity</p>
                <h3 class="text-[18px] font-black text-primary" x-text="totalQty()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Total units</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-stack"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Inventory Asset</p>
                <h3 class="text-[18px] font-black text-primary" x-text="'{{ $company->currency ?? 'SAR' }} ' + totalValue().toLocaleString()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">At Cost Value</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Est. Revenue</p>
                <h3 class="text-[18px] font-black text-primary" x-text="'{{ $company->currency ?? 'SAR' }} ' + totalRevenue().toLocaleString()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Expected Inflow</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <!-- Filter Bar -->
    <div class="report-premium-filter-bar no-print">
        <div class="report-premium-filter-group flex-1 min-w-[220px]">
            <span class="report-premium-filter-label">Search Inventory</span>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchTerm" placeholder="Search item name..." class="report-premium-filter-input !pl-9">
            </div>
        </div>
        <div class="report-premium-filter-group w-auto min-w-[170px]">
            <span class="report-premium-filter-label">Category</span>
            <select x-model="categoryFilter" class="report-premium-filter-input">
                <option value="All">All Categories</option>
                @foreach($categories as $categoryName)
                    <option>{{ $categoryName }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-premium-filter-group w-auto min-w-[170px]">
            <span class="report-premium-filter-label">Stock Intensity</span>
            <select x-model="stockFilter" class="report-premium-filter-input">
                <option value="All">All Levels</option>
                <option value="Low">Low (&lt;5)</option>
                <option value="Medium">Medium (5–20)</option>
                <option value="High">High (&gt;20)</option>
            </select>
        </div>
        <button class="report-premium-btn-primary h-[38px] mt-auto">
            <i class="bi bi-filter"></i> Apply
        </button>
    </div>

        <!-- Table Title -->
    <!-- Table Section -->
    <div class="report-premium-card overflow-hidden mb-6">
        <!-- Table Title Bar -->
        <div class="px-5 py-4 border-b border-brand-border bg-brand-bg/10 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Inventory Asset Matrix</h4>
            </div>
            <div class="flex items-center gap-2">
                <span class="report-premium-badge report-premium-badge-info !rounded-full italic font-black text-[9px] uppercase tracking-widest" x-text="filtered.length + ' SKUs Listed'"></span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="text-center w-12">#</th>
                        <th>Item Identity</th>
                        <th>Classification</th>
                        <th class="text-right">Unit MRP</th>
                        <th class="text-right">Unit COGS</th>
                        <th class="text-center">Phys. Qty</th>
                        <th class="text-center">Intensity</th>
                        <th class="text-right">Total Asset Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(product, index) in filtered" :key="product.id">
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4 text-[11px] font-black text-gray-400 text-center uppercase italic" x-text="index + 1"></td>
                            <td class="px-5 py-4">
                                <span class="text-xs font-black text-primary-dark uppercase" x-text="product.name"></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[11px] font-black text-gray-400 uppercase italic tracking-tighter" x-text="product.category"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-xs font-black font-mono text-accent" x-text="'{{ $company->currency ?? '$' }}' + product.salePrice.toLocaleString()"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-xs font-black font-mono text-primary" x-text="'{{ $company->currency ?? '$' }}' + product.purchasePrice.toLocaleString()"></span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs font-black font-mono" :class="stockClass(product.qty)" x-text="product.qty"></span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="report-premium-badge !rounded-full italic font-black text-[9px] uppercase tracking-widest" :class="stockBadge(product.qty)" x-text="stockLabel(product.qty)"></span>
                            </td>
                            <td class="px-5 py-4 text-right bg-brand-bg/5">
                                <span class="text-xs font-black font-mono text-primary" x-text="'$ ' + stockValue(product).toLocaleString()"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-primary/5">
                    <tr class="font-black text-primary-dark border-t-2 border-primary/20">
                        <td colspan="4" class="px-5 py-5 text-center">
                             <span class="text-[11px] font-black uppercase tracking-[0.2em] italic text-primary-dark opacity-70">Consolidated Inventory Value</span>
                        </td>
                        <td class="px-5 py-5 text-right">
                            <span class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none">Net COGS Val.</span>
                            <span class="text-[14px] font-mono font-black text-primary italic leading-none" x-text="'{{ $company->currency ?? 'SAR' }} ' + totalValue().toLocaleString()"></span>
                        </td>
                        <td class="px-5 py-5 text-center border-l border-gray-100/50">
                            <span class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none">Total Units</span>
                            <span class="text-[14px] font-mono font-black text-accent italic leading-none" x-text="totalQty()"></span>
                        </td>
                        <td class="px-5 py-5 border-l border-gray-100/50"></td>
                        <td class="px-5 py-5 text-right border-l border-gray-100/50 bg-brand-bg/5">
                            <span class="text-[9px] text-gray-400 block font-black uppercase mb-0.5 leading-none">Asset Position</span>
                            <span class="text-[14px] font-mono font-black text-primary italic leading-none" x-text="'{{ $company->currency ?? 'SAR' }} ' + totalValue().toLocaleString()"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Stock Alerts Section -->
    <div class="report-premium-card !bg-primary/10 border border-primary/20 !p-6 rounded-2xl no-print">
        <div class="flex items-center gap-3 mb-4">
             <i class="bi bi-exclamation-triangle-fill text-primary text-xl"></i>
             <h3 class="text-primary font-black uppercase tracking-widest text-sm">Critical Inventory Alerts</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <template x-for="product in products.filter(p => p.qty < 5)" :key="product.id">
                <div class="flex items-center justify-between p-3 bg-white border border-primary/20 rounded-xl shadow-sm">
                    <div>
                        <span class="text-xs font-black text-primary-dark uppercase block" x-text="product.name"></span>
                        <span class="text-[9px] text-primary font-black uppercase tracking-widest">Reorder Required</span>
                    </div>
                    <span class="report-premium-badge report-premium-badge-error !rounded-full italic font-black text-[10px]" x-text="product.qty + ' Units Left'"></span>
                </div>
            </template>
            <template x-if="products.filter(p => p.qty < 5).length === 0">
                <div class="col-span-3 py-4 flex items-center justify-center gap-2">
                    <i class="bi bi-patch-check-fill text-accent"></i>
                    <p class="text-xs font-black text-accent uppercase tracking-widest italic">Inventory levels are within optimal operating parameters.</p>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

