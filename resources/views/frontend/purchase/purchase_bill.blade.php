@extends('admin.admin_master')
@section('page_title', 'Purchase Bills')

@section('admin')
    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

        {{-- ── Header ──────────────────────────────────────────────────────────────── --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Purchase Bills</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('purchase.bill.create') }}" class="btn-premium-primary group normal-case">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Add New Bill</span>
                </a>
            </div>
        </div>

        {{-- ── Stats Cards ─────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            {{-- Total Bills --}}
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Bills</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($billStats['count']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-receipt text-[10px]"></i> Issued invoices
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-receipt text-lg"></i>
                </div>
            </div>

            {{-- Total Paid Out --}}
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Paid Out</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $sym }} {{ number_format($billStats['totalPaid'], 0) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-patch-check-fill text-[10px]"></i> Cleared payments
                    </p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>

            {{-- Balance Due --}}
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Balance Due</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $sym }} {{ number_format($billStats['totalAmount'] - $billStats['totalPaid'], 0) }}</h3>
                    <p class="text-xs font-bold text-primary mt-1.5 flex items-center gap-1">
                        <i class="bi bi-exclamation-triangle text-[10px]"></i> Outstanding
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-credit-card-2-back text-lg"></i>
                </div>
            </div>

            {{-- Total Vouchers --}}
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Vouchers</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($billStats['vouchers']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">Reference units</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-file-earmark-text text-lg"></i>
                </div>
            </div>

        </div>

        {{-- ── Filter & Table ───────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

            {{-- Filters --}}
            <form action="{{ route('purchase.bill') }}" method="GET"
                  class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">

                {{-- Search --}}
                <div class="relative group min-w-[250px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by bill number or supplier..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                {{-- Status Filter --}}
                <div class="relative min-w-[150px]">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="pending"  {{ ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="paid"     {{ ($filters['status'] ?? '') === 'paid'     ? 'selected' : '' }}>Paid</option>
                        <option value="partial"  {{ ($filters['status'] ?? '') === 'partial'  ? 'selected' : '' }}>Partial</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                {{-- Supplier Filter --}}
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

                {{-- Search Submit --}}
                <button type="submit"
                    class="h-9 px-4 flex-shrink-0 flex items-center justify-center rounded-[0.5rem] bg-primary text-white text-xs font-bold uppercase tracking-wide hover:bg-primary-dark transition-all">
                    <i class="bi bi-search mr-1"></i> Search
                </button>

                {{-- Clear Filters --}}
                @if(array_filter($filters ?? []))
                    <a href="{{ route('purchase.bill') }}"
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
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Purchase Bills List</h2>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Supplier</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Items</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Date</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Total</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Balance Due</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($purchaseBills as $key => $bill)
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    {{ str_pad($purchaseBills->firstItem() + $key, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $bill->supplier->name ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        @forelse($bill->items->take(2) as $item)
                                            <span class="text-[12px] font-semibold text-primary-dark leading-tight flex items-center gap-1">
                                                <i class="bi bi-dot text-gray-300"></i>{{ $item->product_name ?? ($item->product->product_name ?? 'Product') }}
                                            </span>
                                        @empty
                                            <span class="text-[12px] font-semibold text-primary-dark italic">—</span>
                                        @endforelse
                                        @if($bill->items->count() > 2)
                                            <span class="text-[11px] font-semibold text-primary-dark">+{{ $bill->items->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ \Carbon\Carbon::parse($bill->bill_date)->format('d M, Y') }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $sym }} {{ number_format($bill->total_amount, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @php $due = $bill->total_amount - $bill->paid_amount; @endphp
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $sym }} {{ number_format($due, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($bill->status === 'paid')
                                        <span class="status-completed">Paid</span>
                                    @elseif($bill->status === 'partial')
                                        <span class="status-warning">Partial</span>
                                    @elseif($bill->status === 'pending')
                                        <span class="status-danger">Pending</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-400 border border-gray-200 uppercase tracking-wider">{{ $bill->status }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('purchase.bill.edit', $bill->id) }}" class="btn-action-edit" title="Edit Bill">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="{{ route('view_payment_out', ['vendor_id' => $bill->supplier_id]) }}"
                                            class="btn-action-view" title="Post Payment">
                                            <i class="bi bi-credit-card"></i>
                                        </a>
                                        <button onclick="confirmDeleteBill({{ $bill->id }}, '{{ $bill->bill_number }}')"
                                            class="btn-action-delete" title="Delete Bill">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-14 text-center">
                                    <div class="flex flex-col items-center gap-2 text-gray-400">
                                        <i class="bi bi-inbox text-4xl"></i>
                                        <p class="text-xs italic">No purchase bills found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer / Pagination --}}
            @if($purchaseBills->total() > 0)
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between gap-4">
                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                        Showing <span class="text-primary-dark">{{ $purchaseBills->firstItem() }}</span>–<span class="text-primary-dark">{{ $purchaseBills->lastItem() }}</span>
                        of <span class="text-primary-dark">{{ $purchaseBills->total() }}</span> entries
                    </p>
                    <div>
                        {{ $purchaseBills->links() }}
                    </div>
                </div>
            @endif

        </div>

    </div>

    @push('scripts')
        <script>
            function confirmDeleteBill(id, number) {
                Swal.fire({
                    title: 'Delete Purchase Bill?',
                    text: `Are you sure you want to delete ${number}? This will reverse stock and accounting entries.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#004161',
                    cancelButtonColor: '#99CC33',
                    confirmButtonText: 'Yes, delete it!',
                    customClass: {
                        popup: 'rounded-[1.5rem]',
                        confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
                        cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/purchase/bills/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        })
                            .then(res => res.json())
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
