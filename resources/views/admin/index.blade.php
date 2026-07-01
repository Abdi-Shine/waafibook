@extends('admin.admin_master')
@section('admin')

<main class="min-h-screen bg-background">

    {{-- ═══════════════════════════════════════════════════════════
         MOBILE LAYOUT  (hidden on lg+)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="lg:hidden dash-mobile-wrap">


        {{-- Quick Actions --}}
        <div class="px-5 pt-4 pb-2">
            <div class="flex gap-2">
                <a href="{{ route('sales.invoice.create') }}"
                   class="flex-1 flex items-center justify-center gap-1.5 py-3 bg-accent text-primary font-bold rounded-xl text-[13px] active:opacity-80 transition-opacity">
                    <i class="bi bi-plus-lg text-base"></i> Add Sale
                </a>
                <a href="{{ route('purchase.bill.create') }}"
                   class="flex-1 flex items-center justify-center gap-1.5 py-3 bg-primary text-white font-bold rounded-xl text-[13px] active:opacity-80 transition-opacity">
                    <i class="bi bi-plus-lg text-base"></i> Add Purchase
                </a>
                <a href="{{ route('sales.pos.view') }}"
                   class="flex-1 flex items-center justify-center gap-1.5 py-3 bg-primary text-white font-bold rounded-xl text-[13px] active:opacity-80 transition-opacity">
                    <i class="bi bi-display text-base"></i> POS Terminal
                </a>
            </div>
        </div>

        {{-- KPI Strip --}}
        @php $fmt = fn($v) => ($v < 0 ? '-$' : '$') . number_format(abs($v), 2); @endphp
        <div class="px-5 py-4">
            <div class="dash-kpi-strip">
                <div class="dash-kpi-item">
                    <span class="dash-kpi-label">Cash on Hand</span>
                    <span class="dash-kpi-value {{ $stats['cash_on_hand'] < 0 ? 'text-red-500' : '' }}">{{ $fmt($stats['cash_on_hand']) }}</span>
                </div>
                <div class="dash-kpi-divider"></div>
                <div class="dash-kpi-item">
                    <span class="dash-kpi-label">Receivable</span>
                    <span class="dash-kpi-value">{{ $fmt($stats['accounts_receivable']) }}</span>
                </div>
                <div class="dash-kpi-divider"></div>
                <div class="dash-kpi-item">
                    <span class="dash-kpi-label">Payable</span>
                    <span class="dash-kpi-value">{{ $fmt($stats['liabilities']) }}</span>
                </div>
                <div class="dash-kpi-divider"></div>
                <div class="dash-kpi-item">
                    <span class="dash-kpi-label">Stock</span>
                    <span class="dash-kpi-value">{{ $fmt($stats['stock_value']) }}</span>
                </div>
            </div>
        </div>

        {{-- Parties List --}}
        <div class="pb-28">
            {{-- Search header matching Parties page --}}
            <div class="flex items-center gap-2 px-5 mb-2">
                <a href="{{ route('customer.index') }}" class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-white border border-gray-200 rounded-xl">
                    <i class="bi bi-search text-gray-400 text-sm"></i>
                    <span class="text-[13px] text-gray-400 font-medium tracking-wide">SEARCH PARTY</span>
                </a>
                <a href="{{ route('customer.index') }}" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl shrink-0">
                    <i class="bi bi-sliders text-gray-500"></i>
                </a>
                <a href="{{ route('customer.index') }}?reopen_create=1"
                   class="flex items-center gap-1 px-3 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
                    <i class="bi bi-plus-lg text-base"></i> New Party
                </a>
            </div>

            {{-- Parties list (customers + suppliers) --}}
            <div class="bg-white border-t border-b border-gray-100">
                @forelse($recentParties as $party)
                    @php
                        $route = route('parties.ledger', ['type' => $party->type, 'id' => $party->id]);

                        // Customer: positive = we receive, Supplier: positive = we owe
                        if ($party->type === 'customer') {
                            $label      = $party->amount_balance > 0 ? "You'll Get" : ($party->amount_balance < 0 ? "You'll Pay" : 'Settled');
                            $labelColor = $party->amount_balance > 0 ? 'text-accent'    : ($party->amount_balance < 0 ? 'text-red-500' : 'text-gray-400');
                        } else {
                            $label      = $party->amount_balance > 0 ? "You'll Pay" : ($party->amount_balance < 0 ? "You'll Get" : 'Settled');
                            $labelColor = $party->amount_balance > 0 ? 'text-red-500' : ($party->amount_balance < 0 ? 'text-accent'   : 'text-gray-400');
                        }
                    @endphp
                    <a href="{{ $route }}"
                       class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                        <div class="min-w-0 pr-3">
                            <p class="text-[15px] font-black text-text-primary leading-tight truncate">
                                {{ strtoupper($party->name) }}
                            </p>
                            <p class="text-xs text-text-secondary mt-0.5">
                                {{ $party->latest_date ? \Carbon\Carbon::parse($party->latest_date)->format('d M Y') : '—' }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[15px] font-black text-text-primary">
                                $ {{ number_format(abs($party->amount_balance), 2) }}
                            </p>
                            <p class="text-xs font-bold {{ $labelColor }} mt-0.5">{{ $label }}</p>
                        </div>
                    </a>
                @empty
                    <div class="py-10 text-center">
                        <i class="bi bi-people text-3xl text-gray-300"></i>
                        <p class="text-sm text-text-secondary mt-2 font-semibold">No parties yet</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>{{-- end mobile --}}


    {{-- ═══════════════════════════════════════════════════════════
         DESKTOP LAYOUT  (hidden on mobile)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="hidden lg:block p-8 content-wrapper mx-auto pb-12">

        {{-- Quick Actions --}}
        @php $formatKpi = fn($value) => ($value < 0 ? '-$' : '$') . number_format(abs($value), 2); @endphp
        <div class="flex items-center justify-end gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.invoice.create') }}"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-accent text-white text-sm font-bold hover:opacity-90 transition-opacity">
                    <i class="bi bi-plus-lg"></i> Add Sale
                </a>
                <a href="{{ route('purchase.bill.create') }}"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-white text-sm font-bold hover:opacity-90 transition-opacity">
                    <i class="bi bi-plus-lg"></i> Add Purchase
                </a>
                <a href="{{ route('sales.pos.view') }}"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary-dark text-white text-sm font-bold hover:opacity-90 transition-opacity">
                    <i class="bi bi-display"></i> POS Terminal
                </a>
            </div>
        </div>

        {{-- Top 4 KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="kpi-card">
                <div class="w-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="kpi-icon-box bg-accent/10"><i class="bi bi-wallet2 text-accent text-lg"></i></div>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-accent/10 text-accent text-[10px] font-bold uppercase tracking-wider">
                            <i class="bi bi-diagram-3 text-[9px]"></i> For Branch
                        </span>
                    </div>
                    <div class="kpi-title uppercase tracking-wider">Cash on Hand</div>
                    <div class="kpi-value {{ $stats['cash_on_hand'] < 0 ? 'text-red-500' : '' }}">{{ $formatKpi($stats['cash_on_hand']) }}</div>
                    <a href="{{ route('cash_in_hand.index') }}" class="kpi-link hover:text-accent">See Cash Amount<i class="bi bi-arrow-right text-[10px]"></i></a>
                </div>
            </div>
            <div class="kpi-card">
                <div class="w-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="kpi-icon-box bg-primary/10"><i class="bi bi-credit-card text-primary text-lg"></i></div>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider">
                            <i class="bi bi-truck text-[9px]"></i> For Suppliers
                        </span>
                    </div>
                    <div class="kpi-title uppercase tracking-wider">Account Payable</div>
                    <div class="kpi-value">{{ $formatKpi($stats['liabilities']) }}</div>
                    <a href="{{ route('supplier.index') }}" class="kpi-link hover:text-primary">See Accounts <i class="bi bi-arrow-right text-[10px]"></i></a>
                </div>
            </div>
            <div class="kpi-card">
                <div class="w-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="kpi-icon-box bg-accent/10"><i class="bi bi-bank text-accent text-lg"></i></div>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-accent/10 text-accent text-[10px] font-bold uppercase tracking-wider">
                            <i class="bi bi-people text-[9px]"></i> For Customers
                        </span>
                    </div>
                    <div class="kpi-title uppercase tracking-wider">Account Receivable</div>
                    <div class="kpi-value">{{ $formatKpi($stats['accounts_receivable']) }}</div>
                    <a href="{{ route('customer.index') }}" class="kpi-link hover:text-accent">See Accounts <i class="bi bi-arrow-right text-[10px]"></i></a>
                </div>
            </div>
            <div class="kpi-card">
                <div class="w-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="kpi-icon-box bg-primary/10"><i class="bi bi-box-seam text-primary text-lg"></i></div>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider">
                            <i class="bi bi-diagram-3 text-[9px]"></i> For Branch
                        </span>
                    </div>
                    <div class="kpi-title uppercase tracking-wider">Stock Values</div>
                    <div class="kpi-value">{{ $formatKpi($stats['stock_value']) }}</div>
                    <a href="{{ route('product.index') }}" class="kpi-link hover:text-primary">See Stock <i class="bi bi-arrow-right text-[10px]"></i></a>
                </div>
            </div>
        </div>

        {{-- Main Dashboard Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
            {{-- Sales Doughnut --}}
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-sm font-bold text-text-primary">Total Sales Volume</h3>
                    <div class="px-2 py-1 bg-background rounded-lg border border-border text-[9px] font-black text-primary uppercase tracking-widest">Live</div>
                </div>
                <div class="relative py-4 flex justify-center h-48">
                    <canvas id="visitorChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="text-2xl font-extrabold text-text-primary leading-none">$ {{ number_format($stats['total_sales_value'] / 1000, 1) }}k</div>
                        <div class="text-[10px] text-text-secondary font-bold uppercase tracking-wider mt-1">Total Sales</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 justify-center mt-6">
                    <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold"><div class="w-2 h-2 bg-primary rounded-full"></div><span>Paid</span></div>
                    <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold"><div class="w-2 h-2 bg-accent rounded-full"></div><span>Unpaid</span></div>
                </div>
            </div>

            {{-- 4 Featured Cards --}}
            <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="featured-card bg-primary">
                    <div class="featured-icon-box"><i class="bi bi-cart3 text-2xl"></i></div>
                    <div class="featured-title">SALES INVOICES</div>
                    <div class="featured-value">{{ $stats['orders_placed'] }}</div>
                    <i class="bi bi-cart3 featured-bg-icon"></i>
                </div>
                <div class="featured-card featured-card-accent bg-accent">
                    <div class="featured-icon-box"><i class="bi bi-receipt text-2xl"></i></div>
                    <div class="featured-title">PURCHASE BILLS</div>
                    <div class="featured-value">{{ $stats['purchase_count'] }}</div>
                    <i class="bi bi-receipt featured-bg-icon"></i>
                </div>
                <div class="featured-card bg-primary">
                    <div class="featured-icon-box"><i class="bi bi-truck text-2xl"></i></div>
                    <div class="featured-title">CASH RECEIVED</div>
                    <div class="featured-value">{{ $stats['total_paid'] > 1000 ? '$ ' . number_format($stats['total_paid'] / 1000, 1) . 'k' : '$ ' . number_format($stats['total_paid'], 0) }}</div>
                    <i class="bi bi-truck featured-bg-icon"></i>
                </div>
                <div class="featured-card featured-card-accent bg-accent">
                    <div class="featured-icon-box"><i class="bi bi-cash-stack text-2xl"></i></div>
                    <div class="featured-title">VENDOR PAID</div>
                    <div class="featured-value">{{ $stats['purchase_paid'] > 1000 ? '$ ' . number_format($stats['purchase_paid'] / 1000, 1) . 'k' : '$ ' . number_format($stats['purchase_paid'], 0) }}</div>
                    <i class="bi bi-cash-stack featured-bg-icon"></i>
                </div>
            </div>

            {{-- Purchase Doughnut --}}
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-sm font-bold text-text-primary">Total Purchase Volume</h3>
                    <div class="px-2 py-1 bg-background rounded-lg border border-border text-[9px] font-black text-accent uppercase tracking-widest">Live</div>
                </div>
                <div class="relative py-4 flex justify-center h-48">
                    <canvas id="orderChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="text-2xl font-extrabold text-text-primary leading-none">$ {{ number_format($stats['purchase_total'] / 1000, 1) }}k</div>
                        <div class="text-[10px] text-text-secondary font-bold uppercase tracking-wider mt-1">Total Purchase</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 justify-center mt-6">
                    <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold"><div class="w-2 h-2 bg-primary rounded-full"></div><span>Paid</span></div>
                    <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold"><div class="w-2 h-2 bg-accent rounded-full"></div><span>Unpaid</span></div>
                </div>
            </div>
        </div>

        {{-- Selling Statistics Bar Chart --}}
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-border">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8">
                <h3 class="text-lg font-bold text-text-primary tracking-tight">Selling Statistics</h3>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-primary rounded-sm"></div>
                        <span class="text-[11px] font-bold text-text-secondary uppercase tracking-widest">Total Sales</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-accent rounded-sm"></div>
                        <span class="text-[11px] font-bold text-text-secondary uppercase tracking-widest">Net Profit</span>
                    </div>
                </div>
            </div>
            <div class="h-80 w-full">
                <canvas id="sellingChart"></canvas>
            </div>
        </div>
    </div>{{-- end desktop --}}

</main>

{{-- Chart.js (desktop only — skip on mobile to save bandwidth) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 1024) return; // skip charts on mobile

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.weight = 'bold';
    Chart.defaults.color = '#9CA3AF';

    new Chart(document.getElementById('visitorChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{ data: [{{ $stats['total_paid'] }}, {{ $stats['total_due'] }}], backgroundColor: ['#004161','#F43F5E'], borderWidth: 0, cutout: '75%' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.label + ': $' + ctx.raw.toLocaleString() } } } }
    });

    new Chart(document.getElementById('orderChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{ data: [{{ $stats['purchase_paid'] }}, {{ $stats['purchase_due'] }}], backgroundColor: ['#004161','#99CC33'], borderWidth: 0, cutout: '75%' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.label + ': $' + ctx.raw.toLocaleString() } } } }
    });

    new Chart(document.getElementById('sellingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [
                { label: 'Total Sales', data: @json($stats['monthly_sales']), backgroundColor: '#004161', borderRadius: 4, barThickness: 12 },
                { label: 'Net Profit',  data: @json($stats['monthly_profit']), backgroundColor: '#99CC33', borderRadius: 4, barThickness: 12 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: {{ $stats['max_monthly_val'] * 1.1 }}, grid: { borderDash: [5,5], color: '#E5E7EB' }, ticks: { stepSize: {{ ($stats['max_monthly_val'] * 1.1) / 4 }}, font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>

@endsection
