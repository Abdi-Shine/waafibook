@extends('admin.admin_master')
@section('page_title', 'Edit Invoice')
@section('admin')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@php
    $currencySymbols = [
        'USD' => '$',
        'SAR' => 'SAR',
        'SOS' => 'SOS',
        'EUR' => '€',
        'GBP' => '£',
        'KES' => 'KSh',
    ];
    $sym  = '$'; // Force Dollar
    $curr = '$'; // Force Dollar

    // Cash/walk-in sales (see add_invoice_sales.blade.php) have no customer_id —
    // the name/phone the cashier typed is instead prepended into `notes` as a
    // "WALK-IN DETAILS" block. Pull it back out here so it can be shown in the
    // Customer/Phone fields instead of leaving them blank.
    $walkinName = null;
    $walkinPhone = null;
    $walkinBlock = '';
    $cleanNotes = $order->notes;
    if (!$order->customer_id && $order->notes && preg_match('/^\s*WALK-IN DETAILS:\s*\n(.*?)\n-+\n?/s', $order->notes, $m)) {
        $walkinBlock = $m[0];
        foreach (explode('|', $m[1]) as $part) {
            $part = trim($part);
            if (str_starts_with($part, 'Name:'))  $walkinName  = trim(substr($part, 5));
            if (str_starts_with($part, 'Phone:')) $walkinPhone = trim(substr($part, 6));
        }
        $cleanNotes = trim(substr($order->notes, strlen($walkinBlock)));
    }
@endphp

<div class="px-4 py-6 md:px-8 bg-gray-50 min-h-screen">

    {{-- Page Heading --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="w-9 h-9 bg-accent/20 border-2 border-accent rounded-lg flex items-center justify-center shrink-0">
                    <i class="bi bi-pencil-square text-primary-dark text-base"></i>
                </div>
                <h1 class="text-xl font-black text-primary-dark tracking-tight">Edit Invoice</h1>
            </div>
        </div>
        <a href="{{ route('sales.invoice.view') }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case shrink-0">
            <i class="bi bi-arrow-left group-hover:-translate-x-0.5 transition-transform duration-300"></i>
            <span>Back to Sales</span>
        </a>
    </div>

    <form id="editForm" autocomplete="off">
        @csrf
        @method('PUT')

        {{-- Top Row --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 mb-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 pb-2 border-b border-gray-100">Customer Information</p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1.5">Customer</label>
                    <select id="customerSelect" name="customer_id" class="w-full">
                        <option value="">Search by Name/Phone #</option>
                        @if($walkinName)
                            {{-- Select2 always shows the placeholder text for an
                                 option with value="", even if it's selected — so
                                 this needs a distinct sentinel value instead. --}}
                            <option value="walkin" data-phone="{{ $walkinPhone }}" selected>Walk-in: {{ $walkinName }}</option>
                        @endif
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}"
                                    data-phone="{{ $c->phone }}"
                                    data-balance="{{ $c->amount_balance ?? 0 }}"
                                    {{ $order->customer_id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1.5">Phone No.</label>
                    <input type="text" id="customerPhone" readonly
                           value="{{ $order->customer->phone ?? $walkinPhone ?? '' }}"
                           class="w-full border border-gray-200 rounded px-3 py-1.5 text-[12px] font-medium text-gray-600 bg-gray-50 focus:outline-none focus:border-primary">
                </div>
                <input type="hidden" id="walkinBlockOriginal" value="{{ $walkinBlock }}">
                <div class="grid grid-cols-2 gap-3 md:contents">
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Invoice Number</label>
                        <div class="text-lg font-black text-primary tracking-tight">{{ $order->invoice_no }}</div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block mb-1.5">Invoice Date</label>
                        <input type="date" id="invoiceDateInput" name="invoice_date"
                               value="{{ \Carbon\Carbon::parse($order->invoice_date)->format('Y-m-d') }}"
                               class="w-full border border-gray-200 rounded px-3 py-1.5 text-[12px] font-medium text-gray-700 focus:outline-none focus:border-primary">
                    </div>
                </div>
            </div>
            <div id="balanceBadge" class="{{ ($order->customer->amount_balance ?? 0) > 0 ? 'flex' : 'hidden' }} mt-3 items-center gap-2 text-[11px] font-bold text-primary bg-primary/10 border border-primary/20 rounded-lg px-3 py-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Outstanding balance: <span id="balanceVal">{{ number_format($order->customer->amount_balance ?? 0, 2) }}</span> {{ $curr }}</span>
            </div>
            {{-- Hidden inputs — not displayed, submitted with form --}}
            <input type="hidden" name="invoice_no"     value="{{ $order->invoice_no }}">
            <input type="hidden" name="branch_id"      value="{{ $order->branch_id }}">
            <input type="hidden" name="payment_method" value="{{ $order->payment_method }}">
        </div>

        {{-- Items Table --}}
        <div class="bg-white border border-gray-200 rounded-lg mb-4 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider w-8 text-center">#</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider min-w-[140px]">Item</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider w-20 text-center">Qty</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider w-24">Unit</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider w-24 text-right">Price/Unit</th>
                            <th class="px-3 py-3 text-[10px] font-black text-gray-500 uppercase tracking-wider w-28 text-right">Amount</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody"></tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50">
                            <td colspan="2" class="px-4 py-3 text-[11px] font-black text-gray-500 uppercase text-right">Total</td>
                            <td class="px-3 py-3 text-center"><span class="text-[13px] font-black text-primary" id="footerQty">0</span></td>
                            <td colspan="2"></td>
                            <td class="px-3 py-3 text-right"><span class="text-[14px] font-black text-accent" id="footerAmount">{{ $sym }} 0.00</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="border-t border-dashed border-gray-200 py-3 px-4 text-left">
                <button type="button" onclick="addItemRow()"
                        class="inline-flex items-center gap-2 text-[12px] font-bold text-primary border border-dashed border-primary/30 rounded-lg px-8 py-1.5 hover:bg-primary hover:text-white hover:border-primary transition-all duration-200">
                    <i class="bi bi-plus-circle text-sm"></i> ADD ROW
                </button>
            </div>
        </div>

        {{-- Notes + Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

            {{-- Notes --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 lg:col-span-2">
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100">
                    <i class="bi bi-chat-left-text text-primary text-sm"></i>
                    <span class="text-[11px] font-bold text-primary-dark uppercase tracking-wider">Notes / Terms &amp; Conditions</span>
                </div>
                <textarea name="notes" rows="6" placeholder="Enter any additional notes, terms, or conditions..."
                          class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none">{{ $cleanNotes }}</textarea>
            </div>

            {{-- Invoice Summary --}}
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 lg:col-span-1">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                    <i class="bi bi-receipt text-primary text-sm"></i>
                    <span class="text-[11px] font-bold text-primary-dark uppercase tracking-wider">Invoice Summary</span>
                </div>


                {{-- Discount + Amount Paid --}}
                <div class="grid grid-cols-2 gap-3 py-2 border-b border-gray-100">
                    <div>
                        <label class="block text-[12px] text-gray-500 font-medium mb-1">Discount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">{{ $curr }}</span>
                            <input type="number" id="discountInput" name="discount" value="{{ round($order->discount ?? 0) }}" min="0" step="0.01"
                                   class="w-full pl-7 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-right">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[12px] text-gray-500 font-medium mb-1">Amount Paid</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">{{ $curr }}</span>
                            <input type="number" name="paid_amount" id="paidAmountInput" value="{{ $order->paid_amount ?? 0 }}" min="0" step="0.01"
                                   class="w-full pl-7 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-right">
                        </div>
                    </div>
                </div>
                <input type="hidden" id="discountPercent" value="0">

                <input type="hidden" name="tax" id="taxVal" value="0">
                <input type="hidden" name="total_amount" id="grandTotalVal" value="{{ $order->total_amount }}">

                {{-- Grand Total --}}
                <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between mt-3">
                    <span class="text-[11px] font-black text-white uppercase tracking-wider">Grand Total</span>
                    <span class="text-[15px] font-black text-accent" id="grandTotalDisplay">{{ $curr }} {{ number_format($order->total_amount, 2) }}</span>
                </div>

                {{-- Balance row --}}
                <div class="flex justify-between items-center px-1 py-2 mt-2 border-t border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Total Customer Balance</span>
                    <span class="font-black text-primary-dark text-[12px]" id="totalCustomerBalDisplay">{{ $curr }} 0.00</span>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm px-6 py-5 flex flex-wrap items-center justify-between">
            <a href="{{ route('sales.invoice.view') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 text-[13px] font-bold text-gray-600 hover:bg-gray-50 transition-all">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="button" onclick="submitUpdate()"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-accent text-primary-dark text-[13px] font-black hover:bg-accent/90 shadow-sm transition-all">
                <i class="bi bi-check2-all"></i> Update Invoice
            </button>
        </div>

    </form>
</div>

{{-- Add Product Modal --}}
<div id="addProductModal"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden modal-overlay">
    <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[100vh] overflow-hidden shadow-2xl flex flex-col relative modal-slide-up">
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

{{-- JSON data for JS --}}
<script id="productData" type="application/json">
{!! json_encode($products->map(fn($p) => [
    'id'             => $p->id,
    'name'           => $p->product_name,
    'code'           => $p->product_code ?? '',
    'price'          => (float) $p->selling_price,
    'purchase_price' => (float) $p->purchase_price,
    'unit'           => $p->unit ?? 'Piece',
    'stock'          => (int) ($p->stocks_sum_quantity ?? 0),
    'category_id'    => $p->category_id,
])) !!}
</script>
<script id="categoryData" type="application/json">
{!! json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) !!}
</script>
<script id="existingItems" type="application/json">
{!! json_encode($order->items->map(fn($i) => [
    'product_id'   => $i->product_id,
    'product_name' => $i->product_name,
    'product_code' => $i->product_code ?? '',
    'quantity'     => (float) $i->quantity,
    'unit_price'   => (float) $i->unit_price,
    'unit'         => $i->unit ?? 'Piece',
    'discount'     => (float) ($i->discount ?? 0),
    'total_price'  => (float) $i->total_price,
])) !!}
</script>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const SYM        = @json($sym);
const CURR       = @json($curr);
const CSRF       = @json(csrf_token());
const R_UPDATE   = @json(route('sales.invoice.update', $order->id));
const R_LIST     = @json(route('sales.invoice.view'));
const R_QUICK_PROD = @json(route('product.quick.store'));
// This order's currently saved due amount is already baked into its
// customer's stored balance, so the live preview must back it out before
// adding the edited due amount back in — otherwise editing the invoice
// double-counts it (see PurchaseController::updateBill's $balanceDiff for
// the equivalent fix on the purchase bill side).
const ORDER_OLD_DUE = @json((float) ($order->due_amount ?? 0));
const PRODUCTS   = JSON.parse(document.getElementById('productData').textContent);
const CATEGORIES = JSON.parse(document.getElementById('categoryData').textContent);
const EXISTING   = JSON.parse(document.getElementById('existingItems').textContent);

let rowCounter    = 0;
let discountFocus = 'amt';

/* ── Init ── */
$(document).ready(function () {
    $('#customerSelect').select2({ placeholder: 'Search by Name/Phone #', allowClear: true, width: '100%' })
        .on('select2:select', function () {
            const el  = $(this).find(':selected');
            document.getElementById('customerPhone').value = el.data('phone') || '';
            window._customerPrevBalance = parseFloat(el.data('balance')) || 0;
            const bal = window._customerPrevBalance;
            document.getElementById('balanceVal').textContent = bal.toFixed(2);
            document.getElementById('balanceBadge').classList.toggle('hidden', bal <= 0);
            recalcAll();
        }).on('select2:clear', () => {
            document.getElementById('customerPhone').value = '';
            document.getElementById('balanceBadge').classList.add('hidden');
            window._customerPrevBalance = 0;
            recalcAll();
        });

    // Set initial customer balance — back out this order's own old due
    // amount since it's already included in the customer's stored balance.
    window._customerPrevBalance = (parseFloat(
        $('#customerSelect').find(':selected').data('balance')
    ) || 0) - ORDER_OLD_DUE;

    // Load existing items
    EXISTING.forEach(item => addItemRow(item));

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

    recalcAll();
});

function calculateCurrentSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        subtotal += parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
    });
    return subtotal;
}

/* ── Item option builder ── */
function buildItemOptions(catId) {
    let html = `<option value="">Select Item</option>`;
    PRODUCTS.forEach(p => {
        if (!catId || catId === 'ALL' || p.category_id == catId) {
            html += `<option value="${p.id}"
                data-price="${p.price}" data-purchase-price="${p.purchase_price}" data-code="${p.code}"
                data-unit="${p.unit}" data-stock="${p.stock}"
                data-catid="${p.category_id}">${p.name}</option>`;
        }
    });
    return html;
}

/* ── Add row (optionally pre-filled) ── */
function addItemRow(prefill) {
    rowCounter++;
    const n  = rowCounter;
    const tr = document.createElement('tr');
    tr.className  = 'item-row';
    tr.dataset.row = n;

    tr.innerHTML = `
        <td class="px-3 py-2 text-[11px] font-bold text-gray-400 text-center">${n}</td>
        <td class="px-2 py-2">
            <select class="tbl-input tbl-select item-select" data-row="${n}">
                ${buildItemOptions('ALL')}
            </select>
        </td>
        <td class="px-2 py-2">
            <input type="number" class="tbl-input qty-input text-center tbl-qty-w"
                   value="${prefill ? prefill.quantity : 1}" min="1" step="1"
                   oninput="this.value = this.value.replace(/[^0-9]/g, ''); calcRow(${n})">
        </td>
        <td class="px-2 py-2">
            <select class="tbl-input tbl-select unit-input tbl-unit-w">
                ${['','Piece','Box','Kg','Litre','Set'].map(u =>
                    `<option value="${u}" ${prefill && prefill.unit===u?'selected':''}>${u||'NONE'}</option>`
                ).join('')}
            </select>
        </td>
        <td class="px-2 py-2">
            <input type="number" class="tbl-input price-input text-right tbl-price-w"
                   value="${prefill ? prefill.unit_price : 0}" min="0" step="0.01"
                   oninput="calcRow(${n})">
        </td>
        <td class="px-2 py-2 text-right">
            <span class="text-[13px] font-black text-[#5a9a22] row-amount"
                  data-val="${prefill ? prefill.total_price : 0}">
                ${CURR} ${prefill ? prefill.total_price.toFixed(2) : '0.00'}
            </span>
            {{-- Carries forward any discount already saved on this line item;
                 there's no UI to edit it here, it just keeps existing totals correct. --}}
            <input type="hidden" class="row-disc-input" value="${prefill ? prefill.discount : 0}">
        </td>
        <td class="px-2 py-2">
            <div class="flex items-center justify-center gap-1">
                <button type="button" class="w-6 h-6 rounded text-gray-400 hover:text-primary transition-colors flex items-center justify-center"
                        onclick="removeRow(${n})" title="Delete">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
            </div>
        </td>
    `;

    document.getElementById('itemsTbody').appendChild(tr);

    initItemSelect2(tr.querySelector('.item-select'), n);

    // If pre-filling, select the matching product option
    if (prefill && prefill.product_id) {
        const itemSel = tr.querySelector('.item-select');
        for (let o of itemSel.options) {
            if (o.value == prefill.product_id) { $(itemSel).val(o.value).trigger('change.select2'); break; }
        }
    }

    $(tr).find('.item-select').on('select2:select', function () { onItemChange(this, n); });

    renumberRows();
}

/* ── Item Select2 init (search dropdown w/ price/stock columns + Add Item) ── */
function initItemSelect2(itemSel, rn) {
    $(itemSel).select2({
        placeholder: 'Search and Select Item',
        width: '100%',
        dropdownAutoWidth: true,
        templateResult: formatProductResult,
        language: {
            noResults: function () {
                return `<div class="p-2 text-center text-gray-400">No items found matching search</div>`;
            }
        },
        escapeMarkup: function (markup) { return markup; }
    }).on('select2:open', function () {
        setTimeout(() => {
            const container = $('.select2-container--open .select2-dropdown');
            if (container.length && !container.find('.s2-header-row').length) {
                container.find('.select2-results').prepend(`
                    <div class="s2-header-row">
                        <div class="s2-add-btn" onmousedown="openAddProductModal(${rn})">
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
                    <div class="product-res-val">${prod.price.toLocaleString()}</div>
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

/* ── Item change handler ── */
function onItemChange(sel, rn) {
    const opt = $(sel).find(':selected');
    if (!opt.val()) return;
    const row = document.querySelector(`tr[data-row="${rn}"]`);
    row.querySelector('.price-input').value = opt.data('price') || 0;
    const unitSel = row.querySelector('.unit-input');
    const unit    = opt.data('unit') || '';
    for (let o of unitSel.options) { if (o.value === unit) { o.selected = true; break; } }
    calcRow(rn);
}

/* ── Remove row ── */
function removeRow(rn) {
    const rows = document.querySelectorAll('#itemsTbody .item-row');
    if (rows.length <= 1) { toastWarn('At least one row is required.'); return; }
    const row = document.querySelector(`tr[data-row="${rn}"]`);
    // Only .item-select is ever initialized as a Select2 (see
    // initItemSelect2) — .unit-input is a plain <select>. Calling
    // select2('destroy') on an element that was never initialized throws,
    // which silently aborted everything below it (the row never actually
    // got removed).
    $(row).find('.item-select').select2('destroy');
    row.remove();
    renumberRows();
    recalcAll();
}

function renumberRows() {
    document.querySelectorAll('#itemsTbody .item-row').forEach((tr, i) => { tr.cells[0].textContent = i + 1; });
}

/* ── Row calc ── */
function calcRow(rn) {
    const row   = document.querySelector(`tr[data-row="${rn}"]`);
    if (!row) return;
    const qty   = parseFloat(row.querySelector('.qty-input').value)      || 0;
    const price = parseFloat(row.querySelector('.price-input').value)    || 0;
    // Discount has no UI here; this hidden field only carries forward
    // whatever was already saved on the line (see addItemRow's comment).
    const discAmt  = parseFloat(row.querySelector('.row-disc-input').value) || 0;
    const gross    = qty * price;
    const rowTotal = Math.max(0, gross - discAmt);
    const span     = row.querySelector('.row-amount');
    span.textContent  = CURR + ' ' + rowTotal.toFixed(2);
    span.dataset.val  = rowTotal.toFixed(2);
    recalcAll();
}

/* ── Global recalc ── */
function recalcAll() {
    let subtotal = 0, totalQty = 0;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        subtotal += parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
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

    const discAmt = parseFloat(amtInput.value) || 0;
    const afterDisc = Math.max(0, subtotal - discAmt);
    const taxAmt    = 0;

    let grandTotal = afterDisc + taxAmt;
    let roundOff   = 0;

    const paid       = parseFloat(document.getElementById('paidAmountInput').value) || 0;
    const balanceDue = Math.max(0, grandTotal - paid);
    const prevBal    = parseFloat(window._customerPrevBalance) || 0;
    const totalCust  = prevBal + balanceDue;

    document.getElementById('subtotalDisplay').textContent   = CURR + ' ' + subtotal.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = CURR + ' ' + grandTotal.toFixed(2);
    document.getElementById('grandTotalVal').value           = grandTotal.toFixed(2);
    document.getElementById('taxVal').value                  = taxAmt.toFixed(2);
    document.getElementById('totalCustomerBalDisplay').textContent = CURR + ' ' + totalCust.toFixed(2);

    document.getElementById('footerQty').textContent    = totalQty % 1 === 0 ? totalQty : totalQty.toFixed(2);
    document.getElementById('footerAmount').textContent = SYM + ' ' + subtotal.toFixed(2);
}

/* ── Submit update ── */
function submitUpdate() {
    // customer_id is intentionally optional — cash/walk-in sales (see
    // add_invoice_sales.blade.php) have no linked Customer record; their
    // name/phone live in the notes' WALK-IN DETAILS block instead. "walkin"
    // is just a placeholder-display sentinel (see the <option> comment
    // above), not a real customer id.
    const rawCustomerId = document.getElementById('customerSelect').value;
    const customerId = rawCustomerId === 'walkin' ? '' : rawCustomerId;

    const items = [];
    let valid = true;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const itemSel = row.querySelector('.item-select');
        const qty     = parseFloat(row.querySelector('.qty-input').value) || 0;
        if (!itemSel.value) return;
        if (qty <= 0) { toastError('Quantity must be greater than 0.'); valid = false; return; }
        const opt     = $(itemSel).find(':selected');
        const discAmt = parseFloat(row.querySelector('.row-disc-input').value) || 0;
        const price   = parseFloat(row.querySelector('.price-input').value) || 0;
        items.push({
            product_id:   itemSel.value,
            product_name: opt.text().split('(')[0].trim(),
            product_code: '',
            unit:         row.querySelector('.unit-input').value || 'Piece',
            quantity:     qty,
            unit_price:   price,
            discount:     discAmt,
            total_price:  parseFloat(row.querySelector('.row-amount').dataset.val) || 0,
        });
    });

    if (!valid) return;
    if (items.length === 0) { toastError('Please add at least one product.'); return; }

    // Re-attach the walk-in name/phone block in front of the user's notes so
    // it isn't lost on save when this is still a customer-less cash sale.
    let notes = document.querySelector('textarea[name="notes"]').value;
    if (!customerId) {
        const walkinBlock = document.getElementById('walkinBlockOriginal')?.value || '';
        if (walkinBlock) notes = walkinBlock + notes;
    }

    const data = {
        _token:         CSRF,
        _method:        'PUT',
        customer_id:    customerId || null,
        branch_id:      document.querySelector('input[name="branch_id"]').value,
        invoice_date:   document.querySelector('input[name="invoice_date"]').value,
        payment_method: document.querySelector('input[name="payment_method"]').value,
        discount:       parseFloat(document.getElementById('discountInput').value) || 0,
        tax:            0,
        total_amount:   parseFloat(document.getElementById('grandTotalVal').value) || 0,
        paid_amount:    parseFloat(document.getElementById('paidAmountInput').value) || 0,
        notes,
        items,
    };

    const btn = document.querySelector('button[onclick="submitUpdate()"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin text-sm"></i> Updating...';

    $.ajax({
        url: R_UPDATE,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function () {
            Swal.fire({ icon: 'success', title: 'Invoice Updated!', confirmButtonColor: '#004161' })
                .then(() => window.location.href = R_LIST);
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-all text-sm"></i> Update Invoice';
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : (xhr.responseJSON?.message || 'Failed to update.'));
        }
    });
}

/* ── Toast helpers ── */
function toastSuccess(msg) {
    Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:3000 })
        .fire({ icon:'success', title:msg });
}
function toastError(msg) {
    Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:4500 })
        .fire({ icon:'error', title:msg });
}
function toastWarn(msg) {
    Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:3000 })
        .fire({ icon:'warning', title:msg });
}

/* ── Add Product Modal ── */
function openAddProductModal() {
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
    const name   = document.getElementById('newProductName').value.trim();
    const catId  = document.getElementById('newProductCategory').value;
    const sPrice = parseFloat(document.getElementById('newProductSellingPrice').value) || 0;

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
        success: function (res) {
            toastSuccess('New product added successfully.');

            // The quick-store endpoint returns `selling_price`, but PRODUCTS
            // (and formatProductResult) key off `price` — normalize before
            // pushing so the new product renders correctly in the dropdown.
            const newProd = {
                id: res.product.id,
                name: res.product.name,
                code: res.product.code,
                price: res.product.selling_price,
                purchase_price: res.product.purchase_price,
                unit: res.product.unit,
                stock: res.product.stock,
                category_id: res.product.category_id,
            };
            PRODUCTS.push(newProd);

            // Update all existing item selects
            document.querySelectorAll('.item-select').forEach(sel => {
                const newOption = new Option(newProd.name, newProd.id, false, false);
                const $opt = $(newOption);
                $opt.attr('data-price', newProd.price);
                $opt.attr('data-purchase-price', newProd.purchase_price);
                $opt.attr('data-code', newProd.code);
                $opt.attr('data-unit', newProd.unit);
                $opt.attr('data-stock', newProd.stock);
                $opt.attr('data-catid', newProd.category_id);
                $(sel).append($opt);
            });

            closeAddProductModal();
        },
        error: function (xhr) {
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save product.');
        },
        complete: function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i><span>Save Product</span>';
        }
    });
}
</script>
@endpush

@endsection


