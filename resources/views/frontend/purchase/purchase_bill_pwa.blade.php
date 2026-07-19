@extends('admin.admin_master')
@section('page_title', 'Purchase Bills')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    deleteBill(id, number) {
        Swal.fire({
            title: 'Delete Purchase Bill?',
            text: `Are you sure you want to delete ${number}? This will reverse stock and accounting entries.`,
            icon: 'warning',
            input: 'password',
            inputPlaceholder: 'Enter your admin password to confirm',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            confirmButtonText: 'Yes, delete it!',
            inputValidator: (value) => { if (!value) return 'Password is required.'; }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('/purchase/bills') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ password: result.value }),
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message || 'Something went wrong.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error!', 'Something went wrong.', 'error'));
            }
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Purchase Bills</h1>
        <a href="{{ route('purchase.bill.create') }}"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Bill
        </a>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-receipt text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Bills</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($billStats['count']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Paid Out</p>
            <p class="text-[16px] font-black text-primary">{{ $sym }} {{ number_format($billStats['totalPaid'], 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[130px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-credit-card-2-back text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Balance Due</p>
            <p class="text-[16px] font-black text-primary">{{ $sym }} {{ number_format($billStats['totalAmount'] - $billStats['totalPaid'], 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('purchase.bill') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="SEARCH BILL OR SUPPLIER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="status" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="partial" {{ ($filters['status'] ?? '') === 'partial' ? 'selected' : '' }}>Partial</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Bills</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($purchaseBills as $bill)
            @php
                $due = $bill->total_amount - $bill->paid_amount;
                $itemNames = $bill->items->take(2)->map(fn ($i) => $i->product_name ?? ($i->product->product_name ?? 'Product'))->implode(', ');
                if ($bill->items->count() > 2) $itemNames .= ' +' . ($bill->items->count() - 2) . ' more';
                $statusColor = match($bill->status) {
                    'paid' => 'bg-accent/10 text-accent',
                    'partial' => 'bg-yellow-50 text-yellow-600',
                    'pending' => 'bg-red-50 text-red-500',
                    default => 'bg-gray-100 text-gray-400',
                };
            @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $bill->supplier->name ?? '—' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $itemNames ?: '—' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $sym }} {{ number_format($bill->total_amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ \Carbon\Carbon::parse($bill->bill_date)->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor }}">{{ ucfirst($bill->status) }}</span>
                        <span class="text-xs text-text-secondary">Bal: {{ $sym }}{{ number_format($due, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('purchase.bill.edit', $bill->id) }}"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </a>
                        <a href="{{ route('view_payment_out', ['vendor_id' => $bill->supplier_id]) }}"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-credit-card text-xs"></i>
                        </a>
                        <button type="button" @click="deleteBill({{ $bill->id }}, '{{ $bill->bill_number }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No purchase bills found.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
