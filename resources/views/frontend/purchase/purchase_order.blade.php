@extends('admin.admin_master')
@section('page_title', 'Purchase Orders')



@section('admin')
    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="purchaseOrder()">
    <!-- Top Header Navigation -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Purchase Orders</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.order.create') }}"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                Create Purchase Order
            </a>
        </div>
    </div>

    <!-- Stats Summary Section (Premium Style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Orders</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">All time recorded</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-file-earmark-text text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Orders</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->where('status', 'pending')->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Awaiting approval</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-clock-history text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Stock Approved</p>
                <h3 class="text-[18px] font-black text-primary">{{ $purchaseOrders->where('status', 'approved')->count() }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Ready for delivery</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-check2-circle text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Stock Investment</p>
                <h3 class="text-[18px] font-black text-primary"><span class="text-gray-300 font-bold">$</span> {{ number_format($purchaseOrders->sum('total_amount'), 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Active Portfolio</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-currency-dollar text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Main Table View -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <!-- Filters -->
            <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Search -->
                <div class="relative group min-w-[250px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" x-model="searchTerm" placeholder="Search by PO #, supplier or reference..."
                           class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[150px]">
                    <select x-model="statusFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Supplier Filter -->
                <div class="relative min-w-[150px]">
                    <select x-model="supplierFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Clear Filters -->
                <button @click="searchTerm = ''; statusFilter = ''; supplierFilter = '';"
                        class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10"
                        x-show="searchTerm !== '' || statusFilter !== '' || supplierFilter !== ''"
                        x-transition
                        title="Clear All Filters">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Table Title -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Purchase Order List</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Batch Ref</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Vendor/Supplier</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Arrival Branch</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">QTY</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Investment Total</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Order Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($purchaseOrders as $po)
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                                x-show="(searchTerm === '' || '{{ strtolower($po->po_number) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($po->supplier->name ?? '') }}'.includes(searchTerm.toLowerCase())) && (statusFilter === '' || '{{ $po->status }}' === statusFilter) && (supplierFilter === '' || '{{ $po->supplier_id }}' === supplierFilter)">
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                    <span class="text-[12px] font-bold text-primary-dark/40 group-hover:text-primary transition-colors leading-tight">
                                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    <div class="uppercase tracking-widest">{{ $po->po_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                    <div class="leading-tight">
                                        {{ $po->supplier->name ?? '- UNKNOWN -' }}
                                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter mt-0.5">Verified Partner</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    @php
                                        $locName = $po->branch->name ?? 'Headquarters';
                                    @endphp
                                    {{ $locName }}
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    {{ $po->items->count() }} <span class="text-[9px] text-gray-400">SKUs</span>
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                    <span class="text-gray-300 font-bold mr-0.5">$</span> {{ number_format($po->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    @if($po->status === 'pending')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[9px] font-black bg-primary/10 text-primary border border-primary/20 shadow-sm uppercase tracking-widest">PENDING</span>
                                    @elseif($po->status === 'approved')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[9px] font-black bg-accent/10 text-accent border border-accent/20 shadow-sm uppercase tracking-widest">APPROVED</span>
                                    @elseif($po->status === 'received')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[9px] font-black bg-primary/10 text-primary border border-primary/20 shadow-sm uppercase tracking-widest">RECEIVED</span>
                                    @elseif($po->status === 'cancelled')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[9px] font-black bg-primary/10 text-primary border border-primary/20 shadow-sm uppercase tracking-widest">CANCELLED</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[9px] font-black bg-gray-50 text-gray-400 border border-gray-200 shadow-sm uppercase tracking-widest">{{ $po->status }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('purchase.order.print', $po->id) }}" class="btn-action-view" title="View / Print">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button @click="openEditModal({{ $po->id }})" class="btn-action-edit" title="Edit Order">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        @if($po->status === 'pending')
                                        <button onclick="updatePOStatus({{ $po->id }}, 'approved')" class="btn-action-view" title="Approve Order">
                                            <i class="bi bi-shield-check"></i>
                                        </button>
                                        @endif
                                        <button onclick="confirmDeletePO({{ $po->id }}, '{{ $po->po_number }}')" class="btn-action-delete" title="Delete Order">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center text-gray-400 text-xs italic">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                    Showing <span class="text-primary-dark">{{ $purchaseOrders->count() }}</span> of <span class="text-primary-dark">{{ $purchaseOrders->count() }}</span> entries
                </p>
                <div class="flex items-center gap-1">
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">1</button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

    <!-- CREATE PURCHASE ORDER MODAL -->
<div x-show="activeModal === 'create-po-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

    <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative"
        @click.away="closeModal()">
        
        <!-- Modal Header (Dark Blue Premium Style) -->
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                        <i :class="editMode ? 'bi bi-pencil-square' : 'bi bi-cart-plus'"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-xl font-bold text-white tracking-tight" x-text="editMode ? 'Edit Purchase Order' : 'Create Purchase Order'"></h2>
                        <p class="text-xs text-primary font-medium mt-0.5" x-text="editMode ? 'Modify existing order details and manifest' : 'Define order details and product manifest'"></p>
                    </div>
                </div>

                <button @click="closeModal()"
                    class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="px-8 py-8 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <form id="createPOForm">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Order Reference</label>
                            <input type="text" name="po_number" id="po_number_display" :value="editMode ? 'UPDATING...' : '{{ $poNo }}'" readonly class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-black text-primary transition-all outline-none cursor-default">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Supplier Entity <span class="text-primary">*</span></label>
                            <select name="supplier_id" id="po_supplier_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                <option value="">Identify Supplier...</option>
                                @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->supplier_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Receiving Branch <span class="text-primary">*</span></label>
                            <select name="branch_id" id="po_branch_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                <option value="">Identify Branch...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Issue Date <span class="text-primary">*</span></label>
                            <input type="date" name="order_date" id="po_order_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all cursor-pointer">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Expected Delivery</label>
                            <input type="date" name="expected_delivery" id="po_expected" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/10 outline-none transition-all cursor-pointer">
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Fiscal Method</label>
                            <select name="payment_terms" id="po_payment_terms" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-lg text-xs font-bold text-slate-700 focus:bg-white outline-none transition-all appearance-none cursor-pointer">
                                <option value="Cash on Delivery">Cash on Delivery</option>
                                <option value="Net 30 Days" selected>Net 30 Days</option>
                                <option value="Net 60 Days">Net 60 Days</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <div class="mb-10">
                        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-50">
                            <h3 class="text-xs font-black text-primary-dark uppercase tracking-[0.2em] flex items-center gap-2">
                                <span class="w-2 h-4 bg-accent rounded-sm"></span>
                                Product Manifest
                            </h3>
                            <button type="button" onclick="addPOItem()" class="flex items-center gap-2 px-4 py-1.5 bg-accent/20 text-primary-dark font-black rounded-lg hover:bg-accent/30 transition-all text-[9px] uppercase tracking-widest">
                                <i class="bi bi-plus-lg"></i> Add Product
                            </button>
                        </div>

                        <!-- Manifest Header Labels -->
                        <div class="hidden md:flex items-center gap-4 px-3 mb-2">
                            <div class="flex-grow min-w-[200px] text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Product Details</div>
                            <div class="w-24 shrink-0 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Qty</div>
                            <div class="w-32 shrink-0 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Unit Price</div>
                            <div class="w-32 shrink-0 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest pr-2">Subtotal</div>
                            <div class="w-10 shrink-0"></div>
                        </div>

                        <div id="poItemsContainer" class="space-y-3">
                            <!-- Items Rows -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-end">
                        <div>
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Terms & Remarks</label>
                            <textarea name="notes" id="po_notes" rows="2" class="w-full px-5 py-3 bg-gray-50 border border-gray-100 rounded-xl text-[12px] font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-primary/10 transition-all outline-none resize-none" placeholder="Provide additional context for this procurement..."></textarea>
                        </div>

                        <div class="bg-primary/10 border border-primary/10 rounded-2xl p-5 relative overflow-hidden flex items-center">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl"></div>
                            <div class="relative z-10 w-full">
                                <div class="flex justify-between items-center">
                                    <span class="text-[11px] font-black text-primary uppercase tracking-[0.2em]">Procurement Total</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-black text-primary">{{ $currency ?? '$' }} <span id="spanTotal">0.00</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                            <input type="hidden" name="total_amount" id="inputTotal" value="0">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button type="button" @click="closeModal()" class="btn-premium-accent">
                    Cancel
                </button>
                <button type="button" onclick="submitPO()" class="btn-premium-primary">
                    <i :class="editMode ? 'bi bi-check2-circle' : 'bi bi-cart-check'"></i>
                    <span x-text="editMode ? 'Update Order' : 'Save Order'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- PURCHASE ORDER DETAIL MODAL -->
<div x-show="activeModal === 'po-detail-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

    <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative"
        @click.away="closeModal()">
        
        <!-- Modal Header -->
        <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-xl font-bold text-white tracking-tight" id="detailPONumber">----</h2>
                        <div class="flex items-center gap-3 mt-1">
                            <span id="detailStatus" class="inline-flex px-2 py-0.5 bg-white/10 rounded-[0.4rem] text-[9px] font-black uppercase tracking-widest border border-white/10">---</span>
                            <span class="text-[10px] text-white/60 font-black uppercase tracking-widest" id="detailDate">---</span>
                        </div>
                    </div>
                </div>

                <button @click="closeModal()"
                    class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
        </div>

        <div class="px-8 py-8 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="p-6 bg-gray-50 border border-gray-100 rounded-2xl">
                        <h4 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 bg-accent rounded-full"></span> Supplier Log
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between pb-2 border-b border-gray-200/50">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Entity Name:</span>
                                <span class="text-xs font-black text-slate-800" id="detailSupplierName">---</span>
                            </div>
                            <div class="flex justify-between pb-2 border-b border-gray-200/50">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Internal Code:</span>
                                <span class="text-xs font-bold text-slate-600" id="detailSupplierCode">---</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Fiscal Policy:</span>
                                <span class="text-xs font-black text-primary" id="detailPaymentTerms">---</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border border-gray-100 rounded-2xl">
                        <h4 class="text-[10px] font-black text-primary uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 bg-accent rounded-full"></span> Destination Point
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between pb-2 border-b border-gray-200/50">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Branch:</span>
                                <span class="text-xs font-black text-slate-800" id="detailLocationName">---</span>
                            </div>
                            <div class="flex justify-between pb-2 border-b border-gray-200/50">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Entry Type:</span>
                                <span class="text-xs font-bold text-slate-600" id="detailLocationType">---</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-gray-400 capitalize">Due For Receipt:</span>
                                <span class="text-xs font-black text-primary" id="detailExpected">---</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-gray-100 rounded-xl mb-10">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-5 py-4 text-[9px] font-black text-primary-dark uppercase tracking-widest w-16 text-center">#</th>
                                <th class="px-5 py-4 text-[9px] font-black text-primary-dark uppercase tracking-widest">SKU Item Description</th>
                                <th class="px-5 py-4 text-[9px] font-black text-primary-dark uppercase tracking-widest text-center">Batch Qty</th>
                                <th class="px-5 py-4 text-[9px] font-black text-primary-dark uppercase tracking-widest text-right">Unit Price</th>
                                <th class="px-5 py-4 text-[9px] font-black text-primary-dark uppercase tracking-widest text-right">Ext. Total</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTable" class="divide-y divide-gray-50">
                            <!-- Items Rows -->
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col md:flex-row justify-between gap-10">
                    <div class="flex-grow p-6 bg-primary/5 border border-primary/10 rounded-2xl max-w-lg">
                        <h5 class="text-[9px] font-black text-primary uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="bi bi-chat-left-dots"></i> Internal Remarks
                        </h5>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium italic" id="detailNotes">---</p>
                    </div>

                    <div class="w-full md:w-[320px] p-6 bg-primary rounded-2xl shadow-xl relative overflow-hidden">
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
                        <div class="space-y-4 relative z-10">
                            <div class="flex justify-between items-center pb-3 border-b border-white/10">
                                <span class="text-[9px] font-black text-white/40 uppercase tracking-widest">Gross Subtotal</span>
                                <span class="text-xs font-black text-white">{{ $currency ?? '$' }} <span id="detailSubtotal">0.00</span></span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-[10px] font-black text-accent uppercase tracking-[0.2em]">Net Payable</span>
                                <div class="text-right">
                                    <div class="text-2xl font-black text-white">{{ $currency ?? '$' }} <span id="detailTotal">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button @click="closeModal()" class="btn-premium-accent">
                    Close
                </button>
                <button onclick="window.print()" class="btn-premium-primary">
                    <i class="bi bi-printer"></i> Print PDF
                </button>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    // --- Data Initialization ---
    const productsDB = @json($products);
    const currencySymbol = "{{ $currency ?? '$' }}";

    document.addEventListener('alpine:init', () => {
        Alpine.data('purchaseOrder', () => ({
            activeModal: null,
            searchTerm: '',
            statusFilter: '',
            supplierFilter: '',
            dateFilter: '',
            editMode: false,
            poId: null,
            
            openCreateModal() {
                this.editMode = false;
                this.poId = null;
                document.getElementById('createPOForm').reset();
                document.getElementById('poItemsContainer').innerHTML = '';
                itemsCount = 0;
                this.activeModal = 'create-po-modal';
                if (typeof addPOItem === 'function') {
                    addPOItem();
                }
            },

            openEditModal(id) {
                Swal.fire({ title: 'Retrieving Data...', didOpen: () => Swal.showLoading() });
                axios.get(`/purchase/orders/${id}`)
                    .then(res => {
                        const po = res.data;
                        this.editMode = true;
                        this.poId = id;
                        
                        // Populate basic fields
                        document.getElementById('po_supplier_id').value = po.supplier_id;
                        document.getElementById('po_branch_id').value = po.branch_id;
                        document.getElementById('po_order_date').value = po.order_date;
                        document.getElementById('po_expected').value = po.expected_delivery || '';
                        document.getElementById('po_payment_terms').value = po.payment_terms;
                        document.getElementById('po_notes').value = po.notes || '';
                        
                        // Populate items
                        const container = document.getElementById('poItemsContainer');
                        container.innerHTML = '';
                        itemsCount = 0;
                        
                        if (po.items && po.items.length > 0) {
                            po.items.forEach(item => {
                                this.addItemWithData(item);
                            });
                        } else {
                            addPOItem();
                        }
                        
                        calculateTotals();
                        Swal.close();
                        this.activeModal = 'create-po-modal';
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Fetch Error', text: 'Failed to retrieve order data.' });
                    });
            },

            addItemWithData(item) {
                const container = document.getElementById('poItemsContainer');
                const idx = itemsCount++;
                const row = document.createElement('div');
                row.className = 'manifest-row manifest-item p-3 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center gap-4 group transition-all hover:bg-white hover:border-primary/20 hover:shadow-sm';
                row.innerHTML = `
                    <div class="flex-grow min-w-[200px] relative">
                        <input type="hidden" name="items[${idx}][product_id]" id="pid_${idx}" value="${item.product_id || ''}">
                        <input type="text" id="p_text_${idx}" oninput="showSuggestions(this, ${idx})" onfocus="showSuggestions(this, ${idx})" 
                            autocomplete="off" value="${item.product_name || (item.product?.product_name || '')}" 
                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-slate-700 outline-none focus:border-primary transition-all">
                        <div id="suggest_${idx}" class="product-suggestions custom-scrollbar shadow-xl"></div>
                    </div>
                    <div class="w-24 shrink-0">
                        <input type="number" name="items[${idx}][quantity]" step="1" value="${item.quantity}" onchange="calculateTotals()" 
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-center text-primary-dark outline-none focus:border-primary transition-all">
                    </div>
                    <div class="w-32 shrink-0 relative">
                        <input type="number" name="items[${idx}][unit_price]" step="0.01" value="${item.unit_price}" onchange="calculateTotals()" 
                            class="w-full pl-6 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-right text-slate-700 outline-none focus:border-primary transition-all">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-black">${currencySymbol}</span>
                    </div>
                    <div class="w-32 shrink-0 text-right px-2">
                        <div class="text-[12px] font-black text-primary-dark manifestation-total">${currencySymbol} ${parseFloat(item.total_amount).toFixed(2)}</div>
                        <input type="hidden" name="items[${idx}][total_amount]" class="manifestation-total-input" value="${item.total_amount}">
                    </div>
                    <div class="w-10 shrink-0 flex justify-end">
                        <button type="button" onclick="removePOItem(this)" class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-300 hover:text-primary hover:border-primary/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                container.appendChild(row);
            },
            
            closeModal() {
                this.activeModal = null;
            },

            viewOrder(id) {
                axios.get(`/purchase/orders/${id}`)
                    .then(res => {
                        const po = res.data;
                        document.getElementById('detailPONumber').textContent = po.po_number || '-';
                        document.getElementById('detailStatus').textContent = po.status || '-';
                        document.getElementById('detailDate').textContent = po.order_date || '-';
                        document.getElementById('detailExpected').textContent = po.expected_delivery || 'N/A';
                        document.getElementById('detailSupplierName').textContent = po.supplier?.name || '-';
                        document.getElementById('detailSupplierCode').textContent = po.supplier?.supplier_code || '-';
                        document.getElementById('detailPaymentTerms').textContent = po.payment_terms || '-';
                        document.getElementById('detailLocationName').textContent = po.branch?.name || '-';
                        document.getElementById('detailLocationType').textContent = 'BRANCH';
                        document.getElementById('detailNotes').textContent = po.notes || 'No internal remarks identified for this order.';
                        
                        if(po.subtotal) {
                            document.getElementById('detailSubtotal').textContent = parseFloat(po.subtotal).toLocaleString(undefined, {minimumFractionDigits:2});
                        }
                        if(po.total_amount) {
                            document.getElementById('detailTotal').textContent = parseFloat(po.total_amount).toLocaleString(undefined, {minimumFractionDigits:2});
                        }

                        const tbody = document.getElementById('detailItemsTable');
                        if(tbody) {
                            tbody.innerHTML = '';
                            (po.items || []).forEach((item, index) => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-gray-50/60 transition-colors bg-white';
                                tr.innerHTML = `
                                    <td class="px-5 py-4 text-center text-[10px] font-black text-gray-300 border-r border-gray-50">${index+1}</td>
                                    <td class="px-5 py-4"><div class="text-[11px] font-black text-slate-700">${item.product_name || '-'}</div></td>
                                    <td class="px-5 py-4 text-center"><span class="text-[11px] font-black text-primary-dark">${parseInt(item.quantity) || 0}</span></td>
                                    <td class="px-5 py-4 text-right text-[11px] font-black text-slate-500">${currencySymbol} ${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                                    <td class="px-5 py-4 text-right"><span class="text-[11px] font-black text-primary-dark">${currencySymbol} ${parseFloat(item.total_amount || 0).toFixed(2)}</span></td>
                                `;
                                tbody.appendChild(tr);
                            });
                        }
                        this.activeModal = 'po-detail-modal';
                    })
                    .catch(err => {
                        console.error(err);
                        if(typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'System Error', text: 'Failed to retrieve PO details.' });
                        } else {
                            alert('Failed to retrieve PO details.');
                        }
                    });
            }
        }));
    });

    const branchOptions = [
        @foreach($branches as $branch) { id: {{ $branch->id }}, name: `{{ addslashes($branch->name) }}` }, @endforeach
    ];
    let itemsCount = 0;

    // --- Modal Management ---
    function openCreatePOModal() {
        const xData = document.querySelector('[x-data]').__x.$data;
        xData.activeModal = 'create-po-modal';
        if (itemsCount === 0) addPOItem();
    }
    function closeCreatePOModal() {
        document.querySelector('[x-data]').__x.$data.activeModal = null;
    }
    function closePODetail() {
        document.querySelector('[x-data]').__x.$data.activeModal = null;
    }

    // --- Location Logic (Simplified for Branch Only) ---

    // --- Dynamic Items Manifest ---
    function addPOItem() {
        const container = document.getElementById('poItemsContainer');
        const idx = itemsCount++;
        const row = document.createElement('div');
        row.className = 'manifest-row manifest-item p-3 bg-gray-50/50 rounded-xl border border-gray-100 flex items-center gap-4 group transition-all hover:bg-white hover:border-primary/20 hover:shadow-sm';
        row.innerHTML = `
            <div class="flex-grow min-w-[200px] relative">
                <input type="hidden" name="items[${idx}][product_id]" id="pid_${idx}">
                <input type="text" id="p_text_${idx}" oninput="showSuggestions(this, ${idx})" onfocus="showSuggestions(this, ${idx})" 
                    autocomplete="off" placeholder="Search by SKU or Name..." 
                    class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-slate-700 outline-none focus:border-primary transition-all">
                <div id="suggest_${idx}" class="product-suggestions custom-scrollbar shadow-xl"></div>
            </div>
            <div class="w-24 shrink-0">
                <input type="number" name="items[${idx}][quantity]" step="1" value="1" onchange="calculateTotals()" 
                    class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-center text-primary-dark outline-none focus:border-primary transition-all">
            </div>
            <div class="w-32 shrink-0 relative">
                <input type="number" name="items[${idx}][unit_price]" step="0.01" value="0.00" onchange="calculateTotals()" 
                    class="w-full pl-6 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-black text-right text-slate-700 outline-none focus:border-primary transition-all">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 font-black">${currencySymbol}</span>
            </div>
            <div class="w-32 shrink-0 text-right px-2">
                <div class="text-[12px] font-black text-primary-dark manifestation-total">${currencySymbol} 0.00</div>
                <input type="hidden" name="items[${idx}][total_amount]" class="manifestation-total-input" value="0">
            </div>
            <div class="w-10 shrink-0 flex justify-end">
                <button type="button" onclick="removePOItem(this)" class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-300 hover:text-primary hover:border-primary/20 transition-all flex items-center justify-center shadow-sm">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function removePOItem(btn) {
        btn.closest('.manifest-row').remove();
        calculateTotals();
    }

    // --- Autocomplete Engine ---
    function showSuggestions(input, idx) {
        const query = input.value.trim().toLowerCase();
        const box = document.getElementById(`suggest_${idx}`);
        box.innerHTML = '';

        const filtered = productsDB.filter(p => 
            p.product_name.toLowerCase().includes(query) || (p.product_code && p.product_code.toLowerCase().includes(query))
        ).slice(0, 15);

        if (filtered.length > 0) {
            filtered.forEach(p => {
                const el = document.createElement('div');
                el.className = 'product-suggestion-item';
                el.innerHTML = `<span>${p.product_name}</span> <span class="suggestion-price">$ ${parseFloat(p.purchase_price).toFixed(2)}</span>`;
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
        const row = document.getElementById(`p_text_${idx}`).closest('.manifest-row');
        row.querySelector(`input[name="items[${idx}][unit_price]"]`).value = p.purchase_price;
        document.getElementById(`suggest_${idx}`).classList.remove('open');
        calculateTotals();
    }

    // --- Financial Engine ---
    function calculateTotals() {
        let grandTotal = 0;
        document.querySelectorAll('.manifest-item').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
            const total = qty * price;
            
            row.querySelector('.manifestation-total').textContent = `${currencySymbol} ${total.toFixed(2)}`;
            row.querySelector('.manifestation-total-input').value = total.toFixed(2);
            grandTotal += total;
        });

        // document.getElementById('spanSubtotal').textContent = grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        if (document.getElementById('spanSubtotal')) {
            document.getElementById('spanSubtotal').textContent = grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
        document.getElementById('spanTotal').textContent = grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('inputSubtotal').value = grandTotal.toFixed(2);
        document.getElementById('inputTotal').value = grandTotal.toFixed(2);
    }

    // --- Database interaction ---
    function submitPO() {
        // Explicit Value Extraction
        const supplierSelect = document.getElementById('po_supplier_id');
        const branchSelect = document.getElementById('po_branch_id');
        
        const supplier = supplierSelect ? supplierSelect.value : null;
        const branch = branchSelect ? branchSelect.value : null;
        const items = document.querySelectorAll('.manifest-item');

        if (!supplier || supplier === "") {
            Swal.fire({ icon: 'warning', title: 'Missing Supplier', text: 'Please identify the Supplier Entity before processing.' });
            return;
        }

        if (!branch || branch === "") {
            Swal.fire({ icon: 'warning', title: 'Missing Branch', text: 'Please identify the Receiving Branch before processing.' });
            return;
        }

        if (items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Empty Manifest', text: 'Please add at least one product to the manifest.' });
            return;
        }

        const form = document.getElementById('createPOForm');
        // Extract plain data from form for JSON submit if needed, or use FormData
        const formData = new FormData(form);
        const xData = document.querySelector('[x-data]').__x.$data;

        // If Edit Mode, we need to handle the method override or use axios.put
        const url = xData.editMode ? `/purchase/orders/${xData.poId}` : '{{ route("purchase.order.store") }}';
        const method = xData.editMode ? 'put' : 'post';

        Swal.fire({ title: 'Processing Order...', didOpen: () => Swal.showLoading() });

        axios({
            method: method,
            url: url,
            data: formData,
            headers: { 'Content-Type': 'multipart/form-data' } // Important for sending files or just standard mixed data
        })
            .then(res => {
                if (res.data.success) {
                    Swal.fire({ icon: 'success', title: 'Order Processed!', text: res.data.message, confirmButtonColor: '#004161' })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'System Error', text: res.data.message });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Validation Fail', text: 'Please ensure all required fields are identified.' });
            });
    }

    function updatePOStatus(id, status) {
        Swal.fire({
            title: 'Verify Sync Approval?',
            text: `Confirm status transition to ${status.toUpperCase()}?`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004161'
        }).then(result => {
            if (result.isConfirmed) {
                axios.post(`/purchase/orders/update-status/${id}`, { status: status })
                    .then(res => {
                        if (res.data.success) location.reload();
                    });
            }
        });
    }

    function confirmDeletePO(id, number) {
        Swal.fire({
            title: 'Purge Order Record?',
            text: `Are you sure you want to permanently delete ${number}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33'
        }).then(result => {
            if (result.isConfirmed) {
                axios.delete(`/purchase/orders/${id}`)
                    .then(res => {
                        if (res.data.success) location.reload();
                    });
            }
        });
    }

    // --- Event Listeners ---
    document.addEventListener('mousedown', (e) => {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('.product-suggestions').forEach(box => box.classList.remove('open'));
        }
    });

    // handleLocationTypeChange(); // No longer needed
</script>
@endpush
@endsection


