@extends('admin.admin_master')
@section('page_title', 'Create Purchase Order')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('purchase.order.index') }}"
               class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-xl text-gray-400 hover:text-primary hover:border-primary/30 transition-all shadow-sm">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Create Purchase Order</h1>
                <p class="text-xs text-gray-400 font-medium mt-0.5">Fill in the details below to create a new purchase order</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.order.index') }}"
               class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all shadow-sm text-sm">
                Cancel
            </a>
            <button type="button" onclick="submitPO()"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm">
                <i class="bi bi-cart-check"></i> Save Order
            </button>
        </div>
    </div>

    <form id="createPOForm" autocomplete="off">
        @csrf

        {{-- Top Section: Supplier + Order Details --}}
        <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                {{-- Left: Supplier --}}
                <div class="p-6 border-b md:border-b-0 md:border-r border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Supplier Entity</p>
                    <div class="relative">
                        <select name="supplier_id" id="po_supplier_id" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Search by Name / Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}  ·  {{ $supplier->supplier_code }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                {{-- Right: Order Details --}}
                <div class="p-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Order Details</p>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-400 font-semibold">Order No.</span>
                            <span class="text-sm font-black text-primary-dark tracking-wider">{{ $poNo }}</span>
                            <input type="hidden" name="po_number" value="{{ $poNo }}">
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-400 font-semibold">Issue Date <span class="text-primary">*</span></span>
                            <input type="date" name="order_date" id="po_order_date" required value="{{ date('Y-m-d') }}"
                                class="text-sm font-semibold text-primary-dark bg-transparent border-0 outline-none focus:ring-0 cursor-pointer text-right">
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-400 font-semibold">Expected Delivery</span>
                            <input type="date" name="expected_delivery" id="po_expected"
                                class="text-sm font-semibold text-primary-dark bg-transparent border-0 outline-none focus:ring-0 cursor-pointer text-right">
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-400 font-semibold">Payment Terms</span>
                            <select name="payment_terms" id="po_payment_terms"
                                class="text-sm font-semibold text-primary-dark bg-transparent border-0 outline-none cursor-pointer text-right">
                                <option value="Cash on Delivery">Cash on Delivery</option>
                                <option value="Net 30 Days" selected>Net 30 Days</option>
                                <option value="Net 60 Days">Net 60 Days</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Table --}}
        <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm mb-4 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/60">
                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest w-10 text-center">#</th>
                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Item Description</th>
                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-28">Qty</th>
                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-36">Price / Unit</th>
                        <th class="px-4 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-36">Amount</th>
                        <th class="px-4 py-4 w-10"></th>
                    </tr>
                </thead>
                <tbody id="poItemsContainer" class="divide-y divide-gray-50">
                    {{-- rows injected by JS --}}
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 bg-gray-50/60">
                        <td colspan="2" class="px-4 py-3">
                            <button type="button" onclick="addPOItem()"
                                class="flex items-center gap-2 px-4 py-1.5 text-primary font-black text-[10px] uppercase tracking-widest border border-primary/20 rounded-lg hover:bg-primary/5 transition-all">
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center text-[11px] font-black text-gray-400 uppercase tracking-widest">Total</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-black text-primary-dark">{{ $currency ?? '$' }} <span id="spanTotal">0.00</span></span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
            <input type="hidden" name="total_amount" id="inputTotal" value="0">
        </div>

        {{-- Bottom Section: Notes + Payment Terms + Total --}}
        <div class="bg-white rounded-[1rem] border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                {{-- Left: Terms & Remarks --}}
                <div class="p-6 border-b md:border-b-0 md:border-r border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Terms & Remarks</p>
                    <textarea name="notes" id="po_notes" rows="4"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-medium text-slate-600 focus:bg-white focus:ring-2 focus:ring-primary/10 transition-all outline-none resize-none"
                        placeholder="Additional context, delivery instructions, or procurement notes..."></textarea>
                </div>

                {{-- Right: Totals Summary --}}
                <div class="p-6 flex flex-col justify-between">
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-400 font-semibold">Sub Total</span>
                            <span class="text-sm font-black text-slate-700">{{ $currency ?? '$' }} <span id="spanSubtotal">0.00</span></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-semibold">Discount</span>
                            <span class="text-sm font-black text-slate-700">—</span>
                        </div>
                    </div>

                    {{-- Total Box --}}
                    <div class="bg-primary rounded-2xl p-5 flex items-center justify-between">
                        <span class="text-[11px] font-black text-white/60 uppercase tracking-widest">Procurement Total</span>
                        <span class="text-2xl font-black text-white">{{ $currency ?? '$' }} <span id="spanTotalBottom">0.00</span></span>
                    </div>
                </div>
            </div>

            {{-- Footer buttons --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <a href="{{ route('purchase.order.index') }}"
                   class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all shadow-sm text-sm">
                    Cancel
                </a>
                <button type="button" onclick="submitPO()"
                    class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm">
                    <i class="bi bi-cart-check"></i> Save Order
                </button>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
    const productsDB   = @json($products);
    const currencySymbol = "{{ $currency ?? '$' }}";
    let itemsCount = 0;

    function addPOItem() {
        const tbody = document.getElementById('poItemsContainer');
        const idx   = itemsCount++;
        const rowNum = tbody.rows.length + 1;
        const tr = document.createElement('tr');
        tr.className = 'manifest-item bg-white hover:bg-gray-50/40 transition-colors';
        tr.innerHTML = `
            <td class="px-4 py-3 text-center text-[11px] font-black text-gray-300">${rowNum}</td>
            <td class="px-4 py-3">
                <div class="relative">
                    <input type="hidden" name="items[${idx}][product_id]" id="pid_${idx}">
                    <input type="text" id="p_text_${idx}"
                        oninput="showSuggestions(this, ${idx})"
                        onfocus="showSuggestions(this, ${idx})"
                        autocomplete="off"
                        placeholder="Search by SKU or Name..."
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-slate-700 outline-none focus:bg-white focus:border-primary transition-all">
                    <div id="suggest_${idx}" class="product-suggestions custom-scrollbar shadow-xl"></div>
                </div>
            </td>
            <td class="px-4 py-3">
                <input type="number" name="items[${idx}][quantity]" step="1" value="1"
                    onchange="calculateTotals()"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-black text-center text-primary-dark outline-none focus:bg-white focus:border-primary transition-all">
            </td>
            <td class="px-4 py-3">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-gray-400 font-black">${currencySymbol}</span>
                    <input type="number" name="items[${idx}][unit_price]" step="0.01" value="0.00"
                        onchange="calculateTotals()"
                        class="w-full pl-6 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-black text-right text-slate-700 outline-none focus:bg-white focus:border-primary transition-all">
                </div>
            </td>
            <td class="px-4 py-3 text-right">
                <span class="text-sm font-black text-primary-dark manifestation-total">${currencySymbol} 0.00</span>
                <input type="hidden" name="items[${idx}][total_amount]" class="manifestation-total-input" value="0">
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removePOItem(this)"
                    class="w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-300 hover:text-red-400 hover:border-red-200 transition-all flex items-center justify-center mx-auto">
                    <i class="bi bi-trash text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removePOItem(btn) {
        btn.closest('.manifest-item').remove();
        refreshRowNumbers();
        calculateTotals();
    }

    function refreshRowNumbers() {
        document.querySelectorAll('.manifest-item').forEach((row, i) => {
            row.querySelector('td:first-child').textContent = i + 1;
        });
    }

    function showSuggestions(input, idx) {
        const query = input.value.trim().toLowerCase();
        const box   = document.getElementById(`suggest_${idx}`);
        box.innerHTML = '';
        const filtered = productsDB.filter(p =>
            p.product_name.toLowerCase().includes(query) ||
            (p.product_code && p.product_code.toLowerCase().includes(query))
        ).slice(0, 15);

        if (filtered.length > 0) {
            filtered.forEach(p => {
                const el = document.createElement('div');
                el.className = 'product-suggestion-item';
                el.innerHTML = `<span>${p.product_name}</span><span class="suggestion-price">${currencySymbol} ${parseFloat(p.purchase_price).toFixed(2)}</span>`;
                el.onmousedown = () => selectProduct(idx, p);
                box.appendChild(el);
            });
            box.classList.add('open');
        } else {
            box.classList.remove('open');
        }
    }

    function selectProduct(idx, p) {
        document.getElementById(`pid_${idx}`).value = p.id;
        document.getElementById(`p_text_${idx}`).value = p.product_name;
        const row = document.getElementById(`p_text_${idx}`).closest('.manifest-item');
        row.querySelector(`input[name="items[${idx}][unit_price]"]`).value = parseFloat(p.purchase_price).toFixed(2);
        document.getElementById(`suggest_${idx}`).classList.remove('open');
        calculateTotals();
    }

    function calculateTotals() {
        let grand = 0;
        document.querySelectorAll('.manifest-item').forEach(row => {
            const qty   = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
            const total = qty * price;
            row.querySelector('.manifestation-total').textContent = `${currencySymbol} ${total.toFixed(2)}`;
            row.querySelector('.manifestation-total-input').value = total.toFixed(2);
            grand += total;
        });
        const fmt = grand.toLocaleString(undefined, { minimumFractionDigits: 2 });
        document.getElementById('spanTotal').textContent        = fmt;
        document.getElementById('spanSubtotal').textContent     = fmt;
        document.getElementById('spanTotalBottom').textContent  = fmt;
        document.getElementById('inputSubtotal').value          = grand.toFixed(2);
        document.getElementById('inputTotal').value             = grand.toFixed(2);
    }

    function submitPO() {
        const supplier = document.getElementById('po_supplier_id').value;
        const items    = document.querySelectorAll('.manifest-item');

        if (!supplier) {
            Swal.fire({ icon: 'warning', title: 'Missing Supplier', text: 'Please select a supplier before saving.' }); return;
        }
        if (items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Empty Manifest', text: 'Please add at least one product to the order.' }); return;
        }

        Swal.fire({ title: 'Processing Order...', didOpen: () => Swal.showLoading() });

        const formData = new FormData(document.getElementById('createPOForm'));
        axios.post('{{ route("purchase.order.store") }}', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
            .then(res => {
                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Order Created!', text: res.data.message, confirmButtonColor: '#004161' })
                        .then(() => window.location.href = '{{ route("purchase.order.index") }}');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please check all required fields and try again.' });
            });
    }

    document.addEventListener('mousedown', e => {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('.product-suggestions').forEach(b => b.classList.remove('open'));
        }
    });

    // Add first row on load
    addPOItem();
</script>
@endpush
@endsection
