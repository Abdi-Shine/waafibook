@extends('admin.admin_master')
@section('page_title', 'Service Order — ' . $order->order_number)
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('service-orders.index') }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-[18px] font-bold text-primary-dark font-mono">{{ $order->order_number }}</h1>
                <p class="text-[12px] text-gray-400">{{ $order->title }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('service-orders.pdf', $order->id) }}" class="btn-action-view flex items-center gap-1.5 px-3 py-2 text-[12px]">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
            @if(!$order->sales_order_id)
            <a href="{{ route('service-orders.edit', $order->id) }}" class="btn-action-edit flex items-center gap-1.5 px-3 py-2 text-[12px]">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Status bar --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</p>
                        <span class="px-3 py-1 rounded-full text-[12px] font-bold {{ $order->status_color }}">
                            {{ ucwords(str_replace('_',' ',$order->status)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Priority</p>
                        <span class="px-3 py-1 rounded text-[12px] font-bold {{ $order->priority_color }}">
                            {{ ucfirst($order->priority) }}
                        </span>
                    </div>
                    @if($order->scheduled_date)
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Scheduled</p>
                        <p class="text-[13px] font-bold text-gray-700">{{ $order->scheduled_date->format('d M Y') }}</p>
                    </div>
                    @endif
                    @if($order->completed_date)
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Completed</p>
                        <p class="text-[13px] font-bold text-gray-700">{{ $order->completed_date->format('d M Y') }}</p>
                    </div>
                    @endif
                </div>

                {{-- Update Status --}}
                @if($order->status !== 'cancelled' && !$order->sales_order_id)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('service-orders.status', $order->id) }}" class="flex items-center gap-2 flex-wrap">
                        @csrf
                        <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                            @foreach(['pending','confirmed','in_progress','completed','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-[13px] font-bold">Update Status</button>
                    </form>
                </div>
                @endif
            </div>

            {{-- Line items --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Service Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-5 py-2 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Unit Price</th>
                                <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider">Disc%</th>
                                <th class="px-5 py-2 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($order->items as $item)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-700">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 font-mono">$ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $item->discount_pct > 0 ? $item->discount_pct.'%' : '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-800 font-mono">$ {{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-100 bg-gray-50">
                                <td colspan="4" class="px-5 py-3 text-right text-[12px] text-gray-500 font-bold">Subtotal</td>
                                <td class="px-5 py-3 text-right font-bold font-mono">$ {{ number_format($order->subtotal, 2) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-5 py-2 text-right text-[12px] text-gray-400">Discount</td>
                                <td class="px-5 py-2 text-right text-gray-500 font-mono text-[12px]">- $ {{ number_format($order->discount_amount, 2) }}</td>
                            </tr>
                            @endif
                            @if($order->tax_amount > 0)
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-5 py-2 text-right text-[12px] text-gray-400">Tax</td>
                                <td class="px-5 py-2 text-right text-gray-500 font-mono text-[12px]">$ {{ number_format($order->tax_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-5 py-3 text-right font-black text-primary uppercase tracking-wider text-[12px]">Total</td>
                                <td class="px-5 py-3 text-right font-black text-primary text-[16px] font-mono">$ {{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Generate Invoice --}}
            @if(!$order->sales_order_id && in_array($order->status, ['confirmed','in_progress','completed']))
            <div class="bg-white rounded-[1rem] border border-accent/30 shadow-sm p-5">
                <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-4">Generate Invoice</h2>
                <form method="POST" action="{{ route('service-orders.invoice', $order->id) }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Payment Method</label>
                        <select name="payment_method" required class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Credit">Credit (Unpaid)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Paid Amount</label>
                        <input type="number" name="paid_amount" min="0" step="0.01" value="{{ $order->total_amount }}"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-[13px] w-36 focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                    </div>
                    <button type="submit" class="px-5 py-2 bg-accent text-primary font-black rounded-lg text-[13px]">
                        <i class="bi bi-receipt me-1"></i> Generate Invoice
                    </button>
                </form>
            </div>
            @endif

            {{-- Linked invoice --}}
            @if($order->sales_order_id)
            <div class="bg-accent/10 border border-accent/30 rounded-[1rem] p-5 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Invoice Generated</p>
                    <p class="text-[16px] font-black text-primary">{{ $order->salesOrder->invoice_no ?? '—' }}</p>
                </div>
                <a href="{{ route('sales.invoice.detail', $order->sales_order_id) }}" class="btn-premium-primary">
                    View Invoice <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            @endif

        </div>

        {{-- Right sidebar --}}
        <div class="space-y-4">

            {{-- Customer --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-3">Customer</h2>
                @if($order->customer)
                <p class="text-[14px] font-bold text-primary">{{ $order->customer->name }}</p>
                <p class="text-[12px] text-gray-500 mt-1">{{ $order->customer->phone ?? '—' }}</p>
                <p class="text-[12px] text-gray-500">{{ $order->customer->email ?? '—' }}</p>
                @else
                <p class="text-[12px] text-gray-400">No customer assigned</p>
                @endif
            </div>

            {{-- Technicians --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-3">Assigned Technicians</h2>
                @forelse($order->employees as $emp)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-[12px]">
                        {{ strtoupper(substr($emp->full_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-[13px] font-semibold text-gray-800">{{ $emp->full_name }}</p>
                        <p class="text-[11px] text-gray-400">{{ ucfirst($emp->pivot->role ?? 'Technician') }}</p>
                    </div>
                </div>
                @empty
                <p class="text-[12px] text-gray-400">No technicians assigned</p>
                @endforelse
            </div>

            {{-- Notes --}}
            @if($order->notes)
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-2">Notes</h2>
                <p class="text-[13px] text-gray-600 leading-relaxed">{{ $order->notes }}</p>
            </div>
            @endif

            {{-- Schedule link --}}
            @if($order->schedule)
            <div class="bg-purple-50 border border-purple-100 rounded-[1rem] p-4">
                <p class="text-[10px] font-bold text-purple-400 uppercase tracking-wider mb-1">Recurring Schedule</p>
                <p class="text-[13px] font-bold text-purple-700">{{ $order->schedule->title }}</p>
                <p class="text-[11px] text-purple-500">{{ $order->schedule->frequency_label }}</p>
            </div>
            @endif

            {{-- Delete --}}
            @if(!$order->sales_order_id)
            <form method="POST" action="{{ route('service-orders.destroy', $order->id) }}"
                  onsubmit="return confirm('Delete this service order? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2.5 border border-red-200 text-red-500 rounded-xl text-[13px] font-bold hover:bg-red-50 transition-colors">
                    <i class="bi bi-trash3 me-1"></i> Delete Order
                </button>
            </form>
            @endif
        </div>

    </div>
</div>
@endsection
