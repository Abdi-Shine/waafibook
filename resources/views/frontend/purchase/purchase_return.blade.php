@extends('admin.admin_master')
@section('page_title', 'Purchase Returns')
@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="{ 
    activeModal: null,
    searchTerm: '',
    statusFilter: '',
    fromDate: '',
    toDate: '',
    supplierFilter: '',
    branchFilter: '',
    bills: @js($bills),
    returns: @js($returns),
    currency: @js($currency),
    selectedBillId: '',
    returnDate: '{{ date('Y-m-d') }}',
    returnReason: '',
    returnItems: [],
    isSubmitting: false,
    amountPaid: 0,
    dueDate: '',
    bankAccountId: '',
    bankAccounts: @js($bankAccounts),
    editReturn: {},
    isEditSubmitting: false,
    pageSize: 10,
    currentPage: 1,
    
    get totalPages() {
        return Math.ceil(this.filteredReturns.length / this.pageSize) || 1;
    },

    get paginatedReturns() {
        const start = (this.currentPage - 1) * this.pageSize;
        return this.filteredReturns.slice(start, start + this.pageSize);
    },
    
    get selectedSupplierBalance() {
        if (!this.selectedBill) return 0;
        return this.selectedBill.supplier ? (parseFloat(this.selectedBill.supplier.amount_balance) || 0) : 0;
    },

    get resultingBalance() {
        const totalReturn = this.calculateSubtotal();
        const payment = parseFloat(this.amountPaid || 0);
        return this.selectedSupplierBalance - totalReturn - payment;
    },
    
    get selectedBill() {
        return this.bills.find(b => b.id == this.selectedBillId);
    },

    updateBillItems() {
        if (this.selectedBill) {
            this.returnItems = this.selectedBill.items.map(item => {
                const alreadyReturned = (item.return_items || []).reduce((sum, ri) => sum + parseFloat(ri.quantity || 0), 0);
                const remaining = item.quantity - alreadyReturned;
                return {
                    id: item.id,
                    product_id: item.product_id,
                    name: item.product ? item.product.product_name : (item.product_name || 'Generic Product'),
                    original_qty: item.quantity,
                    already_returned_qty: alreadyReturned,
                    remaining_qty: remaining,
                    return_qty: remaining,
                    rate: item.unit_price,
                    total: remaining * item.unit_price
                };
            }).filter(i => i.remaining_qty > 0);
            
            this.amountPaid = 0; 
        } else {
            this.returnItems = [];
        }
    },

    calculateSubtotal() {
        return this.returnItems.reduce((acc, item) => acc + (parseFloat(item.return_qty || 0) * parseFloat(item.rate || 0)), 0);
    },

    openCreateModal() {
        this.activeModal = 'return-modal';
        this.selectedBillId = '';
        this.returnDate = '{{ date('Y-m-d') }}';
        this.returnReason = '';
        this.returnItems = [];
        this.amountPaid = 0;
        this.dueDate = '';
        this.bankAccountId = '';
    },

    async submitReturn() {
        if (!this.selectedBillId || !this.returnDate || !this.returnReason || this.returnItems.length === 0) {
            Swal.fire('Error', 'Please fill all required fields and add items.', 'error');
            return;
        }

        if (this.amountPaid > 0 && !this.bankAccountId) {
            Swal.fire('Error', 'Please select a Bank Account for the refund.', 'error');
            return;
        }

        this.isSubmitting = true;
        try {
            const response = await axios.post('{{ route('purchase.return.store') }}', {
                purchase_bill_id: this.selectedBillId,
                return_date: this.returnDate,
                reason: this.returnReason,
                amount_paid: this.amountPaid,
                bank_account_id: this.bankAccountId,
                due_date: this.dueDate,
                items: this.returnItems.map(i => ({
                    product_id: i.product_id,
                    bill_item_id: i.id,
                    quantity: i.return_qty,
                    unit_price: i.rate
                }))
            });

            if (response.data.success) {
                Swal.fire('Success', response.data.message, 'success').then(() => {
                    window.location.reload();
                });
            }
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'Something went wrong', 'error');
        } finally {
            this.isSubmitting = false;
        }
    },

    get filteredReturns() {
        return this.returns.filter(r => {
            const matchesSearch = r.return_number.toLowerCase().includes(this.searchTerm.toLowerCase()) || 
                                (r.supplier?.name || '').toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                (r.bill?.bill_number || '').toLowerCase().includes(this.searchTerm.toLowerCase());
            
            const matchesStatus = this.statusFilter === '' || r.status === this.statusFilter;
            const matchesSupplier = this.supplierFilter === '' || r.supplier_id == this.supplierFilter;
            const matchesBranch = this.branchFilter === '' || r.branch_id == this.branchFilter;
            
            let matchesDate = true;
            if (this.fromDate) {
                matchesDate = matchesDate && r.return_date >= this.fromDate;
            }
            if (this.toDate) {
                matchesDate = matchesDate && r.return_date <= this.toDate;
            }

            return matchesSearch && matchesStatus && matchesSupplier && matchesBranch && matchesDate;
        });
    },

    nextPage() {
        if (this.currentPage < this.totalPages) this.currentPage++;
    },

    prevPage() {
        if (this.currentPage > 1) this.currentPage--;
    },

    resetPage() {
        this.currentPage = 1;
    },

    openEditModal(r) {
        this.editReturn = {
            id: r.id,
            return_date: r.return_date ? r.return_date.substring(0, 10) : '',
            due_date: r.due_date ? r.due_date.substring(0, 10) : '',
            reason: r.reason || '',
            status: r.status || 'pending',
            notes: r.notes || '',
            return_number: r.return_number,
            supplier_name: r.supplier ? r.supplier.name : 'Unknown',
            total_amount: r.total_amount,
            subtotal: r.subtotal,
            bill_number: r.bill ? r.bill.bill_number : '',
            items: (r.items || []).map(i => ({
                name: i.product ? i.product.product_name : (i.product_name || 'Product'),
                quantity: i.quantity,
                unit_price: i.unit_price,
                subtotal: i.subtotal,
            })),
        };
        this.activeModal = 'edit-return-modal';
    },

    async submitEditReturn() {
        if (!this.editReturn.return_date || !this.editReturn.reason) {
            Swal.fire('Error', 'Please fill all required fields.', 'error');
            return;
        }
        this.isEditSubmitting = true;
        try {
            const response = await axios.put(`/purchase/returns/${this.editReturn.id}`, {
                return_date: this.editReturn.return_date,
                due_date: this.editReturn.due_date,
                reason: this.editReturn.reason,
                status: this.editReturn.status,
                notes: this.editReturn.notes,
            });
            if (response.data.success) {
                Swal.fire('Updated!', response.data.message, 'success').then(() => {
                    window.location.reload();
                });
            }
        } catch (error) {
            Swal.fire('Error', error.response?.data?.message || 'Something went wrong', 'error');
        } finally {
            this.isEditSubmitting = false;
        }
    },

    confirmDelete(id, code) {
        deleteRecordWithPassword(`/purchase/returns/${id}`, code, {
            title: 'Delete Return Record?',
            text: `Are you sure you want to delete ${code}? This action is irreversible.`
        });
    }
}">
    
    <!-- Top Header Navigation -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Stock Return Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openCreateModal()" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                <span>Create Return Record</span>
            </button>
        </div>
    </div>

    <!-- Stats Cards (Premium Style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Return Value</p>
                <h3 class="text-[18px] font-black text-primary"><span x-text="currency"></span> <span x-text="returns.reduce((acc, r) => acc + parseFloat(r.total_amount), 0).toLocaleString(undefined, {minimumFractionDigits:2})"></span></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-graph-up-arrow text-[10px]"></i> Total value recovered
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-arrow-return-left text-lg"></i>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Processing</p>
                <h3 class="text-[18px] font-black text-primary" x-text="returns.filter(r => r.status === 'pending').length"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-clock-fill text-[10px]"></i> Awaiting synchronization
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-clock-history text-lg"></i>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Success Returns</p>
                <h3 class="text-[18px] font-black text-primary" x-text="returns.filter(r => r.status === 'approved' || r.status === 'active' || r.status === 'refunded').length"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Successfully restored to stock</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-shield-check text-lg"></i>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Debit Notes Issued</p>
                <h3 class="text-[18px] font-black text-primary" x-text="returns.length"></h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Total financial reversals</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-file-earmark-text text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        
        <!-- Filters (Matches Customer Screen) -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" @input="resetPage()" placeholder="Search by name, bill # or return ref..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>
            
            <!-- Date From -->
            <div class="relative min-w-[150px]">
                <input type="date" x-model="fromDate" @change="resetPage()" 
                    class="w-full pl-3 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer">
            </div>

            <!-- Date To -->
            <div class="relative min-w-[150px]">
                <input type="date" x-model="toDate" @change="resetPage()" 
                    class="w-full pl-3 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer">
            </div>

            <!-- Supplier Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="supplierFilter" @change="resetPage()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Branch Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="branchFilter" @change="resetPage()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Clear Filters -->
            <button @click="searchTerm = ''; fromDate = ''; toDate = ''; supplierFilter = ''; branchFilter = '';" 
                    class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10" 
                    x-show="searchTerm !== '' || fromDate !== '' || toDate !== '' || supplierFilter !== '' || branchFilter !== ''"
                    x-transition
                    title="Clear All Filters">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Table Title -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Stock Return Records</h2>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left" id="returnsTable">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Bill Ref.</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Supplier</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Product Info</th>
                        <th class="px-5 py-4 text-right text-[12px] font-black text-primary-dark uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-4 text-center text-[12px] font-black text-primary-dark uppercase tracking-wider">Status</th>
                        <th class="px-5 py-4 text-center text-[12px] font-black text-primary-dark uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="filteredReturns.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center gap-4 text-gray-200">
                                    <i class="bi bi-inbox text-7xl"></i>
                                    <span class="text-[12px] font-black uppercase tracking-widest italic tracking-[0.2em]">No procurement returns identified for current filters</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="(r, index) in paginatedReturns" :key="r.id">
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group border-b border-gray-100">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                <span x-text="String((currentPage - 1) * pageSize + index + 1).padStart(2, '0')"></span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark" x-text="new Date(r.return_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })"></td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark" x-text="r.bill?.bill_number || 'No Ref'"></td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark" x-text="r.supplier?.name || 'Unknown Entity'"></td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                <div class="flex flex-col leading-tight">
                                    <span x-text="r.items && r.items[0] ? (r.items[0].product?.product_name || 'Item') : 'Mixed Items'"></span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter mt-1" x-text="`Manifest: ${r.items?.length || 0} items`"></span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                                <span x-text="currency" class="text-gray-400 mr-0.5"></span> <span x-text="parseFloat(r.total_amount).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider"
                                    :class="{
                                        'bg-accent/10 text-primary border-accent/20': r.status === 'approved' || r.status === 'active' || r.status === 'refunded',
                                        'bg-gray-100 text-gray-400 border-gray-200': r.status === 'pending',
                                        'bg-primary/10 text-primary border-primary/20': r.status === 'rejected'
                                    }"
                                    x-text="r.status === 'approved' || r.status === 'active' || r.status === 'refunded' ? 'PAID' : r.status.toUpperCase()"></span>
                            </td>
                            <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a :href="`{{ url('/purchase/returns/') }}/${r.id}/view`" class="btn-action-view" title="View Return" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button @click="openEditModal(r)" class="btn-action-edit" title="Edit Return">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="confirmDelete(r.id, r.return_number)" class="btn-action-delete" title="Delete Return">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Premium Pagination Footer (Matches Customer) -->
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest"
               x-text="`Showing ${filteredReturns.length > 0 ? (currentPage - 1) * pageSize + 1 : 0} to ${Math.min(currentPage * pageSize, filteredReturns.length)} of ${filteredReturns.length} entries`"></p>
            <div class="flex items-center gap-1.5" x-show="filteredReturns.length > 0">
                <button @click="prevPage()" :disabled="currentPage === 1" 
                    class="w-7 h-7 flex items-center justify-center rounded-[0.4rem] border border-gray-200 bg-white transition-all text-xs"
                    :class="currentPage === 1 ? 'text-gray-200 cursor-not-allowed' : 'text-gray-500 hover:bg-gray-50 hover:border-gray-300'">
                    <i class="bi bi-chevron-left"></i>
                </button>
                
                <template x-for="p in totalPages" :key="p">
                    <button @click="currentPage = p" 
                        class="w-7 h-7 flex items-center justify-center rounded-[0.4rem] text-[11px] font-black transition-all"
                        :class="currentPage === p ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:border-gray-300'"
                        x-text="p">
                    </button>
                </template>

                <button @click="nextPage()" :disabled="currentPage === totalPages" 
                    class="w-7 h-7 flex items-center justify-center rounded-[0.4rem] border border-gray-200 bg-white transition-all text-xs"
                    :class="currentPage === totalPages ? 'text-gray-200 cursor-not-allowed' : 'text-gray-500 hover:bg-gray-50 hover:border-gray-300'">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div x-show="activeModal === 'return-modal'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-background rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col" @click.away="activeModal = null">

            <!-- Header -->
            <div class="px-6 py-5 bg-primary shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-4 text-white">
                    <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div>
                        <h2 class="text-[18px] font-bold tracking-tight">Purchase Return</h2>
                        <p class="text-xs text-white/60 font-medium mt-0.5">Fill in the required details below</p>
                    </div>
                </div>
                <button @click="activeModal = null"
                    class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow space-y-4">

                <!-- Section: Return Details -->
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-6">
                    <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-5 pb-2 border-b border-gray-100">
                        Return Details
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Purchase Bill <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select x-model="selectedBillId" @change="updateBillItems()"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Purchase Bill --</option>
                                    <template x-for="bill in bills" :key="bill.id">
                                        <option :value="bill.id" x-text="`${bill.bill_number} — ${bill.supplier ? bill.supplier.name : 'Unknown'}`"></option>
                                    </template>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                            <div class="px-1 text-[11px] font-black text-accent uppercase tracking-tight" x-show="selectedBill">
                                BAL: <span x-text="currency + ' ' + parseFloat(selectedSupplierBalance).toLocaleString(undefined,{minimumFractionDigits:2})"></span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Return Date <span class="text-primary">*</span></label>
                            <input type="date" x-model="returnDate"
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Return Reason <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select x-model="returnReason"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Reason --</option>
                                    <option value="damaged">Damaged on Arrival</option>
                                    <option value="technical">Defective / Not Working</option>
                                    <option value="wrong_sku">Wrong Item Delivered</option>
                                    <option value="quality">Quality Issue</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Due Date <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                            <input type="date" x-model="dueDate"
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Section: Return Items -->
                <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                        <i class="bi bi-box-seam text-primary-dark text-sm"></i>
                        <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Return Items</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left">
                            <thead>
                                <tr class="bg-white border-b border-gray-100">
                                    <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-r border-gray-100">Item</th>
                                    <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center w-32 border-r border-gray-100">Qty</th>
                                    <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center w-36 border-r border-gray-100">Price/Unit</th>
                                    <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right w-36">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="returnItems.length === 0">
                                    <tr>
                                        <td colspan="4" class="px-6 py-14 text-center">
                                            <div class="flex flex-col items-center gap-2 text-gray-300">
                                                <i class="bi bi-box-seam text-5xl"></i>
                                                <span class="text-[11px] font-bold uppercase tracking-widest">Select a bill above to load items</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="(item, index) in returnItems" :key="index">
                                    <tr class="hover:bg-gray-50/60 transition-colors bg-white border-b border-gray-100">
                                        <td class="px-5 py-4 border-r border-gray-100">
                                            <p class="text-[13px] font-bold text-primary-dark" x-text="item.name"></p>
                                            <div class="flex items-center gap-3 mt-0.5">
                                                <span class="text-[10px] text-gray-400 font-semibold" x-text="`Ordered: ${item.original_qty}`"></span>
                                                <span class="text-[10px] text-primary font-semibold" x-show="item.already_returned_qty > 0" x-text="`Returned: ${item.already_returned_qty}`"></span>
                                                <span class="text-[10px] text-accent font-black" x-text="`Available: ${item.remaining_qty}`"></span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-center border-r border-gray-100">
                                            <input type="number" x-model="item.return_qty" :max="item.remaining_qty" min="0"
                                                class="w-20 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-center font-bold text-primary-dark text-[13px] focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </td>
                                        <td class="px-5 py-4 text-center border-r border-gray-100">
                                            <span class="text-[13px] font-semibold text-gray-500" x-text="`${currency} ${parseFloat(item.rate).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="text-[13px] font-black text-primary-dark" x-text="`${currency} ${(parseFloat(item.return_qty||0) * parseFloat(item.rate)).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section: Summary -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Bank Account -->
                    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 lg:col-span-2">
                        <p class="text-[11px] font-bold text-primary-dark uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Refund Details</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-primary-dark uppercase tracking-wider mb-1">Amount Paid</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400" x-text="currency"></span>
                                    <input type="number" x-model="amountPaid" min="0" step="0.01"
                                        class="w-full pl-7 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-right">
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-primary-dark uppercase tracking-wider mb-1">Bank Account</label>
                                <select x-model="bankAccountId"
                                    class="w-full pl-4 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[12px] font-semibold text-primary-dark focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Account --</option>
                                    <template x-for="account in bankAccounts" :key="account.id">
                                        <option :value="account.id" x-text="account.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 lg:col-span-1">
                        <p class="text-[10px] font-black text-primary-dark uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Return Summary</p>
                        <div class="space-y-2 mb-3">
                            <div class="flex justify-between text-[11px] font-semibold text-gray-500">
                                <span>Supplier Balance</span>
                                <span x-text="`${currency} ${parseFloat(selectedSupplierBalance).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-semibold text-gray-500">
                                <span>— Return Total</span>
                                <span x-text="`${currency} ${calculateSubtotal().toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                            </div>
                            <div class="flex justify-between text-[11px] font-semibold text-gray-500 pb-2 border-b border-gray-100">
                                <span>— Amount Paid</span>
                                <span x-text="`${currency} ${parseFloat(amountPaid||0).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                            </div>
                        </div>
                        <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between">
                            <span class="text-[11px] font-black text-white uppercase tracking-wider">New Balance</span>
                            <span class="text-[15px] font-black text-accent" x-text="`${currency} ${resultingBalance.toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 bg-white flex items-center justify-between shrink-0">
                <button type="button" @click="activeModal = null" class="btn-premium-accent">Cancel</button>
                <button @click="submitReturn()" :disabled="isSubmitting" class="btn-premium-primary">
                    <i class="bi bi-check2-circle" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'Processing...' : 'Process Return'"></span>
                </button>
            </div>
        </div>
    </div>


    <!-- Edit Return Modal -->
    <div x-show="activeModal === 'edit-return-modal'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">

            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Update Return</h2>
                            <p class="text-xs text-white/60 font-medium mt-0.5" x-text="'Ref: ' + editReturn.return_number"></p>
                        </div>
                    </div>
                    <button @click="activeModal = null"
                        class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Content -->
            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">

                <!-- Row 1: Identifier + Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Return Identifier</label>
                        <div class="relative">
                            <input type="text" :value="editReturn.return_number" readonly
                                class="w-full px-5 py-3.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-bold text-gray-500 cursor-not-allowed pr-10">
                            <i class="bi bi-hash absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Return Processing Date <span class="text-primary">*</span></label>
                        <input type="date" x-model="editReturn.return_date"
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <!-- Row 2: Reference Bill + Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reference Bill ID</label>
                        <div class="relative">
                            <input type="text" :value="editReturn.bill_number || 'N/A'" readonly
                                class="w-full px-5 py-3.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-bold text-gray-500 cursor-not-allowed pr-10">
                            <i class="bi bi-receipt absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Status <span class="text-primary">*</span></label>
                        <div class="relative">
                            <select x-model="editReturn.status"
                                class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer pr-12">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="refunded">Refunded</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Reason + Due Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Return Reason <span class="text-primary">*</span></label>
                        <div class="relative">
                            <select x-model="editReturn.reason"
                                class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer pr-12">
                                <option value="damaged">Physical Damage on Arrival</option>
                                <option value="technical">Technical Malfunction / Defect</option>
                                <option value="wrong_sku">SKU Mismatch (Wrong Item)</option>
                                <option value="quality">Quality Control Violation</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Due Date <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                        <input type="date" x-model="editReturn.due_date"
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <!-- Supplier Info Bar -->
                <div class="rounded-xl bg-primary/5 border border-primary/10 px-5 py-4 flex items-center gap-6 mb-6">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Supplier</span>
                        <span class="text-[13px] font-black text-primary-dark" x-text="editReturn.supplier_name"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Amount</span>
                        <span class="text-[13px] font-black text-primary" x-text="'$ ' + parseFloat(editReturn.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                    </div>
                    <div class="flex flex-col ml-auto text-right">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Items</span>
                        <span class="text-[13px] font-black text-primary-dark" x-text="(editReturn.items || []).length + ' item(s)'"></span>
                    </div>
                </div>

                <!-- Reclamation Inventory (read-only) -->
                <div class="mb-4 flex items-center gap-2">
                    <i class="bi bi-box-seam text-primary text-sm"></i>
                    <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Reclamation Inventory</h3>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm mb-6">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white border-b border-gray-100">
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Item Details</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Return Qty</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Unit Rate</th>
                                <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="!editReturn.items || editReturn.items.length === 0">
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2 text-gray-300">
                                            <i class="bi bi-box-seam text-4xl"></i>
                                            <span class="text-[11px] font-black uppercase tracking-widest">No Items Found</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(item, idx) in (editReturn.items || [])" :key="idx">
                                <tr class="hover:bg-gray-50/60 transition-colors bg-white border-b border-gray-50">
                                    <td class="px-6 py-4">
                                        <span class="text-[13px] font-black text-primary" x-text="item.name"></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center w-20 h-9 bg-gray-100 rounded-lg text-[13px] font-black text-primary-dark" x-text="item.quantity"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-[13px] font-bold text-gray-400" x-text="'$ ' + parseFloat(item.unit_price).toLocaleString()"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-[14px] font-black text-primary" x-text="'$ ' + parseFloat(item.subtotal || (item.quantity * item.unit_price)).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Totals + Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Notes -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Notes <span class="text-gray-400 font-medium normal-case">(optional)</span></label>
                        <input type="text" x-model="editReturn.notes" rows="4" placeholder="Add any additional notes..."
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></input>
                    </div>
                    <!-- Total Summary -->
                    <div class="rounded-xl border border-gray-200 overflow-hidden h-fit">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary/60">Net Credit Value</span>
                            <span class="text-[14px] font-black text-accent" x-text="'$ ' + parseFloat(editReturn.subtotal || editReturn.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary/60">Grand Total</span>
                            <span class="text-[16px] font-black text-primary" x-text="'$ ' + parseFloat(editReturn.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits:2})"></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button type="button" @click="activeModal = null" class="btn-premium-accent">
                    Cancel
                </button>
                <button @click="submitEditReturn()" :disabled="isEditSubmitting" class="btn-premium-primary">
                    <i class="bi bi-check2-circle" x-show="!isEditSubmitting"></i>
                    <span x-text="isEditSubmitting ? 'Saving...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

@endsection


