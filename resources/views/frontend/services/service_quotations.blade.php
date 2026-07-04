@extends('admin.admin_master')
@section('page_title', 'Service Quotations')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Service Quotations</h1>
            <p class="text-[13px] text-gray-400 mt-0.5">Create and send quotes; convert to service orders</p>
        </div>
        <a href="{{ route('service-quotations.create') }}" class="btn-premium-primary group">
            <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform duration-200"></i>
            <span>New Quotation</span>
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach([['Draft', $totalDraft, 'bi-file-earmark', 'gray'],['Sent', $totalSent, 'bi-send', 'blue'],['Accepted', $totalAccepted, 'bi-check2', 'green'],['Converted', $totalConverted, 'bi-arrow-right-circle', 'accent']] as [$label, $val, $icon, $color])
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">{{ $label }}</p>
                <h3 class="text-[22px] font-black {{ $color === 'accent' ? 'text-accent' : ($color === 'green' ? 'text-green-600' : ($color === 'blue' ? 'text-blue-600' : 'text-gray-700')) }}">{{ $val }}</h3>
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
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Quote #, title, customer..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                <option value="">All</option>
                @foreach(['draft','sent','accepted','declined','converted'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-[13px] font-bold">Filter</button>
        <a href="{{ route('service-quotations.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-[13px] font-bold">Reset</a>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Quote #</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Valid Until</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</th>
                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($quotations as $q)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3 font-mono font-bold text-primary text-[12px]">{{ $q->quote_number }}</td>
                        <td class="px-5 py-3 font-medium text-gray-700">{{ $q->customer->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600 max-w-[180px] truncate">{{ $q->title ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $q->valid_until ? $q->valid_until->format('d M Y') : '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $q->status_color }}">{{ ucfirst($q->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-right font-bold font-mono">$ {{ number_format($q->total_amount, 2) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('service-quotations.show', $q->id) }}" class="btn-action-edit" title="View"><i class="bi bi-eye"></i></a>
                                @if($q->status !== 'converted')
                                    <a href="{{ route('service-quotations.edit', $q->id) }}" class="btn-action-view" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('service-quotations.convert', $q->id) }}" class="inline" onsubmit="return confirm('Convert this quotation to a Service Order?')">
                                        @csrf
                                        <button type="submit" class="btn-action-edit" title="Convert to Order"><i class="bi bi-arrow-right-circle"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('service-quotations.pdf', $q->id) }}" class="btn-action-view" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center text-gray-400">
                            <i class="bi bi-file-earmark-text text-3xl block mb-3"></i>
                            <p class="text-[13px] font-bold uppercase tracking-widest">No quotations found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">{{ $quotations->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
