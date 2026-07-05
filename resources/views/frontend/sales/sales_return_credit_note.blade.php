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

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen overflow-x-hidden" x-data="{
            showModal: false,
            showViewModal: false,
            showEditModal: false,
            activeReturn: null,
            editReason: '',
            editDate: '',
            editNotes: '',
            editStatus: 'approved',
            editItems: [],
            isEditSubmitting: false,

            get editTotal() {
                return this.editItems.reduce((s, i) => s + (parseFloat(i.quantity) || 0) * parseFloat(i.unit_price), 0).toFixed(2);
            },

            numToWords(amount) {
                const n = Math.floor(parseFloat(amount) || 0);
                const c = Math.round(((parseFloat(amount) || 0) - n) * 100);
                const ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                              'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                              'Seventeen','Eighteen','Nineteen'];
                const tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
                const hw = (x) => {
                    if (x === 0) return '';
                    let w = '';
                    if (x >= 1000000) { w += hw(Math.floor(x/1000000)) + ' Million '; x %= 1000000; }
                    if (x >= 1000)    { w += hw(Math.floor(x/1000))    + ' Thousand '; x %= 1000; }
                    if (x >= 100)     { w += ones[Math.floor(x/100)]   + ' Hundred '; x %= 100; }
                    if (x >= 20)      { w += tens[Math.floor(x/10)]    + ' '; x %= 10; }
                    if (x > 0)        { w += ones[x] + ' '; }
                    return w;
                };
                let r = hw(n).trim() + ' Dollar' + (n !== 1 ? 's' : '');
                if (c > 0) r += ' and ' + hw(c).trim() + ' Cent' + (c !== 1 ? 's' : '');
                return r + ' only';
            },

            openViewModal(r) { this.activeReturn = r; this.showViewModal = true; },
            openEditModal(r) {
                this.activeReturn = r;
                this.editReason = r.reason;
                this.editDate   = r.return_date;
                this.editNotes  = r.notes ?? '';
                this.editStatus = r.status ?? 'approved';
                this.editItems  = (r.items ?? []).map(i => ({ ...i, quantity: parseFloat(i.quantity) }));
                this.showEditModal = true;
            },
            async submitEdit() {
                this.isEditSubmitting = true;
                try {
                    const res = await axios.patch(`/sales-return/${this.activeReturn.id}`, {
                        reason: this.editReason,
                        return_date: this.editDate,
                        notes: this.editNotes,
                        status: this.editStatus,
                        items: this.editItems.map(i => ({ id: i.id, quantity: i.quantity })),
                        _token: document.querySelector('meta[name=csrf-token]').content,
                    });
                    Swal.fire('Updated', res.data?.message || 'Credit note updated.', 'success')
                        .then(() => window.location.reload());
                } catch (e) {
                    Swal.fire('Error', e.response?.data?.message || 'Something went wrong.', 'error');
                } finally {
                    this.isEditSubmitting = false;
                }
            },
            customerName: 'SELECT CUSTOMER',
            returnAmount: '',
            returnDate: '{{ date('Y-m-d') }}',
            invoices: @js($invoices),
            selectedInvoiceId: '',
            returnItems: [],
            isSubmitting: false,

            get selectedInvoice() {
                return this.invoices.find(inv => inv.id == this.selectedInvoiceId);
            },

            // Mirrors Purchase Return's item picker: load the chosen invoice's
            // lines, subtracting whatever has already been returned against
            // them, so the same item can't be returned twice.
            updateInvoiceItems() {
                if (this.selectedInvoice) {
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
                } else {
                    this.returnItems = [];
                }
            },

            get selectedReturnItems() {
                return this.returnItems.filter(i => i.selected && i.return_qty > 0);
            },

            get calculatedAmount() {
                return this.selectedReturnItems.reduce((sum, i) => sum + (parseFloat(i.return_qty || 0) * parseFloat(i.rate || 0)), 0);
            },

            openCreateModal() {
                this.showModal = true;
                this.selectedInvoiceId = '';
                this.returnItems = [];
                this.returnAmount = '';
                this.returnDate = '{{ date('Y-m-d') }}';
            },

            async submitReturn() {
                const form = document.getElementById('creditNoteForm');
                const data = Object.fromEntries(new FormData(form).entries());

                if (this.selectedInvoiceId) {
                    if (this.selectedReturnItems.length === 0) {
                        Swal.fire('Error', 'Select at least one item to return, with a quantity greater than 0.', 'error');
                        return;
                    }
                    data.items = this.selectedReturnItems.map(i => ({
                        order_item_id: i.id,
                        product_id: i.product_id,
                        quantity: i.return_qty,
                        unit_price: i.rate,
                    }));
                    delete data.amount;
                } else if (!data.amount || parseFloat(data.amount) <= 0) {
                    Swal.fire('Error', 'Enter a return amount, or select an invoice to return specific items.', 'error');
                    return;
                }

                this.isSubmitting = true;
                try {
                    const response = await axios.post('{{ route('sales.return.store') }}', data, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    Swal.fire('Success', response.data?.message || 'Credit note issued successfully.', 'success')
                        .then(() => window.location.reload());
                } catch (error) {
                    Swal.fire('Error', error.response?.data?.message || 'Something went wrong.', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            }
        }">

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Sales Return & Credit Note</h1>
                <p class="text-[13px] text-gray-500 mt-1">Manage product returns and store credits</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openCreateModal()" class="btn-premium-primary group">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>New Credit Note</span>
                </button>
            </div>
        </div>

        <!-- Premium Statistics Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Returns -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Returns</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['total_returns']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-clock-history text-xs"></i> This Month
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-arrow-return-left text-lg"></i>
                </div>
            </div>

            <!-- Return Value -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Return Value</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($stats['return_value'], 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-cash-stack text-xs"></i> Total value
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-currency-dollar text-lg"></i>
                </div>
            </div>

            <!-- Active Credit Notes -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Credit Notes</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['credit_notes']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-check-circle text-xs"></i> Successfully issued
                    </p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-receipt text-lg"></i>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Pending</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['pending']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                         <i class="bi bi-hourglass-split text-xs"></i> Awaiting Approval
                    </p>
                </div>
                <div class="w-11 h-11 bg-slate-50/50 rounded-[0.6rem] flex items-center justify-center text-primary/40 flex-shrink-0">
                    <i class="bi bi-patch-check text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            
            <!-- Filter Bar -->
            <form action="{{ route('sales.return.view') }}" method="GET"
                  x-data="{ hasFilters: {{ request()->hasAny(['search','status']) ? 'true' : 'false' }} }"
                  class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Search -->
                <div class="relative group min-w-[250px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search credit notes or customers..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[150px]">
                    <select name="status" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Clear Filters -->
                <button type="button" onclick="window.location.href='{{ route('sales.return.view') }}'"
                        x-show="hasFilters" x-transition
                        class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10"
                        title="Clear All Filters">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </form>

            <!-- Table List Header -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Credit Notes Records</h2>
            </div>

            <!-- Table -->
            <div class="overflow-y-auto overflow-x-auto" style="max-height:75vh;">
                <table class="w-full whitespace-nowrap text-left border-collapse">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Product Name</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Customer</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Reference Info</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Amount</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($returns as $key => $return)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($key + 1, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-0.5">
                                    @forelse($return->items as $item)
                                        <span class="text-[12px] font-semibold text-primary-dark">{{ $item->product->product_name ?? '—' }}</span>
                                    @empty
                                        <span class="text-[12px] text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $return->customer->name ?? '—' }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $return->customer->phone ?? '' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $return->invoice->invoice_no ?? '—' }}</span>
                                    <span class="text-[10px] text-gray-400">{{ $return->reason }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $symbol }} {{ number_format($return->amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @php
                                    $statusClass = match($return->status) {
                                        'approved' => 'status-completed',
                                        'pending'  => 'status-warning',
                                        default    => 'status-danger',
                                    };
                                    $statusLabel = match($return->status) {
                                        'approved' => 'APPROVED',
                                        'pending'  => 'PENDING',
                                        default    => 'REJECTED',
                                    };
                                @endphp
                                <span class="premium-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $returnJson = json_encode([
                                        'id'          => $return->id,
                                        'credit_note_no' => $return->credit_note_no,
                                        'customer'    => ['name' => $return->customer->name ?? '—', 'phone' => $return->customer->phone ?? ''],
                                        'invoice_no'  => $return->invoice->invoice_no ?? '—',
                                        'reason'      => $return->reason,
                                        'return_date' => $return->return_date,
                                        'amount'      => number_format($return->amount, 2),
                                        'status'      => $return->status,
                                        'notes'       => $return->notes ?? '',
                                        'items'       => $return->items->map(fn($i) => ['id' => $i->id, 'product_id' => $i->product_id, 'product_name' => $i->product->product_name ?? '—', 'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'subtotal' => $i->subtotal])->values(),
                                    ]);
                                @endphp
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="openViewModal({{ $returnJson }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all text-xs" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button @click="openEditModal({{ $returnJson }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-accent/10 text-primary hover:bg-accent hover:text-white transition-all text-xs" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button onclick="confirmDeleteReturn({{ $return->id }})" class="btn-action-delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="delete-return-{{ $return->id }}" action="{{ route('sales.return.destroy', $return->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-300">
                                    <i class="bi bi-inbox text-4xl"></i>
                                    <span class="text-xs font-semibold text-gray-400">No credit notes found</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Modal -->
        <template x-teleport="body">
        <div x-show="showViewModal" x-cloak
             @click.self="showViewModal = false"
             style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);">
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:90%;max-width:42rem;max-height:90vh;display:flex;flex-direction:column;border-radius:0.75rem;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.4);"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <!-- Invoice card — no wrapper, fills the container directly -->
                <div id="cn-printable" class="overflow-y-auto flex-1 min-h-0 bg-white">

                        {{-- Title --}}
                        <div class="text-center py-4 border-b border-gray-200">
                            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Credit Note</h2>
                        </div>

                        {{-- Company --}}
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-4">
                            <img src="{{ (!empty($company?->logo) && file_exists(public_path($company->logo))) ? asset($company->logo) : asset('upload/waafibooklogo/waafibook_logo.jpg') }}"
                                 class="w-16 h-16 object-contain border border-dashed border-gray-300 rounded flex-shrink-0">
                            <div>
                                <div class="text-xl font-black text-gray-800">{{ $company?->name ?? 'Company Name' }}</div>
                                <div class="text-sm text-gray-500 mt-1">Phone:&nbsp;&nbsp;{{ $company?->phone ?? '' }}</div>
                            </div>
                        </div>

                        {{-- Return From | Return Details --}}
                        <div class="grid grid-cols-2 divide-x divide-gray-200 border-b border-gray-200">
                            <div class="px-6 py-4">
                                <div class="text-xs font-bold text-gray-500 mb-2">Return From:</div>
                                <div class="text-base font-black text-gray-800 uppercase" x-text="activeReturn?.customer?.name ?? 'WALK-IN'"></div>
                                <div class="text-sm text-gray-500 mt-1">Contact No:&nbsp;&nbsp;<strong class="text-gray-700" x-text="activeReturn?.customer?.phone || 'N/A'"></strong></div>
                            </div>
                            <div class="px-6 py-4">
                                <div class="text-xs font-bold text-gray-500 mb-2">Return Details:</div>
                                <div class="text-sm text-gray-700">Credit Note No.:&nbsp;&nbsp;<strong x-text="activeReturn?.credit_note_no"></strong></div>
                                <div class="text-sm text-gray-700 mt-1">Date:&nbsp;&nbsp;<strong x-text="activeReturn?.return_date"></strong></div>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <template x-if="activeReturn?.items?.length > 0">
                            <div class="border-b border-gray-200">
                                <div class="grid border-b border-gray-100 bg-gray-50 px-6 py-2" style="grid-template-columns:2rem 1fr 5rem 6rem 5.5rem;">
                                    <span class="text-xs font-bold text-gray-500">#</span>
                                    <span class="text-xs font-bold text-gray-500">Item Name</span>
                                    <span class="text-xs font-bold text-gray-500 text-right">Qty</span>
                                    <span class="text-xs font-bold text-gray-500 text-right">Unit Price</span>
                                    <span class="text-xs font-bold text-gray-500 text-right">Amount</span>
                                </div>
                                <template x-for="(item, idx) in (activeReturn?.items ?? [])" :key="idx">
                                    <div class="grid px-6 py-2.5 border-b border-gray-50" style="grid-template-columns:2rem 1fr 5rem 6rem 5.5rem;">
                                        <span class="text-sm text-gray-400" x-text="idx + 1"></span>
                                        <span class="text-sm font-medium text-gray-800" x-text="item.product_name"></span>
                                        <span class="text-sm text-gray-600 text-right" x-text="parseFloat(item.quantity).toFixed(2)"></span>
                                        <span class="text-sm text-gray-600 text-right" x-text="'$ ' + parseFloat(item.unit_price).toFixed(2)"></span>
                                        <span class="text-sm font-bold text-gray-800 text-right" x-text="'$ ' + parseFloat(item.subtotal).toFixed(2)"></span>
                                    </div>
                                </template>
                                <div class="grid px-6 py-2.5 bg-gray-50" style="grid-template-columns:2rem 1fr 5rem 6rem 5.5rem;">
                                    <span></span>
                                    <span class="text-sm font-black text-gray-800">Total</span>
                                    <span class="text-sm font-bold text-gray-800 text-right"
                                          x-text="(activeReturn?.items ?? []).reduce((s,i)=>s+parseFloat(i.quantity),0).toFixed(2)"></span>
                                    <span></span>
                                    <span class="text-sm font-black text-gray-800 text-right"
                                          x-text="'$ ' + parseFloat(activeReturn?.amount ?? 0).toFixed(2)"></span>
                                </div>
                            </div>
                        </template>

                        {{-- Sub Total / Total --}}
                        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-sm text-gray-700">Sub Total</span>
                            <span class="text-gray-400 text-sm">:</span>
                            <span class="text-sm font-bold text-gray-800" x-text="'$ ' + parseFloat(activeReturn?.amount ?? 0).toFixed(2)"></span>
                        </div>
                        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-sm font-black text-gray-800">Total</span>
                            <span class="text-gray-400 text-sm">:</span>
                            <span class="text-sm font-black text-gray-800" x-text="'$ ' + parseFloat(activeReturn?.amount ?? 0).toFixed(2)"></span>
                        </div>

                        {{-- Amount in Words --}}
                        <div class="px-6 py-2 border-b border-gray-200 bg-gray-50">
                            <span class="text-sm font-black text-gray-700">Amount in Words:</span>
                        </div>
                        <div class="px-6 py-3 border-b border-gray-200">
                            <span class="text-sm text-gray-700" x-text="numToWords(activeReturn?.amount ?? 0)"></span>
                        </div>

                        {{-- Paid / Balance --}}
                        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-sm text-gray-700">Paid</span>
                            <span class="text-gray-400 text-sm">:</span>
                            <span class="text-sm font-bold text-gray-800">$ 0.00</span>
                        </div>
                        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-sm text-gray-700">Balance</span>
                            <span class="text-gray-400 text-sm">:</span>
                            <span class="text-sm font-black text-gray-800" x-text="'$ ' + parseFloat(activeReturn?.amount ?? 0).toFixed(2)"></span>
                        </div>

                        {{-- Authorized Signatory --}}
                        <div class="grid grid-cols-2">
                            <div></div>
                            <div class="px-6 py-5">
                                <div class="border border-gray-300 p-4 rounded">
                                    <div class="text-sm font-black text-gray-800 mb-14">For {{ $company?->name ?? 'Company' }}:</div>
                                    <div class="text-center text-xs text-gray-500 border-t border-gray-200 pt-2">Authorized Signatory</div>
                                </div>
                            </div>
                        </div>

                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between shrink-0">
                    <div class="flex gap-2">
                        <a :href="'/sales-return/' + activeReturn?.id + '/pdf'" target="_blank"
                           class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary font-black rounded-lg hover:bg-gray-50 transition-all text-[11px] uppercase tracking-wider shadow-sm">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>
                    </div>
                    <button @click="showViewModal = false"
                            class="px-5 py-2.5 bg-primary text-white font-black rounded-lg hover:bg-primary/90 transition-all text-[11px] uppercase tracking-wider">
                        Close
                    </button>
                </div>
            </div>
        </div>
        </template>

        <!-- Edit Modal -->
        <template x-teleport="body">
        <div x-show="showEditModal" x-cloak
             @click.self="showEditModal = false"
             style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(15,23,42,0.4);backdrop-filter:blur(4px);">
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:90%;max-width:32rem;max-height:90vh;display:flex;flex-direction:column;border-radius:1.25rem;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <!-- Header -->
                <div class="px-6 py-5 bg-primary flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white">
                            <i class="bi bi-pencil-square text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-white" x-text="'Edit — ' + (activeReturn?.credit_note_no ?? '')"></h2>
                            <p class="text-[10px] text-white/60">Update credit note details</p>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 space-y-5 overflow-y-auto flex-1 min-h-0 custom-scrollbar">

                    {{-- Customer + Invoice (read-only) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Customer</label>
                            <div class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 flex items-center gap-2">
                                <i class="bi bi-person text-primary/40"></i>
                                <span x-text="activeReturn?.customer?.name ?? '—'"></span>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Original Invoice</label>
                            <div class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 flex items-center gap-2">
                                <i class="bi bi-receipt text-primary/40"></i>
                                <span x-text="activeReturn?.invoice_no ?? '—'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Items (editable qty) --}}
                    <template x-if="editItems.length > 0">
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Items Being Returned</label>
                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 bg-gray-50">
                                <template x-for="(item, idx) in editItems" :key="idx">
                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                        <i class="bi bi-check2-square text-accent text-base flex-shrink-0"></i>
                                        <span class="flex-1 text-[12px] font-bold text-primary-dark" x-text="item.product_name"></span>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <span class="text-[11px] text-gray-400">Qty:</span>
                                            <input type="number" x-model.number="item.quantity" min="0.01" step="0.01"
                                                   class="w-20 px-2 py-1 border border-primary/30 rounded-lg text-[12px] text-center font-bold text-primary focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none bg-white">
                                        </div>
                                        <span class="text-[12px] font-black text-primary w-20 text-right flex-shrink-0"
                                              x-text="'$ ' + ((item.quantity || 0) * item.unit_price).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Return Amount (auto-computed from items) --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Amount</label>
                        <div class="w-full px-4 py-2.5 bg-primary/5 border border-primary/20 rounded-lg text-[13px] font-black text-primary flex items-center justify-between">
                            <span x-text="'$ ' + editTotal"></span>
                            <i class="bi bi-calculator text-primary/40 text-xs"></i>
                        </div>
                    </div>

                    {{-- Reason + Status --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Reason <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select x-model="editReason" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="Defective">Defective Product</option>
                                    <option value="Wrong">Wrong Item Delivered</option>
                                    <option value="Damaged">Damaged in Transit</option>
                                    <option value="Other">Other Reason</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Status <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select x-model="editStatus" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Return Date --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Date <span class="text-primary">*</span></label>
                        <input type="date" x-model="editDate"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Notes</label>
                        <textarea x-model="editNotes" rows="2" placeholder="Additional notes..."
                                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between shrink-0">
                    <button @click="showEditModal = false" class="px-5 py-2.5 bg-white border border-gray-200 text-primary/60 font-black rounded-lg hover:bg-gray-50 transition-all text-[11px] uppercase tracking-wider shadow-sm">
                        Cancel
                    </button>
                    <button @click="submitEdit()" :disabled="isEditSubmitting"
                            class="btn-premium-accent" :class="isEditSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                        <i class="bi" :class="isEditSubmitting ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="isEditSubmitting ? 'Saving…' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
        </template>

        <!-- Issue Credit Note Modal -->
        <div x-show="showModal" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative"
                 @click.away="showModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-arrow-return-left"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold text-white tracking-tight">Process Sales Return</h2>
                                <p class="text-xs text-white/60 font-medium mt-0.5">Issue a new credit note for your customer</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                    <form id="creditNoteForm" @submit.prevent="submitReturn()" class="space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Select Customer <span class="text-primary">*</span></label>
                                <div class="relative group">
                                    <select name="customer_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">Select Customer...</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-person absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-40"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Original Invoice</label>
                                <div class="relative group">
                                    <select name="invoice_id" x-model="selectedInvoiceId" @change="updateInvoiceItems()" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">Select Invoice (optional)...</option>
                                        @foreach($invoices as $invoice)
                                            <option value="{{ $invoice->id }}">{{ $invoice->invoice_no }} — {{ $invoice->customer->name ?? '' }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-receipt absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-40"></i>
                                </div>
                                <p class="text-[10px] text-gray-400 font-medium">Pick an invoice to select which items are being returned and restock them automatically.</p>
                            </div>
                        </div>

                        <!-- Item picker — only shown once an invoice is selected -->
                        <div class="space-y-1.5" x-show="selectedInvoiceId" x-cloak>
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Items Being Returned</label>
                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-56 overflow-y-auto">
                                <template x-if="returnItems.length === 0">
                                    <p class="text-[12px] text-gray-400 px-4 py-3">Every item on this invoice has already been fully returned.</p>
                                </template>
                                <template x-for="(item, idx) in returnItems" :key="item.id">
                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                        <input type="checkbox" x-model="item.selected" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/30">
                                        <span class="flex-1 text-[12px] font-bold text-primary-dark" x-text="item.name"></span>
                                        <span class="text-[10px] text-gray-400" x-text="'of ' + item.remaining_qty + ' available'"></span>
                                        <input type="number" x-model.number="item.return_qty" :max="item.remaining_qty" min="0.01" step="0.01"
                                               class="w-20 px-2 py-1 bg-gray-50 border border-gray-200 rounded text-[12px] font-bold text-primary text-right">
                                        <span class="text-[11px] font-bold text-primary w-16 text-right" x-text="(item.return_qty * item.rate).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Reason <span class="text-primary">*</span></label>
                                <div class="relative group">
                                    <select name="reason" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                        <option value="">Select reason...</option>
                                        <option value="Defective">Defective Product</option>
                                        <option value="Wrong">Wrong Item Delivered</option>
                                        <option value="Damaged">Damaged in Transit</option>
                                        <option value="Other">Other Reason</option>
                                    </select>
                                    <i class="bi bi-chat-dots absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-40"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Date <span class="text-primary">*</span></label>
                                <input type="date" name="return_date" x-model="returnDate" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Return Amount <span class="text-primary">*</span></label>
                            <div class="relative">
                                <input type="number" name="amount" step="0.01" placeholder="0.00"
                                       :required="!selectedInvoiceId"
                                       :readonly="!!selectedInvoiceId"
                                       :value="selectedInvoiceId ? calculatedAmount.toFixed(2) : undefined"
                                       :class="selectedInvoiceId ? 'bg-gray-100 text-gray-500' : 'bg-gray-50 text-primary'"
                                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-[13px] font-medium focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-40 font-black text-xs">$</div>
                            </div>
                            <p class="text-[10px] text-gray-400 font-medium" x-show="selectedInvoiceId">Calculated automatically from the items selected above.</p>
                        </div>

                        <div class="space-y-1.5 pt-2">
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider">Additional Notes</label>
                            <textarea name="notes" rows="3" placeholder="Enter any additional notes..." class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 shrink-0 flex items-center justify-between">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 bg-white border border-gray-200 text-primary/60 font-black rounded-lg hover:bg-gray-50 transition-all text-[11px] uppercase tracking-wider shadow-sm">
                        Cancel
                    </button>
                    <button type="submit" form="creditNoteForm" :disabled="isSubmitting" class="btn-premium-accent" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                        <i class="bi" :class="isSubmitting ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="isSubmitting ? 'Saving…' : 'Issue Credit Note'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Record count -->
        <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $returns->count() }} {{ Str::plural('entry', $returns->count()) }}
            </p>
        </div>

    </div>

</div>

<script>
function confirmDeleteReturn(id) {
    Swal.fire({
        title: 'Delete Credit Note?',
        text: 'This will reverse the journal entry and restore the customer balance. Cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#004161',
        cancelButtonColor: '#99CC33',
        confirmButtonText: 'Yes, delete it!',
        customClass: {
            popup: 'rounded-[1.5rem]',
            confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
            cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-return-' + id).submit();
        }
    });
}
</script>

@endsection

@push('js')
<script>
function printCreditNote() {
    var doc = document.getElementById('cn-printable');
    if (!doc) return;
    var css = '* { margin:0; padding:0; box-sizing:border-box; }'
            + 'body { font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#1a1a2e; background:#fff; padding:20px; }'
            + 'h2 { text-align:center; font-size:22px; font-weight:bold; margin-bottom:16px; color:#1a1a2e; }'
            + '.border-b { border-bottom:1px solid #e5e7eb; }'
            + '.grid { display:grid; }'
            + '.grid-cols-2 { grid-template-columns:1fr 1fr; }'
            + 'img { max-width:64px; max-height:64px; object-fit:contain; }'
            + 'strong { font-weight:bold; }';
    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Credit Note</title><style>' + css + '</style></head><body>' + doc.innerHTML + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function() { w.print(); }, 400);
}
</script>
@endpush
