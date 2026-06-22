@extends('admin.blank_master')
@section('page_title', 'New Sales Invoice')
@section('admin')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@php
    $currencySymbols = [
        'USD' => '$',
    ];
    $sym  = '$'; // Force Dollar
    $curr = '$'; // Force Dollar
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- ── Top Header Bar ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm icon-gradient-primary">
                <i class="bi bi-receipt text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Sale Invoice</h1>
            </div>

            {{-- Credit / Cash toggle (next to title) --}}
            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg select-none"
                 id="saleTypeToggleWrap">

                <span id="labelCredit"
                      class="text-[12px] font-black cursor-pointer transition-colors duration-200 toggle-label-off"
                      onclick="setSaleType('credit')">Credit</span>

                <button type="button" id="saleTypeToggle" onclick="flipSaleType()"
                        class="relative rounded-full focus:outline-none flex-shrink-0 transition-all duration-300 toggle-track"
                        aria-label="Toggle sale type">
                    <span id="saleTypeKnob"
                          class="absolute bg-white rounded-full shadow-sm transition-transform duration-300 toggle-knob"></span>
                </button>

                <span id="labelCash"
                      class="text-[12px] font-black cursor-pointer transition-colors duration-200 toggle-label-on"
                      onclick="setSaleType('cash')">Cash</span>

                <input type="hidden" id="saleTypeInput" name="sale_type" value="cash">
            </div>
        </div>

        {{-- Right: Back button --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.invoice.view') }}"
               class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case btn-premium-accent">
                <i class="bi bi-arrow-left group-hover:-translate-x-0.5 transition-transform duration-300"></i>
                <span>Back to Invoices</span>
            </a>
        </div>
    </div>

    <form id="invoiceForm" autocomplete="off">
        @csrf

        {{-- ── Top Row: Customer Info ─────────────────────────────────────────── --}}
        <div class="space-y-4 mb-4">

                {{-- Customer Information --}}
                <div id="customerSection" class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6 transition-opacity duration-300">
                    <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-6 pb-2 border-b border-gray-100">
                        Customer &amp; Sale Info
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-4">

                        {{-- Left: Customer select + Balance + Add New --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                                Customer <span id="customerRequiredStar" class="text-primary">*</span>
                            </label>
                            <div class="relative">
                                <select id="customerSelect" name="customer_id" class="w-full">
                                    <option value=""></option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}"
                                                data-phone="{{ $c->phone }}"
                                                data-balance="{{ $c->amount_balance ?? 0 }}"
                                                data-name="{{ $c->name }}">
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-1 px-1 flex items-center justify-between">
                                <div class="text-[11px] font-black text-accent uppercase tracking-tight" id="balDisplayWrapper">
                                    BAL: <span id="partyBalanceDisplay">0.00</span>
                                </div>
                                <button type="button" onclick="openAddCustomerModal()"
                                        class="text-[10px] font-bold text-primary hover:text-primary-dark hover:underline">
                                    + Add New
                                </button>
                            </div>
                        </div>

                        {{-- Right: Phone No. --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                                Phone No.
                            </label>
                            <input type="text" id="billingName" name="billing_name" placeholder="Contact number"
                                   class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>

                        {{-- Invoice Number --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Invoice Number</label>
                            <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark">{{ $invoiceNo }}</div>
                        </div>

                        {{-- Invoice Date --}}
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Invoice Date</label>
                            <input type="date" id="invoiceDateCredit" name="invoice_date" value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>

                        {{-- Hidden branch & account (auto from user's assigned branch) --}}
                        <input type="hidden" name="branch_id" id="branchSelect" value="{{ auth()->user()->getAssignedBranchId() ?? ($branches->first()->id ?? '') }}">
                        <input type="hidden" name="payment_account_id" id="paymentAccountSelect" value="">

                    </div>
                </div>

                {{-- Walk-in / Cash Details (visible only when sale_type is 'cash') --}}
                <div id="cashDetailsSection" class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6">
                    <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-6 pb-2 border-b border-gray-100">
                        Walk-in Customer Details
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Billing Name (Optional)</label>
                            <input type="text" id="cashBillingName" name="cash_billing_name" placeholder="Enter name"
                                   class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone No.</label>
                            <input type="text" id="cashPhone" name="cash_phone" placeholder="Enter phone"
                                   class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Invoice Number</label>
                            <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark">{{ $invoiceNo }}</div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Invoice Date</label>
                            <input type="date" id="invoiceDateCash" name="invoice_date_cash" value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    {{-- Hidden branch & account for cash mode --}}
                    <input type="hidden" name="branch_id_cash" id="branchIdCash" value="{{ auth()->user()->getAssignedBranchId() ?? ($branches->first()->id ?? '') }}">
                    <input type="hidden" name="payment_account_id_cash" id="paymentAccountSelectCash" value="">
                </div>

        </div>

        {{-- ── Items Table ─────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-cart3 text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Sale Items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-12 text-center border-r border-gray-100">#</th>

                            {{-- Optional: Category --}}
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100 hidden col-cat">CATEGORY</th>

                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100">ITEM</th>

                            {{-- Optional: Code --}}
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-28 text-center border-r border-gray-100 hidden col-code">CODE</th>

                            {{-- Optional: Description --}}
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100 hidden col-desc">DESCRIPTION</th>

                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-24 text-center border-r border-gray-100">QTY</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">UNIT</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100">PRICE/UNIT</th>

                            {{-- Optional: Discount --}}
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100 hidden col-disc">DISCOUNT</th>

                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-center border-r border-gray-100 hidden col-profit">PROFIT</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-32 text-right">AMOUNT</th>
                            <th class="w-10 text-center relative">
                                <button type="button" onclick="toggleColPicker()"
                                        class="hover:opacity-80 transition-opacity" title="Toggle Columns">
                                    <i class="bi bi-sliders text-primary text-base"></i>
                                </button>
                                {{-- Popover --}}
                                <div id="colPicker"
                                     class="absolute right-0 top-full mt-1 bg-white border border-gray-200 shadow-xl rounded-[0.75rem] py-3 px-4 z-50 hidden text-left col-picker-popover">
                                    <p class="text-[10px] font-black text-primary-dark uppercase tracking-widest mb-2.5">Optional Columns</p>
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('cat', this.checked)"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Item Category</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('code', this.checked)"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Item Code</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('desc', this.checked)"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Description</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('disc', this.checked)"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Discount</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" onchange="toggleTableCol('profit', this.checked)"
                                                   class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30" checked>
                                            <span class="text-[13px] font-semibold text-gray-700 group-hover:text-primary transition-colors">Live Profit</span>
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
                            <td class="px-4 py-3 border-r border-gray-100"></td>
                            <td class="px-4 py-3 border-r border-gray-100 hidden col-cat"></td>
                            <td class="px-4 py-3 border-r border-gray-100">
                                <button type="button" onclick="addItemRow()"
                                        class="flex items-center gap-1.5 text-[11px] font-black text-primary bg-accent/10 hover:bg-accent/20 border border-accent/30 rounded-[0.5rem] px-4 py-1.5 transition-all uppercase tracking-wider">
                                    <i class="bi bi-plus-lg"></i> ADD ROW
                                </button>
                            </td>
                            <td class="border-r border-gray-100 hidden col-code"></td>
                            <td class="border-r border-gray-100 hidden col-desc"></td>

                            <td class="text-center border-r border-gray-100">
                                <span class="text-[14px] font-black text-primary-dark" id="footerQty">0</span>
                            </td>

                            <td class="border-r border-gray-100"></td>

                            <td class="text-[11px] font-black text-primary uppercase tracking-wider text-right border-r border-gray-100">
                                TOTAL
                            </td>

                            <td class="border-r border-gray-100 hidden col-disc text-center"></td>

                            <td class="text-center border-r border-gray-100 hidden col-profit">
                                <span class="text-[12px] font-black text-accent" id="footerProfit">0.00</span>
                            </td>

                            <td class="text-right border-r border-gray-100">
                                <span class="text-[14px] font-black text-primary-dark" id="footerAmount">0.00</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- ── Bottom: Notes + Summary ───────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">

            {{-- Notes --}}
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 lg:col-span-2">
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100">
                    <i class="bi bi-chat-left-text text-primary text-sm"></i>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Notes / Terms &amp; Conditions</span>
                </div>
                <textarea name="notes" rows="6" placeholder="Enter any notes or terms..."
                          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-[12px] text-gray-600 resize-none focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder-gray-300 transition-all"></textarea>
            </div>

            {{-- Invoice Summary --}}
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 lg:col-span-1">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                    <i class="bi bi-receipt text-primary text-sm"></i>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Invoice Summary</span>
                </div>

                {{-- Subtotal row --}}
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-[12px] text-gray-500 font-medium">Subtotal</span>
                    <span class="text-[13px] font-bold text-gray-700" id="subtotalDisplay">{{ $curr }} 0.00</span>
                </div>

                {{-- Discount --}}
                <div class="flex items-center justify-between py-2 border-b border-gray-100 gap-3">
                    <span class="text-[12px] text-gray-500 font-medium shrink-0">Discount</span>
                    <div class="relative w-36">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">{{ $curr }}</span>
                        <input type="number" id="discountInput" name="discount" value="0" min="0" step="0.01"
                               class="w-full pl-8 pr-3 py-1 border border-gray-200 rounded-lg text-[12px] font-bold text-right text-primary-dark focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                {{-- Amount Paid --}}
                <div class="flex items-center justify-between py-2 border-b border-gray-100 gap-3" id="paidAmountWrapper">
                    <span class="text-[12px] text-gray-500 font-medium shrink-0">Amount Paid</span>
                    <div class="relative w-36">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">{{ $curr }}</span>
                        <input type="number" name="paid_amount" id="paidAmountInput" value="0" min="0" step="0.01"
                               class="w-full pl-8 pr-3 py-1 border border-gray-200 rounded-lg text-[12px] font-bold text-right text-primary-dark focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 transition-all">
                    </div>
                </div>

                {{-- Hidden fields --}}
                <input type="hidden" name="tax" id="taxVal" value="0">
                <input type="hidden" name="total_amount" id="grandTotalVal" value="0">

                {{-- Grand Total Box --}}
                <div class="bg-primary rounded-xl px-5 py-4 flex items-center justify-between mt-4">
                    <span class="text-[13px] font-black text-white uppercase tracking-wider">Grand Total</span>
                    <span class="text-[20px] font-black text-accent" id="grandTotalDisplay">{{ $curr }} 0.00</span>
                </div>

                {{-- Balance rows --}}
                <div class="mt-3 space-y-1 px-1" id="totalBalanceWrapper">
                    <div class="flex justify-between text-[11px] pt-1">
                        <span class="text-gray-400">Balance Due (this invoice)</span>
                        <span class="font-bold text-primary" id="balanceDueDisplay">{{ $curr }} 0.00</span>
                    </div>
                    <div class="flex justify-between text-[11px] bg-primary/8 rounded-lg px-3 py-2 mt-1">
                        <span class="font-black text-primary-dark">Total Customer Balance</span>
                        <span class="font-black text-primary" id="totalCustomerBalDisplay">{{ $curr }} 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Action Buttons ───────────────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm px-6 py-4 flex flex-wrap items-center gap-3 justify-between">
            <a href="{{ route('sales.invoice.view') }}"
               class="inline-flex items-center gap-2 px-5 py-2 rounded-lg border border-gray-300 text-[13px] font-bold text-gray-600 hover:bg-gray-50 transition-all">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="button" onclick="submitForm('send')"
                    class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-accent text-primary-dark text-[13px] font-black hover:bg-accent/90 shadow-sm transition-all">
                <i class="bi bi-check2-all"></i> Save Invoice
            </button>
        </div>

    </form>
</div>

{{-- ── Add Customer Modal ─────────────────────────────────────────────────────── --}}
<div id="addPartyModal"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden modal-overlay">
    <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[100vh] overflow-hidden shadow-2xl flex flex-col relative modal-slide-up">
        {{-- Header --}}
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-xl font-bold tracking-tight uppercase">Add New Customer</h2>
                        <p class="text-xs text-white/60 font-medium mt-0.5">Quickly add a customer</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddPartyModal()"
                        class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>
        {{-- Body --}}
        <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Customer Name <span class="text-primary">*</span></label>
                    <input type="text" id="newPartyName" placeholder="Enter full name"
                           class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone No. <span class="text-primary">*</span></label>
                    <input type="text" id="newPartyPhone" placeholder="Enter phone number"
                           class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Opening Balance</label>
                    <input type="number" id="newPartyBalance" placeholder="0.00" value="0" min="0" step="0.01"
                           class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
            </div>
        </div>
        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
            <button type="button" onclick="closeAddPartyModal()" class="btn-premium-accent">
                Cancel
            </button>
            <button type="button" onclick="saveNewParty()" id="savePartyBtn" class="btn-premium-primary">
                <i class="bi bi-check2-circle"></i>
                <span>Save Customer</span>
            </button>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────────── --}}
{{-- Product/Category data for JS                        --}}
{{-- ─────────────────────────────────────────────────── --}}
<script id="productData" type="application/json">
{!! json_encode($products->map(fn($p) => [
    'id'          => $p->id,
    'name'        => $p->product_name,
    'code'        => $p->product_code ?? '',
    'price'       => (float) $p->selling_price,
    'purchase'    => (float) ($p->purchase_price ?? 0),
    'unit'        => $p->unit ?? 'Piece',
    'stock'       => (int) ($p->stocks_sum_quantity ?? 0),
    'category_id' => $p->category_id,
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
const R_STORE   = @json(route('sales.invoice.store'));
const R_DRAFT   = @json(route('sales.invoice.draft'));
const R_LIST    = @json(route('sales.invoice.view'));
const R_QUICK_CUST = @json(route('sales.quick.customer'));
const PRODUCTS  = JSON.parse(document.getElementById('productData').textContent);
const CATEGORIES= JSON.parse(document.getElementById('categoryData').textContent);

let rowCounter = 0;
let discountFocus = 'amt'; // 'pct' or 'amt'

/* ─────────────────────────── SELECT2 INIT ───────── */
$(document).ready(function() {
    $('#customerSelect').select2({
        placeholder: 'Search customer…',
        allowClear: true,
        width: '100%',
    }).on('select2:select', function(e) {
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

    // Add 3 rows by default
    addItemRow();
    addItemRow();
    addItemRow();

    // Set default Walk-in Customer name for Cash mode (default)
    const cashNameEl = document.getElementById('cashBillingName');
    if (cashNameEl) cashNameEl.value = 'Walk-in Customer';

    // Initial state
    setSaleType('cash');

    // Summary listeners
    document.getElementById('discountInput').addEventListener('input', function() {
        recalcAll();
    });


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

function buildItemOptions(catId) {
    let html = `<option value="">Select Item</option>`;
    PRODUCTS.forEach(p => {
        html += `<option value="${p.id}"
            data-price="${p.price}"
            data-purchase="${p.purchase}"
            data-code="${p.code}"
            data-unit="${p.unit}"
            data-stock="${p.stock}"
            data-catid="${p.category_id}">${p.name}</option>`;
    });
    return html;
}

/* ─────────────────────────── ADD ROW ────────────── */
function addItemRow() {
    rowCounter++;
    const n = rowCounter;
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.row = n;

    tr.innerHTML = `
        <td class="px-4 py-3 text-[11px] font-black text-gray-400 text-center border-r border-gray-100 row-num">${n}</td>

        <td class="px-2 py-1 border-r border-gray-100 hidden col-cat">
            <input type="text" class="tbl-clean-input cat-input" placeholder="Category">
        </td>

        <td class="px-2 py-1 border-r border-gray-100">
            <select class="item-select" data-row="${n}" onchange="onItemChange(this, ${n})">
                ${buildItemOptions('ALL')}
            </select>
        </td>

        <td class="px-2 py-1 border-r border-gray-100 hidden col-code text-center">
            <input type="text" class="tbl-clean-input code-input" placeholder="Code">
        </td>

        <td class="px-2 py-1 border-r border-gray-100 hidden col-desc">
            <input type="text" class="tbl-clean-input desc-input" placeholder="Add description">
        </td>

        <td class="px-2 py-1 border-r border-gray-100 text-center">
            <input type="number" class="tbl-clean-input qty-input" value="1" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, ''); calcRow(${n})">
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
            <input type="number" class="tbl-clean-input price-input" value="0" min="0" step="0.01" oninput="calcRow(${n})">
        </td>

        <td class="px-2 py-1 border-r border-gray-100 hidden col-disc text-center">
            <div class="flex items-center gap-1 justify-center">
                <input type="number" class="tbl-clean-input row-disc-input row-disc-input-w" value="0" min="0" step="0.01" oninput="calcRow(${n})">
                <button type="button" class="text-[10px] font-black text-primary row-disc-toggle" onclick="toggleRowDisc(this, ${n})">Amt</button>
            </div>
        </td>

        <td class="px-4 py-1 border-r border-gray-100 text-center hidden col-profit">
            <span class="text-[13px] font-black text-accent row-profit" data-val="0">0.00</span>
        </td>

        <td class="px-4 py-1 border-r border-gray-100 text-right">
            <span class="text-[13px] font-black text-[#004161] row-amount" data-val="0">0.00</span>
        </td>

        <td class="px-2 py-1 text-center">
            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-primary hover:bg-primary/10 transition-all" onclick="removeRow(this)">
                <i class="bi bi-trash3 text-[14px]"></i>
            </button>
        </td>
    `;


    document.getElementById('itemsTbody').appendChild(tr);

    // Init Select2 for item select
    const $sel = $(tr).find('.item-select');
    $sel.select2({
        placeholder: 'Search and Select Item',
        width: '100%',
        dropdownAutoWidth: true,
        templateResult: formatProductOption,
        templateSelection: (state) => state.text.split('(')[0].trim()
    }).on('select2:select', function() {
        onItemChange(this, n);
    }).on('select2:open', function() {
        // Inject header if not exists
        setTimeout(() => {
            const $results = $('.select2-results__options');
            if ($results.length && !$results.find('.select2-item-header').length) {
                $results.prepend(`
                    <div class="s2-header-row">
                        <div><button type="button" class="s2-add-btn" onclick="openAddPartyModal()"><i class="bi bi-plus-circle-fill"></i> ADD ITEM</button></div>
                        <div class="s2-header-cols">
                            <span class="s2-span-sale">SALE PRICE</span>
                            <span class="s2-span-purchase">PURCHASE PRICE</span>
                            <span class="s2-span-stock">STOCK</span>
                        </div>
                    </div>
                `);
            }
        }, 1);
    });

    renumberRows();
    recalcAll();
}

function formatProductOption(state) {
    if (!state.id) return state.text;
    const opt      = $(state.element);
    const code     = opt.data('code')     || 'N/A';
    const price    = parseFloat(opt.data('price'))    || 0;
    const purchase = parseFloat(opt.data('purchase')) || 0;
    const stock    = parseInt(opt.data('stock'))      || 0;
    const lowStock = stock <= 0 ? ' low' : '';

    return $(`
        <div class="product-res">
            <div class="product-res-info">
                <div class="product-res-name">${state.text}</div>
                <div class="product-res-code">${code}</div>
            </div>
            <div class="product-res-meta">
                <div class="product-res-col product-res-col-sale">
                    <div class="product-res-label">SALE PRICE</div>
                    <div class="product-res-val">${price.toFixed(2)}</div>
                </div>
                <div class="product-res-col product-res-col-purchase">
                    <div class="product-res-label">PURCHASE PRICE</div>
                    <div class="product-res-val">${purchase.toFixed(2)}</div>
                </div>
                <div class="product-res-col product-res-col-stock">
                    <div class="product-res-label">STOCK</div>
                    <div class="product-res-val product-res-stock${lowStock}">${stock}</div>
                </div>
            </div>
        </div>
    `);
}

/* ─────────────────────────── CATEGORY CHANGE ────── */


/* ─────────────────────────── ITEM CHANGE ────────── */
function onItemChange(sel, rn) {
    const opt   = $(sel).find(':selected');
    if (!opt.val()) return;
    const row   = document.querySelector(`tr[data-row="${rn}"]`);

    row.querySelector('.price-input').value = opt.data('price') || 0;

    // Set unit
    const unitSel = row.querySelector('.unit-input');
    const unit    = opt.data('unit') || '';
    for (let o of unitSel.options) {
        if (o.value.toLowerCase() === unit.toLowerCase()) { o.selected = true; break; }
    }

    calcRow(rn);
}

/* ─────────────────────────── REMOVE ROW ─────────── */
function removeRow(btn) {
    const rows = document.querySelectorAll('#itemsTbody .item-row');
    if (rows.length <= 1) {
        toastWarn('At least one row is required.');
        return;
    }

    // Find the row closest to the clicked button
    const row = btn.closest('tr');
    if (!row) return;

    // Destroy Select2 before removing row to prevent memory leaks/zombie elements
    $(row).find('select').each(function() {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });

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

    // Calculate Profit
    const opt = $(row.querySelector('.item-select')).find(':selected');
    const purchase = parseFloat(opt.data('purchase')) || 0;
    const rowCost = qty * purchase;
    const rowProfit = rowTotal - rowCost;

    row.querySelector('.row-amount').textContent = rowTotal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
    row.querySelector('.row-amount').dataset.val  = rowTotal.toFixed(2);
    
    const profitEl = row.querySelector('.row-profit');
    if (profitEl) {
        profitEl.textContent = rowProfit.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
        profitEl.dataset.val = rowProfit.toFixed(2);
    }

    recalcAll();
}

/* ─────────────────────────── GLOBAL CALC ────────── */
function recalcAll() {
    let subtotal = 0;
    let totalQty = 0;
    let totalProfit = 0;

    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const amt = parseFloat(row.querySelector('.row-amount').dataset.val) || 0;
        const profit = parseFloat(row.querySelector('.row-profit')?.dataset.val) || 0;
        subtotal += amt;
        totalProfit += profit;
        totalQty += parseFloat(row.querySelector('.qty-input').value) || 0;
    });

    // Global discount
    const discAmt = parseFloat(document.getElementById('discountInput')?.value) || 0;
    const afterDisc= Math.max(0, subtotal - discAmt);

    // Tax
    const taxRate  = 0;
    const taxAmt   = afterDisc * taxRate / 100;

    let grandTotal = afterDisc + taxAmt;

    // Auto-set paid amount if Cash
    const paidInput = document.getElementById('paidAmountInput');
    if (_saleType === 'cash') {
        paidInput.value = grandTotal.toFixed(2);
    }

    const paid     = parseFloat(paidInput.value) || 0;
    const balanceDue = Math.max(0, grandTotal - paid);

    document.getElementById('subtotalDisplay').textContent   = subtotal.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = CURR + ' ' + grandTotal.toFixed(2);
    document.getElementById('grandTotalVal').value           = grandTotal.toFixed(2);
    document.getElementById('taxVal').value                  = taxAmt.toFixed(2);
    document.getElementById('balanceDueDisplay').textContent = balanceDue.toFixed(2);

    // Total customer balance = previous balance + balance due on this invoice
    const prevBal      = parseFloat(window._customerPrevBalance) || 0;
    const totalCustBal = prevBal + balanceDue;
    document.getElementById('totalCustomerBalDisplay').textContent = CURR + ' ' + totalCustBal.toFixed(2);

    // Footer row
    document.getElementById('footerQty').textContent    = Math.round(totalQty);
    document.getElementById('footerAmount').textContent = subtotal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (document.getElementById('footerProfit')) {
        document.getElementById('footerProfit').textContent = totalProfit.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
}



function toggleRowDisc(btn, rn) {
    const isActive = btn.classList.toggle('active');
    btn.textContent = isActive ? '%' : 'Amt';
    calcRow(rn);
}

/* ─────────────────────────── SUBMIT ─────────────── */
function collectFormData(isDraft = false) {
    const customerId = document.getElementById('customerSelect').value;
    const isCredit   = document.getElementById('saleTypeInput').value === 'credit';
    if (!isDraft && isCredit && !customerId) { toastError('Please select a customer for credit sales.'); return null; }

    const branchId = document.getElementById('branchSelect').value;

const items = [];
    let valid = true;
    document.querySelectorAll('#itemsTbody .item-row').forEach(row => {
        const itemSel = row.querySelector('.item-select');
        const qty     = parseFloat(row.querySelector('.qty-input').value) || 0;
        if (!itemSel.value) return; // skip blank rows
        if (qty <= 0) { toastError('Quantity must be greater than 0.'); valid = false; return; }

        const opt     = $(itemSel).find(':selected');
        const isPct   = row.querySelector('.row-disc-toggle').classList.contains('active');
        const disc    = parseFloat(row.querySelector('.row-disc-input').value) || 0;
        const price   = parseFloat(row.querySelector('.price-input').value) || 0;
        const discAmt = isPct ? (qty * price * disc / 100) : disc;

        items.push({
            product_id:   itemSel.value,
            product_name: opt.text().split('(')[0].trim(),
            product_code: row.querySelector('.code-input').value,
            unit:         row.querySelector('.unit-input').value || 'Piece',
            quantity:     qty,
            unit_price:   price,
            discount:     discAmt,
            total_price:  parseFloat(row.querySelector('.row-amount').dataset.val) || 0,
        });
    });

    if (!valid) return null;
    if (!isDraft && items.length === 0) { toastError('Please add at least one product.'); return null; }

    const invDateEl = isCredit
        ? document.getElementById('invoiceDateCredit')
        : document.getElementById('invoiceDateCash');
    const payMethEl = document.getElementById('paymentMethodInput');
    const notesEl   = document.querySelector('textarea[name="notes"]');
    let notes = notesEl ? notesEl.value : '';

    // If Cash Sale, collect and prepend walk-in details to notes
    if (!isCredit) {
        const cashName = document.getElementById('cashBillingName').value.trim();
        const cashPhone = document.getElementById('cashPhone').value.trim();

        let details = [];
        if (cashName) details.push("Name: " + cashName);
        if (cashPhone) details.push("Phone: " + cashPhone);

        if (details.length > 0) {
            notes = " WALK-IN DETAILS: \n" + details.join(" | ") + "\n----------------\n" + notes;
        }
    }

    // Pick the correct payment account based on sale type
    const isCredit2 = document.getElementById('saleTypeInput').value === 'credit';
    const payAccId  = isCredit2
        ? (document.getElementById('paymentAccountSelect')?.value || '')
        : (document.getElementById('paymentAccountSelectCash')?.value || '');

    return {
        _token:         CSRF,
        customer_id:    customerId,
        branch_id:      branchId,
        payment_account_id: payAccId,
        invoice_date:   invDateEl ? invDateEl.value : new Date().toISOString().split('T')[0],
        payment_method: document.getElementById('paymentMethodInput')?.value || (_saleType === 'cash' ? 'Cash' : 'Bank Transfer'),
        discount:       parseFloat(document.getElementById('discountInput')?.value) || 0,
        tax:            parseFloat(document.getElementById('taxVal')?.value) || 0,
        total_amount:   parseFloat(document.getElementById('grandTotalVal')?.value) || 0,
        paid_amount:    parseFloat(document.getElementById('paidAmountInput')?.value) || 0,
        notes:          notes,
        items,
    };
}

function submitForm(mode) {
    const data = collectFormData();
    if (!data) return;

    const btns = document.querySelectorAll('.btn-action');
    btns.forEach(b => b.disabled = true);

    $.ajax({
        url: R_STORE,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(res) {
            if (mode === 'print') {
                Swal.fire({ icon: 'success', title: 'Invoice Saved!', text: 'Redirecting...', timer: 1200, showConfirmButton: false })
                    .then(() => window.location.href = R_LIST);
            } else {
                Swal.fire({ icon: 'success', title: 'Invoice Created!', confirmButtonColor: '#004161' })
                    .then(() => window.location.href = R_LIST);
            }
        },
        error: function(xhr) {
            btns.forEach(b => b.disabled = false);
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : (xhr.responseJSON?.message || 'Failed to save invoice.'));
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
            Swal.fire({ icon: 'success', title: 'Draft Saved!', text: 'Invoice saved as draft.', confirmButtonColor: '#004161' })
                .then(() => window.location.href = R_LIST);
        },
        error: function(xhr) {
            btns.forEach(b => b.disabled = false);
            toastError(xhr.responseJSON?.message || 'Failed to save draft.');
        }
    });
}

/* ─────────────────────────── SALE TYPE TOGGLE ── */
let _saleType = 'cash';

function setSaleType(type) {
    _saleType = type;
    const knob    = document.getElementById('saleTypeKnob');
    const toggle  = document.getElementById('saleTypeToggle');
    const lCredit = document.getElementById('labelCredit');
    const lCash   = document.getElementById('labelCash');
    const input   = document.getElementById('saleTypeInput');
    const pmSel   = document.getElementById('paymentMethodInput');

    const section = document.getElementById('customerSection');
    const cashSection = document.getElementById('cashDetailsSection');
    const star    = document.getElementById('customerRequiredStar');

    if (type === 'cash') {
        knob.classList.remove('is-credit');
        toggle.classList.remove('is-active');
        lCredit.classList.replace('toggle-label-on', 'toggle-label-off');
        lCash.classList.replace('toggle-label-off', 'toggle-label-on');
        if (pmSel) pmSel.value = 'Cash';

        // Toggle areas
        section.classList.add('hidden');
        cashSection.classList.remove('hidden');
        if (star) star.classList.add('hidden');
        document.getElementById('paidAmountWrapper').classList.replace('flex', 'hidden');
        document.getElementById('totalBalanceWrapper').classList.replace('flex', 'hidden');

        // Clear customer selection when hidden to ensure it's a walk-in sale
        $('#customerSelect').val(null).trigger('change');

        // Default the billing name to Walk-in Customer
        const cashNameEl = document.getElementById('cashBillingName');
        if (cashNameEl && cashNameEl.value.trim() === '') {
            cashNameEl.value = 'Walk-in Customer';
        }
    } else {
        knob.classList.add('is-credit');
        toggle.classList.add('is-active');
        lCredit.classList.replace('toggle-label-off', 'toggle-label-on');
        lCash.classList.replace('toggle-label-on', 'toggle-label-off');
        if (pmSel) pmSel.value = 'Bank Transfer';

        // Toggle areas
        section.classList.remove('hidden');
        cashSection.classList.add('hidden');
        if (star) star.classList.remove('hidden');
        document.getElementById('paidAmountWrapper').classList.replace('hidden', 'flex');
        document.getElementById('totalBalanceWrapper').classList.replace('hidden', 'flex');
    }
    input.value = type;
    recalcAll();
}

function flipSaleType() {
    setSaleType(_saleType === 'credit' ? 'cash' : 'credit');
}


/* ─────────────────────────── ADD PARTY MODAL ───── */
function openAddCustomerModal() {
    document.getElementById('addPartyModal').classList.remove('hidden');
    document.getElementById('newPartyName').focus();
}

function closeAddPartyModal() {
    document.getElementById('addPartyModal').classList.add('hidden');
    // Clear inputs
    document.getElementById('newPartyName').value = '';
    document.getElementById('newPartyPhone').value = '';
    document.getElementById('newPartyBalance').value = '0';
}

function saveNewParty() {
    const name    = document.getElementById('newPartyName').value.trim();
    const phone   = document.getElementById('newPartyPhone').value.trim();
    const balance = parseFloat(document.getElementById('newPartyBalance').value) || 0;

    if (!name) { toastError('Customer name is required.'); return; }
    if (!phone) { toastError('Phone number is required.'); return; }

    const btn = document.getElementById('savePartyBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1 animate-spin"></i> Saving…';

    $.ajax({
        url: R_QUICK_CUST,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: { name, phone, amount_balance: balance },
        success: function(res) {
            toastSuccess('New party added successfully.');

            // Add to Select2 and select it
            const newOption = new Option(res.name, res.id, true, true);
            $(newOption).data('phone', res.phone || '');
            $(newOption).data('balance', res.balance || 0);
            $(newOption).data('name', res.name);
            $('#customerSelect').append(newOption).trigger('change');

            // Trigger the select2:select logic manually since we appended/selected
            $('#customerSelect').trigger({
                type: 'select2:select',
                params: { data: { id: res.id, text: res.name } }
            });

            closeAddPartyModal();
        },
        error: function(xhr) {
            const errs = xhr.responseJSON?.errors;
            toastError(errs ? Object.values(errs).flat().join(' | ') : 'Failed to save party.');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle mr-1"></i> Save Customer';
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
