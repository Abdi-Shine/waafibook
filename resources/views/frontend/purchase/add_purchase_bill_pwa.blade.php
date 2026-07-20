@extends('admin.admin_master')
@section('page_title', 'New Purchase Bill')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    supplierId: '',
    supplierBalance: 0,
    purchaseDate: '{{ date('Y-m-d') }}',
    discountAmount: 0,
    paidAmount: 0,
    saving: false,
    showAddSupplierModal: false,
    savingSupplier: false,
    newSupplierForm: { name: '', phone: '', supplier_type: 'company', email: '', address: '', amount_balance: '' },
    pickingItemIndex: null,
    productSearch: '',
    showAddProductModal: false,
    savingProduct: false,
    newProductForm: { product_name: '', category_id: '', selling_price: '' },
    categories: @js($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])),
    products: @js($products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->product_name,
        'code' => $p->product_code,
        'unit' => $p->unit,
        'price' => (float) $p->purchase_price,
        'sellingPrice' => (float) $p->selling_price,
        'stock' => (float) ($p->stocks_sum_quantity ?? 0),
    ])),
    suppliers: @js($suppliers->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'balance' => (float) ($s->amount_balance ?? 0),
    ])),
    items: [{ product_id: '', product_name: '', product_code: '', unit: 'Piece', quantity: 1, unit_price: 0, discount: 0 }],

    get filteredProducts() {
        if (!this.productSearch) return this.products;
        const q = this.productSearch.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q));
    },
    openProductPicker(i) {
        this.pickingItemIndex = i;
        this.productSearch = '';
    },
    pickProduct(p) {
        const i = this.pickingItemIndex;
        if (i === null) return;
        this.items[i].product_id = p.id;
        this.onProductSelect(i);
        this.pickingItemIndex = null;
    },
    openAddProductModal() {
        this.newProductForm = { product_name: '', category_id: '', selling_price: '' };
        this.showAddProductModal = true;
    },
    async submitNewProduct() {
        this.savingProduct = true;
        try {
            const payload = {
                ...this.newProductForm,
                selling_price: this.newProductForm.selling_price === '' ? 0 : this.newProductForm.selling_price,
            };
            const response = await fetch('{{ route('product.quick.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (response.status === 422) {
                const firstError = Object.values(data.errors || {})[0]?.[0] || 'Please check the form.';
                Swal.fire({ icon: 'error', title: 'Could not add product', text: firstError });
                return;
            }
            if (!response.ok || !data.success) {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message || 'Please try again.' });
                return;
            }
            const p = data.product;
            const mapped = {
                id: p.id,
                name: p.name,
                code: p.code,
                unit: p.unit || 'Piece',
                price: parseFloat(p.purchase_price) || 0,
                sellingPrice: parseFloat(p.selling_price) || 0,
                stock: parseFloat(p.stock) || 0,
            };
            this.products.push(mapped);
            this.showAddProductModal = false;
            if (this.pickingItemIndex !== null) {
                this.pickProduct(mapped);
            }
            Swal.fire({ toast: true, position: 'top', icon: 'success', title: p.name + ' added', timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not add product. Please try again.' });
        } finally {
            this.savingProduct = false;
        }
    },

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
            this.items[i].unit_price = p.price;
            const unitOptions = ['Piece', 'Box', 'Kg', 'Litre', 'Set'];
            const match = unitOptions.find(u => u.toLowerCase() === (p.unit || '').toLowerCase());
            this.items[i].unit = match || 'Piece';
        }
    },
    onSupplierSelect() {
        const s = this.suppliers.find(s => s.id == this.supplierId);
        this.supplierBalance = s ? s.balance : 0;
    },
    openAddSupplierModal() {
        this.newSupplierForm = { name: '', phone: '', supplier_type: 'company', email: '', address: '', amount_balance: '' };
        this.showAddSupplierModal = true;
    },
    async submitNewSupplier() {
        this.savingSupplier = true;
        try {
            const payload = {
                ...this.newSupplierForm,
                email: this.newSupplierForm.email || null,
                address: this.newSupplierForm.address || null,
                amount_balance: this.newSupplierForm.amount_balance === '' ? 0 : this.newSupplierForm.amount_balance,
            };
            const response = await fetch('{{ route('supplier.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (response.status === 422) {
                const firstError = Object.values(data.errors || {})[0]?.[0] || 'Please check the form.';
                Swal.fire({ icon: 'error', title: 'Could not add supplier', text: firstError });
                return;
            }
            if (!response.ok) {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message || 'Please try again.' });
                return;
            }
            const s = data.supplier || data;
            this.suppliers.push({ id: s.id, name: s.name, balance: parseFloat(s.amount_balance) || 0 });
            this.supplierId = s.id;
            this.supplierBalance = parseFloat(s.amount_balance) || 0;
            this.showAddSupplierModal = false;
            Swal.fire({ toast: true, position: 'top', icon: 'success', title: s.name + ' added', timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not add supplier. Please try again.' });
        } finally {
            this.savingSupplier = false;
        }
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
                payment_account_id: null,
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
                notes: '',
                items: validItems.map(it => ({
                    product_id: it.product_id,
                    product_name: it.product_name,
                    product_code: it.product_code,
                    unit: it.unit || 'Piece',
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
                        <template x-for="s in suppliers" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
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

        <div class="flex items-center justify-between">
            <p class="text-[11px] font-black text-accent uppercase tracking-tight" x-show="supplierId">
                BAL: <span x-text="'{{ $curr }} ' + supplierBalance.toFixed(2)"></span>
            </p>
            <button type="button" @click="openAddSupplierModal()"
                class="text-[11px] font-bold text-primary hover:text-primary-dark ml-auto">
                <i class="bi bi-plus-lg text-[10px]"></i> Add New
            </button>
        </div>
    </div>

    {{-- Items --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-box-seam text-primary-dark text-sm"></i>
            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Purchase Items</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left" style="min-width: 720px;">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-10 text-center border-r border-gray-100">#</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100" style="min-width: 220px;">Item</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-20 text-center border-r border-gray-100">Qty</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-28 text-center border-r border-gray-100">Unit</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-24 text-center border-r border-gray-100">Price/Unit</th>
                        <th class="px-3 py-3 text-[10px] font-black text-primary-dark uppercase tracking-wider w-24 text-right">Amount</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr class="border-b border-gray-100">
                            <td class="px-3 py-2.5 text-[11px] font-black text-gray-400 text-center border-r border-gray-100" x-text="i + 1"></td>
                            <td class="px-2 py-1.5 border-r border-gray-100">
                                <button type="button" @click="openProductPicker(i)"
                                    class="w-full flex items-center justify-between gap-1 pl-2 pr-2 py-1.5 bg-transparent border-none text-[13px] font-medium text-left outline-none"
                                    :class="item.product_name ? 'text-gray-700' : 'text-gray-400'">
                                    <span class="truncate" x-text="item.product_name || 'Search and select item'"></span>
                                    <i class="bi bi-chevron-down text-gray-400 text-[10px] shrink-0"></i>
                                </button>
                            </td>
                            <td class="px-2 py-1.5 border-r border-gray-100">
                                <input type="number" min="0" step="0.01" x-model="item.quantity"
                                    class="w-full px-1 py-1.5 bg-transparent border-none text-[13px] font-medium text-gray-700 outline-none text-center">
                            </td>
                            <td class="px-2 py-1.5 border-r border-gray-100">
                                <div class="relative">
                                    <select x-model="item.unit"
                                        class="w-full pl-1 pr-5 py-1.5 bg-transparent border-none text-[13px] font-bold text-primary-dark outline-none appearance-none text-center">
                                        <option value="">NONE</option>
                                        <option value="Piece">Piece</option>
                                        <option value="Box">Box</option>
                                        <option value="Kg">Kg</option>
                                        <option value="Litre">Litre</option>
                                        <option value="Set">Set</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                                </div>
                            </td>
                            <td class="px-2 py-1.5 border-r border-gray-100">
                                <input type="number" min="0" step="0.01" x-model="item.unit_price"
                                    class="w-full px-1 py-1.5 bg-transparent border-none text-[13px] font-medium text-gray-700 outline-none text-center">
                            </td>
                            <td class="px-3 py-2.5 text-[13px] font-black text-primary-dark text-right" x-text="lineAmount(item).toFixed(2)"></td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" @click="removeItem(i)" x-show="items.length > 1"
                                    class="w-6 h-6 rounded-lg bg-red-50 border border-red-100 inline-flex items-center justify-center text-red-500">
                                    <i class="bi bi-trash text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 bg-white">
                        <td class="px-3 py-3 border-r border-gray-100"></td>
                        <td class="px-3 py-3 border-r border-gray-100">
                            <button type="button" @click="addItem()"
                                class="flex items-center gap-1.5 text-[11px] font-black text-primary bg-accent/10 hover:bg-accent/20 border border-accent/30 rounded-[0.5rem] px-4 py-1.5 transition-all uppercase tracking-wider whitespace-nowrap">
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center border-r border-gray-100">
                            <span class="text-[13px] font-black text-primary-dark" x-text="items.reduce((s, it) => s + (parseFloat(it.quantity) || 0), 0)"></span>
                        </td>
                        <td class="border-r border-gray-100"></td>
                        <td class="px-2 py-3 text-[11px] font-black text-primary uppercase tracking-wider text-right border-r border-gray-100">Total</td>
                        <td class="px-3 py-3 text-right">
                            <span class="text-[13px] font-black text-primary-dark" x-text="subtotal.toFixed(2)"></span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
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

    {{-- Register Supplier — mobile bottom sheet --}}
    <div x-show="showAddSupplierModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddSupplierModal = false">
        <div x-show="showAddSupplierModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Register Supplier</h2>
                <button @click="showAddSupplierModal = false" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewSupplier()" class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Full Name <span class="text-primary">*</span></label>
                    <input type="text" x-model="newSupplierForm.name" required placeholder="Enter supplier name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Phone Number <span class="text-primary">*</span></label>
                    <input type="text" x-model="newSupplierForm.phone" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="e.g. 252612345678"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Supplier Type</label>
                    <div class="relative">
                        <select x-model="newSupplierForm.supplier_type"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="company">Company</option>
                            <option value="individual">Individual</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Email</label>
                    <input type="email" x-model="newSupplierForm.email" placeholder="Optional"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Address</label>
                    <input type="text" x-model="newSupplierForm.address" placeholder="Optional"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Opening Balance</label>
                    <input type="number" step="0.01" x-model="newSupplierForm.amount_balance" placeholder="0.00"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddSupplierModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="savingSupplier"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="savingSupplier ? 'opacity-60' : ''">
                        <i class="bi" :class="savingSupplier ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="savingSupplier ? 'Saving...' : 'Save Supplier'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Select Product — mobile bottom sheet --}}
    <div x-show="pickingItemIndex !== null" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="pickingItemIndex = null">
        <div x-show="pickingItemIndex !== null" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[85vh] flex flex-col">

            <div class="px-5 py-4 bg-primary flex items-center justify-between shrink-0">
                <h2 class="text-white font-bold text-[16px]">Select Product</h2>
                <button @click="pickingItemIndex = null" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <div class="px-4 py-3 shrink-0">
                <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl mb-3">
                    <i class="bi bi-search text-gray-400 text-sm"></i>
                    <input type="text" x-model="productSearch" placeholder="Search products..."
                        class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                        autocomplete="off">
                </div>
                <button type="button" @click="openAddProductModal()"
                    class="flex items-center gap-1.5 text-[11px] font-black text-primary bg-accent/10 hover:bg-accent/20 border border-accent/30 rounded-[0.5rem] px-4 py-1.5 transition-all uppercase tracking-wider">
                    <i class="bi bi-plus-lg"></i> Add Item
                </button>
            </div>

            <div class="flex items-center px-4 py-2 border-y border-gray-100 bg-background/50 shrink-0">
                <span class="flex-1 text-[9px] font-black text-primary-dark uppercase tracking-wider">Item</span>
                <span class="w-14 text-[9px] font-black text-primary-dark uppercase tracking-wider text-right">Sale</span>
                <span class="w-14 text-[9px] font-black text-primary-dark uppercase tracking-wider text-right">Cost</span>
                <span class="w-10 text-[9px] font-black text-primary-dark uppercase tracking-wider text-right">Stock</span>
            </div>

            <div class="overflow-y-auto flex-1">
                <template x-for="p in filteredProducts" :key="p.id">
                    <button type="button" @click="pickProduct(p)"
                        class="w-full flex items-center px-4 py-3 border-b border-gray-100 last:border-0 active:bg-gray-50 text-left">
                        <div class="flex-1 min-w-0 pr-2">
                            <p class="text-[13px] font-black text-primary-dark truncate" x-text="p.name"></p>
                            <p class="text-[11px] text-gray-400" x-text="p.code"></p>
                        </div>
                        <span class="w-14 text-[12px] font-bold text-primary-dark text-right" x-text="p.sellingPrice.toFixed(0)"></span>
                        <span class="w-14 text-[12px] font-bold text-primary-dark text-right" x-text="p.price.toFixed(0)"></span>
                        <span class="w-10 text-[12px] font-bold text-accent text-right" x-text="p.stock"></span>
                    </button>
                </template>
                <template x-if="!filteredProducts.length">
                    <div class="py-10 text-center">
                        <i class="bi bi-box-seam text-3xl text-gray-300"></i>
                        <p class="text-sm text-text-secondary mt-2 font-semibold">No products found.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Add Product — mobile bottom sheet --}}
    <div x-show="showAddProductModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[80] bg-slate-900/40" @click.self="showAddProductModal = false">
        <div x-show="showAddProductModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Add New Product</h2>
                <button @click="showAddProductModal = false" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form @submit.prevent="submitNewProduct()" class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Product Name <span class="text-primary">*</span></label>
                    <input type="text" x-model="newProductForm.product_name" required placeholder="Enter product name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="newProductForm.category_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select Category</option>
                            <template x-for="c in categories" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Selling Price</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                        <input type="number" min="0" step="0.01" x-model="newProductForm.selling_price" placeholder="0.00"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddProductModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="savingProduct"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="savingProduct ? 'opacity-60' : ''">
                        <i class="bi" :class="savingProduct ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="savingProduct ? 'Saving...' : 'Save Product'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
