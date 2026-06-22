@extends('admin.admin_master')
@section('page_title', 'Inventory Overview')



@section('admin')
<div class="p-6 space-y-6">

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center">
                    <i class="bi bi-boxes text-primary text-lg"></i>
                </div>
                <h1 class="text-[22px] font-black text-primary-dark tracking-tight">Inventory Intelligence</h1>
            </div>
            <p class="text-[12px] text-text-secondary font-medium ml-12 italic">Live stock tracking, valuation, and procurement alert management</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 bg-white border border-brand-border rounded-xl px-4 py-2 shadow-sm text-[11px] font-black text-primary-dark">
                <span class="w-2 h-2 rounded-full bg-accent/10 pulse-dot"></span>
                Live Inventory Tracking
            </span>
        </div>
    </div>

    {{-- ── KPI CARDS ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Total Stock Value --}}
        <div class="bg-white rounded-2xl p-6 border border-brand-border shadow-sm kpi-card-hover border-l-4 border-l-accent">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent">
                    <i class="bi bi-stack text-xl"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-text-secondary uppercase tracking-widest mb-1">Total Inventory Value</p>
            <h3 class="text-2xl font-black text-primary-dark">{{ number_format($totalStockValue, 2) }}</h3>
            <p class="text-[9px] text-text-secondary mt-1">{{ $company->currency ?? 'SAR' }} at Purchase Price</p>
        </div>

        {{-- Low Stock --}}
        <div class="bg-white rounded-2xl p-6 border border-brand-border shadow-sm kpi-card-hover border-l-4 border-l-amber-500">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <i class="bi bi-exclamation-triangle text-xl"></i>
                </div>
                @if($lowStockCount > 0)
                <span class="w-2.5 h-2.5 rounded-full bg-primary/10 pulse-dot flex-shrink-0 mt-1"></span>
                @endif
            </div>
            <p class="text-[10px] font-black text-text-secondary uppercase tracking-widest mb-1">Low Stock Items</p>
            <h3 class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-primary' : 'text-primary-dark' }}">{{ $lowStockCount }}</h3>
            <p class="text-[9px] text-text-secondary mt-1">Below Reorder Threshold</p>
        </div>

        {{-- Out of Stock --}}
        <div class="bg-white rounded-2xl p-6 border border-brand-border shadow-sm kpi-card-hover border-l-4 border-l-error">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <i class="bi bi-x-circle text-xl"></i>
                </div>
                @if($outOfStockCount > 0)
                <span class="w-2.5 h-2.5 rounded-full bg-primary/10 pulse-dot flex-shrink-0 mt-1"></span>
                @endif
            </div>
            <p class="text-[10px] font-black text-text-secondary uppercase tracking-widest mb-1">Out of Stock</p>
            <h3 class="text-2xl font-black {{ $outOfStockCount > 0 ? 'text-primary' : 'text-primary-dark' }}">{{ $outOfStockCount }}</h3>
            <p class="text-[9px] text-text-secondary mt-1">Immediate Action Required</p>
        </div>

        {{-- Total SKUs --}}
        <div class="bg-white rounded-2xl p-6 border border-brand-border shadow-sm kpi-card-hover border-l-4 border-l-primary">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                    <i class="bi bi-box-seam text-xl"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-text-secondary uppercase tracking-widest mb-1">Active Catalog</p>
            <h3 class="text-2xl font-black text-primary-dark">{{ number_format($totalSKUs) }}</h3>
            <p class="text-[9px] text-text-secondary mt-1">Master Product Definitions</p>
        </div>
    </div>

    {{-- ── ALERTS BANNER ── --}}
    @if($lowStockCount > 0 || $outOfStockCount > 0)
    <div class="flex flex-col md:flex-row gap-4">
        @if($outOfStockCount > 0)
        <div class="flex-1 bg-primary/10 border border-primary/20 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center text-primary flex-shrink-0 mt-0.5">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
            <div>
                <p class="text-[13px] font-black text-primary mb-0.5">{{ $outOfStockCount }} Products Out of Stock</p>
                <p class="text-[11px] text-text-secondary">Immediate procurement required to prevent sales loss across locations.</p>
            </div>
        </div>
        @endif
        @if($lowStockCount > 0)
        <div class="flex-1 bg-primary/10 border border-primary/20 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-9 h-9 bg-primary/10 rounded-xl flex items-center justify-center text-primary flex-shrink-0 mt-0.5">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-[13px] font-black text-primary mb-0.5">{{ $lowStockCount }} Items Below Reorder Level</p>
                <p class="text-[11px] text-text-secondary">Schedule replenishment to maintain uninterrupted supply chain.</p>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── CHARTS + LOW STOCK ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Stock Value by Category --}}
        <div class="bg-white rounded-2xl border border-brand-border shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1.5 h-5 bg-accent rounded-full"></div>
                <h3 class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Inventory Concentration by Category</h3>
            </div>
            <div class="h-[280px]">
                <canvas id="categoryStockChart"></canvas>
            </div>
        </div>

        {{-- Low Stock List --}}
        <div class="bg-white rounded-2xl border border-brand-border shadow-sm flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-brand-border flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-5 bg-primary/10 rounded-full"></div>
                    <h3 class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Procurement Required</h3>
                </div>
                <span class="text-[10px] font-black bg-primary/10 text-primary px-2.5 py-1 rounded-full">{{ $lowStockCount }} items</span>
            </div>
            <div class="overflow-y-auto max-h-[320px]">
                <table class="w-full text-left">
                    <thead class="bg-brand-bg/20 border-b border-brand-border sticky top-0">
                        <tr class="text-[10px] font-black text-primary-dark uppercase tracking-widest">
                            <th class="px-6 py-3">Product</th>
                            <th class="px-6 py-3 text-center">Available</th>
                            <th class="px-6 py-3 text-center">Alert Lv.</th>
                            <th class="px-6 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/40">
                        @forelse($lowStockItems as $item)
                        <tr class="stock-row transition-colors">
                            <td class="px-6 py-3">
                                <p class="text-[12px] font-black text-primary-dark">{{ $item->product_name }}</p>
                                <p class="text-[9px] font-bold text-text-secondary uppercase">{{ $item->category->name ?? 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-3 text-center font-black text-[14px] {{ $item->current_stock <= 0 ? 'text-primary' : 'text-primary' }}">{{ $item->current_stock }}</td>
                            <td class="px-6 py-3 text-center font-bold text-text-secondary text-[12px]">{{ $item->low_stock_threshold }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($item->current_stock <= 0)
                                    <span class="px-2 py-0.5 bg-primary/10 text-primary text-[9px] font-black rounded-full uppercase">Empty</span>
                                @else
                                    <span class="px-2 py-0.5 bg-primary/10 text-primary text-[9px] font-black rounded-full uppercase">Low</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center">
                                <p class="text-[11px] font-bold italic text-accent opacity-60">✓ All stock levels are healthy</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('categoryStockChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @js($categoryStock->take(8)->pluck('name')),
            datasets: [{
                label: 'Stock Value',
                data: @js($categoryStock->take(8)->pluck('stock_value')),
                backgroundColor: '#99CC33',
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#004161',
                    callbacks: { label: ctx => ' Value: ' + parseFloat(ctx.raw).toLocaleString() + ' {{ $company->currency ?? "SAR" }}' }
                }
            },
            scales: {
                x: { beginAtZero: true, grid: { borderDash:[2,2], color:'#f1f5f9' }, ticks: { font:{size:9,weight:'bold'}, color:'#64748b' } },
                y: { grid: { display: false }, ticks: { font:{size:9,weight:'bold'}, color:'#64748b' } }
            }
        }
    });
});
</script>
@endpush


