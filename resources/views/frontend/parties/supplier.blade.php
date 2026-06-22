@extends('admin.admin_master')
@section('page_title', 'Suppliers')

@section('admin')
@php
    $currencySymbols = [
        'USD'=>'$','EUR'=>'€','GBP'=>'£','JPY'=>'¥','AUD'=>'A$','CAD'=>'C$',
        'CHF'=>'Fr','CNY'=>'¥','INR'=>'₹','MYR'=>'RM','SGD'=>'S$','AED'=>'د.إ',
        'SAR'=>'﷼','NGN'=>'₦','KES'=>'KSh','ZAR'=>'R',
    ];
    $symbol = $currencySymbols[$company->currency ?? 'USD'] ?? ($company->currency ?? '$');
@endphp

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="{
            activeModal: null,
            searchTerm: '',
            statusFilter: '',
            typeFilter: '',
            editMode: false,
            saving: false,
            savedSupplier: null,
            formErrors: {},
            supplierData: {
                id: '',
                supplier_code: '',
                first_name: '',
                last_name: '',
                name: '',
                email: '',
                phone: '',
                supplier_type: 'individual',
                address: '',
                account_id: '',
                amount_balance: 0
            },

            init() {
                if ('{{ request('reopen_create') }}' === '1') {
                    this.openCreateModal();
                }
            },

            openCreateModal() {
                this.editMode = false;
                this.savedSupplier = null;
                this.formErrors = {};
                this.supplierData = {
                    id: '',
                    name: '',
                    email: '',
                    phone: '',
                    supplier_type: 'individual',
                    address: '',
                    account_id: '',
                    amount_balance: 0
                };
                this.activeModal = 'supplier-modal';
                document.getElementById('supplierForm').reset();
            },

            async submitSupplier() {
                this.saving = true;
                this.formErrors = {};
                try {
                    const form = document.getElementById('supplierForm');
                    const response = await fetch('/suppliers', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content },
                        body: new FormData(form)
                    });
                    const data = await response.json();

                    if (response.status === 422) {
                        this.formErrors = data.errors || {};
                        return;
                    }
                    if (!response.ok) {
                        Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message || 'Please try again.' });
                        return;
                    }

                    this.savedSupplier = data;
                    setTimeout(() => {
                        window.location.href = window.location.pathname + '?reopen_create=1';
                    }, 900);
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save supplier. Please try again.' });
                } finally {
                    this.saving = false;
                }
            },

            openImportModal() {
                this.activeModal = 'import-modal';
                document.getElementById('importForm').reset();
            },

            openEditModal(supplier) {
                this.editMode = true;
                this.savedSupplier = null;
                this.formErrors = {};
                this.supplierData = { ...supplier };
                this.activeModal = 'supplier-modal';
            },

            confirmDelete(id, name) {
                deleteRecordWithPassword('{{ url('/suppliers') }}/' + id, name, {
                    title: 'Delete Supplier?',
                    text: `Are you sure you want to delete ${name}? This action cannot be undone.`
                });
            }
        }">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Supplier Management</h1>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openImportModal()" class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case">
                    <i class="bi bi-download group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Import CSV</span>
                </button>
                <a href="{{ route('supplier.export') }}" class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case">
                    <i class="bi bi-file-earmark-excel group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Export</span>
                </a>
                <button @click="openCreateModal()" class="btn-premium-primary group normal-case">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Add Supplier</span>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Suppliers -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Suppliers</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['total']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-truck text-[10px]"></i> Active Base
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-truck text-lg"></i>
                </div>
            </div>

            <!-- Active Suppliers -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Active Suppliers</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['active']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-building-check text-[10px]"></i> Verified Status
                    </p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-building-check text-lg"></i>
                </div>
            </div>

            <!-- Total Payables -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Payables</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $symbol ?? '$' }} {{ number_format($stats['payables'], 0) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">Outstanding Balances</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-wallet2 text-lg"></i>
                </div>
            </div>

            <!-- Business Suppliers -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Business Hub</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['company_suppliers']) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">B2B Partners</p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-buildings text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

            <!-- Filters -->
            <form action="{{ route('supplier.index') }}" method="GET"
                  x-data="{ hasFilters: {{ request()->hasAny(['search', 'status', 'type']) ? 'true' : 'false' }} }"
                  class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">

                <!-- Search -->
                <div class="relative group min-w-[250px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search suppliers..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Status -->
                <div class="relative min-w-[150px]">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Type -->
                <div class="relative min-w-[150px]">
                    <select name="type" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Types</option>
                        <option value="individual" {{ request('type') === 'individual' ? 'selected' : '' }}>Individual</option>
                        <option value="company"    {{ request('type') === 'company'    ? 'selected' : '' }}>Business</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Clear Filters -->
                <button type="button" onclick="window.location.href='{{ route('supplier.index') }}'"
                        x-show="hasFilters" x-transition
                        class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10"
                        title="Clear All Filters">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </form>

            <!-- Table Title -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Supplier List</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Supplier</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Contact</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Address</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Type</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Balance</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($suppliers as $key => $supplier)
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    {{ str_pad($suppliers->firstItem() + $key, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[12px] font-semibold text-primary-dark leading-tight">{{ $supplier->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[12px] font-semibold text-primary-dark">{{ $supplier->phone ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $supplier->address ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[12px] font-semibold text-primary-dark capitalize">{{ $supplier->supplier_type }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ $symbol ?? '$' }} {{ number_format($supplier->amount_balance, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @php $status = $supplier->status ?? 'active'; @endphp
                                    @if($status === 'active')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-accent/10 text-primary border border-accent/20 uppercase tracking-wider">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-gray-100 text-gray-400 border border-gray-200 uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('parties.ledger', ['type' => 'supplier', 'id' => $supplier->id]) }}" class="btn-action-view" title="View Ledger">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($supplier->phone)
                                            <button onclick="sendWhatsAppStatement('{{ $supplier->phone }}', '{{ \App\Support\PublicUrl::temporarySigned('supplier.statement.public-pdf', now()->addDays(7), ['id' => $supplier->id]) }}')"
                                                class="btn-action-whatsapp" title="Send Statement on WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        @endif
                                        <button @click="openEditModal(@js($supplier))" class="btn-action-edit" title="Edit Supplier">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button @click="confirmDelete('{{ $supplier->id }}', '{{ $supplier->name }}')" class="btn-action-delete" title="Delete Supplier">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                        <form id="delete-form-{{ $supplier->id }}" action="{{ route('supplier.destroy', $supplier->id) }}" method="POST" class="hidden">
                                            @csrf @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center">
                                    <div class="flex flex-col items-center gap-2 text-gray-300">
                                        <i class="bi bi-inbox text-4xl"></i>
                                        <span class="text-xs font-semibold text-gray-400">No suppliers found</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($suppliers->count() > 0)
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                        Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of {{ $suppliers->total() }} entries
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($suppliers->onFirstPage())
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                                <i class="bi bi-chevron-left text-xs"></i>
                            </button>
                        @else
                            <a href="{{ $suppliers->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                                <i class="bi bi-chevron-left text-xs"></i>
                            </a>
                        @endif

                        @foreach ($suppliers->links()->elements as $element)
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $suppliers->currentPage())
                                        <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                                    @else
                                        <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm text-xs font-bold">{{ $page }}</a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        @if ($suppliers->hasMorePages())
                            <a href="{{ $suppliers->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                                <i class="bi bi-chevron-right text-xs"></i>
                            </a>
                        @else
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                                <i class="bi bi-chevron-right text-xs"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- SUPPLIER MODAL -->
        <div x-show="activeModal === 'supplier-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative">

                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4 text-white">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold tracking-tight" x-text="editMode ? 'Edit Supplier' : 'Add New Supplier'"></h2>
                                <p class="text-xs text-white/60 font-medium mt-0.5">Fill in the required details below</p>
                            </div>
                        </div>
                        <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">

                    <!-- Saved Confirmation Banner -->
                    <div x-show="savedSupplier" x-cloak x-transition
                         class="mb-5 flex items-start gap-3 bg-accent/10 border border-accent/30 rounded-xl px-4 py-3">
                        <i class="bi bi-check-circle-fill text-accent text-lg mt-0.5"></i>
                        <div class="text-[13px]">
                            <p class="font-bold text-primary-dark">Your supplier have been saved. Thank you!</p>
                            <p class="text-text-secondary mt-0.5" x-text="savedSupplier?.name"></p>
                        </div>
                    </div>

                    <form id="supplierForm" :action="editMode ? '/suppliers/' + supplierData.id : '/suppliers'" method="POST"
                          @submit="if (!editMode) { $event.preventDefault(); submitSupplier(); }">
                        @csrf
                        <template x-if="editMode">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Full Name <span class="text-primary">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="name" x-model="supplierData.name" required placeholder="Enter full name"
                                            :class="formErrors.name ? 'border-red-400' : 'border-gray-200'"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-person absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                    <p x-show="formErrors.name" x-text="formErrors.name?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Balance Amount</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" name="amount_balance" x-model="supplierData.amount_balance" placeholder="0.00"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-currency-dollar absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Email Address</label>
                                    <div class="relative">
                                        <input type="email" name="email" x-model="supplierData.email" placeholder="supplier@example.com"
                                            :class="formErrors.email ? 'border-red-400' : 'border-gray-200'"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-envelope absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                    <p x-show="formErrors.email" x-text="formErrors.email?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone Number <span class="text-primary">*</span></label>
                                    <div class="relative">
                                        <input type="text" name="phone" x-model="supplierData.phone" required placeholder="+966 5X XXX XXXX"
                                            :class="formErrors.phone ? 'border-red-400' : 'border-gray-200'"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-telephone absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                    <p x-show="formErrors.phone" x-text="formErrors.phone?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-gray-100">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Supplier Type</label>
                                    <div class="relative">
                                        <select name="supplier_type" x-model="supplierData.supplier_type" required
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                            <option value="individual">Individual</option>
                                            <option value="company">Business</option>
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Address</label>
                                    <div class="relative">
                                        <input type="text" name="address" x-model="supplierData.address" placeholder="Enter physical address..."
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-geo-alt absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="activeModal = null" class="btn-premium-accent">
                        Cancel
                    </button>
                    <button type="submit" form="supplierForm" :disabled="saving" class="btn-premium-primary" :class="saving ? 'opacity-60 cursor-not-allowed' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : (editMode ? 'Update Supplier' : 'Save & New')"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- IMPORT MODAL -->
        <div x-show="activeModal === 'import-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">

                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4 text-white">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-filetype-csv"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold tracking-tight">Import Suppliers</h2>
                                <p class="text-xs text-white/60 font-medium mt-0.5">Upload a CSV file to import.</p>
                            </div>
                        </div>
                        <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                    <form id="importForm" action="{{ route('supplier.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <h4 class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">CSV Format Expected</h4>
                                <ul class="text-[11px] text-gray-500 list-disc list-inside space-y-1">
                                    <li><strong>Column 1:</strong> Full Name (Required)</li>
                                    <li><strong>Column 2:</strong> Email (Optional)</li>
                                    <li><strong>Column 3:</strong> Phone (Optional)</li>
                                    <li><strong>Column 4:</strong> Type ('individual' or 'business')</li>
                                    <li><strong>Column 5:</strong> Address (Optional)</li>
                                    <li><strong>Column 6:</strong> Initial Amount/Balance (Optional, e.g., 500)</li>
                                </ul>
                                <p class="text-[10px] text-gray-400 mt-2 italic">* First row is considered header and ignored.</p>
                            </div>
                            <div class="space-y-1.5 pt-2">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Select CSV File <span class="text-primary">*</span></label>
                                <input type="file" name="csv_file" required accept=".csv"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 cursor-pointer file:mr-4 file:py-1.5 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 outline-none">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="activeModal = null" class="btn-premium-accent">
                        Cancel
                    </button>
                    <button type="submit" form="importForm" class="btn-premium-primary">
                        <i class="bi bi-upload"></i>
                        <span>Confirm Import</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

@push('scripts')
<script>
function sendWhatsAppStatement(phone, pdfUrl) {
    const cleanPhone = phone.replace(/\D/g, '');
    const url = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(pdfUrl)}`;
    window.open(url, '_blank');
}
</script>
@endpush
@endsection
