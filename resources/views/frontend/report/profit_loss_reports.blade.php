@extends('admin.admin_master')
@section('page_title', 'Profit & Loss')

@push('css')
<style>
    .pl-section-header td { background: #f1f5f9; padding: 10px 20px; font-size: 10px; font-weight: 900; color: #004161; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid #004161; }
    .pl-data-row td { padding: 12px 20px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .pl-data-row td:first-child { padding-left: 36px; color: #475569; }
    .pl-data-row:hover td { background: #f8fafc; }
    .pl-data-row .amount { font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .pl-data-row .pct { text-align: right; color: #94a3b8; font-size: 12px; font-weight: 600; }

    /* Total row colors */
    .total-revenue td    { background: #eff6ff; border-left: 4px solid #3b82f6; }
    .total-revenue td    { color: #1d4ed8; font-weight: 800; font-size: 13px; }

    .total-cogs td       { background: #fff7ed; border-left: 4px solid #f97316; }
    .total-cogs td       { color: #c2410c; font-weight: 800; font-size: 13px; }

    .total-gross td      { background: #f0fdf4; border-left: 4px solid #22c55e; }
    .total-gross td      { color: #15803d; font-weight: 900; font-size: 14px; }

    .total-opex td       { background: #fef2f2; border-left: 4px solid #ef4444; }
    .total-opex td       { color: #b91c1c; font-weight: 800; font-size: 13px; }

    .total-net td        { background: #004161; border-left: 4px solid #99CC33; }
    .total-net td        { color: #ffffff; font-weight: 900; font-size: 15px; }
    .total-net td .pct   { color: #99CC33; }

    .amount { text-align: right; font-variant-numeric: tabular-nums; }
    .pct    { text-align: right; }

    @media print {
        #sidebar, header, .no-print { display: none !important; }
        .min-h-screen { min-height: 0 !important; height: auto !important; }
        html, body { height: auto !important; background: white !important; }
        .main-content { margin-left: 0 !important; }
    }
</style>
@endpush

@section('admin')
@php $cur = $company->currency ?? '$'; @endphp

<div class="px-4 py-8 md:px-8 bg-background min-h-screen">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 no-print">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Profit & Loss Statement</h1>
            <p class="text-[12px] text-gray-400 mt-0.5">Performance tracking for {{ $company->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 border border-gray-200 bg-white text-gray-600 font-semibold rounded-lg hover:bg-gray-50 text-[13px]">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="{{ route('reports.profit_loss.pdf', request()->query()) }}" class="flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 text-[13px]">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[11px] text-gray-400 font-medium mb-1">Total Revenue</p>
            <h3 class="text-[20px] font-black text-primary">{{ number_format($totalRevenue, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1">{{ $cur }} Sales</p>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[11px] text-gray-400 font-medium mb-1">Gross Profit</p>
            <h3 class="text-[20px] font-black text-green-600">{{ number_format($grossProfit, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1">{{ $totalRevenue > 0 ? number_format(($grossProfit/$totalRevenue)*100,1) : 0 }}% Margin</p>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[11px] text-gray-400 font-medium mb-1">Total Expenses</p>
            <h3 class="text-[20px] font-black text-red-500">{{ number_format($totalExpenses, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1">{{ $totalRevenue > 0 ? number_format(($totalExpenses/$totalRevenue)*100,1) : 0 }}% of Rev</p>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[11px] text-gray-400 font-medium mb-1">Net Profit</p>
            <h3 class="text-[20px] font-black {{ $netProfit >= 0 ? 'text-primary' : 'text-red-600' }}">{{ number_format($netProfit, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1">{{ $totalRevenue > 0 ? number_format(($netProfit/$totalRevenue)*100,1) : 0 }}% Net Margin</p>
        </div>
    </div>

    {{-- Filter --}}
    <form id="plFilterForm" action="{{ route('reports.profit_loss') }}" method="GET"
          class="no-print bg-white rounded-[1rem] border border-gray-100 shadow-sm px-5 py-3 mb-6 flex flex-wrap items-center gap-3">

        {{-- Quick period pills --}}
        @php $activePeriod = request('period', ''); @endphp
        <div class="flex items-center gap-1">
            @foreach(['this_month' => 'This Month', 'last_month' => 'Last Month', 'this_quarter' => 'This Quarter', 'this_year' => 'This Year', 'last_year' => 'Last Year'] as $key => $label)
            <button type="button" id="btn-{{ $key }}" onclick="setQuickPeriod('{{ $key }}')"
                class="quick-pill px-3.5 py-1.5 text-[12px] font-semibold rounded-lg transition-all
                    {{ $activePeriod === $key ? 'bg-gray-200 text-primary-dark font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-primary-dark' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div class="w-px h-6 bg-gray-200 mx-1"></div>

        <input type="hidden" id="period_key" name="period" value="{{ $activePeriod }}">
        <input type="date" id="from_date" name="from_date" value="{{ $filters['from_date'] }}"
            class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-[14px] font-semibold text-gray-700 outline-none focus:border-primary w-44">
        <span class="text-gray-400 font-bold">→</span>
        <input type="date" id="to_date" name="to_date" value="{{ $filters['to_date'] }}"
            class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-[14px] font-semibold text-gray-700 outline-none focus:border-primary w-44">

        <button type="submit" class="ml-auto px-5 py-2 bg-primary text-white font-bold rounded-xl text-[13px] hover:bg-primary/90 transition-all">
            Run Report
        </button>
    </form>

    <script>
    function setQuickPeriod(period) {
        const now = new Date();
        let from, to;
        if (period === 'this_month') {
            from = new Date(now.getFullYear(), now.getMonth(), 1);
            to   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        } else if (period === 'last_month') {
            from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            to   = new Date(now.getFullYear(), now.getMonth(), 0);
        } else if (period === 'this_quarter') {
            const q = Math.floor(now.getMonth() / 3);
            from = new Date(now.getFullYear(), q * 3, 1);
            to   = new Date(now.getFullYear(), q * 3 + 3, 0);
        } else if (period === 'this_year') {
            from = new Date(now.getFullYear(), 0, 1);
            to   = new Date(now.getFullYear(), 11, 31);
        } else if (period === 'last_year') {
            from = new Date(now.getFullYear() - 1, 0, 1);
            to   = new Date(now.getFullYear() - 1, 11, 31);
        }
        document.getElementById('from_date').value = fmt(from);
        document.getElementById('to_date').value   = fmt(to);
        document.getElementById('period_key').value = period;
        document.querySelectorAll('.quick-pill').forEach(b => {
            b.classList.remove('bg-gray-200','text-primary-dark','font-bold');
            b.classList.add('text-gray-500');
        });
        const btn = document.getElementById('btn-' + period);
        if (btn) { btn.classList.add('bg-gray-200','text-primary-dark','font-bold'); btn.classList.remove('text-gray-500'); }
        document.getElementById('plFilterForm').submit();
    }
    function fmt(d) {
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    </script>

    {{-- Statement Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden mb-6">
        {{-- Table Header Bar --}}
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary text-sm"></i>
                <h4 class="text-[12px] font-black text-gray-600 uppercase tracking-widest">Financial Statement Details</h4>
            </div>
            <span class="text-[10px] font-black text-primary bg-primary/8 border border-primary/15 px-3 py-1 rounded-full uppercase tracking-wider">
                {{ \Carbon\Carbon::parse($filters['from_date'])->format('M d') }} — {{ \Carbon\Carbon::parse($filters['to_date'])->format('M d, Y') }}
            </span>
        </div>

        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-5 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest text-left w-1/2">Account Category</th>
                    <th class="px-5 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Amount ({{ $cur }})</th>
                    <th class="px-5 py-3 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">% of Revenue</th>
                </tr>
            </thead>
            <tbody>

                {{-- ── OPERATING INCOME ── --}}
                <tr class="pl-section-header">
                    <td colspan="3">Operating Income</td>
                </tr>
                <tr class="pl-data-row">
                    <td>Sales Revenue</td>
                    <td class="amount">{{ number_format($totalRevenue, 2) }}</td>
                    <td class="pct">100.0%</td>
                </tr>
                <tr class="total-revenue">
                    <td class="px-5 py-3.5 uppercase">Total Operating Revenue</td>
                    <td class="px-5 py-3.5 amount">{{ number_format($totalRevenue, 2) }}</td>
                    <td class="px-5 py-3.5 pct">100.0%</td>
                </tr>

                {{-- ── COGS ── --}}
                <tr class="pl-section-header">
                    <td colspan="3">Cost of Sales (COGS)</td>
                </tr>
                <tr class="pl-data-row">
                    <td>Purchases / Direct Costs</td>
                    <td class="amount">{{ number_format($totalCogs, 2) }}</td>
                    <td class="pct">{{ $totalRevenue > 0 ? number_format(($totalCogs/$totalRevenue)*100,1) : 0 }}%</td>
                </tr>
                <tr class="total-cogs">
                    <td class="px-5 py-3.5 uppercase">Total Cost of Goods Sold</td>
                    <td class="px-5 py-3.5 amount">{{ number_format($totalCogs, 2) }}</td>
                    <td class="px-5 py-3.5 pct">{{ $totalRevenue > 0 ? number_format(($totalCogs/$totalRevenue)*100,1) : 0 }}%</td>
                </tr>

                {{-- ── GROSS PROFIT ── --}}
                <tr class="total-gross">
                    <td class="px-5 py-4 uppercase tracking-wider">Gross Profit Margin</td>
                    <td class="px-5 py-4 amount text-[16px]">{{ number_format($grossProfit, 2) }}</td>
                    <td class="px-5 py-4 pct">{{ $totalRevenue > 0 ? number_format(($grossProfit/$totalRevenue)*100,1) : 0 }}%</td>
                </tr>

                {{-- ── OPERATING EXPENSES ── --}}
                <tr class="pl-section-header">
                    <td colspan="3">Operating Expenses (OPEX)</td>
                </tr>
                @forelse($expenseDetails as $exp)
                <tr class="pl-data-row">
                    <td>{{ $exp->name }}</td>
                    <td class="amount">{{ number_format($exp->amount, 2) }}</td>
                    <td class="pct">{{ number_format($exp->percent, 1) }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-5 py-8 text-center text-gray-400 text-[12px] italic">No operating expenses for this period</td>
                </tr>
                @endforelse
                <tr class="total-opex">
                    <td class="px-5 py-3.5 uppercase">Total Operating Expenses</td>
                    <td class="px-5 py-3.5 amount">{{ number_format($totalExpenses, 2) }}</td>
                    <td class="px-5 py-3.5 pct">{{ $totalRevenue > 0 ? number_format(($totalExpenses/$totalRevenue)*100,1) : 0 }}%</td>
                </tr>

                {{-- ── NET PROFIT ── --}}
                <tr class="total-net">
                    <td class="px-5 py-5 uppercase tracking-widest">Net Profit / (Loss)</td>
                    <td class="px-5 py-5 amount text-[20px]">{{ number_format($netProfit, 2) }}</td>
                    <td class="px-5 py-5 pct" style="color:#99CC33; font-weight:900;">
                        {{ $totalRevenue > 0 ? number_format(($netProfit/$totalRevenue)*100,1) : 0 }}% Margin
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

</div>
@endsection
