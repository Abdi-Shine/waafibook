@extends('admin.blank_master')
@section('page_title', 'Create Purchase Order')
@section('admin')

    @push('css')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @php
        $currencySymbols = ['USD' => '$', 'SAR' => 'SAR', 'SOS' => 'SOS', 'EUR' => '€', 'GBP' => '£', 'KES' => 'KSh'];
    @endphp

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

        {{-- ── Top Header Bar ──────────────────────────────────────────── --}}
        <div class="flex flex-row justify-between items-center mb-6 gap-3 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm icon-gradient-primary">
                    <i class="bi bi-cart-check text-white text-lg md:text-xl"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-[16px] md:text-[20px] font-bold text-primary-dark whitespace-nowrap">Purchase Order</h1>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('purchase.order.index') }}"
                    class="flex items-center gap-2 px-3 md:px-5 py-2 md:py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-[13px] md:text-sm group normal-case whitespace-nowrap">
                    <i class="bi bi-arrow-left group-hover:-translate-x-0.5 transition-transform duration-300"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <form id="createPOForm" autocomplete="off">
            @csrf

            {{-- ── Supplier & Order Info ────────────────────────────────── --}}
            <div class="mb-4">
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 mb-6">
                    <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-6 pb-2 border-b border-gray-100">
                        Supplier & Order Details
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-4">

                        {{-- Supplier --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                                Supplier <span class="text-primary">*</span>
                            </label>
                            <div class="relative">
                                <select id="supplierSelect" name="supplier_id"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value=""></option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}"
                                            data-phone="{{ $s->phone }}"
                                            data-balance="{{ $s->amount_balance ?? 0 }}"
                                            data-name="{{ $s->name }}">
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-1 px-1 flex items-center justify-between">
                                <div class="text-[11px] font-black text-accent uppercase tracking-tight">
                                    BAL: <span id="partyBalanceDisplay">0.00</span>
                                </div>
                                <button type="button" onclick="openAddSupplierModal()"
                                    class="text-[10px] font-bold text-primary hover:text-primary-dark hover:underline">
                                    + Add New
                                </button>
                            </div>
                        </div>

                        {{-- PO Number --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">PO Number</label>
                            <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark">{{ $poNo }}</div>
                            <input type="hidden" name="po_number" value="{{ $poNo }}">
                        </div>

                        {{-- Order Date + Expected Delivery --}}
                        <div class="grid grid-cols-2 gap-3 md:contents">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Order Date <span class="text-primary">*</span></label>
                                <input type="date" name="order_date" value="{{ date('Y-m-d') }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expected Delivery</label>
                                <input type="date" name="expected_delivery"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>

                    </div>
                    <input type="hidden" name="payment_terms" value="Net 30 Days">
                    <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                    <input type="hidden" name="vat" id="taxVal" value="0">
                    <input type="hidden" name="total_amount" id="grandTotalVal" value="0">
                </div>
            </div>

            {{-- ── Items Table ──────────────────────────────────────────── --}}
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
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100">ITEM</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-24 text-center border-r border-gray-100">QTY</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">UNIT</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">PRICE/UNIT</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-right">AMOUNT</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody"></tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-white">
                                <td class="px-4 py-3 border-r border-gray-100"></td>
                                <td class="px-4 py-3 border-r border-gray-100">
                                    <button type="button" onclick="addItemRow()"
                                        class="flex items-center gap-1.5 text-[11px] font-black text-primary bg-accent/10 hover:bg-accent/20 border border-accent/30 rounded-[0.5rem] px-4 py-1.5 transition-all uppercase tracking-wider">
                                        <i class="bi bi-plus-lg"></i> ADD ROW
                                    </button>
                                </td>
                                <td class="text-center border-r border-gray-100">
                                    <span class="text-[14px] font-black text-primary-dark" id="footerQty">0</span>
                                </td>
                                <td class="border-r border-gray-100"></td>
                                <td class="text-[11px] font-black text-primary uppercase tracking-wider text-right border-r border-gray-100 px-4">TOTAL</td>
                                <td class="text-right px-4">
                                    <span class="text-[14px] font-black text-primary-dark" id="footerAmount">0.00</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ── Bottom: Notes + Summary ───────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

                {{-- Notes --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 lg:col-span-2">
                    <label class="block text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">
                        Notes / Terms &amp; Conditions
                    </label>
                    <textarea name="notes" rows="5"
                        placeholder="Enter any additional notes, terms, or conditions for this order..."
                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                </div>

                {{-- Summary --}}
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 lg:col-span-1">
                    <p class="text-[10px] font-black text-primary-dark uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">
                        Order Summary
                    </p>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Sub Total</span>
                            <span class="text-[13px] font-black text-primary-dark">{{ $curr }} <span id="subtotalDisplay">0.00</span></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Tax / VAT</span>
                            <span class="text-[13px] font-black text-primary-dark">—</span>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between mt-3">
                        <span class="text-[11px] font-black text-white uppercase tracking-wider">Total Amount</span>
                        <span class="text-[15px] font-black text-accent" id="grandTotalDisplay">{{ $curr }} 0.00</span>
                    </div>
                </div>
            </div>

            {{-- ── Action Buttons ────────────────────────────────────────── --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm px-6 py-5 flex flex-wrap items-center justify-between">
                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('purchase.order.index') }}" class="btn-premium-accent">Cancel</a>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button type="button" onclick="submitPO()" class="btn-premium-primary">
                        <i class="bi bi-cart-check"></i>
                        <span>Save Order</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    {{-- ── Add Supplier Modal ──────────────────────────────────────────── --}}
    <div id="addSupplierModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden modal-overlay">
        <div class="bg-white rounded-[1.25rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative modal-slide-up">
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight uppercase">Add New Supplier</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5">Quickly add a supplier</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeAddSupplierModal()"
                        class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="px-6 py-6 overflow-y-auto flex-grow bg-white space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Supplier Name <span class="text-primary">*</span></label>
                        <input type="text" id="newSupplierName" placeholder="Enter supplier name"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone No. <span class="text-primary">*</span></label>
                        <input type="text" id="newSupplierPhone" placeholder="Enter phone number"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Supplier Type</label>
                        <select id="newSupplierType"
                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all appearance-none cursor-pointer">
                            <option value="company">Business</option>
                            <option value="individual">Individual</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Opening Balance</label>
                        <input type="number" id="newSupplierBalance" placeholder="0.00" value="0" min="0" step="0.01"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <button type="button" onclick="closeAddSupplierModal()" class="btn-premium-accent">Cancel</button>
                <button type="button" onclick="saveNewSupplier()" id="saveSupplierBtn" class="btn-premium-primary">
                    <i class="bi bi-check2-circle"></i> <span>Save Supplier</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Add Product Modal ───────────────────────────────────────────── --}}
    <div id="addProductModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden modal-overlay">
        <div class="bg-white rounded-[1.25rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative modal-slide-up">
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold tracking-tight uppercase">Add New Product</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5">Quickly add an item</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeAddProductModal()"
                        class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="px-6 py-6 overflow-y-auto flex-grow bg-white space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Product Name <span class="text-primary">*</span></label>
                        <input type="text" id="newProductName" placeholder="Enter product name"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                        <select id="newProductCategory"
                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Selling Price</label>
                    <input type="number" id="newProductSellingPrice" placeholder="0.00" value="0" min="0" step="0.01"
                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <button type="button" onclick="closeAddProductModal()" class="btn-premium-accent">Cancel</button>
                <button type="button" onclick="saveNewProduct()" id="saveProductBtn" class="btn-premium-primary">
                    <i class="bi bi-check2-circle"></i> <span>Save Product</span>
                </button>
            </div>
        </div>
    </div>

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
        const SYM      = @json($sym);
        const CURR     = @json($curr);
        const CSRF     = @json(csrf_token());
        const R_STORE  = @json(route('purchase.order.store'));
        const R_LIST   = @json(route('purchase.order.index'));
        const R_QUICK_SUPP = @json(route('supplier.store'));
        const R_QUICK_PROD = @json(route('product.quick.store'));
        const PRODUCTS = JSON.parse(document.getElementById('productData').textContent);
        const CATEGORIES = JSON.parse(document.getElementById('categoryData').textContent);

        let rowCounter = 0;

        /* ── SELECT2 INIT ── */
        $(document).ready(function () {
            $('#supplierSelect').select2({
                placeholder: 'Search supplier…',
                allowClear: true,
                width: '100%',
            }).on('select2:select change', function () {
                const el = $(this).find(':selected');
                const bal = parseFloat(el.data('balance')) || 0;
                document.getElementById('partyBalanceDisplay').textContent =
                    bal.toLocaleString('en', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                window._supplierPrevBal = bal;
            }).on('select2:clear', function () {
                document.getElementById('partyBalanceDisplay').textContent = '0.00';
                window._supplierPrevBal = 0;
            });

            addItemRow();
            addItemRow();
            addItemRow();
        });

        /* ── BUILD ITEM OPTIONS ── */
        function buildItemOptions() {
            let html = `<option value="">Select Item</option>`;
            PRODUCTS.forEach(p => {
                html += `<option value="${p.id}"
                    data-purchase-price="${p.purchase_price}"
                    data-code="${p.code}"
                    data-unit="${p.unit}"
                    data-stock="${p.stock}">${p.name}</option>`;
            });
            return html;
        }

        /* ── ADD ROW ── */
        function addItemRow() {
            rowCounter++;
            const n  = rowCounter;
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.dataset.row = n;
            tr.innerHTML = `
                <td class="px-4 py-3 text-[11px] font-black text-gray-400 text-center border-r border-gray-100 row-num">${n}</td>
                <td class="px-2 py-1 border-r border-gray-100">
                    <select class="item-select" data-row="${n}">${buildItemOptions()}</select>
                </td>
                <td class="px-2 py-1 border-r border-gray-100 text-center">
                    <input type="number" class="tbl-clean-input qty-input" value="1" min="1" step="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); calcRow(${n})">
                </td>
                <td class="px-4 py-1 border-r border-gray-100 text-center">
                    <select class="unit-select-clean unit-input">
                        <option value="">NONE</option>
                        <option value="Piece">Piece</option>
                        <option value="Box">Box</option>
                        <option value="Kg">Kg</option>
                        <option value="Litre">Litre</option>
                        <option value="Set">Set</option>
                    </select>
                </td>
                <td class="px-2 py-1 border-r border-gray-100 text-center">
                    <input type="number" class="tbl-clean-input price-input" value="0" min="0" step="0.01"
                        oninput="calcRow(${n})">
                </td>
                <td class="px-4 py-1 border-r border-gray-100 text-right">
                    <span class="text-[13px] font-black text-[#004161] row-amount" data-val="0">0.00</span>
                </td>
                <td class="px-2 py-1 text-center">
                    <button type="button" onclick="removeRow(this)"
                        class="w-8 h-8 rounded-lg flex items-center justify-center border border-primary/20 text-primary hover:bg-primary/10 transition-all mx-auto">
                        <i class="bi bi-trash text-[13px]"></i>
                    </button>
                </td>
            `;
            document.getElementById('itemsTbody').appendChild(tr);

            $(tr).find('.item-select').select2({
                placeholder: 'Search and Select Item',
                width: '100%',
                dropdownAutoWidth: true,
                templateResult: formatProductResult,
                language: { noResults: () => `<div class="p-2 text-center text-gray-400">No items found</div>` },
                escapeMarkup: m => m,
            }).on('select2:select', function () {
                onItemChange(this, n);
            }).on('select2:open', function () {
                window._lastOpenedSelect2 = this;
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
                            </div>`);
                    }
                }, 10);
            });

            renumberRows();
            recalcAll();
        }

        function formatProductResult(p) {
            if (!p.id) return p.text;
            const prod = PRODUCTS.find(x => x.id == p.id);
            if (!prod) return p.text;
            return $(`<div class="product-res">
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
            </div>`);
        }

        function onItemChange(sel, rn) {
            const opt = $(sel).find(':selected');
            if (!opt.val()) return;
            const row = document.querySelector(`tr[data-row="${rn}"]`);
            row.querySelector('.price-input').value = opt.attr('data-purchase-price') || 0;
            const unit = opt.attr('data-unit') || '';
            const unitSel = row.querySelector('.unit-input');
            if (unit) {
                for (let o of unitSel.options) {
                    if (o.value.toLowerCase() === unit.toLowerCase()) { o.selected = true; break; }
                }
            } else {
                unitSel.selectedIndex = 0;
            }
            calcRow(rn);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('#itemsTbody .item-row');
            if (rows.length <= 1) { toastWarn('At least one row is required.'); return; }
            const row = btn.closest('tr');
            try { const sel = $(row).find('.item-select'); if (sel.data('select2')) sel.select2('destroy'); } catch(e) {}
            row.remove();
            renumberRows();
            recalcAll();
        }

        function renumberRows() {
            document.querySelectorAll('#itemsTbody .item-row').forEach((tr, i) => {
                tr.cells[0].textContent = i + 1;
            });
        }

        function calcRow(rn) {
            const row = document.querySelector(`tr[data-row="${rn}"]`);
            if (!row) return;
            const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = qty * price;
            row.querySelector('.row-amount').textContent = total.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            row.querySelector('.row-amount').dataset.val = total.toFixed(2);
            recalcAll();
        }

        function recalcAll() {
            let subtotal = 0, totalQty = 0;
            document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
                subtotal += parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
                totalQty += parseFloat(row.querySelector('.qty-input').value) || 0;
            });
            const fmt = n => n.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('inputSubtotal').value       = subtotal.toFixed(2);
            document.getElementById('grandTotalVal').value       = subtotal.toFixed(2);
            document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
            document.getElementById('grandTotalDisplay').textContent = CURR + ' ' + fmt(subtotal);
            document.getElementById('footerQty').textContent    = Math.round(totalQty);
            document.getElementById('footerAmount').textContent = fmt(subtotal);
        }

        /* ── SUBMIT ── */
        function submitPO() {
            const supplierId = document.getElementById('supplierSelect').value;
            if (!supplierId) { toastError('Please select a supplier.'); return; }

            const items = [];
            let valid = true;
            document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
                const itemSel = row.querySelector('.item-select');
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                if (!itemSel.value) return;
                if (qty <= 0) { toastError('Quantity must be greater than 0.'); valid = false; return; }
                const opt = $(itemSel).find(':selected');
                items.push({
                    product_id:   isNaN(itemSel.value) ? null : itemSel.value,
                    product_name: opt.text().split('(')[0].trim(),
                    unit:         row.querySelector('.unit-input').value || 'Piece',
                    quantity:     qty,
                    unit_price:   parseFloat(row.querySelector('.price-input').value) || 0,
                    total_amount: parseFloat(row.querySelector('.row-amount').dataset.val) || 0,
                });
            });
            if (!valid) return;
            if (items.length === 0) { toastError('Please add at least one product.'); return; }

            const data = {
                _token:            CSRF,
                supplier_id:       supplierId,
                po_number:         document.querySelector('input[name="po_number"]').value,
                order_date:        document.querySelector('input[name="order_date"]').value,
                expected_delivery: document.querySelector('input[name="expected_delivery"]').value,
                payment_terms:     'Net 30 Days',
                subtotal:          parseFloat(document.getElementById('inputSubtotal').value) || 0,
                vat:               0,
                total_amount:      parseFloat(document.getElementById('grandTotalVal').value) || 0,
                notes:             document.querySelector('textarea[name="notes"]').value,
                items,
            };

            Swal.fire({ title: 'Processing Order…', didOpen: () => Swal.showLoading() });
            $.ajax({
                url: R_STORE,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Order Created!', text: res.message, confirmButtonColor: '#004161' })
                            .then(() => window.location.href = R_LIST);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    }
                },
                error: function (xhr) {
                    const errs = xhr.responseJSON?.errors;
                    toastError(errs ? Object.values(errs).flat().join(' | ') : (xhr.responseJSON?.message || 'Failed to save order.'));
                }
            });
        }

        /* ── ADD SUPPLIER MODAL ── */
        function openAddSupplierModal()  { document.getElementById('addSupplierModal').classList.remove('hidden'); document.getElementById('newSupplierName').focus(); }
        function closeAddSupplierModal() { document.getElementById('addSupplierModal').classList.add('hidden'); document.getElementById('newSupplierName').value = ''; document.getElementById('newSupplierPhone').value = ''; document.getElementById('newSupplierBalance').value = '0'; }

        function saveNewSupplier() {
            const name    = document.getElementById('newSupplierName').value.trim();
            const phone   = document.getElementById('newSupplierPhone').value.trim();
            const type    = document.getElementById('newSupplierType').value;
            const balance = parseFloat(document.getElementById('newSupplierBalance').value) || 0;
            if (!name)  { toastError('Supplier name is required.'); return; }
            if (!phone) { toastError('Phone number is required.'); return; }
            const btn = document.getElementById('saveSupplierBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i> Saving…';
            $.ajax({
                url: R_QUICK_SUPP,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                data: { name, phone, supplier_type: type, amount_balance: balance },
                success: function (res) {
                    toastSuccess('New supplier added successfully.');
                    const newOpt = new Option(name, res.id, true, true);
                    $(newOpt).data('phone', phone).data('balance', balance).data('name', name);
                    $('#supplierSelect').append(newOpt).trigger('change');
                    closeAddSupplierModal();
                },
                error: function (xhr) {
                    const errs = xhr.responseJSON?.errors;
                    toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save supplier.');
                },
                complete: function () { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg mr-1"></i> Save Supplier'; }
            });
        }

        /* ── ADD PRODUCT MODAL ── */
        function openAddProductModal()  { $('.item-select').select2('close'); document.getElementById('addProductModal').classList.remove('hidden'); document.getElementById('newProductName').focus(); }
        function closeAddProductModal() { document.getElementById('addProductModal').classList.add('hidden'); document.getElementById('newProductName').value = ''; document.getElementById('newProductCategory').selectedIndex = 0; document.getElementById('newProductSellingPrice').value = '0'; }

        function saveNewProduct() {
            const name  = document.getElementById('newProductName').value.trim();
            const catId = document.getElementById('newProductCategory').value;
            const sPrice = parseFloat(document.getElementById('newProductSellingPrice').value) || 0;
            if (!name)  { toastError('Product name is required.'); return; }
            if (!catId) { toastError('Category is required.'); return; }
            const btn = document.getElementById('saveProductBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i> Saving…';
            $.ajax({
                url: R_QUICK_PROD,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                data: { product_name: name, category_id: catId, selling_price: sPrice },
                success: function (res) {
                    toastSuccess('New product added.');
                    PRODUCTS.push(res.product);
                    document.querySelectorAll('.item-select').forEach(sel => {
                        const $opt = $(new Option(res.product.name, res.product.id, false, false));
                        $opt.attr('data-purchase-price', res.product.purchase_price).attr('data-unit', res.product.unit).attr('data-stock', res.product.stock).attr('data-code', res.product.code);
                        $(sel).append($opt);
                    });
                    if (window._lastOpenedSelect2) $(window._lastOpenedSelect2).val(res.product.id).trigger('change');
                    closeAddProductModal();
                },
                error: function (xhr) {
                    const errs = xhr.responseJSON?.errors;
                    toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save product.');
                },
                complete: function () { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg mr-1"></i> Save Product'; }
            });
        }

        function toastSuccess(msg) { Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }).fire({ icon: 'success', title: msg }); }
        function toastError(msg)   { Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4500 }).fire({ icon: 'error', title: msg }); }
        function toastWarn(msg)    { Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }).fire({ icon: 'warning', title: msg }); }
    </script>
    @endpush

@endsection
