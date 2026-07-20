@extends('admin.admin_master')
@section('page_title', 'Purchase Orders')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    approveOrder(id) {
        Swal.fire({
            title: 'Approve this Order?',
            text: 'Confirm status change to APPROVED?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('/purchase/orders/update-status') }}/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: 'approved' }),
                })
                    .then(res => res.json())
                    .then(data => { if (data.success) location.reload(); else Swal.fire('Error!', data.message || 'Something went wrong.', 'error'); })
                    .catch(() => Swal.fire('Error!', 'Something went wrong.', 'error'));
            }
        });
    },
    deleteOrder(id, number) {
        deleteRecordWithPassword('{{ url('/purchase/orders') }}/' + id, number, {
            title: 'Delete Purchase Order?',
            text: 'Are you sure you want to permanently delete ' + number + '?'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Purchase Orders</h1>
        <a href="{{ route('purchase.order.create') }}"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Order
        </a>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[110px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-file-earmark-text text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Orders</p>
            <p class="text-[16px] font-black text-primary">{{ $purchaseOrders->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[110px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-clock-history text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Pending</p>
            <p class="text-[16px] font-black text-primary">{{ $purchaseOrders->where('status', 'pending')->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[110px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-check2-circle text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Approved</p>
            <p class="text-[16px] font-black text-primary">{{ $purchaseOrders->where('status', 'approved')->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-currency-dollar text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Stock Investment</p>
            <p class="text-[16px] font-black text-primary">{{ $sym }} {{ number_format($purchaseOrders->sum('total_amount'), 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('purchase.order.index') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="SEARCH PO OR SUPPLIER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="status" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="received" {{ ($filters['status'] ?? '') === 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Purchase Order List</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($purchaseOrders as $po)
            @php
                $itemNames = $po->items->take(2)->map(fn ($i) => $i->product->product_name ?? ($i->product_name ?? 'Product'))->implode(', ');
                if ($po->items->count() > 2) $itemNames .= ' +' . ($po->items->count() - 2) . ' more';
                $statusColor = match($po->status) {
                    'approved', 'received' => 'bg-accent/10 text-accent',
                    'pending' => 'bg-gray-100 text-gray-400',
                    'cancelled' => 'bg-red-50 text-red-500',
                    default => 'bg-gray-100 text-gray-400',
                };
            @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $po->supplier->name ?? '—' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $po->po_number }} · {{ $itemNames ?: '—' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $sym }} {{ number_format($po->total_amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ \Carbon\Carbon::parse($po->order_date)->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor }}">{{ ucfirst($po->status) }}</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('purchase.order.print', $po->id) }}" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        @if($po->status === 'pending')
                            <button type="button" @click="approveOrder({{ $po->id }})"
                                class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                                <i class="bi bi-shield-check text-xs"></i>
                            </button>
                        @endif
                        <button type="button" @click="deleteOrder({{ $po->id }}, '{{ $po->po_number }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No purchase orders found.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
