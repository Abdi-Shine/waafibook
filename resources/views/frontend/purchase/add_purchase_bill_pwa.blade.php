@extends('admin.admin_master')
@section('page_title', 'New Purchase Bill')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    supplierId: '',
    supplierBalance: 0,
    purchaseDate: '{{ date('Y-m-d') }}',
    paymentAccountId: '',
    discountAmount: 0,
    paidAmount: 0,
    notes: '',
    saving: false,
    products: @js($products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->product_name,
        'code' => $p->product_code,
        'unit' => $p->unit,
        'price' => (float) $p->purchase_price,
    ])),
    suppliers: @js($suppliers->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'balance' => (float) ($s->amount_balance ?? 0),
    ])),
    items: [{ product_id: '', product_name: '', product_code: '', unit: 'Piece', quantity: 1, unit_price: 0, discount: 0 }],

    get subtotal() {
        return this.items.reduce((sum, it) => sum + Math.max(0, (parseFloat(it.quantity) || 0) * (parseFloat(it.unit_price) || 0) - (parseFloat(it.discount) || 0)), 0);
    },
    get grandTotal() {
        return Math.max(0, this.subtotal - (parseFloat(this.discountAmount) || 0));
    },
    get balanceDue() {
        return Math.max(0, this.grandTotal - (parseFloat(this.paidAmount) || 0));
    },
    lineAmount(it) {
        return Math.max(0, (parseFloat(it.quantity) || 0) * (parseFloat(it.unit_price) || 0) - (parseFloat(it.discount) || 0));
    },
    addItem() {
        this.items.push({ product_id: '', product_name: '', product_code: '', unit: 'Piece', quantity: 1, unit_price: 0, discount: 0 });
    },
    removeItem(i) {
        if (this.items.length > 1) this.items.splice(i, 1);
    },
    onProductSelect(i) {
        const p = this.products.find(p => p.id == this.items[i].product_id);
        if (p) {
            this.items[i].product_name = p.name;
            this.items[i].product_code = p.code;
            this.items[i].unit = p.unit || 'Piece';
            this.items[i].unit_price = p.price;
        }
    },
    onSupplierSelect() {
        const s = this.suppliers.find(s => s.id == this.supplierId);
        this.supplierBalance = s ? s.balance : 0;
    },
    async submitBill() {
        if (!this.supplierId) {
            Swal.fire({ icon: 'error', title: 'Supplier required', text: 'Please select a supplier.' });
            return;
        }
        const validItems = this.items.filter(it => it.product_id && parseFloat(it.quantity) > 0);
        if (!validItems.length) {
            Swal.fire({ icon: 'error', title: 'No items', text: 'Please add at least one product.' });
            return;
        }
        this.saving = true;
        try {
            const payload = {
                supplier_id: this.supplierId,
                branch_id: {{ auth()->user()->getAssignedBranchId() ?? ($branches->first()->id ?? 'null') }},
                payment_account_id: this.paymentAccountId || null,
                purchase_type: 'Purchase',
                purchase_no: '{{ $purchase_no }}',
                supplier_invoice_no: '{{ $voucher_no ?? '' }}',
                purchase_date: this.purchaseDate,
                expected_delivery: null,
                payment_terms: this.paidAmount >= this.grandTotal ? 'Cash' : 'Credit',
                subtotal: this.subtotal,
                discount: parseFloat(this.discountAmount) || 0,
                vat: 0,
                total_amount: this.grandTotal,
                paid_amount: parseFloat(this.paidAmount) || 0,
                notes: this.notes,
                items: validItems.map(it => ({
                    product_id: it.product_id,
                    product_name: it.product_name,
                    product_code: it.product_code,
                    unit: it.unit,
                    quantity: parseFloat(it.quantity) || 0,
                    unit_price: parseFloat(it.unit_price) || 0,
                    discount: parseFloat(it.discount) || 0,
                    total_amount: this.lineAmount(it),
                })),
            };
            const response = await fetch('{{ route('purchase.bill.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (data.status !== 'success') {
                Swal.fire({ icon: 'error', title: 'Failed to save', text: data.message || 'Please try again.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Bill Recorded!', text: 'Stock updated successfully.', confirmButtonColor: '#004161' })
                .then(() => window.location.href = '{{ route('purchase.bill') }}');
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save the bill. Please try again.' });
        } finally {
            this.saving = false;
        }
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Purchase Bill</h1>
        <div class="px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-[12px] font-bold text-primary-dark shrink-0">
            {{ $purchase_no }}
        </div>
    </div>

    {{-- Supplier & Date --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-3">Supplier & Date</p>

        <div class="grid grid-cols-2 gap-3 mb-1.5">
            <div>
                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Supplier <span class="text-primary">*</span></label>
                <div class="relative">
                    <select x-model="supplierId" @change="onSupplierSelect()" required
                        class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div>
                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Bill Date <span class="text-primary">*</span></label>
                <input type="date" x-model="purchaseDate" required
                    class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
            </div>
        </div>

        <p class="text-[11px] font-black text-accent uppercase tracking-tight" x-show="supplierId">
            BAL: <span x-text="'{{ $curr }} ' + supplierBalance.toFixed(2)"></span>
        </p>
    </div>

    {{-- Items --}}
    <div class="mx-5 mt-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide">Purchase Items</p>
            <button type="button" @click="addItem()"
                class="flex items-center gap-1 px-3 py-1.5 bg-accent/10 text-primary font-bold rounded-lg text-[11px] uppercase tracking-wide border border-accent/30">
                <i class="bi bi-plus-lg"></i> Add Item
            </button>
        </div>

        <template x-for="(item, i) in items" :key="i">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black text-gray-400 uppercase tracking-wide" x-text="'Item ' + (i + 1)"></span>
                    <button type="button" @click="removeItem(i)" x-show="items.length > 1"
                        class="w-6 h-6 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500">
                        <i class="bi bi-trash text-[10px]"></i>
                    </button>
                </div>

                <div class="mb-3">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Product</label>
                    <div class="relative">
                        <select x-model="item.product_id" @change="onProductSelect(i)"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Qty</label>
                        <input type="number" min="0" step="0.01" x-model="item.quantity"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Unit</label>
                        <div class="relative">
                            <select x-model="item.unit"
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                                <option value="Piece">Piece</option>
                                <option value="Box">Box</option>
                                <option value="Kg">Kg</option>
                                <option value="Litre">Litre</option>
                                <option value="Set">Set</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Price/Unit</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                            <input type="number" min="0" step="0.01" x-model="item.unit_price"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Discount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                            <input type="number" min="0" step="0.01" x-model="item.discount"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <span class="text-[11px] font-black text-gray-400 uppercase tracking-wide">Amount</span>
                    <span class="text-[14px] font-black text-primary-dark" x-text="'{{ $curr }} ' + lineAmount(item).toFixed(2)"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Summary --}}
    <div class="mx-5 mt-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-3">Payment</p>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount Discount</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                    <input type="number" min="0" step="0.01" x-model="discountAmount"
                        class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>
            </div>
            <div>
                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount Paid</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                    <input type="number" min="0" step="0.01" x-model="paidAmount"
                        class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Payment Account</label>
            <div class="relative">
                <select x-model="paymentAccountId"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                    <option value="">None</option>
                    @foreach($accounts->whereIn('type', ['cash', 'bank']) as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </div>

        <div class="flex items-center justify-between px-1 py-2 border-t border-gray-100 text-[12px] font-semibold text-gray-500">
            <span>Subtotal</span>
            <span x-text="'{{ $curr }} ' + subtotal.toFixed(2)"></span>
        </div>
        <div class="flex items-center justify-between px-1 pb-2 text-[12px] font-semibold text-gray-500">
            <span>Balance Due</span>
            <span x-text="'{{ $curr }} ' + balanceDue.toFixed(2)"></span>
        </div>

        <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between">
            <span class="text-[11px] font-black text-white uppercase tracking-wider">Total Amount</span>
            <span class="text-[15px] font-black text-accent" x-text="'{{ $curr }} ' + grandTotal.toFixed(2)"></span>
        </div>
    </div>

    {{-- Notes --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Notes</label>
        <textarea x-model="notes" rows="3" placeholder="Additional notes, terms, or conditions..."
            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
    </div>

    {{-- Actions --}}
    <div class="mx-5 mt-5 flex gap-3">
        <a href="{{ route('purchase.view') }}"
            class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide text-center">
            Cancel
        </a>
        <button type="button" @click="submitBill()" :disabled="saving"
            class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
            :class="saving ? 'opacity-60' : ''">
            <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
            <span x-text="saving ? 'Saving...' : 'Save Bill'"></span>
        </button>
    </div>
</div>
@endsection
