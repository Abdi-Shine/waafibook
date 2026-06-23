@extends('admin.admin_master')
@section('page_title', 'Edit Purchase Bill')
@section('admin')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@php
    $currencySymbols = ['USD'=>'$','SAR'=>'SAR','SOS'=>'SOS','EUR'=>'€','GBP'=>'£','KES'=>'KSh'];
    $sym  = '$'; // Forcing Dollar
    $curr = '$'; // Forcing Dollar
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- ── Top Header Bar ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm icon-gradient-primary">
                <i class="bi bi-receipt text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Edit Purchase Bill</h1>
            </div>
        </div>
        {{-- Right: Back button --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.view') }}"
               class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case">
                <i class="bi bi-arrow-left group-hover:-translate-x-0.5 transition-transform duration-300"></i>
                <span>Back to Bills</span>
            </a>
        </div>
    </div>

    <form id="invoiceForm" autocomplete="off" method="POST" action="{{ route('purchase.bill.update', $bill->id) }}">
        @csrf

        {{-- ── Top Row: Customer Info ─────────────────────────────────────────── --}}
        <div class="mb-4">

            {{-- Supplier Information --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 mb-6">
                <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-6 pb-2 border-b border-gray-100">
                    Supplier & Logistics
                </p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-4">

                    {{-- Left: Supplier select --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                            Supplier <span class="text-primary">*</span>
                        </label>
                        <div class="relative">
                            <select id="supplierSelect" name="supplier_id"
                                class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                <option value=""></option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" data-phone="{{ $s->phone }}"
                                        data-balance="{{ $s->amount_balance ?? 0 }}" data-name="{{ $s->name }}">
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-1 px-1 flex items-center justify-between">
                            <div class="text-[11px] font-black text-accent uppercase tracking-tight" id="balDisplayWrapper">
                                BAL: <span id="partyBalanceDisplay">0.00</span>
                            </div>
                            <button type="button" onclick="openAddSupplierModal()"
                                class="text-[10px] font-bold text-primary hover:text-primary-dark hover:underline">
                                + Add New
                            </button>
                        </div>
                    </div>

                    {{-- Phone No. --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone No.</label>
                        <input type="text" id="billingName" name="billing_name" placeholder="Contact number"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>

                    {{-- Bill Number (read-only) --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Bill Number</label>
                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark">{{ $bill->bill_number }}</div>
                        <input type="hidden" name="purchase_no" value="{{ $bill->bill_number }}">
                    </div>

                    {{-- Bill Date --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Bill Date</label>
                        <input type="date" id="purchaseDateInput" name="purchase_date"
                               value="{{ \Carbon\Carbon::parse($bill->bill_date)->format('Y-m-d') }}"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>

                    {{-- Hidden branch & account --}}
                    <input type="hidden" id="locationType" value="branch">
                    <input type="hidden" id="locationItem" value="branch_{{ $bill->branch_id }}">
                    <input type="hidden" name="payment_account_id" id="paymentAccountSelect" value="{{ $bill->payment_account_id }}">

                </div>
                <input type="hidden" name="purchase_type" id="purchase_type" value="Purchase">

                {{-- Hidden bill metadata --}}
                <input type="hidden" name="supplier_invoice_no" value="{{ $bill->supplier_invoice_no }}">
                <input type="hidden" id="targetLocationItem" value="branch_{{ $bill->branch_id }}">
                <input type="hidden" name="branch_id" id="branch_id">
            </div>
        </div>

        {{-- ── Items Table ─────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-box-seam text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Purchase Items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-12 text-center border-r border-gray-100">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100 hidden col-cat">CATEGORY</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100">ITEM</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-28 text-center border-r border-gray-100 hidden col-code">CODE</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100 hidden col-desc">DESCRIPTION</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-24 text-center border-r border-gray-100">QTY</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">UNIT</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">PRICE/UNIT</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100 hidden col-disc">DISCOUNT</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-right">AMOUNT</th>
                            <th class="w-10 text-center relative">
                                <button type="button" onclick="toggleColPicker()" class="hover:opacity-80 transition-opacity" title="Toggle Columns">
                                    <i class="bi bi-sliders text-primary text-base"></i>
                                </button>
                                {{-- Popover --}}
                                <div id="colPicker" class="absolute right-0 top-full mt-1 bg-white border border-gray-200 shadow-xl rounded-lg py-3 px-4 z-50 hidden text-left col-picker-popover">
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('cat', this.checked)" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Item Category</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('code', this.checked)" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Item Code</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('desc', this.checked)" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Description</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('disc', this.checked)" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Discount</span>
                                        </label>
                                    </div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody"></tbody>
                    {{-- Total row --}}
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-white">
                            <td class="px-4 py-3 border-r border-gray-100"></td> {{-- 1: # --}}
                            <td class="px-4 py-3 border-r border-gray-100 hidden col-cat"></td> {{-- 2: CAT --}}
                            <td class="px-4 py-3 border-r border-gray-100"> {{-- 3: ITEM --}}
                                <button type="button" onclick="addItemRow()"
                                    class="flex items-center gap-1.5 text-[11px] font-black text-primary bg-accent/10 hover:bg-accent/20 border border-accent/30 rounded-[0.5rem] px-4 py-1.5 transition-all uppercase tracking-wider">
                                    <i class="bi bi-plus-lg"></i> ADD ROW
                                </button>
                            </td>
                            <td class="px-4 py-3 border-r border-gray-100 hidden col-code"></td> {{-- 4: CODE --}}
                            <td class="px-4 py-3 border-r border-gray-100 hidden col-desc"></td> {{-- 5: DESC --}}

                            <td class="px-4 py-3 text-center border-r border-gray-100"> {{-- 6: QTY --}}
                                <span class="text-[14px] font-black text-[#004161]" id="footerQty">0</span>
                            </td>

                            <td class="px-4 py-3 border-r border-gray-100"></td> {{-- 7: UNIT --}}
                            
                            <td class="px-4 py-3 text-[11px] font-black text-[#5a6a85] uppercase tracking-wider text-right border-r border-gray-100"> {{-- 8: PURCHASE --}}
                                TOTAL
                            </td>

                            <td class="px-4 py-3 border-r border-gray-100 hidden col-disc text-center"></td> {{-- 10: DISC --}}

                            <td class="px-4 py-3 text-right border-r border-gray-100"> {{-- 11: AMOUNT --}}
                                <span class="text-[14px] font-black text-[#004161]" id="footerAmount">0.00</span>
                            </td>
                            <td></td> {{-- 12: Action --}}
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>



        {{-- ── Bottom: Notes + Summary ───────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

            {{-- Notes --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 lg:col-span-2">
                <label class="block text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">
                    Notes / Terms &amp; Conditions
                </label>
                <textarea name="notes" rows="5" placeholder="Enter any additional notes, terms, or conditions for this invoice..."
                          class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
            </div>

            {{-- Summary --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 lg:col-span-1">
                <p class="text-[10px] font-black text-primary-dark uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">
                    Invoice Summary
                </p>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    {{-- Discount --}}
                    <div>
                        <label class="block text-[10px] font-black text-primary-dark uppercase tracking-wider mb-1">Discount</label>
                        <div class="relative">
                            <input type="number" id="discountInput" name="discount" value="0" min="0" step="1" placeholder="0"
                                   class="w-full pl-3 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-right">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-gray-400">($)</span>
                        </div>
                    </div>

                    {{-- Paid Amount --}}
                    <div>
                        <label class="block text-[10px] font-black text-primary-dark uppercase tracking-wider mb-1">Amount Paid</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">{{ $curr }}</span>
                            <input type="number" name="paid_amount" id="paidAmountInput" value="0" min="0" step="0.01"
                                   class="w-full pl-7 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-right">
                        </div>
                    </div>
                </div>
                <input type="hidden" id="discountPercent" value="0">

                {{-- Hidden due date --}}
                <input type="hidden" name="expected_delivery" id="dueDateInput">

                {{-- Hidden fields --}}
                <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                <input type="hidden" name="tax" id="taxVal" value="0">
                <input type="hidden" name="total_amount" id="grandTotalVal" value="0">

                {{-- Grand Total Box --}}
                <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between mt-3">
                    <span class="text-[11px] font-black text-white uppercase tracking-wider">Total Amount</span>
                    <span class="text-[15px] font-black text-accent" id="grandTotalDisplay">{{ $curr }} 0.00</span>
                </div>

                {{-- Supplier Balance --}}
                <div class="flex justify-between items-center px-1 py-2 mt-2 border-t border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Current Supplier Balance</span>
                    <span class="font-black text-primary-dark text-[12px]" id="totalCustomerBalDisplay">{{ $curr }} 0.00</span>
                </div>
                    {{-- Hidden displays --}}
                    <div class="hidden">
                        <span id="subtotalDisplay">0</span>
                        <span id="balanceDueDisplay">0</span>
                    </div>
                </div>
            </div>

        {{-- ── Action Buttons ───────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm px-6 py-5 flex flex-wrap items-center justify-between">
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('purchase.view') }}" class="btn-premium-accent">
                    Cancel
                </a>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button type="button" onclick="submitForm('update')" class="btn-premium-primary">
                    Update Bill & Stock
                </button>
            </div>
        </div>

    </form>
</div>

{{-- ── Add Supplier Modal ─────────────────────────────────────────────────────── --}}
<div id="addSupplierModal"
     class="fixed inset-0 z-50 flex items-center justify-center hidden modal-overlay">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[100vh] overflow-hidden modal-slide-up">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 modal-header-primary">
            <div class="flex items-center gap-2">
                <i class="bi bi-person-plus text-white text-base"></i>
                <h3 class="text-[15px] font-black text-white">Add New Supplier</h3>
            </div>
            <button type="button" onclick="closeAddSupplierModal()"
                    class="w-7 h-7 rounded-full bg-white/20 text-white hover:bg-white/30 flex items-center justify-center transition-all">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="text-[11px] font-bold text-gray-600 block mb-1.5">Supplier Name <span class="text-primary">*</span></label>
                <input type="text" id="newSupplierName" placeholder="Enter supplier name"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="text-[11px] font-bold text-gray-600 block mb-1.5">Phone No. <span class="text-primary">*</span></label>
                <input type="text" id="newSupplierPhone" placeholder="Enter phone number"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="text-[11px] font-bold text-gray-600 block mb-1.5">Supplier Type <span class="text-primary">*</span></label>
                <select id="newSupplierType" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 focus:outline-none focus:border-primary">
                    <option value="company">Company</option>
                    <option value="individual">Individual</option>
                </select>
            </div>
            <div>
                <label class="text-[11px] font-bold text-gray-600 block mb-1.5">Opening Balance</label>
                <input type="number" id="newSupplierBalance" placeholder="0.00" value="0" min="0" step="0.01"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-[13px] font-medium text-gray-700 focus:outline-none focus:border-primary">
            </div>
        </div>
        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeAddSupplierModal()"
                    class="px-4 py-2 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg text-[12px] hover:bg-gray-100 transition-all">
                Cancel
            </button>
            <button type="button" onclick="saveNewSupplier()" id="saveSupplierBtn"
                    class="btn-premium-primary">
                <i class="bi bi-check-lg mr-1"></i> Save Supplier
            </button>
        </div>
    </div>
</div>

{{-- ── Add Product Modal ─────────────────────────────────────────────────────── --}}
<div id="addProductModal"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden modal-overlay">
    <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[100vh] overflow-hidden shadow-2xl flex flex-col relative modal-slide-up">
        {{-- Header --}}
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-xl font-bold tracking-tight uppercase">Add New Product</h2>
                        <p class="text-xs text-white/60 font-medium mt-0.5">Quickly add an item</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddProductModal()"
                    class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>
        {{-- Body --}}
        <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Product Name <span class="text-primary">*</span></label>
                    <input type="text" id="newProductName" placeholder="Enter product name"
                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                    <select id="newProductCategory"
                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Selling Price</label>
                    <input type="number" id="newProductSellingPrice" placeholder="0.00" value="0" min="0" step="0.01"
                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
        </div>
        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
            <button type="button" onclick="closeAddProductModal()" class="btn-premium-accent">
                Cancel
            </button>
            <button type="button" onclick="saveNewProduct()" id="saveProductBtn" class="btn-premium-primary">
                <i class="bi bi-check2-circle"></i>
                <span>Save Product</span>
            </button>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────────── --}}
{{-- Product/Category data for JS                        --}}
{{-- ─────────────────────────────────────────────────── --}}
<script id="productData" type="application/json">
{!! json_encode($products->map(fn($p) => [
    'id'             => $p->id,
    'name'           => $p->product_name,
    'code'           => $p->product_code ?? '',
    'selling_price'  => (float) $p->selling_price,
    'purchase_price' => (float) $p->purchase_price,
    'unit'           => $p->unit ?? 'Piece',
    'stock'          => (int) ($p->stocks_sum_quantity ?? 0),
    'category_id'    => $p->category_id,
])) !!}
</script>
<script id="categoryData" type="application/json">
{!! json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) !!}
</script>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
/* ── Constants ── */
const SYM       = @json($sym);
const CURR      = @json($curr);
const CSRF      = @json(csrf_token());
const R_STORE   = @json(route('purchase.bill.store'));
const R_UPDATE   = @json(route('purchase.bill.update', $bill->id));
const R_LIST    = @json(route('purchase.view'));
const R_QUICK_SUPP = @json(route('supplier.store'));
const R_QUICK_PROD = @json(route('product.quick.store'));
const R_DRAFT      = @json(route('purchase.bill.draft.update', $bill->id));
const PRODUCTS  = JSON.parse(document.getElementById('productData').textContent);
const CATEGORIES= JSON.parse(document.getElementById('categoryData').textContent);

const locationData = {
    branch: [@foreach($branches as $branch) { value: 'branch_{{ $branch->id }}', label: '{{ addslashes($branch->name) }}', branchId: '{{ $branch->id }}' }, @endforeach]
};

function handleLocationTypeChange() {
    handleLocationItemChange();
}

function handleLocationItemChange() {
    // locationItem and paymentAccountSelect are both hidden <input> elements
    // on this page (not <select>s), so the branch id is derived from
    // locationItem's "branch_<id>" value directly, and there is no account
    // dropdown here to filter/auto-select.
    const val = document.getElementById('locationItem').value;
    const branchId = val.startsWith('branch_') ? val.slice('branch_'.length) : '';

    document.getElementById('branch_id').value = branchId;
}

function handleTargetLocationTypeChange() {
    const type = document.getElementById('targetLocationType').value;
    const select = document.getElementById('targetLocationItem');
    select.innerHTML = `<option value="">Select Branch</option>`;
    locationData[type].forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.value;
        opt.textContent = item.label;
        opt.dataset.branch = item.branchId;
        select.appendChild(opt);
    });
}

function handleTargetLocationItemChange() {
    // Logic for usage of target location can be added here if needed
}

$(document).ready(function() {
    // --- PRE-FILL DATA ---
    // Pre-fill Supplier
    $('#supplierSelect').val('{{ $bill->supplier_id }}').trigger('change');

    // The phone/balance display only updates via the supplierSelect 'change'
    // handler attached in a later $(document).ready block, which isn't
    // registered yet when the trigger('change') above fires — so set them
    // directly here from the bill's own supplier instead of relying on it.
    document.getElementById('billingName').value = {!! json_encode($bill->supplier->phone ?? '') !!};
    const partyBalanceDisplay = document.getElementById('partyBalanceDisplay');
    if (partyBalanceDisplay) {
        const supplierBal = parseFloat({{ $bill->supplier->amount_balance ?? 0 }}) || 0;
        partyBalanceDisplay.textContent = supplierBal.toLocaleString('en', {minimumFractionDigits:0, maximumFractionDigits:2});

        // The supplier's stored balance already includes this bill's current
        // (pre-edit) net impact, which the backend reverts before applying
        // the edited total (see PurchaseController::updateBill's $balanceDiff
        // logic) — so the live preview must subtract that same old impact,
        // otherwise editing the bill double-counts it.
        const oldNetImpact = parseFloat({{ ($bill->total_amount ?? 0) - ($bill->paid_amount ?? 0) }}) || 0;
        window._customerPrevBalance = supplierBal - oldNetImpact;
    }

    // Pre-fill Location
    const locType = 'branch';
    const locItem = '{{ "branch_".$bill->branch_id }}';
    document.getElementById('locationType').value = locType;
    handleLocationTypeChange();
    document.getElementById('locationItem').value = locItem;
    handleLocationItemChange();

    // Pre-fill Account
    $('#paymentAccountSelect').val('{{ $bill->payment_account_id }}');
    
    // Pre-fill items — pass each item's data straight into addItemRow() so
    // the correct option is already selected in the markup before Select2
    // initializes (see addItemRow's comment for why that matters).
    @foreach($bill->items as $item)
        addItemRow({
            pid: {{ $item->product_id ?? 'null' }},
            pname: {!! json_encode($item->product_name) !!},
            qty: {{ $item->quantity ?? 1 }},
            price: {{ $item->unit_price ?? 0 }},
            code: {!! json_encode($item->product_code ?? '') !!},
            unit: {!! json_encode($item->unit ?? '') !!},
            discount: {{ $item->discount ?? 0 }}
        });
        calcRow(rowCounter);
    @endforeach
    
    // Pre-fill Summary extras
    document.getElementById('discountInput').value = '{{ $bill->discount ?? 0 }}';
    document.getElementById('paidAmountInput').value = '{{ $bill->paid_amount ?? 0 }}';
    document.getElementById('dueDateInput').value = '{{ $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format("Y-m-d") : "" }}';
    document.querySelector('textarea[name="notes"]').value = {!! json_encode($bill->notes) !!};
    
    recalcAll();
});

let rowCounter = 0;
let discountFocus = 'amt'; // 'pct' or 'amt'

/* ─────────────────────────── SELECT2 INIT ───────── */
$(document).ready(function() {
    $('#supplierSelect').select2({
        placeholder: 'Search supplier…',
        allowClear: true,
        width: '100%',
    }).on('select2:select change', function(e) {
        const el   = $(this).find(':selected');
        const ph   = el.data('phone')   || '';
        const name = el.data('name')    || '';
        const bal  = parseFloat(el.data('balance')) || 0;

        // Populate Phone No.
        document.getElementById('billingName').value = ph;

        // Party Balance display
        const balDiv = document.getElementById('partyBalanceDisplay');
        if (balDiv) {
            balDiv.textContent = bal.toLocaleString('en', {minimumFractionDigits:0, maximumFractionDigits:2});
        }

        window._customerPrevBalance = bal;
        recalcAll();

    }).on('select2:clear', () => {
        document.getElementById('billingName').value = '';
        const balDiv = document.getElementById('partyBalanceDisplay');
        if (balDiv) balDiv.textContent = '0.00';
        window._customerPrevBalance = 0;
        recalcAll();
    });

    // Add a starter row only if the pre-fill block (a separate, earlier-running
    // $(document).ready callback) hasn't already populated this bill's real
    // items — otherwise this unconditionally added a second, blank row.
    if (document.getElementById('itemsTbody').children.length === 0) {
        addItemRow();
    }

    // Summary listeners
    document.getElementById('discountInput').addEventListener('input', function() {
        discountFocus = 'amt';
        syncDiscountBoxes();
        recalcAll();
    });

    document.getElementById('discountPercent').addEventListener('input', function() {
        discountFocus = 'pct';
        syncDiscountBoxes();
        recalcAll();
    });

    function syncDiscountBoxes() {
        const subtotal = calculateCurrentSubtotal();
        const pctInput = document.getElementById('discountPercent');
        const amtInput = document.getElementById('discountInput');

        if (subtotal <= 0) return;

        if (discountFocus === 'pct') {
            const pct = parseFloat(pctInput.value) || 0;
            amtInput.value = Math.round(subtotal * pct / 100);
        } else {
            const amt = parseFloat(amtInput.value) || 0;
            const pct = (amt / subtotal) * 100;
            pctInput.value = Math.round(pct);
        }
    }


    document.getElementById('paidAmountInput').addEventListener('input', recalcAll);
});

function calculateCurrentSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const amt = parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
        subtotal += amt;
    });
    return subtotal;
}

/* ─────────────────────────── ADD ROW ────────────── */
function addItemRow(prefill = null) {
    rowCounter++;
    const n = rowCounter;
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.row = n;

    tr.innerHTML = `
        <td class="px-4 py-3 text-[11px] font-black text-gray-400 text-center border-r border-gray-100 row-num">${n}</td>

        {{-- Optional: Category --}}
        <td class="px-2 py-1 border-r border-gray-100 hidden col-cat">
            <input type="text" class="tbl-clean-input cat-input" placeholder="Category">
        </td>

        {{-- Item Select --}}
        <td class="px-2 py-1 border-r border-gray-100">
            <select class="item-select" data-row="${n}">
                ${buildItemOptions(prefill)}
            </select>
        </td>

        {{-- Optional: Code --}}
        <td class="px-2 py-1 border-r border-gray-100 hidden col-code text-center">
            <input type="text" class="tbl-clean-input code-input" placeholder="Code" value="${prefill && prefill.code ? prefill.code : ''}">
        </td>

        {{-- Optional: Description --}}
        <td class="px-2 py-1 border-r border-gray-100 hidden col-desc">
            <input type="text" class="tbl-clean-input desc-input" placeholder="Add description">
        </td>

        {{-- Qty --}}
        <td class="px-2 py-1 border-r border-gray-100 text-center">
            <input type="number" class="tbl-clean-input qty-input" value="${prefill && prefill.qty ? prefill.qty : 1}" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, ''); calcRow(${n})">
        </td>

        {{-- Unit --}}
        <td class="px-4 py-1 border-r border-gray-100 text-center">
            <select class="unit-select-clean unit-input">
                <option value="" ${!prefill || !prefill.unit ? 'selected' : ''}>NONE</option>
                <option value="Piece" ${prefill && prefill.unit === 'Piece' ? 'selected' : ''}>Piece</option>
                <option value="Box" ${prefill && prefill.unit === 'Box' ? 'selected' : ''}>Box</option>
                <option value="Kg" ${prefill && prefill.unit === 'Kg' ? 'selected' : ''}>Kg</option>
                <option value="Litre" ${prefill && prefill.unit === 'Litre' ? 'selected' : ''}>Litre</option>
                <option value="Set" ${prefill && prefill.unit === 'Set' ? 'selected' : ''}>Set</option>
            </select>
        </td>

        {{-- Purchase Price --}}
        <td class="px-2 py-1 border-r border-gray-100 text-center">
            <input type="number" class="tbl-clean-input price-input" value="${prefill && prefill.price !== undefined ? prefill.price : 0}" min="0" step="0.01" oninput="calcRow(${n})">
        </td>

        {{-- Optional: Discount --}}
        <td class="px-2 py-1 border-r border-gray-100 hidden col-disc text-center">
            <div class="flex items-center gap-1 justify-center">
                <input type="number" class="tbl-clean-input row-disc-input row-disc-input-w" value="${prefill && prefill.discount !== undefined ? prefill.discount : 0}" min="0" step="0.01" oninput="calcRow(${n})">
                <button type="button" class="text-[10px] font-black text-primary row-disc-toggle" onclick="toggleRowDisc(this, ${n})">Amt</button>
            </div>
        </td>

        {{-- Amount --}}
        <td class="px-4 py-1 border-r border-gray-100 text-right">
            <span class="text-[13px] font-black text-[#004161] row-amount" data-val="0">0.00</span>
        </td>

        <td class="px-2 py-1 text-center">
            <button type="button" 
                    class="w-8 h-8 rounded-lg flex items-center justify-center border border-primary/20 text-primary hover:bg-primary/10 hover:text-primary transition-all mx-auto" 
                    onclick="removeBillRow(this)">
                <i class="bi bi-trash text-[13px]"></i>
            </button>
        </td>
    `;


    document.getElementById('itemsTbody').appendChild(tr);

    // Init Select2 for item select
    $(tr).find('.item-select').select2({
        placeholder: 'Search and Select Item',
        width: '100%',
        dropdownAutoWidth: true,
        templateResult: formatProductResult,
        language: {
            noResults: function() {
                return `<div class="p-2 text-center text-gray-400">No items found matching search</div>`;
            }
        },
        escapeMarkup: function(markup) { return markup; }
    }).on('select2:select', function() {
        onItemChange(this, n);
    }).on('select2:open', function() {
        window._lastOpenedSelect2 = this;

        // Inject sticky header & Add Item button to the dropdown container
        setTimeout(() => {
            const container = $('.select2-container--open .select2-dropdown');
            if (container.length && !container.find('.s2-header-row').length) {
                container.find('.select2-results').prepend(`
                    <div class="s2-header-row">
                        <div class="s2-add-btn" onmousedown="openAddProductModal()">
                            <i class="bi bi-plus-circle-fill"></i> Add Item
                        </div>
                        <div class="s2-header-cols">
                            <span class="product-res-col-sale">SALE PRICE</span>
                            <span class="product-res-col-purchase">PURCHASE PRICE</span>
                            <span class="product-res-col-stock">STOCK</span>
                        </div>
                    </div>
                `);
            }
        }, 10);
    });

    renumberRows();
    recalcAll();
}

/* ─────────────────────────── SELECT2 OPTIONS/FORMATTING ── */
function buildItemOptions(prefill) {
    let html = `<option value="">Select Item</option>`;
    let matched = false;
    PRODUCTS.forEach(p => {
        const isSelected = !!(prefill && prefill.pid && p.id == prefill.pid);
        if (isSelected) matched = true;
        html += `<option value="${p.id}" ${isSelected ? 'selected' : ''}
                        data-selling-price="${p.selling_price}"
                        data-purchase-price="${p.purchase_price}"
                        data-code="${p.code}"
                        data-unit="${p.unit}"
                        data-stock="${p.stock}"
                        data-catid="${p.category_id}">${p.name}</option>`;
    });
    // Older bills may have a typed product name that never matched a catalog
    // product (saved before product_id was tracked) — inject it as an extra
    // selected option so Select2 shows it correctly from the very first
    // render, instead of a post-init .val() call (which doesn't stick).
    if (prefill && prefill.pname && !matched) {
        const safeName = prefill.pname.replace(/"/g, '&quot;');
        html += `<option value="legacy_${rowCounter}" selected
                        data-purchase-price="${prefill.price ?? 0}"
                        data-code="${prefill.code ?? ''}"
                        data-unit="${prefill.unit ?? ''}">${safeName}</option>`;
    }
    return html;
}

function formatProductResult(p) {
    if (!p.id) return p.text;
    const prod = PRODUCTS.find(x => x.id == p.id);
    if (!prod) return p.text;

    return $(`
        <div class="product-res">
            <div class="product-res-info">
                <div class="product-res-name">${prod.name}</div>
                <div class="product-res-code">${prod.code || ''}</div>
            </div>
            <div class="product-res-meta">
                <div class="product-res-col product-res-col-sale">
                    <div class="product-res-label">SALE PRICE</div>
                    <div class="product-res-val">${prod.selling_price.toLocaleString()}</div>
                </div>
                <div class="product-res-col product-res-col-purchase">
                    <div class="product-res-label">PURCHASE PRICE</div>
                    <div class="product-res-val">${prod.purchase_price.toLocaleString()}</div>
                </div>
                <div class="product-res-col product-res-col-stock">
                    <div class="product-res-label">STOCK</div>
                    <div class="product-res-val product-res-stock ${prod.stock <= 0 ? 'low' : ''}">${prod.stock}</div>
                </div>
            </div>
        </div>
    `);
}

/* ─────────────────────────── ITEM CHANGE ────────── */
function onItemChange(sel, rn) {
    const opt = $(sel).find(':selected');
    if (!opt.val()) return;
    const row = document.querySelector(`tr[data-row="${rn}"]`);

    const pPrice = opt.attr('data-purchase-price') || 0;
    const unit   = opt.attr('data-unit') || '';
    const code   = opt.attr('data-code') || '';

    row.querySelector('.price-input').value = pPrice;
    row.querySelector('.code-input').value  = code;

    const unitSel = row.querySelector('.unit-input');
    if (unit) {
        for (let o of unitSel.options) {
            if (o.value.toLowerCase() === unit.toLowerCase()) { o.selected = true; break; }
        }
    } else {
        unitSel.selectedIndex = 0; // Default to NONE
    }

    calcRow(rn);
}

function removeBillRow(btn) {
    const rows = document.querySelectorAll('#itemsTbody .item-row');
    if (rows.length <= 1) {
        toastWarn('At least one row is required.'); 
        return; 
    }
    const row = btn.closest('tr');
    if (!row) return;

    // Safely destroy Select2 if it exists
    try {
        const sel = $(row).find('.item-select');
        if (sel.data('select2')) {
            sel.select2('destroy');
        }
    } catch (e) { console.warn('Select2 destroy failed', e); }

    row.remove();
    renumberRows();
    recalcAll();
}

function renumberRows() {
    document.querySelectorAll('#itemsTbody .item-row').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

/* ─────────────────────────── ROW CALC ───────────── */
function calcRow(rn) {
    const row   = document.querySelector(`tr[data-row="${rn}"]`);
    if (!row) return;
    const qty   = parseFloat(row.querySelector('.qty-input').value)       || 0;
    const price = parseFloat(row.querySelector('.price-input').value)     || 0;
    
    // Optional Discount
    const discEl = row.querySelector('.row-disc-input');
    const disc   = discEl ? (parseFloat(discEl.value) || 0) : 0;
    const isPct  = row.querySelector('.row-disc-toggle')?.textContent === 'Pct';

    const gross    = qty * price;
    const discAmt  = isPct ? (gross * disc / 100) : disc;
    const rowTotal = Math.max(0, gross - discAmt);

    row.querySelector('.row-amount').textContent = rowTotal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
    row.querySelector('.row-amount').dataset.val  = rowTotal.toFixed(2);
    recalcAll();
}

/* ─────────────────────────── GLOBAL CALC ────────── */
function recalcAll() {
    let subtotal = 0;
    let totalQty = 0;

    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const amt = parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
        subtotal += amt;
        totalQty += parseFloat(row.querySelector('.qty-input').value) || 0;
    });

    // Global discount - recalculate based on last focus
    const pctInput = document.getElementById('discountPercent');
    const amtInput = document.getElementById('discountInput');
    
    if (discountFocus === 'pct' && subtotal > 0) {
        const p = parseFloat(pctInput.value) || 0;
        const a = Math.round(subtotal * p / 100);
        amtInput.value = a;
    } else if (discountFocus === 'amt' && subtotal > 0) {
        const a = parseFloat(amtInput.value) || 0;
        const p = Math.round((a / subtotal) * 100);
        pctInput.value = p;
    }

    const discAmt  = parseFloat(amtInput.value) || 0;
    const afterDisc= Math.max(0, subtotal - discAmt);

    // Tax
    const taxRate  = 0;
    const taxAmt   = afterDisc * taxRate / 100;

    let grandTotal = afterDisc + taxAmt;
    let roundOff   = 0;

    const paid     = parseFloat(document.getElementById('paidAmountInput').value) || 0;
    const netImpact = grandTotal - paid;
    const balanceDue = Math.max(0, netImpact);

    document.getElementById('inputSubtotal').value           = subtotal.toFixed(2);
    document.getElementById('subtotalDisplay').textContent   = subtotal.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = CURR + ' ' + grandTotal.toFixed(2);
    document.getElementById('grandTotalVal').value           = grandTotal.toFixed(2);
    document.getElementById('taxVal').value                  = taxAmt.toFixed(2);
    document.getElementById('balanceDueDisplay').textContent = balanceDue.toFixed(2);

    // Total supplier balance = previous balance + (grand total - paid amount)
    const prevBal      = parseFloat(window._customerPrevBalance) || 0;
    const totalCustBal = prevBal + netImpact;
    document.getElementById('totalCustomerBalDisplay').textContent = CURR + ' ' + totalCustBal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});

    // Footer row
    document.getElementById('footerQty').textContent    = Math.round(totalQty);
    document.getElementById('footerAmount').textContent = subtotal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
}



function toggleRowDisc(btn, rn) {
    const isActive = btn.classList.toggle('active');
    btn.textContent = isActive ? '%' : 'Amt';
    calcRow(rn);
}

/* ─────────────────────────── SUBMIT ─────────────── */
function collectFormData(isDraft = false) {
    const supplierId = document.getElementById('supplierSelect').value;
    if (!isDraft && !supplierId) { toastError('Please select a supplier.'); return null; }

    const brId = document.getElementById('branch_id').value;
    if (!isDraft && !brId) { toastError('Please select a branch.'); return null; }

    const items = [];
    let valid = true;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const itemSel = row.querySelector('.item-select');
        const qty     = parseFloat(row.querySelector('.qty-input').value) || 0;
        if (!itemSel.value) return; // skip blank rows

        const opt = $(itemSel).find(':selected');
        if (!isDraft && qty <= 0) { toastError('Quantity must be greater than 0.'); valid = false; return; }

        const isPct   = row.querySelector('.row-disc-toggle').classList.contains('active');
        const disc    = parseFloat(row.querySelector('.row-disc-input').value) || 0;
        const price   = parseFloat(row.querySelector('.price-input').value) || 0;
        const discAmt = isPct ? (qty * price * disc / 100) : disc;

        // An existing catalog product has a numeric id; a legacy/typed name
        // (see buildItemOptions) has a non-numeric placeholder value, so the
        // backend resolves/creates the product by name instead.
        const pid   = itemSel.value;
        const isNew = isNaN(pid);

        items.push({
            product_id:    isNew ? null : pid,
            product_name:  opt.text().split('(')[0].trim(),
            product_code:  row.querySelector('.code-input').value || opt.attr('data-code') || null,
            unit:          row.querySelector('.unit-input').value || 'Piece',
            quantity:      qty,
            unit_price:    price,
            discount:      discAmt,
            total_amount:  parseFloat(row.querySelector('.row-amount').dataset.val) || 0,
        });
    });

    if (!valid) return null;
    if (!isDraft && items.length === 0) { toastError('Please add at least one product.'); return null; }

    const purDateEl = document.querySelector('input[name="purchase_date"]');
    const dueDateEl = document.querySelector('input[name="expected_delivery"]');
    const notesEl   = document.querySelector('textarea[name="notes"]');
    const purNoEl   = document.querySelector('input[name="purchase_no"]');
    const invRefEl  = document.querySelector('input[name="supplier_invoice_no"]');
    const purType   = document.getElementById('purchaseTypeInput')?.value || 'cash';

    return {
        _token:              CSRF,
        supplier_id:         supplierId,
        branch_id:           brId || null,
        payment_account_id:  document.getElementById('paymentAccountSelect')?.value || null,
        purchase_type:       document.getElementById('purchase_type').value,
        purchase_no:         purNoEl ? purNoEl.value : '',
        supplier_invoice_no: invRefEl ? invRefEl.value : '',
        purchase_date:       purDateEl ? purDateEl.value : new Date().toISOString().split('T')[0],
        expected_delivery:   dueDateEl ? dueDateEl.value : '',
        payment_terms:       purType === 'cash' ? 'Cash' : 'Credit',
        subtotal:            parseFloat(document.getElementById('inputSubtotal')?.value) || 0,
        discount:            parseFloat(document.getElementById('discountInput')?.value) || 0,
        vat:                 parseFloat(document.getElementById('taxVal')?.value) || 0,
        total_amount:        parseFloat(document.getElementById('grandTotalVal')?.value) || 0,
        paid_amount:         parseFloat(document.getElementById('paidAmountInput')?.value) || 0,
        notes:               notesEl ? notesEl.value : '',
        items,
    };
}

function submitForm(mode) {
    const data = collectFormData();
    if (!data) return;

    const btns = document.querySelectorAll('.btn-action');
    btns.forEach(b => b.disabled = true);

    $.ajax({
        url: mode === 'update' ? R_UPDATE : R_STORE,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(res) {
            Swal.fire({ icon: 'success', title: 'Bill Recorded!', text: 'Stock updated successfully.', confirmButtonColor: '#004161' })
                .then(() => window.location.href = R_LIST);
        },
        error: function(xhr) {
            btns.forEach(b => b.disabled = false);
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : (xhr.responseJSON?.message || 'Failed to record bill.'));
        }
    });
}

function saveDraft() {
    const data = collectFormData(true);
    if (!data) return;

    const btns = document.querySelectorAll('.btn-action');
    btns.forEach(b => b.disabled = true);

    $.ajax({
        url: R_DRAFT,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(res) {
            Swal.fire({ icon: 'success', title: 'Draft Saved!', text: 'Your changes have been saved as a draft.', confirmButtonColor: '#004161' })
                .then(() => window.location.href = R_LIST);
        },
        error: function(xhr) {
            btns.forEach(b => b.disabled = false);
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : (xhr.responseJSON?.message || 'Failed to save draft.'));
        }
    });
}




/* ─────────────────────────── ADD SUPPLIER MODAL ───── */
function openAddSupplierModal() {
    document.getElementById('addSupplierModal').classList.remove('hidden');
    document.getElementById('newSupplierName').focus();
}

function closeAddSupplierModal() {
    document.getElementById('addSupplierModal').classList.add('hidden');
    // Clear inputs
    document.getElementById('newSupplierName').value = '';
    document.getElementById('newSupplierPhone').value = '';
    document.getElementById('newSupplierBalance').value = '0';
}

function saveNewSupplier() {
    const name    = document.getElementById('newSupplierName').value.trim();
    const phone   = document.getElementById('newSupplierPhone').value.trim();
    const type    = document.getElementById('newSupplierType').value;
    const balance = parseFloat(document.getElementById('newSupplierBalance').value) || 0;

    if (!name) { toastError('Supplier name is required.'); return; }
    if (!phone) { toastError('Phone number is required.'); return; }

    const btn = document.getElementById('saveSupplierBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i> Saving…';

    $.ajax({
        url: R_QUICK_SUPP,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        data: { name, phone, supplier_type: type, amount_balance: balance },
        success: function(res) {
            toastSuccess('New supplier added successfully.');
            
            // Add to Select2 and select it
            const newOption = new Option(name, res.id, true, true);
            $(newOption).data('phone', phone);
            $(newOption).data('balance', balance);
            $(newOption).data('name', name);
            $('#supplierSelect').append(newOption).trigger('change');
            
            closeAddSupplierModal();
        },
        error: function(xhr) {
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save supplier.');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg mr-1"></i> Save Supplier';
        }
    });
}

/* ─────────────────────────── ADD PRODUCT MODAL ────── */
function openAddProductModal() {
    // Close select2 dropdown if open
    $('.item-select').select2('close');
    document.getElementById('addProductModal').classList.remove('hidden');
    document.getElementById('newProductName').focus();
}

function closeAddProductModal() {
    document.getElementById('addProductModal').classList.add('hidden');
    document.getElementById('newProductName').value = '';
    document.getElementById('newProductCategory').selectedIndex = 0;
    document.getElementById('newProductSellingPrice').value = '0';
}

function saveNewProduct() {
    const name    = document.getElementById('newProductName').value.trim();
    const catId   = document.getElementById('newProductCategory').value;
    const sPrice  = parseFloat(document.getElementById('newProductSellingPrice').value) || 0;

    if (!name) { toastError('Product name is required.'); return; }
    if (!catId) { toastError('Category is required.'); return; }

    const btn = document.getElementById('saveProductBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i> Saving…';

    $.ajax({
        url: R_QUICK_PROD,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        data: { product_name: name, category_id: catId, selling_price: sPrice },
        success: function(res) {
            toastSuccess('New product added successfully.');

            // Push to local PRODUCTS array so new rows can use it
            PRODUCTS.push(res.product);

            // Update all existing item selects
            document.querySelectorAll('.item-select').forEach(sel => {
                const newOption = new Option(res.product.name, res.product.id, false, false);
                const $opt = $(newOption);
                $opt.attr('data-selling-price', res.product.selling_price);
                $opt.attr('data-purchase-price', res.product.purchase_price);
                $opt.attr('data-code', res.product.code);
                $opt.attr('data-unit', res.product.unit);
                $opt.attr('data-stock', res.product.stock);
                $opt.attr('data-catid', res.product.category_id);
                $(sel).append($opt);
            });

            closeAddProductModal();
        },
        error: function(xhr) {
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save product.');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i><span>Save Product</span>';
        }
    });
}

function toastSuccess(msg) {
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        .fire({ icon: 'success', title: msg });
}
function toastError(msg) {
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4500 })
        .fire({ icon: 'error', title: msg });
}
function toastWarn(msg) {
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        .fire({ icon: 'warning', title: msg });
}

/* ─────────────────────────── COLUMN PICKER ─────── */
function toggleColPicker() {
    document.getElementById('colPicker').classList.toggle('hidden');
}

function toggleTableCol(colId, show) {
    document.querySelectorAll('.col-' + colId).forEach(el => {
        if (show) el.classList.remove('hidden');
        else el.classList.add('hidden');
    });
}

// Close picker on outside click
document.addEventListener('click', (e) => {
    const picker = document.getElementById('colPicker');
    if (picker && !picker.contains(e.target) && !e.target.closest('button[onclick="toggleColPicker()"]')) {
        picker.classList.add('hidden');
    }
});

function toggleRowDisc(btn, rn) {
    const isAmt = btn.textContent === 'Amt';
    btn.textContent = isAmt ? 'Pct' : 'Amt';
    btn.classList.toggle('text-primary', isAmt);
    btn.classList.toggle('text-accent', !isAmt);
    calcRow(rn);
}
</script>
@endpush

@endsection


