@extends('admin.admin_master')
@section('page_title', 'Recurring Schedules')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Recurring Schedules</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Auto-generate service orders on a recurring frequency</p>
        </div>
        <a href="{{ route('service-schedules.create') }}" class="btn-premium-primary group">
            <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform duration-200"></i>
            <span>New Schedule</span>
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach([['Active', $totalActive, 'bi-play-circle', 'green'],['Paused', $totalPaused, 'bi-pause-circle', 'yellow'],['Ended', $totalEnded, 'bi-stop-circle', 'gray'],['Due Today', $dueToday, 'bi-calendar-check', 'accent']] as [$label, $val, $icon, $color])
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">{{ $label }}</p>
                <h3 class="text-[22px] font-black {{ $color === 'accent' ? 'text-accent' : ($color === 'green' ? 'text-green-600' : ($color === 'yellow' ? 'text-yellow-500' : 'text-gray-700')) }}">{{ $val }}</h3>
            </div>
            <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400">
                <i class="bi {{ $icon }} text-lg"></i>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, customer..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                <option value="">All</option>
                @foreach(['active','paused','ended'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Frequency</label>
            <select name="frequency" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                <option value="">All</option>
                @foreach(['daily','weekly','monthly','quarterly','yearly'] as $f)
                    <option value="{{ $f }}" {{ request('frequency') === $f ? 'selected' : '' }}>{{ ucfirst($f) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-[13px] font-bold">Filter</button>
        <a href="{{ route('service-schedules.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[13px] font-bold">Reset</a>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Frequency</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Next Due</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Orders</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($schedules as $s)
                    @php
                        $isOverdue = $s->next_due_date && $s->next_due_date->isPast() && $s->status === 'active';
                        $statusColors = ['active'=>'bg-green-50 text-green-700','paused'=>'bg-yellow-50 text-yellow-700','ended'=>'bg-gray-100 text-gray-500'];
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3 font-bold text-gray-700">{{ $s->title }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $s->customer->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-primary/10 text-primary rounded-full text-[10px] font-bold uppercase">{{ $s->frequency }}</span>
                        </td>
                        <td class="px-5 py-3 font-mono text-[12px] {{ $isOverdue ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                            {{ $s->next_due_date ? $s->next_due_date->format('d M Y') : '—' }}
                            @if($isOverdue)<span class="ml-1 text-[10px]">⚠ Overdue</span>@endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusColors[$s->status] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($s->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="w-7 h-7 rounded-full bg-primary text-white text-[11px] font-black inline-flex items-center justify-center">{{ $s->serviceOrders->count() }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('service-schedules.edit', $s->id) }}" class="btn-action-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('service-schedules.destroy', $s->id) }}" class="inline" onsubmit="return confirm('Delete this schedule?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action-delete" title="Delete"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center text-gray-400">
                            <i class="bi bi-arrow-repeat text-3xl block mb-3"></i>
                            <p class="text-[13px] font-bold uppercase tracking-widest">No recurring schedules</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">{{ $schedules->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
