@extends('admin.admin_master')
@section('page_title', 'Purchase Returns')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    activeModal: null,
    saving: false,
    savingEdit: false,
    search: '',
    bills: @js($bills),
    returns: @js($returns),
    currency: @js($currency),
    cashAccounts: @js($cashAccounts),
    selectedBillId: '',
    returnDate: '{{ date('Y-m-d') }}',
    returnReason: '',
    returnType: 'credit',
    refundAccountId: '',
    returnItems: [],
    editReturn: {},

    get filteredReturns() {
        if (!this.search) return this.returns;
        const q = this.search.toLowerCase();
        return this.returns.filter(r =>
            (r.return_number || '').toLowerCase().includes(q) ||
            (r.supplier?.name || '').toLowerCase().includes(q) ||
            (r.bill?.bill_number || '').toLowerCase().includes(q)
        );
    },
    get selectedBill() {
        return this.bills.find(b => b.id == this.selectedBillId);
    },
    get selectedSupplierBalance() {
        return this.selectedBill?.supplier ? (parseFloat(this.selectedBill.supplier.amount_balance) || 0) : 0;
    },
    get returnTotal() {
        return this.returnItems.reduce((acc, item) => acc + (parseFloat(item.return_qty || 0) * parseFloat(item.rate || 0)), 0);
    },
    updateBillItems() {
        if (!this.selectedBill) { this.returnItems = []; return; }
        const balance = parseFloat(this.selectedBill.balance_amount || 0);
        const paid = parseFloat(this.selectedBill.paid_amount || 0);
        this.returnType = (balance <= 0 && paid > 0) ? 'cash' : 'credit';
        this.refundAccountId = '';
        const billBranchId = this.selectedBill.branch_id;
        this.returnItems = this.selectedBill.items.map(item => {
            const alreadyReturned = (item.return_items || []).reduce((sum, ri) => sum + parseFloat(ri.quantity || 0), 0);
            const remainingFromBill = item.quantity - alreadyReturned;
            const stocks = (item.product && item.product.stocks) ? item.product.stocks : [];
            const branchStock = stocks.find(s => s.branch_id == billBranchId);
            const actualStock = branchStock ? parseFloat(branchStock.quantity) : 0;
            const available = Math.min(remainingFromBill, actualStock);
            return {
                id: item.id,
                product_id: item.product_id,
                name: item.product ? item.product.product_name : (item.product_name || 'Generic Product'),
                original_qty: item.quantity,
                already_returned_qty: alreadyReturned,
                stock_qty: actualStock,
                remaining_qty: available,
                return_qty: available,
                rate: item.unit_price,
            };
        }).filter(i => i.remaining_qty > 0);
    },
    openCreateModal() {
        this.activeModal = 'create';
        this.selectedBillId = '';
        this.returnDate = '{{ date('Y-m-d') }}';
        this.returnReason = '';
        this.returnItems = [];
        this.returnType = 'credit';
        this.refundAccountId = '';
    },
    async submitReturn() {
        if (!this.selectedBillId || !this.returnDate || !this.returnReason || this.returnItems.length === 0) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Please fill all required fields and make sure the bill has returnable items.' });
            return;
        }
        if (this.returnType === 'cash' && !this.refundAccountId) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Please select a Refund Account for the cash refund.' });
            return;
        }
        this.saving = true;
        try {
            const response = await fetch('{{ route('purchase.return.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify({
                    purchase_bill_id: this.selectedBillId,
                    return_date: this.returnDate,
                    reason: this.returnReason,
                    return_type: this.returnType,
                    refund_account_id: this.returnType === 'cash' ? this.refundAccountId : null,
                    items: this.returnItems.map(i => ({
                        product_id: i.product_id,
                        bill_item_id: i.id,
                        quantity: i.return_qty,
                        unit_price: i.rate,
                    })),
                }),
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
    openEditModal(r) {
        this.editReturn = {
            id: r.id,
            return_number: r.return_number,
            return_date: r.return_date ? r.return_date.substring(0, 10) : '',
            due_date: r.due_date ? r.due_date.substring(0, 10) : '',
            reason: r.reason || '',
            status: r.status || 'pending',
            notes: r.notes || '',
        };
        this.activeModal = 'edit';
    },
    async submitEditReturn() {
        if (!this.editReturn.return_date || !this.editReturn.reason) {
            Swal.fire({ icon: 'error', title: 'Missing info', text: 'Please fill all required fields.' });
            return;
        }
        this.savingEdit = true;
        try {
            const response = await fetch('{{ url('/purchase/returns') }}/' + this.editReturn.id, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: JSON.stringify({
                    return_date: this.editReturn.return_date,
                    due_date: this.editReturn.due_date,
                    reason: this.editReturn.reason,
                    status: this.editReturn.status,
                    notes: this.editReturn.notes,
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
        deleteRecordWithPassword('{{ url('/purchase/returns') }}/' + id, code, {
            title: 'Delete Return Record?',
            text: 'Are you sure you want to delete ' + code + '? This action is irreversible.'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Purchase Returns</h1>
        <button @click="openCreateModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Return
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-arrow-return-left text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Total Value</p>
            <p class="text-[16px] font-black text-primary" x-text="currency + ' ' + returns.reduce((acc, r) => acc + parseFloat(r.total_amount), 0).toLocaleString(undefined, {maximumFractionDigits: 0})"></p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-clock-history text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Pending</p>
            <p class="text-[16px] font-black text-primary" x-text="returns.filter(r => r.status === 'pending').length"></p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-shield-check text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Success</p>
            <p class="text-[16px] font-black text-primary" x-text="returns.filter(r => ['approved','active','refunded'].includes(r.status)).length"></p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH BILL OR SUPPLIER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
    </div>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Return Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="r in filteredReturns" :key="r.id">
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate" x-text="r.supplier?.name || 'Unknown Entity'"></p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate" x-text="r.return_number + ' · ' + (r.bill?.bill_number || 'No Ref')"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary" x-text="currency + ' ' + parseFloat(r.total_amount).toFixed(2)"></p>
                        <p class="text-xs text-text-secondary mt-0.5" x-text="new Date(r.return_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                        :class="['approved','active','refunded'].includes(r.status) ? 'bg-accent/10 text-accent' : (r.status === 'pending' ? 'bg-gray-100 text-gray-400' : 'bg-red-50 text-red-500')"
                        x-text="r.status"></span>
                    <div class="flex items-center gap-2">
                        <a :href="`{{ url('/purchase/returns') }}/${r.id}/view`" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        <button type="button" @click="openEditModal(r)"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteReturn(r.id, r.return_number)"
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
                <p class="text-sm text-text-secondary mt-2 font-semibold">No return records found.</p>
            </div>
        </template>
    </div>

    {{-- New Return — mobile bottom sheet --}}
    <div x-show="activeModal === 'create'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'create'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Purchase Return</h2>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Purchase Bill <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="selectedBillId" @change="updateBillItems()"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Purchase Bill --</option>
                            <template x-for="bill in bills" :key="bill.id">
                                <option :value="bill.id" x-text="bill.bill_number + ' — ' + (bill.supplier ? bill.supplier.name : 'Unknown')"></option>
                            </template>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                    <p class="text-[11px] font-black text-accent uppercase tracking-tight mt-1.5" x-show="selectedBill">
                        BAL: <span x-text="currency + ' ' + selectedSupplierBalance.toFixed(2)"></span>
                    </p>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Reason <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="returnReason"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select --</option>
                            <option value="damaged">Damaged on Arrival</option>
                            <option value="technical">Defective / Not Working</option>
                            <option value="wrong_sku">Wrong Item Delivered</option>
                            <option value="quality">Quality Issue</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Date <span class="text-primary">*</span></label>
                    <input type="date" x-model="returnDate" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div x-show="selectedBillId">
                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Return Items</p>
                    <template x-if="!returnItems.length">
                        <div class="py-8 text-center border border-dashed border-gray-200 rounded-xl">
                            <i class="bi bi-box-seam text-2xl text-gray-300"></i>
                            <p class="text-xs text-gray-400 mt-1.5 font-semibold">No returnable items on this bill.</p>
                        </div>
                    </template>
                    <template x-for="(item, index) in returnItems" :key="index">
                        <div class="bg-gray-50 rounded-xl p-3.5 mb-2.5 border border-gray-100">
                            <p class="text-[13px] font-black text-primary-dark" x-text="item.name"></p>
                            <p class="text-[10px] text-gray-400 font-semibold mt-0.5">
                                <span x-text="'Ordered: ' + item.original_qty"></span>
                                <span class="ml-2" x-text="'In Stock: ' + item.stock_qty"></span>
                                <span class="ml-2 text-accent font-black" x-text="'Returnable: ' + item.remaining_qty"></span>
                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                <div class="flex-1">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">Return Qty</label>
                                    <input type="number" x-model="item.return_qty" :max="item.remaining_qty" min="0"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] font-bold text-primary-dark text-center outline-none">
                                </div>
                                <div class="flex-1 text-right">
                                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 block">Amount</label>
                                    <p class="text-[14px] font-black text-primary-dark" x-text="currency + ' ' + ((parseFloat(item.return_qty||0) * parseFloat(item.rate))).toFixed(2)"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Settlement Method</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="returnType = 'credit'; refundAccountId = ''"
                            :class="returnType === 'credit' ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-500 border-gray-200'"
                            class="px-3 py-2.5 rounded-lg border text-[12px] font-bold uppercase tracking-wide transition-all">
                            <i class="bi bi-file-earmark-minus"></i> Credit
                        </button>
                        <button type="button" @click="returnType = 'cash'"
                            :class="returnType === 'cash' ? 'bg-accent text-primary border-accent' : 'bg-gray-50 text-gray-500 border-gray-200'"
                            class="px-3 py-2.5 rounded-lg border text-[12px] font-bold uppercase tracking-wide transition-all">
                            <i class="bi bi-cash-coin"></i> Cash Refund
                        </button>
                    </div>
                </div>

                <div x-show="returnType === 'cash'" x-transition>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Refund Account <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="refundAccountId"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Cash / Bank Account --</option>
                            <template x-for="acc in cashAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.name + (acc.type === 'bank' ? ' (Bank)' : ' (Cash)')"></option>
                            </template>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="rounded-xl px-4 py-3 flex items-center justify-between" :class="returnType === 'cash' ? 'bg-accent' : 'bg-primary'">
                    <span class="text-[11px] font-black uppercase tracking-wider" :class="returnType === 'cash' ? 'text-primary-dark' : 'text-white'">Return Total</span>
                    <span class="text-[15px] font-black" :class="returnType === 'cash' ? 'text-primary-dark' : 'text-accent'" x-text="currency + ' ' + returnTotal.toFixed(2)"></span>
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
                        <span x-text="saving ? 'Processing...' : 'Process Return'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Return — mobile bottom sheet --}}
    <div x-show="activeModal === 'edit'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'edit'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Update Return</h2>
                <p class="text-xs text-white/60 font-medium mt-0.5" x-text="'Ref: ' + editReturn.return_number"></p>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Date <span class="text-primary">*</span></label>
                    <input type="date" x-model="editReturn.return_date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Status <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="editReturn.status"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="refunded">Refunded</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Return Reason <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select x-model="editReturn.reason"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="damaged">Physical Damage on Arrival</option>
                            <option value="technical">Technical Malfunction / Defect</option>
                            <option value="wrong_sku">SKU Mismatch (Wrong Item)</option>
                            <option value="quality">Quality Control Violation</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Due Date <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                    <input type="date" x-model="editReturn.due_date"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Notes</label>
                    <textarea x-model="editReturn.notes" rows="3" placeholder="Optional notes..."
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
