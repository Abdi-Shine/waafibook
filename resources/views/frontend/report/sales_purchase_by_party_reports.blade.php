@extends('admin.admin_master')
@section('page_title', 'Sale/Purchase by Party')



@section('admin')
<div class="report-premium-container"
     x-data="{
        searchTerm: '',
        partyType: 'All Parties',
        parties: {{ \Illuminate\Support\Js::from($parties->values()) }},
        get filteredParties() {
            return this.parties.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(this.searchTerm.toLowerCase());
                const matchesType = this.partyType === 'All Parties' || 
                                   (this.partyType === 'With Sales' && p.sales > 0) ||
                                   (this.partyType === 'With Purchases' && p.purchases > 0);
                return matchesSearch && matchesType;
            });
        },
        getTotalSales() {
            return this.filteredParties.reduce((sum, p) => sum + p.sales, 0);
        },
        getTotalPurchases() {
            return this.filteredParties.reduce((sum, p) => sum + p.purchases, 0);
        }
     }">
    
    <!-- Header Section -->
    <div class="report-premium-header no-print">
        <div>
            <h1 class="report-premium-title">Sales-Purchase by Party</h1>
            <p class="report-premium-subtitle">Comparative analysis of sales and purchase transactions</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="report-premium-btn-outline">
                <i class="bi bi-printer text-sm"></i> PRINT
            </button>
            <button class="report-premium-btn-outline">
                <i class="bi bi-file-earmark-excel text-sm"></i> EXCEL
            </button>
            <a href="{{ route('reports.sales_purchase_by_party.pdf') }}" class="report-premium-btn-primary">
                <i class="bi bi-file-earmark-pdf text-sm"></i> EXPORT PDF
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="report-premium-stat-grid">
        <!-- Total Sales -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Sales</p>
                <h3 class="text-[18px] font-black text-primary" x-text="'{{ $company->currency ?? 'SAR' }} ' + getTotalSales().toLocaleString()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Generated Revenue</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cart-check text-lg"></i>
            </div>
        </div>

        <!-- Total Purchases -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Purchases</p>
                <h3 class="text-[18px] font-black text-primary" x-text="'{{ $company->currency ?? 'SAR' }} ' + getTotalPurchases().toLocaleString()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Cost Incurred</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-cash-stack text-lg"></i>
            </div>
        </div>

        <!-- Net Cash-Flow -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Net Flow</p>
                <h3 class="text-[18px] font-black text-primary" :class="(getTotalSales() - getTotalPurchases()) >= 0 ? 'text-primary' : 'text-primary'"
                    x-text="'{{ $company->currency ?? 'SAR' }} ' + (getTotalSales() - getTotalPurchases()).toLocaleString()"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1" :class="(getTotalSales() - getTotalPurchases()) >= 0 ? 'text-primary' : 'text-primary'"
                   x-text="(getTotalSales() >= getTotalPurchases() ? 'Positive Balance' : 'Deficit')"></p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-graph-up text-lg"></i>
            </div>
        </div>

        <!-- Active Parties -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Parties</p>
                <h3 class="text-[18px] font-black text-primary" x-text="filteredParties.length"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">With Transactions</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-people text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="report-premium-filter-bar no-print">
        <div class="report-premium-filter-group flex-1 min-w-[250px]">
            <span class="report-premium-filter-label">Search Party</span>
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchTerm" placeholder="Search party name..." class="report-premium-filter-input !pl-9">
            </div>
        </div>

        <div class="report-premium-filter-group w-auto min-w-[180px]">
            <span class="report-premium-filter-label">Transaction Filter</span>
            <select x-model="partyType" class="report-premium-filter-input">
                <option>All Parties</option>
                <option>With Sales</option>
                <option>With Purchases</option>
            </select>
        </div>
        
        <button type="button" class="report-premium-btn-primary h-[38px] mt-auto">
            <i class="bi bi-funnel"></i> Generate
        </button>
    </div>

    <!-- Table Section -->
    <div class="report-premium-card overflow-hidden mb-6">
        <!-- Table Title Bar -->
        <div class="px-5 py-4 border-b border-brand-border bg-brand-bg/10 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[11px] font-black text-text-secondary uppercase tracking-widest">Comparative List</h4>
            </div>
            <span class="report-premium-badge report-premium-badge-info !rounded-full italic font-black uppercase tracking-widest text-[9px]" x-text="filteredParties.length + ' Identified Parties'"></span>
        </div>

        <!-- Table Display -->
        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="w-16 text-center">#</th>
                        <th>Party Name</th>
                        <th class="text-right">Sale Amount</th>
                        <th class="text-right">Purchase Amount</th>
                        <th class="text-right">Net Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(party, index) in filteredParties" :key="party.id">
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white">
                            <td class="px-5 py-4 text-[11px] font-black text-gray-400 text-center" x-text="index + 1"></td>
                            <td class="px-5 py-4">
                                <span class="text-xs font-black text-primary-dark block" x-text="party.name"></span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter" x-text="(party.sales > 0 && party.purchases > 0) ? 'Hybrid Partner' : (party.sales > 0 ? 'Customer' : 'Supplier')"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-xs font-black text-accent font-mono" x-show="party.sales > 0" x-text="'$ ' + party.sales.toLocaleString()"></span>
                                <span class="text-[11px] text-gray-300" x-show="party.sales == 0">---</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-xs font-black text-primary font-mono" x-show="party.purchases > 0" x-text="'$ ' + party.purchases.toLocaleString()"></span>
                                <span class="text-[11px] text-gray-300" x-show="party.purchases == 0">---</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-xs font-black font-mono" :class="(party.sales - party.purchases) >= 0 ? 'text-accent' : 'text-primary'"
                                      x-text="'$ ' + (party.sales - party.purchases).toLocaleString()"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="bg-[#e8f5e9]/50 border-t-2 border-primary/20">
                        <td colspan="2" class="px-5 py-5 text-center">
                            <span class="text-[10px] font-black text-primary-dark uppercase tracking-widest">Aggregated Comparison</span>
                        </td>
                        <td class="px-5 py-5 text-right">
                            <span class="text-[9px] text-gray-500 block font-bold uppercase mb-0.5">Total Sales</span>
                            <span class="text-xs font-black text-accent font-mono" x-text="'{{ $company->currency ?? 'SAR' }} ' + getTotalSales().toLocaleString()"></span>
                        </td>
                        <td class="px-5 py-5 text-right">
                            <span class="text-[9px] text-gray-500 block font-bold uppercase mb-0.5">Total Purchases</span>
                            <span class="text-xs font-black text-primary font-mono" x-text="'{{ $company->currency ?? 'SAR' }} ' + getTotalPurchases().toLocaleString()"></span>
                        </td>
                        <td class="px-5 py-5 text-right">
                             <span class="text-[9px] text-gray-500 block font-bold uppercase mb-0.5">Final Position</span>
                             <span class="text-xs font-black font-mono" :class="(getTotalSales() - getTotalPurchases()) >= 0 ? 'text-accent' : 'text-primary'"
                                   x-text="'{{ $company->currency ?? 'SAR' }} ' + (getTotalSales() - getTotalPurchases()).toLocaleString()"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Info Cards removed as per request -->
</div>
@endsection


