@extends('admin.admin_master')
@section('page_title', 'Service Orders')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Service Orders</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Track, assign, and invoice service jobs</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('service-quotations.index') }}" class="btn-action-edit flex items-center gap-1.5 px-4 py-2 text-[13px]">
                <i class="bi bi-file-earmark-text"></i> Quotations
            </a>
            <a href="{{ route('service-orders.create') }}" class="btn-premium-primary group">
                <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform duration-200"></i>
                <span>New Order</span>
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Open Orders</p>
                <h3 class="text-[22px] font-black text-primary">{{ $totalOpen }}</h3>
            </div>
            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                <i class="bi bi-clipboard-check text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Completed This Month</p>
                <h3 class="text-[22px] font-black text-accent">{{ $completedMonth }}</h3>
            </div>
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent">
                <i class="bi bi-check2-circle text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Overdue</p>
                <h3 class="text-[22px] font-black text-red-500">{{ $overdueCount }}</h3>
            </div>
            <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-400">
                <i class="bi bi-exclamation-triangle text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Revenue This Month</p>
                <h3 class="text-[22px] font-black text-primary">$ {{ number_format($revenueMonth, 2) }}</h3>
            </div>
            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, title, customer..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                <option value="">All Status</option>
                @foreach(['pending','confirmed','in_progress','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Priority</label>
            <select name="priority" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                <option value="">All Priority</option>
                @foreach(['low','normal','high','urgent'] as $p)
                    <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-[13px] font-bold">Filter</button>
        <a href="{{ route('service-orders.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[13px] font-bold">Reset</a>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Order #</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Scheduled</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Priority</th>
                        <th class="px-5 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-5 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50/50 transition-colors {{ $order->is_overdue ? 'border-l-2 border-red-300' : '' }}">
                        <td class="px-5 py-3 font-mono font-bold text-primary text-[12px]">{{ $order->order_number }}</td>
                        <td class="px-5 py-3 font-medium text-gray-700">{{ $order->customer->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700 max-w-[200px] truncate">{{ $order->title }}</td>
                        <td class="px-5 py-3 text-gray-500">
                            {{ $order->scheduled_date ? $order->scheduled_date->format('d M Y') : '—' }}
                            @if($order->is_overdue)
                                <span class="ml-1 text-[10px] font-bold text-red-500">OVERDUE</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $order->priority_color }}">
                                {{ strtoupper($order->priority) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->status_color }}">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-bold text-gray-800 font-mono">$ {{ number_format($order->total_amount, 2) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('service-orders.show', $order->id) }}" class="btn-action-edit" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(!$order->sales_order_id)
                                <a href="{{ route('service-orders.edit', $order->id) }}" class="btn-action-view" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                <a href="{{ route('service-orders.pdf', $order->id) }}" class="btn-action-view" title="PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center text-gray-400">
                            <i class="bi bi-tools text-3xl block mb-3"></i>
                            <p class="text-[13px] font-bold uppercase tracking-widest">No service orders found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
