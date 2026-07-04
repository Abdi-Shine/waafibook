@extends('admin.admin_master')
@section('page_title', isset($quotation) ? 'Edit Quotation' : 'New Quotation')
@section('admin')

@php $editMode = isset($quotation); @endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('service-quotations.index') }}" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-primary">
            <i class="bi bi-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-[18px] font-bold text-primary-dark">{{ $editMode ? 'Edit Quotation' : 'New Quotation' }}</h1>
            @if($editMode)<p class="text-[12px] text-gray-400">{{ $quotation->quote_number }}</p>@endif
        </div>
    </div>

    <form method="POST" action="{{ $editMode ? route('service-quotations.update', $quotation->id) : route('service-quotations.store') }}"
          x-data="{
            items: {{ $editMode ? $quotation->items->map(fn($i)=>['product_id'=>$i->product_id,'description'=>$i->description,'quantity'=>$i->quantity,'unit_price'=>$i->unit_price,'discount_pct'=>$i->discount_pct,'total'=>$i->total]) : '[]' }},
            addItem() { this.items.push({product_id:'',description:'',quantity:1,unit_price:0,discount_pct:0,total:0}); },
            removeItem(i) { this.items.splice(i,1); },
            calcTotals() { this.items.forEach(i=>{ i.total = parseFloat(i.quantity||0)*parseFloat(i.unit_price||0)*(1-parseFloat(i.discount_pct||0)/100); }); },
            get subtotal() { return this.items.reduce((s,i)=>s+parseFloat(i.total||0),0); }
          }">
        @csrf
        @if($editMode) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">

                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500 mb-4">Quotation Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Customer <span class="text-red-400">*</span></label>
                            <select name="customer_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" {{ ($editMode && $quotation->customer_id == $c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Title</label>
                            <input type="text" name="title" value="{{ $editMode ? $quotation->title : old('title') }}"
                                placeholder="Quote title / subject"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                                @foreach(['draft','sent','accepted','declined'] as $s)
                                    <option value="{{ $s }}" {{ ($editMode && $quotation->status === $s) ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Valid Until</label>
                            <input type="date" name="valid_until" value="{{ $editMode ? $quotation->valid_until?->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-[13px] focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none resize-none">{{ $editMode ? $quotation->notes : '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-[13px] font-black uppercase tracking-wider text-gray-500">Items</h2>
                        <button type="button" @click="addItem()" class="flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-[12px] font-bold">
                            <i class="bi bi-plus-lg"></i> Add Line
                        </button>
                    </div>
                    <table class="w-full text-[13px]">
                        <thead><tr class="border-b border-gray-100">
                            <th class="pb-2 text-left text-[10px] font-bold text-gray-400 uppercase">Description</th>
                            <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase w-20">Qty</th>
                            <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase w-28">Unit Price</th>
                            <th class="pb-2 text-center text-[10px] font-bold text-gray-400 uppercase w-20">Disc%</th>
                            <th class="pb-2 text-right text-[10px] font-bold text-gray-400 uppercase w-24">Total</th>
                            <th class="pb-2 w-8"></th>
                        </tr></thead>
                        <tbody>
                            <template x-for="(item,index) in items" :key="index">
                                <tr class="border-b border-gray-50">
                                    <td class="py-2 pr-2">
                                        <input type="hidden" :name="'items['+index+'][product_id]'" x-model="item.product_id">
                                        <input type="text" :name="'items['+index+'][description]'" x-model="item.description" required @change="calcTotals()"
                                            class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none">
                                    </td>
                                    <td class="py-2 px-1"><input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" min="0.01" step="0.01" @input="calcTotals()" class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 outline-none"></td>
                                    <td class="py-2 px-1"><input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" min="0" step="0.01" @input="calcTotals()" class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 outline-none"></td>
                                    <td class="py-2 px-1"><input type="number" :name="'items['+index+'][discount_pct]'" x-model="item.discount_pct" min="0" max="100" step="0.01" @input="calcTotals()" class="w-full px-2 py-1.5 border border-gray-200 rounded text-[13px] text-center focus:ring-1 focus:ring-primary/20 outline-none"></td>
                                    <td class="py-2 pl-1 text-right font-bold font-mono text-[12px]" x-text="'$ '+parseFloat(item.total||0).toFixed(2)"></td>
                                    <td class="py-2 pl-1"><button type="button" @click="removeItem(index)" class="w-7 h-7 flex items-center justify-center rounded text-red-400 hover:bg-red-50"><i class="bi bi-trash3 text-xs"></i></button></td>
                                </tr>
                            </template>
                            <tr x-show="items.length===0"><td colspan="6" class="py-6 text-center text-[12px] text-gray-400">Click "Add Line" to add items</td></tr>
                        </tbody>
                    </table>

                    <div class="mt-4 flex justify-end">
                        <div class="w-60 space-y-2 text-[13px]">
                            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="font-mono font-bold" x-text="'$ '+subtotal.toFixed(2)"></span></div>
                            <div class="flex items-center justify-between gap-2"><span class="text-gray-500">Discount ($)</span><input type="number" name="discount_amount" min="0" step="0.01" value="{{ $editMode ? $quotation->discount_amount : 0 }}" class="w-24 px-2 py-1 border border-gray-200 rounded text-[13px] text-right font-mono focus:ring-1 focus:ring-primary/20 outline-none"></div>
                            <div class="flex items-center justify-between gap-2"><span class="text-gray-500">Tax ($)</span><input type="number" name="tax_amount" min="0" step="0.01" value="{{ $editMode ? $quotation->tax_amount : 0 }}" class="w-24 px-2 py-1 border border-gray-200 rounded text-[13px] text-right font-mono focus:ring-1 focus:ring-primary/20 outline-none"></div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-xl font-bold text-[14px]">
                        {{ $editMode ? 'Update Quotation' : 'Save Quotation' }}
                    </button>
                    <a href="{{ route('service-quotations.index') }}" class="w-full py-3 bg-accent text-primary rounded-xl font-bold text-[14px] text-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
