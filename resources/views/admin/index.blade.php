@extends('admin.admin_master')
@section('admin')

    <main class="min-h-screen pb-12 bg-background">
        <!-- CONTENT -->
        <div class="p-4 md:p-8 content-wrapper mx-auto">
            <!-- Quick Actions -->
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

            <!-- Top 4 KPI Cards -->
            @php
                $formatKpi = fn($value) => ($value < 0 ? '-$' : '$') . number_format(abs($value), 2);
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Cash on Hand (Assets) -->
                <div class="kpi-card">
                    <div class="w-full">
                        <div class="flex items-start justify-between mb-3">
                            <div class="kpi-icon-box bg-accent/10">
                                <i class="bi bi-wallet2 text-accent text-lg"></i>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-accent/10 text-accent text-[10px] font-bold uppercase tracking-wider">
                                <i class="bi bi-diagram-3 text-[9px]"></i> For Branch
                            </span>
                        </div>
                        <div class="kpi-title uppercase tracking-wider">Cash on Hand</div>
                        <div class="kpi-value {{ $stats['cash_on_hand'] < 0 ? 'text-red-500' : '' }}">{{ $formatKpi($stats['cash_on_hand']) }}</div>
                        <a href="{{ route('sales.invoice.create') }}" class="kpi-link hover:text-accent">
                            See Ledger <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <!-- Account Payable -->
                <div class="kpi-card">
                    <div class="w-full">
                        <div class="flex items-start justify-between mb-3">
                            <div class="kpi-icon-box bg-primary/10">
                                <i class="bi bi-credit-card text-primary text-lg"></i>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider">
                                <i class="bi bi-truck text-[9px]"></i> For Suppliers
                            </span>
                        </div>
                        <div class="kpi-title uppercase tracking-wider">Account Payable</div>
                        <div class="kpi-value">{{ $formatKpi($stats['liabilities']) }}</div>
                        <a href="{{ route('supplier.index') }}" class="kpi-link hover:text-primary">
                            See Accounts <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <!-- Account Receivable -->
                <div class="kpi-card">
                    <div class="w-full">
                        <div class="flex items-start justify-between mb-3">
                            <div class="kpi-icon-box bg-accent/10">
                                <i class="bi bi-bank text-accent text-lg"></i>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-accent/10 text-accent text-[10px] font-bold uppercase tracking-wider">
                                <i class="bi bi-people text-[9px]"></i> For Customers
                            </span>
                        </div>
                        <div class="kpi-title uppercase tracking-wider">Account Receivable</div>
                        <div class="kpi-value">{{ $formatKpi($stats['accounts_receivable']) }}</div>
                        <a href="{{ route('customer.index') }}" class="kpi-link hover:text-accent">
                            See Accounts <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <!-- Stock Values -->
                <div class="kpi-card">
                    <div class="w-full">
                        <div class="flex items-start justify-between mb-3">
                            <div class="kpi-icon-box bg-primary/10">
                                <i class="bi bi-box-seam text-primary text-lg"></i>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider">
                                <i class="bi bi-diagram-3 text-[9px]"></i> For Branch
                            </span>
                        </div>
                        <div class="kpi-title uppercase tracking-wider">Stock Values</div>
                        <div class="kpi-value">{{ $formatKpi($stats['stock_value']) }}</div>
                        <a href="{{ route('product.index') }}" class="kpi-link hover:text-primary">
                            See Stock <i class="bi bi-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>



            <!-- Main Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
                <!-- Visitor Stats (Doughnut) -->
                <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-sm font-bold text-text-primary">Total Sales Volume</h3>
                    <div class="px-2 py-1 bg-background rounded-lg border border-border text-[9px] font-black text-primary uppercase tracking-widest">Live</div>
                </div>

                    <div class="relative py-4 flex justify-center h-48">
                        <canvas id="visitorChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <div class="text-2xl font-extrabold text-text-primary leading-none">$
                                {{ number_format($stats['total_sales_value'] / 1000, 1) }}k
                            </div>
                            <div class="text-[10px] text-text-secondary font-bold uppercase tracking-wider mt-1">Total Sales
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 justify-center mt-6">
                        <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold">
                            <div class="w-2 h-2 bg-primary rounded-full"></div><span>Paid</span>
                        </div>
                        <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold">
                            <div class="w-2 h-2 bg-accent rounded-full"></div><span>Unpaid</span>
                        </div>
                    </div>
                </div>

                <!-- 4 Featured Cards Grid -->
                <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Order Placed -->
                    <div class="featured-card bg-primary">
                        <div class="featured-icon-box">
                            <i class="bi bi-cart3 text-2xl"></i>
                        </div>
                        <div class="featured-title">SALES INVOICES</div>
                        <div class="featured-value">{{ $stats['orders_placed'] }}</div>
                        <i class="bi bi-cart3 featured-bg-icon"></i>
                    </div>

                    <!-- Purchase Bills -->
                    <div class="featured-card featured-card-accent bg-accent">
                        <div class="featured-icon-box">
                            <i class="bi bi-receipt text-2xl"></i>
                        </div>
                        <div class="featured-title">PURCHASE BILLS</div>
                        <div class="featured-value">{{ $stats['purchase_count'] }}</div>
                        <i class="bi bi-receipt featured-bg-icon"></i>
                    </div>

                    <!-- On Shipping -->
                    <div class="featured-card bg-primary">
                        <div class="featured-icon-box">
                            <i class="bi bi-truck text-2xl"></i>
                        </div>
                        <div class="featured-title">CASH RECEIVED</div>
                        <div class="featured-value">
                            {{ $stats['total_paid'] > 1000 ? '$ ' . number_format($stats['total_paid'] / 1000, 1) . 'k' : '$ ' . number_format($stats['total_paid'], 0) }}
                        </div>
                        <i class="bi bi-truck featured-bg-icon"></i>
                    </div>

                    <!-- Purchase Paid -->
                    <div class="featured-card featured-card-accent bg-accent">
                        <div class="featured-icon-box">
                            <i class="bi bi-cash-stack text-2xl"></i>
                        </div>
                        <div class="featured-title">VENDOR PAID</div>
                        <div class="featured-value">
                            {{ $stats['purchase_paid'] > 1000 ? '$ ' . number_format($stats['purchase_paid'] / 1000, 1) . 'k' : '$ ' . number_format($stats['purchase_paid'], 0) }}
                        </div>
                        <i class="bi bi-cash-stack featured-bg-icon"></i>
                    </div>
                </div>

                <!-- Purchase Volume Chart -->
                <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-border">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-sm font-bold text-text-primary">Total Purchase Volume</h3>
                        <div class="px-2 py-1 bg-background rounded-lg border border-border text-[9px] font-black text-accent uppercase tracking-widest">Live</div>
                    </div>

                    <div class="relative py-4 flex justify-center h-48">
                        <canvas id="orderChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <div class="text-2xl font-extrabold text-text-primary leading-none">$
                                {{ number_format($stats['purchase_total'] / 1000, 1) }}k
                            </div>
                            <div class="text-[10px] text-text-secondary font-bold uppercase tracking-wider mt-1">Total Purchase
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 justify-center mt-6">
                        <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold">
                            <div class="w-2 h-2 bg-primary rounded-full"></div><span>Paid</span>
                        </div>
                        <div class="flex items-center gap-1 text-[10px] text-text-secondary font-bold">
                            <div class="w-2 h-2 bg-accent rounded-full"></div><span>Unpaid</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selling Statistics (Bar Chart) -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8">
                    <h3 class="text-lg font-bold text-text-primary tracking-tight">Selling Statistics</h3>
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-primary rounded-sm"></div>
                            <span class="text-[11px] font-bold text-text-secondary uppercase tracking-widest">Total
                                Sales</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-accent rounded-sm"></div>
                            <span class="text-[11px] font-bold text-text-secondary uppercase tracking-widest">Net
                                Profit</span>
                        </div>
                    </div>
                </div>
                <div class="h-80 w-full">
                    <canvas id="sellingChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Chart.js Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Shared Chart.js Defaults
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.font.weight = 'bold';
            Chart.defaults.color = '#9CA3AF';

            // Visitor Chart (Donut)
        const ctxVisitor = document.getElementById('visitorChart').getContext('2d');
        new Chart(ctxVisitor, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: [{{ $stats['total_paid'] }}, {{ $stats['total_due'] }}],
                    backgroundColor: ['#004161', '#F43F5E'], // Blue for Paid, Rose for Unpaid
                    hoverBackgroundColor: ['#00314d', '#E11D48'],
                    borderWidth: 0,
                    cutout: '75%',
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                return label + ': $' + value.toLocaleString();
                            }
                        }
                    }
                } 
            }
        });

            // Purchase Chart (Donut)
            const ctxOrder = document.getElementById('orderChart').getContext('2d');
            new Chart(ctxOrder, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Unpaid'],
                    datasets: [{
                        data: [{{ $stats['purchase_paid'] }}, {{ $stats['purchase_due'] }}],
                        backgroundColor: ['#004161', '#99CC33'], // Blue for Paid, Green for Unpaid
                        borderWidth: 0,
                        cutout: '75%',
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    return label + ': $' + value.toLocaleString();
                                }
                            }
                        }
                    } 
                }
            });

            // Selling Statistics (Bar Chart)
            const ctxSelling = document.getElementById('sellingChart').getContext('2d');
            new Chart(ctxSelling, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Total Sales',
                            data: @json($stats['monthly_sales']),
                            backgroundColor: '#004161',
                            borderRadius: 4,
                            barThickness: 12
                        },
                        {
                            label: 'Net Profit',
                            data: @json($stats['monthly_profit']),
                            backgroundColor: '#99CC33',
                            borderRadius: 4,
                            barThickness: 12
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: {{ $stats['max_monthly_val'] * 1.1 }},
                            grid: { borderDash: [5, 5], color: '#E5E7EB' },
                            ticks: { stepSize: {{ ($stats['max_monthly_val'] * 1.1) / 4 }}, font: { size: 10 } }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                    }
                }
            });
        });
    </script>

@endsection