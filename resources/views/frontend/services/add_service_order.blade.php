@extends('admin.admin_master')
@section('page_title', isset($order) ? 'Edit Service Order' : 'New Service Order')
@section('admin')

@php $editMode = isset($order); @endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('service-orders.index') }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-[18px] font-bold text-primary-dark">{{ $editMode ? 'Edit Service Order' : 'New Service Order' }}</h1>
            @if($editMode)
                <p class="text-[12px] text-gray-400">{{ $order->order_number }}</p>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $editMode ? route('service-orders.update', $order->id) : route('service-orders.store') }}"
          x-data="{
            items: {{ $editMode ? $order->items->map(fn($i)=>['product_id'=>$i->product_id,'description'=>$i->description,'quantity'=>$i->quantity,'unit_price'=>$i->unit_price,'discount_pct'=>$i->discount_pct,'total'=>$i->total]) : '[]' }},
            selectedEmployees: {{ $editMode ? $order->employees->pluck('id') : '[]' }},
            addItem() {
                this.items.push({product_id:'',description:'',quantity:1,unit_price:0,discount_pct:0,total:0});
            },
            removeItem(index) { this.items.splice(index, 1); this.calcTotals(); },
            calcTotals() {
                this.items.forEach(i => {
                    i.total = parseFloat(i.quantity||0) * parseFloat(i.unit_price||0) * (1 - parseFloat(i.discount_pct||0)/100);
                });
            },
            get subtotal() { return this.items.reduce((s,i)=>s+parseFloat(i.total||0),0); },
            get grandTotal() { return this.subtotal + parseFloat(document.getElementById('tax_amount')?.value||0) - parseFloat(document.getElementById('discount_amount')?.value||0); }
          }">
        @csrf
        @if($editMode) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: Main form --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Basic Info --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-4">Order Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Customer <span class="text-red-400">*</span></label>
                            <select name="customer_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ ($editMode && $order->customer_id == $c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Title <span class="text-red-400">*</span></label>
                            <input type="text" name="title" required value="{{ $editMode ? $order->title : old('title') }}"
                                placeholder="e.g. AC Maintenance, Plumbing Repair"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Scheduled Date</label>
                            <input type="date" name="scheduled_date" value="{{ $editMode ? $order->scheduled_date?->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Priority</label>
                            <select name="priority" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($editMode && $order->priority === $val) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!$editMode)
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        @endif
                        <div class="md:col-span-2">
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Description</label>
                            <textarea name="description" rows="2" placeholder="Detailed scope of work..."
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none resize-none">{{ $editMode ? $order->description : '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Service Items</h2>
                        <button type="button" @click="addItem()" class="flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-[12px] font-bold">
                            <i class="bi bi-plus-lg"></i> Add Line
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px]">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="pb-2 text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider w-20">Qty</th>
                                    <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider w-24">Unit Price</th>
                                    <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider w-20">Disc %</th>
                                    <th class="pb-2 text-right text-[10px] font-bold text-gray-400 uppercase tracking-wider w-24">Total</th>
                                    <th class="pb-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="border-b border-gray-50">
                                        <td class="py-2 pr-2">
                                            <input type="hidden" :name="'items['+index+'][product_id]'" x-model="item.product_id">
                                            <input type="text" :name="'items['+index+'][description]'" x-model="item.description"
                                                required placeholder="Service description"
                                                @change="calcTotals()"
                                                class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                        </td>
                                        <td class="py-2 px-1">
                                            <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity"
                                                min="0.01" step="0.01" @input="calcTotals()"
                                                class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                        </td>
                                        <td class="py-2 px-1">
                                            <input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price"
                                                min="0" step="0.01" @input="calcTotals()"
                                                class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                        </td>
                                        <td class="py-2 px-1">
                                            <input type="number" :name="'items['+index+'][discount_pct]'" x-model="item.discount_pct"
                                                min="0" max="100" step="0.01" @input="calcTotals()"
                                                class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                        </td>
                                        <td class="py-2 pl-1 text-right font-bold text-gray-800 font-mono text-[12px]" x-text="'$ ' + parseFloat(item.total||0).toFixed(2)"></td>
                                        <td class="py-2 pl-1">
                                            <button type="button" @click="removeItem(index)" class="w-7 h-7 flex items-center justify-center rounded text-red-400 hover:bg-red-50">
                                                <i class="bi bi-trash3 text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="py-6 text-center text-[12px] text-gray-400">Click "Add Line" to add service items</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    <div class="mt-4 flex justify-end">
                        <div class="w-64 space-y-2 text-[13px]">
                            <div class="flex justify-between text-gray-500">
                                <span>Subtotal</span>
                                <span class="font-mono font-bold" x-text="'$ ' + subtotal.toFixed(2)"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-gray-500">Discount ($)</span>
                                <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01"
                                    value="{{ $editMode ? $order->discount_amount : 0 }}"
                                    class="w-28 px-2 py-1 border border-gray-200 rounded text-[13px] text-right font-mono focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-gray-500">Tax ($)</span>
                                <input type="number" id="tax_amount" name="tax_amount" min="0" step="0.01"
                                    value="{{ $editMode ? $order->tax_amount : 0 }}"
                                    class="w-28 px-2 py-1 border border-gray-200 rounded text-[13px] text-right font-mono focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                            </div>
                            <div class="flex justify-between font-black text-primary border-t border-gray-100 pt-2">
                                <span>Total</span>
                                <span class="font-mono" x-text="'$ ' + grandTotal.toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right sidebar --}}
            <div class="space-y-4">

                {{-- Assign Technicians --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-3">Assign Technicians</h2>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($employees as $emp)
                        <label class="flex items-center gap-2.5 cursor-pointer hover:bg-gray-50 rounded-lg p-2 -mx-2">
                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                {{ ($editMode && $order->employees->contains($emp->id)) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <div>
                                <p class="text-[13px] font-semibold text-gray-800">{{ $emp->full_name }}</p>
                                <p class="text-[11px] text-gray-400">{{ $emp->designation ?? $emp->department ?? 'Technician' }}</p>
                            </div>
                        </label>
                        @endforeach
                        @if($employees->isEmpty())
                            <p class="text-[12px] text-gray-400 text-center py-4">No active employees found</p>
                        @endif
                    </div>
                </div>

                {{-- Notes --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-3">Internal Notes</h2>
                    <textarea name="notes" rows="3" placeholder="Internal notes for this order..."
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none resize-none">{{ $editMode ? $order->notes : '' }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-bold text-[14px] hover:bg-primary/90 transition-colors">
                        {{ $editMode ? 'Update Order' : 'Create Order' }}
                    </button>
                    <a href="{{ route('service-orders.index') }}" class="w-full py-3 bg-accent text-primary rounded-xl font-bold text-[14px] text-center hover:bg-accent/90 transition-colors">
                        Cancel
                    </a>
                </div>

            </div>
        </div>

    </form>
</div>
@endsection
