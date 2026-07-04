@extends('admin.admin_master')
@section('page_title', 'Service Revenue Report')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Service Revenue Report</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Revenue from service orders by period</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-[13px] font-bold">Apply</button>
        <a href="{{ route('reports.service-revenue') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[13px] font-bold">Reset</a>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">Total Revenue</p>
            <h3 class="text-[22px] font-black text-primary">$ {{ number_format($totalRevenue, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">Orders Invoiced</p>
            <h3 class="text-[22px] font-black text-accent">{{ $totalInvoiced }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">Avg Order Value</p>
            <h3 class="text-[22px] font-black text-gray-700">$ {{ $totalInvoiced > 0 ? number_format($totalRevenue / $totalInvoiced, 2) : '0.00' }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">Pending Revenue</p>
            <h3 class="text-[22px] font-black text-yellow-500">$ {{ number_format($pendingRevenue, 2) }}</h3>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Orders Detail</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Order #</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Date</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Customer</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Title</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Status</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-400 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-mono text-[12px] font-bold text-primary">
                            <a href="{{ route('service-orders.show', $order->id) }}" class="hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $order->customer->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600 max-w-[160px] truncate">{{ $order->title ?? '—' }}</td>
                        <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->status_color }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span></td>
                        <td class="px-5 py-3 text-right font-bold font-mono">$ {{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-[13px] text-gray-400">No service orders in this period</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($orders->count())
                <tfoot>
                    <tr class="bg-gray-50 border-t border-gray-200">
                        <td colspan="5" class="px-5 py-3 text-right font-black text-primary uppercase text-[12px]">Total</td>
                        <td class="px-5 py-3 text-right font-black text-primary font-mono">$ {{ number_format($totalRevenue, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator && $orders->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">{{ $orders->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
