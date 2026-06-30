@extends('admin.admin_master')
@section('admin')
@php
    $user    = auth()->user();
    $company = \App\Models\Company::find($user->company_id);
    $symbol  = '$';
    $firstName = explode(' ', $user->name ?? 'there')[0];
@endphp

<div class="min-h-screen bg-[#f4f6fa] pb-24 font-inter">

    {{-- ── Welcome ─────────────────────────────────────────────────── --}}
    <div class="px-5 pt-6 pb-2">
        <h1 class="text-[24px] font-black text-primary-dark leading-tight">Hi, {{ $firstName }}</h1>
        <p class="text-[13px] text-gray-400 font-medium mt-0.5">
            Here's how <span class="font-bold text-primary">{{ $company->name ?? 'your business' }}</span> is doing this month.
        </p>
    </div>

    {{-- ── KPI strip ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 px-5 mt-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Sales</p>
            <p class="text-[20px] font-black text-primary-dark">${{ number_format($stats['total_sales_value'], 0) }}</p>
            <p class="text-[11px] text-accent font-bold mt-0.5">{{ $stats['orders_placed'] }} orders</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Balance Due</p>
            <p class="text-[20px] font-black text-red-500">${{ number_format($stats['total_due'], 0) }}</p>
            <p class="text-[11px] text-gray-400 font-bold mt-0.5">Pending collection</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cash on Hand</p>
            <p class="text-[20px] font-black text-primary-dark">${{ number_format($stats['cash_on_hand'], 0) }}</p>
            <p class="text-[11px] text-gray-400 font-bold mt-0.5">Available balance</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Net Profit</p>
            <p class="text-[20px] font-black {{ $stats['net_profit'] >= 0 ? 'text-accent' : 'text-red-500' }}">
                ${{ number_format(abs($stats['net_profit']), 0) }}
            </p>
            <p class="text-[11px] text-gray-400 font-bold mt-0.5">This period</p>
        </div>
    </div>

    {{-- ── Quick Actions ────────────────────────────────────────────── --}}
    <div class="px-5 mt-6">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-4">Quick Actions</p>
        <div class="grid grid-cols-4 gap-3">
            <a href="{{ route('sales.invoice.create') }}" class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-content: center shadow-md group-hover:bg-primary-dark transition-colors" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-file-earmark-text text-white text-xl"></i>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">New<br>Invoice</span>
            </a>
            <a href="{{ route('purchase.bill.create') }}" class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-content: center shadow-md group-hover:bg-primary-dark transition-colors" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-clipboard-check text-white text-xl"></i>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">New<br>Bill</span>
            </a>
            <a href="{{ route('payment.in.index') }}" class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-content: center shadow-md group-hover:bg-primary-dark transition-colors" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-arrow-down-circle text-white text-xl"></i>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Record<br>Payment</span>
            </a>
            <a href="{{ route('expense.index') }}" class="flex flex-col items-center gap-2 group">
                <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-content: center shadow-md group-hover:bg-primary-dark transition-colors" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-currency-dollar text-white text-xl"></i>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">New<br>Expense</span>
            </a>
        </div>
    </div>

    {{-- ── Recent Invoices ──────────────────────────────────────────── --}}
    <div class="px-5 mt-6">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Recent Invoices</p>
            <a href="{{ route('sales.invoice.view') }}" class="text-[12px] font-bold text-primary hover:underline">See all</a>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @forelse($recentInvoices as $inv)
            @php
                $statusLabel = match($inv->status) {
                    'completed' => ['PAID',    'bg-accent/15 text-accent'],
                    'partial'   => ['PARTIAL', 'bg-blue-50 text-blue-600'],
                    'pending'   => ['UNPAID',  'bg-gray-100 text-gray-500'],
                    'cancelled' => ['CANCELLED','bg-red-50 text-red-400'],
                    default     => [strtoupper($inv->status), 'bg-gray-100 text-gray-500'],
                };
                $isOverdue = $inv->due_date && \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->status !== 'completed';
                if ($isOverdue) $statusLabel = ['OVERDUE', 'bg-red-50 text-red-500'];
            @endphp
            <a href="{{ route('sales.invoice.detail', $inv->id) }}" class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-50 hover:bg-gray-50/70 transition-colors last:border-b-0">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-content:center flex-shrink-0" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-file-earmark-text text-primary text-base"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-primary-dark truncate">
                        {{ $inv->customer->name ?? 'Walk-in Customer' }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        {{ $inv->invoice_no }}
                        @if($inv->due_date)
                            · Due {{ \Carbon\Carbon::parse($inv->due_date)->format('d M') }}
                        @endif
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[14px] font-black text-primary-dark">${{ number_format($inv->total_amount, 2) }}</p>
                    <span class="inline-block text-[10px] font-black px-2 py-0.5 rounded-full mt-0.5 {{ $statusLabel[1] }}">
                        {{ $statusLabel[0] }}
                    </span>
                </div>
            </a>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-[13px]">
                <i class="bi bi-file-earmark-x text-2xl block mb-2 opacity-30"></i>
                No invoices yet.
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Recent Purchases ─────────────────────────────────────────── --}}
    @php
        $recentBills = \App\Models\PurchaseBill::with('supplier')->latest()->take(3)->get();
    @endphp
    @if($recentBills->count())
    <div class="px-5 mt-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Recent Purchases</p>
            <a href="{{ route('purchase.bill.index') }}" class="text-[12px] font-bold text-primary hover:underline">See all</a>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @foreach($recentBills as $bill)
            @php
                $bStatus = match($bill->status) {
                    'completed' => ['PAID',    'bg-accent/15 text-accent'],
                    'partial'   => ['PARTIAL', 'bg-blue-50 text-blue-600'],
                    default     => ['UNPAID',  'bg-gray-100 text-gray-500'],
                };
            @endphp
            <div class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-50 last:border-b-0">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-content:center flex-shrink-0" style="display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-bag-check text-amber-500 text-base"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-primary-dark truncate">{{ $bill->supplier->name ?? 'Unknown Supplier' }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $bill->bill_number }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-[14px] font-black text-primary-dark">${{ number_format($bill->total_amount, 2) }}</p>
                    <span class="inline-block text-[10px] font-black px-2 py-0.5 rounded-full mt-0.5 {{ $bStatus[1] }}">{{ $bStatus[0] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- ── Bottom Navigation Bar ────────────────────────────────────────── --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 shadow-lg safe-area-pb"
     style="padding-bottom: env(safe-area-inset-bottom, 0);">
    <div class="flex items-center justify-around h-16 max-w-lg mx-auto px-2">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 min-w-0 flex-1 group">
            <i class="bi bi-house-fill text-xl {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}"></i>
            <span class="text-[10px] font-bold {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">Home</span>
        </a>
        <a href="{{ route('sales.invoice.view') }}" class="flex flex-col items-center gap-0.5 min-w-0 flex-1 group">
            <i class="bi bi-receipt text-xl {{ request()->routeIs('sales.invoice*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}"></i>
            <span class="text-[10px] font-bold {{ request()->routeIs('sales.invoice*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">Sales</span>
        </a>
        {{-- FAB --}}
        <a href="{{ route('sales.invoice.create') }}" class="relative -top-4 w-14 h-14 rounded-full bg-primary shadow-xl flex items-center justify-content:center text-white hover:bg-primary-dark transition-colors flex-shrink-0" style="display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-plus-lg text-2xl"></i>
        </a>
        <a href="{{ route('report.profit.loss') }}" class="flex flex-col items-center gap-0.5 min-w-0 flex-1 group">
            <i class="bi bi-bar-chart-line text-xl {{ request()->routeIs('report.*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}"></i>
            <span class="text-[10px] font-bold {{ request()->routeIs('report.*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">Reports</span>
        </a>
        <a href="#" onclick="document.querySelector('.sidebar')?.classList.toggle('show')" class="flex flex-col items-center gap-0.5 min-w-0 flex-1 group">
            <i class="bi bi-grid text-xl text-gray-400 group-hover:text-primary"></i>
            <span class="text-[10px] font-bold text-gray-400 group-hover:text-primary">Menu</span>
        </a>
    </div>
</nav>

@endsection
