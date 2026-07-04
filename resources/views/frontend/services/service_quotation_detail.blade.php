@extends('admin.admin_master')
@section('page_title', 'Quotation — ' . $quotation->quote_number)
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('service-quotations.index') }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary"><i class="bi bi-arrow-left text-sm"></i></a>
            <div>
                <h1 class="text-[18px] font-bold text-primary-dark font-mono">{{ $quotation->quote_number }}</h1>
                <p class="text-[12px] text-gray-400">{{ $quotation->title }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('service-quotations.pdf', $quotation->id) }}" class="btn-action-view flex items-center gap-1.5 px-3 py-2 text-[12px]"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            @if($quotation->status !== 'converted')
                <a href="{{ route('service-quotations.edit', $quotation->id) }}" class="btn-action-edit flex items-center gap-1.5 px-3 py-2 text-[12px]"><i class="bi bi-pencil"></i> Edit</a>
                <form method="POST" action="{{ route('service-quotations.convert', $quotation->id) }}" onsubmit="return confirm('Convert to Service Order?')">
                    @csrf
                    <button type="submit" class="btn-premium-primary"><i class="bi bi-arrow-right-circle me-1"></i> Convert to Order</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">

            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <div class="flex flex-wrap gap-6">
                    <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</p>
                        <span class="px-3 py-1 rounded-full text-[12px] font-bold {{ $quotation->status_color }}">{{ ucfirst($quotation->status) }}</span></div>
                    <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Customer</p>
                        <p class="text-[13px] font-bold text-gray-700">{{ $quotation->customer->name ?? '—' }}</p></div>
                    @if($quotation->valid_until)
                    <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Valid Until</p>
                        <p class="text-[13px] font-bold text-gray-700 {{ $quotation->valid_until->isPast() && $quotation->status === 'sent' ? 'text-red-500' : '' }}">{{ $quotation->valid_until->format('d M Y') }}</p></div>
                    @endif
                    @if($quotation->convertedOrder)
                    <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Converted To</p>
                        <a href="{{ route('service-orders.show', $quotation->convertedOrder->id) }}" class="text-[13px] font-bold text-primary font-mono underline">{{ $quotation->convertedOrder->order_number }}</a></div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Items</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead><tr class="bg-gray-50">
                            <th class="px-5 py-2 text-left text-[10px] font-bold text-gray-400 uppercase">Description</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase">Qty</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase">Unit Price</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold text-gray-400 uppercase">Disc%</th>
                            <th class="px-5 py-2 text-right text-[10px] font-bold text-gray-400 uppercase">Total</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($quotation->items as $item)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-700">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-center font-mono">$ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $item->discount_pct > 0 ? $item->discount_pct.'%' : '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold font-mono">$ {{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-100 bg-gray-50">
                                <td colspan="4" class="px-5 py-3 text-right text-[12px] text-gray-500 font-bold">Subtotal</td>
                                <td class="px-5 py-3 text-right font-bold font-mono">$ {{ number_format($quotation->subtotal, 2) }}</td>
                            </tr>
                            @if($quotation->discount_amount > 0)
                            <tr class="bg-gray-50"><td colspan="4" class="px-5 py-2 text-right text-[12px] text-gray-400">Discount</td><td class="px-5 py-2 text-right font-mono text-[12px]">- $ {{ number_format($quotation->discount_amount, 2) }}</td></tr>
                            @endif
                            @if($quotation->tax_amount > 0)
                            <tr class="bg-gray-50"><td colspan="4" class="px-5 py-2 text-right text-[12px] text-gray-400">Tax</td><td class="px-5 py-2 text-right font-mono text-[12px]">$ {{ number_format($quotation->tax_amount, 2) }}</td></tr>
                            @endif
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-5 py-3 text-right font-black text-primary uppercase text-[12px]">Total</td>
                                <td class="px-5 py-3 text-right font-black text-primary text-[16px] font-mono">$ {{ number_format($quotation->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($quotation->notes)
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-2">Notes</h2>
                <p class="text-[13px] text-gray-600 leading-relaxed">{{ $quotation->notes }}</p>
            </div>
            @endif

            @if($quotation->status !== 'converted')
            <form method="POST" action="{{ route('service-quotations.destroy', $quotation->id) }}"
                  onsubmit="return confirm('Delete this quotation?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2.5 border border-red-200 text-red-500 rounded-xl text-[13px] font-bold hover:bg-red-50 transition-colors">
                    <i class="bi bi-trash3 me-1"></i> Delete Quotation
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
