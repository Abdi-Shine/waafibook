@extends('admin.admin_master')
@section('page_title', 'Technician Performance Report')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Technician Performance</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Orders assigned and completed per technician</p>
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
        <a href="{{ route('reports.technician-performance') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[13px] font-bold">Reset</a>
    </form>

    {{-- Technician Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($technicians as $tech)
        @php
            $completionRate = $tech->total_assigned > 0 ? round(($tech->total_completed / $tech->total_assigned) * 100) : 0;
        @endphp
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center font-black text-[14px]">
                        {{ strtoupper(substr($tech->employee->full_name ?? 'T', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-[14px] font-bold text-gray-800">{{ $tech->employee->full_name ?? 'Unknown' }}</p>
                        <p class="text-[11px] text-gray-400">{{ $tech->employee->position ?? 'Technician' }}</p>
                    </div>
                </div>
                <span class="text-[12px] font-black {{ $completionRate >= 80 ? 'text-green-600' : ($completionRate >= 50 ? 'text-yellow-500' : 'text-red-500') }}">{{ $completionRate }}%</span>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3">
                <div class="text-center p-2 bg-gray-50 rounded-lg">
                    <p class="text-[18px] font-black text-gray-800">{{ $tech->total_assigned }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Assigned</p>
                </div>
                <div class="text-center p-2 bg-green-50 rounded-lg">
                    <p class="text-[18px] font-black text-green-700">{{ $tech->total_completed }}</p>
                    <p class="text-[10px] text-green-500 mt-0.5">Completed</p>
                </div>
                <div class="text-center p-2 bg-yellow-50 rounded-lg">
                    <p class="text-[18px] font-black text-yellow-700">{{ $tech->total_assigned - $tech->total_completed }}</p>
                    <p class="text-[10px] text-yellow-500 mt-0.5">Pending</p>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex justify-between text-[11px] text-gray-400 mb-1"><span>Completion Rate</span><span>{{ $completionRate }}%</span></div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $completionRate >= 80 ? 'bg-green-500' : ($completionRate >= 50 ? 'bg-yellow-400' : 'bg-red-400') }} rounded-full transition-all" style="width: {{ $completionRate }}%"></div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center text-gray-400">
            <i class="bi bi-people text-3xl block mb-3"></i>
            <p class="text-[13px] font-bold uppercase tracking-widest">No technician data in this period</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
