@extends('admin.admin_master')
@section('page_title', 'Sales Returns')

@section('admin')
@php
    $currencySymbols = [
        'USD'=>'$','EUR'=>'€','GBP'=>'£','JPY'=>'¥','AUD'=>'A$','CAD'=>'C$',
        'CHF'=>'Fr','CNY'=>'¥','INR'=>'₹','MYR'=>'RM','SGD'=>'S$','AED'=>'د.إ',
        'SAR'=>'﷼','NGN'=>'₦','KES'=>'KSh','ZAR'=>'R',
    ];
    $symbol = $currencySymbols[$company->currency ?? 'USD'] ?? ($company->currency ?? '$');
@endphp

<div class="pb-28 bg-background min-h-screen" x-data="{
    activeModal: null,
    saving: false,
    savingEdit: false,
    search: '',
    returns: @js($returns->map(fn($r) => [
        'id' => $r->id,
        'credit_note_no' => $r->credit_note_no,
        'customer' => ['name' => $r->customer->name ?? '—', 'phone' => $r->customer->phone ?? ''],
        'invoice_no' => $r->invoice->invoice_no ?? '—',
        'reason' => $r->reason,
        'return_date' => $r->return_date,
        'amount' => (float) $r->amount,
        'status' => $r->status,
        'notes' => $r->notes ?? '',
        'items' => $r->items->map(fn($i) => [
            'id' => $i->id,
            'product_id' => $i->product_id,
            'product_name' => $i->product->product_name ?? '—',
            'quantity' => (float) $i->quantity,
            'unit_price' => (float) $i->unit_price,
            'subtotal' => (float) $i->subtotal,
        ])->values(),
    ])),
    invoices: @js($invoices),
    currency: @js($symbol),
    selectedInvoiceId: '',
    returnDate: '{{ date('Y-m-d') }}',
    returnReason: '',
    returnAmount: '',
    returnItems: [],
    editReturn: {},
    editItems: [],

    get filteredReturns() {
        if (!this.search) return this.returns;
        const q = this.search.toLowerCase();
        return this.returns.filter(r =>
            (r.credit_note_no || '').toLowerCase().includes(q) ||
            (r.customer?.name || '').toLowerCase().includes(q) ||
            (r.invoice_no || '').toLowerCase().includes(q)
        );
    },
    get selectedInvoice() {
        return this.invoices.find(inv => inv.id == this.selectedInvoiceId);
    },
    get selectedReturnItems() {
        return this.returnItems.filter(i => i.selected && i.return_qty > 0);
    },
    get calculatedAmount() {
        return this.selectedReturnItems.reduce((sum, i) => sum + (parseFloat(i.return_qty || 0) * parseFloat(i.rate || 0)), 0);
    },
    get editTotal() {
        return this.editItems.reduce((s, i) => s + (parseFloat(i.quantity) || 0) * parseFloat(i.unit_price), 0);
    },
    updateInvoiceItems() {
        if (!this.selectedInvoice) { this.returnItems = []; return; }
        this.returnItems = this.selectedInvoice.items.map(item => {
            const alreadyReturned = (item.return_items || []).reduce((sum, ri) => sum + parseFloat(ri.quantity || 0), 0);
            const remaining = item.quantity - alreadyReturned;
            return {
                id: item.id,
                product_id: item.product_id,
                name: item.product ? item.product.product_name : (item.product_name || 'Product'),
                remaining_qty: remaining,
                return_qty: remaining,
                rate: item.unit_price,
                selected: remaining > 0,
            };
        }).filter(i => i.remaining_qty > 0);
    },
    openCreateModal() {
        this.activeModal = 'create';
        this.selectedInvoiceId = '';
        this.returnItems = [];
        this.returnDate = '{{ date('Y-m-d') }}';
        this.returnReason = '';
        this.returnAmount = '';
    },
    async submitReturn() {
        const custId = document.getElementById('mobileCustomerSelect').value;
        if (!custId || !this.returnReason || !this.returnDate) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Please select a customer, reason and date.' });
            return;
        }
        let payload = {
            customer_id: custId,
            invoice_id: this.selectedInvoiceId || null,
            reason: this.returnReason,
            return_date: this.returnDate,
            notes: document.getElementById('mobileReturnNotes').value,
        };
        if (this.selectedInvoiceId) {
            if (this.selectedReturnItems.length === 0) {
                Swal.fire({ icon: 'error', title: 'Missing info', text: 'Select at least one item to return, with a quantity greater than 0.' });
                return;
            }
            payload.items = this.selectedReturnItems.map(i => ({
                order_item_id: i.id,
                product_id: i.product_id,
                quantity: i.return_qty,
                unit_price: i.rate,
            }));
        } else if (!this.returnAmount || parseFloat(this.returnAmount) <= 0) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Enter a return amount, or select an invoice to return specific items.' });
            return;
        } else {
            payload.amount = this.returnAmount;
        }

        this.saving = true;
        try {
            const response = await fetch('{{ route('sales.return.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Something went wrong.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Success', text: data.message }).then(() => window.location.reload());
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not process return. Please try again.' });
        } finally {
            this.saving = false;
        }
    },
    openViewModal(r) {
        this.editReturn = r;
        this.activeModal = 'view';
    },
    openEditModal(r) {
        this.editReturn = {
            id: r.id,
            credit_note_no: r.credit_note_no,
            return_date: r.return_date ? r.return_date.substring(0, 10) : '',
            reason: r.reason || '',
            status: r.status || 'approved',
            notes: r.notes || '',
        };
        this.editItems = (r.items || []).map(i => ({ ...i }));
        this.activeModal = 'edit';
    },
    async submitEditReturn() {
        if (!this.editReturn.return_date || !this.editReturn.reason) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Please fill all required fields.' });
            return;
        }
        this.savingEdit = true;
        try {
            const response = await fetch('{{ url('/sales-return') }}/' + this.editReturn.id, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify({
                    return_date: this.editReturn.return_date,
                    reason: this.editReturn.reason,
                    status: this.editReturn.status,
                    notes: this.editReturn.notes,
                    items: this.editItems.map(i => ({ id: i.id, quantity: i.quantity })),
                }),
            });
            const data = await response.json();
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Something went wrong.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Updated', text: data.message }).then(() => window.location.reload());
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not update return. Please try again.' });
        } finally {
            this.savingEdit = false;
        }
    },
    deleteReturn(id, code) {
        deleteRecordWithPassword('{{ url('/sales-return') }}/' + id, code, {
            title: 'Delete Credit Note?',
            text: 'This will reverse the journal entry and restore the customer balance. Cannot be undone.'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Sales Returns</h1>
        <button @click="openCreateModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Credit Note
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Return Value</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($stats['return_value'], 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-hourglass-split text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Pending</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-receipt text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Credit Notes</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['credit_notes']) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.return.view') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH CREDIT NOTE OR CUSTOMER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="status" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Credit Note Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="r in filteredReturns" :key="r.id">
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate" x-text="r.customer?.name || 'Unknown'"></p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate" x-text="r.credit_note_no + ' · ' + (r.invoice_no || 'No Ref')"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary" x-text="currency + ' ' + parseFloat(r.amount).toFixed(2)"></p>
                        <p class="text-xs text-text-secondary mt-0.5" x-text="new Date(r.return_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                        :class="r.status === 'approved' ? 'bg-accent/10 text-accent' : (r.status === 'pending' ? 'bg-gray-100 text-gray-400' : 'bg-red-50 text-red-500')"
                        x-text="r.status"></span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openViewModal(r)"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </button>
                        <button type="button" @click="openEditModal(r)"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteReturn(r.id, r.credit_note_no)"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="!filteredReturns.length">
            <div class="py-10 text-center">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No credit notes found.</p>
            </div>
        </template>
    </div>

    {{-- New Credit Note — mobile bottom sheet --}}
    <div x-show="activeModal === 'create'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'create'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Process Sales Return</h2>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Select Customer <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select id="mobileCustomerSelect"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select Customer...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Original Invoice</label>
                    <div class="relative">
                        <select x-model="selectedInvoiceId" @change="updateInvoiceItems()"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select Invoice (optional)...</option>
                            <template x-for="inv in invoices" :key="inv.id">
                                <option :value="inv.id" x-text="inv.invoice_no + ' — ' + (inv.customer ? inv.customer.name : '')"></option>
                            </template>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium mt-1">Pick an invoice to select which items are being returned and restock them.</p>
                </div>

                <div x-show="selectedInvoiceId" x-cloak class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                        <i class="bi bi-box-seam text-primary-dark text-sm"></i>
                        <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Items Being Returned</h2>
                    </div>

                    <template x-if="!returnItems.length">
                        <div class="py-8 text-center">
                            <i class="bi bi-box-seam text-2xl text-gray-300"></i>
                            <p class="text-xs text-gray-400 mt-1.5 font-semibold">Every item on this invoice has already been fully returned.</p>
                        </div>
                    </template>

                    <div class="divide-y divide-gray-100" x-show="returnItems.length">
                        <template x-for="(item, idx) in returnItems" :key="item.id">
                            <div class="flex items-center gap-2.5 px-4 py-3">
                                <input type="checkbox" x-model="item.selected" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-bold text-primary-dark truncate" x-text="item.name"></p>
                                    <p class="text-[10px] text-gray-400" x-text="'of ' + item.remaining_qty + ' available'"></p>
                                </div>
                                <input type="number" x-model="item.return_qty" :max="item.remaining_qty" min="0" step="0.01"
                                    class="w-16 px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark text-center outline-none shrink-0">
                                <span class="text-[12px] font-black text-primary-dark w-16 text-right shrink-0" x-text="currency + (parseFloat(item.return_qty||0) * parseFloat(item.rate)).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Reason <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="returnReason"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select reason...</option>
                            <option value="Defective">Defective Product</option>
                            <option value="Wrong">Wrong Item Delivered</option>
                            <option value="Damaged">Damaged in Transit</option>
                            <option value="Other">Other Reason</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Date <span class="text-primary">*</span></label>
                    <input type="date" x-model="returnDate" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div x-show="!selectedInvoiceId" x-cloak>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Amount <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $symbol }}</span>
                        <input type="number" x-model="returnAmount" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div x-show="selectedInvoiceId" x-cloak class="rounded-xl px-4 py-3 flex items-center justify-between bg-primary">
                    <span class="text-[11px] font-black uppercase tracking-wider text-white">Return Total</span>
                    <span class="text-[15px] font-black text-accent" x-text="currency + ' ' + calculatedAmount.toFixed(2)"></span>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Additional Notes</label>
                    <textarea id="mobileReturnNotes" rows="3" placeholder="Enter any additional notes..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="button" @click="submitReturn()" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Processing...' : 'Issue Credit Note'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- View Credit Note — mobile bottom sheet --}}
    <div x-show="activeModal === 'view'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'view'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10 flex items-center justify-between">
                <div>
                    <h2 class="text-white font-bold text-[16px]">Credit Note</h2>
                    <p class="text-xs text-white/60 font-medium mt-0.5" x-text="editReturn.credit_note_no"></p>
                </div>
                <a :href="'{{ url('/sales-return') }}/' + editReturn.id + '/pdf'" target="_blank"
                    class="w-9 h-9 flex items-center justify-center bg-white/10 border border-white/10 rounded-lg text-white">
                    <i class="bi bi-file-earmark-pdf"></i>
                </a>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div class="bg-gray-50 rounded-xl border border-gray-100 p-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wide mb-1">Customer</p>
                    <p class="text-[14px] font-black text-primary-dark" x-text="editReturn.customer?.name"></p>
                    <p class="text-[11px] text-gray-400 mt-0.5" x-text="editReturn.customer?.phone || 'N/A'"></p>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wide">Invoice Ref</p>
                            <p class="text-[12px] font-bold text-primary-dark mt-0.5" x-text="editReturn.invoice_no"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wide">Date</p>
                            <p class="text-[12px] font-bold text-primary-dark mt-0.5" x-text="editReturn.return_date"></p>
                        </div>
                    </div>
                </div>

                <template x-if="(editReturn.items || []).length > 0">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 bg-background/50">
                            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Returned Items</h2>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <template x-for="(item, idx) in (editReturn.items || [])" :key="idx">
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <div class="min-w-0 pr-2">
                                        <p class="text-[13px] font-bold text-primary-dark truncate" x-text="item.product_name"></p>
                                        <p class="text-[10px] text-gray-400" x-text="parseFloat(item.quantity).toFixed(2) + ' × ' + currency + parseFloat(item.unit_price).toFixed(2)"></p>
                                    </div>
                                    <span class="text-[13px] font-black text-primary-dark shrink-0" x-text="currency + parseFloat(item.subtotal).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="rounded-xl px-4 py-3 flex items-center justify-between bg-primary">
                    <span class="text-[11px] font-black uppercase tracking-wider text-white">Total Credit</span>
                    <span class="text-[16px] font-black text-accent" x-text="currency + ' ' + parseFloat(editReturn.amount || 0).toFixed(2)"></span>
                </div>

                <template x-if="editReturn.notes">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wide mb-1">Notes</p>
                        <p class="text-[13px] text-gray-600" x-text="editReturn.notes"></p>
                    </div>
                </template>

                <button type="button" @click="activeModal = null"
                    class="w-full py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Edit Credit Note — mobile bottom sheet --}}
    <div x-show="activeModal === 'edit'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'edit'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Update Credit Note</h2>
                <p class="text-xs text-white/60 font-medium mt-0.5" x-text="'Ref: ' + editReturn.credit_note_no"></p>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <template x-if="editItems.length > 0">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 bg-background/50">
                            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Items Being Returned</h2>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <template x-for="(item, idx) in editItems" :key="idx">
                                <div class="flex items-center gap-2.5 px-4 py-2.5">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-bold text-primary-dark truncate" x-text="item.product_name"></p>
                                    </div>
                                    <input type="number" x-model.number="item.quantity" min="0.01" step="0.01"
                                        class="w-16 px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-[12px] font-bold text-primary-dark text-center outline-none shrink-0">
                                    <span class="text-[12px] font-black text-primary-dark w-16 text-right shrink-0" x-text="currency + ((item.quantity || 0) * item.unit_price).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="rounded-xl px-4 py-3 flex items-center justify-between bg-primary/5 border border-primary/20">
                    <span class="text-[11px] font-black uppercase tracking-wider text-primary">Return Amount</span>
                    <span class="text-[15px] font-black text-primary" x-text="currency + ' ' + editTotal.toFixed(2)"></span>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Reason <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="editReturn.reason"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="Defective">Defective Product</option>
                            <option value="Wrong">Wrong Item Delivered</option>
                            <option value="Damaged">Damaged in Transit</option>
                            <option value="Other">Other Reason</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Status <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="editReturn.status"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Date <span class="text-primary">*</span></label>
                    <input type="date" x-model="editReturn.return_date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Notes</label>
                    <textarea x-model="editReturn.notes" rows="3" placeholder="Additional notes..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="button" @click="submitEditReturn()" :disabled="savingEdit"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="savingEdit ? 'opacity-60' : ''">
                        <i class="bi" :class="savingEdit ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="savingEdit ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
