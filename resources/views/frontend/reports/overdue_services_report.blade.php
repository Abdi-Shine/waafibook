@extends('admin.admin_master')
@section('page_title', 'Overdue Services Report')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Overdue Services</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Service orders past their scheduled date</p>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-100 p-5 rounded-[1rem]">
            <p class="text-[12px] text-red-400 mb-1">Total Overdue</p>
            <h3 class="text-[24px] font-black text-red-600">{{ $totalOverdue }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">1–3 Days</p>
            <h3 class="text-[24px] font-black text-yellow-500">{{ $overdue1to3 }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">4–7 Days</p>
            <h3 class="text-[24px] font-black text-orange-500">{{ $overdue4to7 }}</h3>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm">
            <p class="text-[12px] text-gray-400 mb-1">7+ Days</p>
            <h3 class="text-[24px] font-black text-red-600">{{ $overdue7plus }}</h3>
        </div>
    </div>

    {{-- Overdue Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Order #</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Customer</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Title</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Priority</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase">Scheduled</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase">Days Overdue</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($overdueOrders as $order)
                    @php
                        $daysOverdue = now()->diffInDays($order->scheduled_date);
                        $urgencyClass = $daysOverdue >= 7 ? 'text-red-600 bg-red-50' : ($daysOverdue >= 4 ? 'text-orange-600 bg-orange-50' : 'text-yellow-600 bg-yellow-50');
                        $priorityColors = ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','normal'=>'bg-blue-50 text-blue-600','low'=>'bg-gray-100 text-gray-500'];
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-mono text-[12px] font-bold text-primary">
                            <a href="{{ route('service-orders.show', $order->id) }}" class="hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $order->customer->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600 max-w-[150px] truncate">{{ $order->title ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $priorityColors[$order->priority] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($order->priority) }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 font-mono text-[12px]">{{ $order->scheduled_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $urgencyClass }}">{{ $daysOverdue }}d</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="{{ route('service-orders.show', $order->id) }}" class="btn-action-edit" title="View"><i class="bi bi-arrow-right-circle"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center text-gray-400">
                            <i class="bi bi-check-circle text-3xl block mb-3 text-green-400"></i>
                            <p class="text-[13px] font-bold uppercase tracking-widest text-green-500">No overdue service orders</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
