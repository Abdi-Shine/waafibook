@extends('admin.admin_master')
@section('page_title', 'Purchase Orders')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- ── Header ── --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Purchase Orders</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.order.create') }}" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                <span>Create Purchase Order</span>
            </a>
        </div>
    </div>

    {{-- ── Stats Cards ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Orders</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-file-earmark-text text-[10px]"></i> All time recorded
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-file-earmark-text text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Orders</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->where('status', 'pending')->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-clock-history text-[10px]"></i> Awaiting approval
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-clock-history text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Approved Orders</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->where('status', 'approved')->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-check2-circle text-[10px]"></i> Ready for delivery
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-check2-circle text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Stock Investment</p>
                <h3 class="text-[18px] font-black text-primary">{{ $sym }} {{ number_format($purchaseOrders->sum('total_amount'), 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Active portfolio</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-currency-dollar text-lg"></i>
            </div>
        </div>

    </div>

    {{-- ── Filter & Table ── --}}
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

        {{-- Filters --}}
        <form action="{{ route('purchase.order.index') }}" method="GET"
              class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">

            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by PO #, supplier..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <div class="relative min-w-[150px]">
                <select name="status" onchange="this.form.submit()"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="pending"   {{ ($filters['status'] ?? '') === 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="approved"  {{ ($filters['status'] ?? '') === 'approved'  ? 'selected' : '' }}>Approved</option>
                    <option value="received"  {{ ($filters['status'] ?? '') === 'received'  ? 'selected' : '' }}>Received</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <div class="relative min-w-[200px]">
                <select name="supplier_id" onchange="this.form.submit()"
                    class="w-full pl-3 pr-10 py-2.5 bg-gray-50/50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ ($filters['supplier_id'] ?? '') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <button type="submit"
                class="h-9 px-4 flex-shrink-0 flex items-center justify-center rounded-[0.5rem] bg-primary text-white text-xs font-bold uppercase tracking-wide hover:bg-primary-dark transition-all">
                <i class="bi bi-search mr-1"></i> Search
            </button>

            @if(array_filter($filters ?? []))
                <a href="{{ route('purchase.order.index') }}"
                    class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-[0.5rem] shadow-sm pb-clear-btn"
                    title="Clear Filters">
                    <i class="bi bi-x-lg pb-clear-icon"></i>
                </a>
            @endif
        </form>

        {{-- Table Title --}}
        <div class="px-5 py-3 flex items-center border-b border-gray-100 bg-background/50">
            <div class="flex items-center gap-2">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Purchase Order List</h2>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-y-auto overflow-x-auto" style="max-height:75vh;">
            <table class="w-full whitespace-nowrap text-left">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">PO Number</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Supplier</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Items</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Total</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchaseOrders as $key => $po)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($key + 1, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-black text-primary-dark uppercase tracking-wider">{{ $po->po_number }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-[12px] font-semibold text-primary-dark leading-tight">
                                    {{ $po->supplier->name ?? '—' }}
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter mt-0.5">Verified Partner</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-0.5">
                                    @forelse($po->items->take(2) as $item)
                                        <span class="text-[12px] font-semibold text-primary-dark leading-tight flex items-center gap-1">
                                            <i class="bi bi-dot text-gray-300"></i>{{ $item->product->product_name ?? ($item->product_name ?? 'Product') }}
                                        </span>
                                    @empty
                                        <span class="text-[12px] font-semibold text-gray-400 italic">—</span>
                                    @endforelse
                                    @if($po->items->count() > 2)
                                        <span class="text-[11px] font-semibold text-primary">+{{ $po->items->count() - 2 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-semibold text-primary-dark">
                                    {{ \Carbon\Carbon::parse($po->order_date)->format('d M, Y') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $sym }} {{ number_format($po->total_amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($po->status === 'approved' || $po->status === 'received')
                                    <span class="status-completed">{{ ucfirst($po->status) }}</span>
                                @elseif($po->status === 'pending')
                                    <span class="status-warning">Pending</span>
                                @elseif($po->status === 'cancelled')
                                    <span class="status-danger">Cancelled</span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-400 border border-gray-200 uppercase tracking-wider">{{ $po->status }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('purchase.order.print', $po->id) }}" class="btn-action-view" title="View / Print">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('purchase.order.create') }}" class="btn-action-edit" title="Edit Order"
                                       onclick="event.preventDefault(); editPO({{ $po->id }})">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @if($po->status === 'pending')
                                    <button onclick="updatePOStatus({{ $po->id }}, 'approved')" class="btn-action-view" title="Approve Order">
                                        <i class="bi bi-shield-check"></i>
                                    </button>
                                    @endif
                                    <button onclick="confirmDeletePO({{ $po->id }}, '{{ $po->po_number }}')" class="btn-action-delete" title="Delete Order">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <i class="bi bi-inbox text-4xl"></i>
                                    <p class="text-xs italic">No purchase orders found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Record count --}}
        <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing <span class="text-primary-dark">{{ $purchaseOrders->count() }}</span> {{ Str::plural('entry', $purchaseOrders->count()) }}
            </p>
        </div>

    </div>

</div>

@push('scripts')
<script>
    function updatePOStatus(id, status) {
        Swal.fire({
            title: 'Approve this Order?',
            text: `Confirm status change to ${status.toUpperCase()}?`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            customClass: { popup: 'rounded-[1.5rem]', confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest', cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest' }
        }).then(result => {
            if (result.isConfirmed) {
                fetch(`/purchase/orders/update-status/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ status })
                }).then(r => r.json()).then(res => { if (res.success) location.reload(); });
            }
        });
    }

    function editPO(id) {
        Swal.fire({ title: 'Loading…', didOpen: () => Swal.showLoading() });
        fetch(`/purchase/orders/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(po => {
                Swal.close();
                window.location.href = `/purchase/orders/${id}/edit`;
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load order.' }));
    }

    function confirmDeletePO(id, number) {
        Swal.fire({
            title: 'Delete Purchase Order?',
            text: `Are you sure you want to permanently delete ${number}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            confirmButtonText: 'Yes, delete it!',
            customClass: { popup: 'rounded-[1.5rem]', confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest', cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest' }
        }).then(result => {
            if (result.isConfirmed) {
                fetch(`/purchase/orders/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error!', 'Something went wrong.', 'error'));
            }
        });
    }
</script>
@endpush
@endsection
