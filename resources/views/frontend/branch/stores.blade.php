@extends('admin.admin_master')
@section('page_title', 'Stores')

@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{
    activeModal: null,
    editMode: false,
    searchTerm: '',
    statusFilter: '',
    branchFilter: '',
    storeData: {
        id: '',
        name: '',
        branch_id: '',
        location: '',
        phone: '',
        email: '',
        status: 'Active',
        code: ''
    },

    openCreateModal() {
        this.editMode = false;
        this.storeData = {
            id: '',
            name: '',
            branch_id: '{{ $branches->first()->id ?? '' }}',
            location: '',
            phone: '',
            email: '',
            status: 'Active',
            code: ''
        };
        this.activeModal = 'store-modal';
        document.getElementById('storeForm').reset();
    },

    openEditModal(store) {
        this.editMode = true;
        this.storeData = { ...store };
        this.activeModal = 'store-modal';
    },

    confirmDelete(id, name) {
        Swal.fire({
            title: 'Close Store?',
            text: `Are you sure you want to delete ${name}? This will archive its inventory data.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                popup: 'rounded-[1.5rem]',
                confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
                cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
}">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Store Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openCreateModal()"
                class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                Add New Store
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Stores -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Stores</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['total_stores']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-shop text-[10px]"></i> Active store units
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-shop text-lg"></i>
            </div>
        </div>

        <!-- Gross Sales -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Revenue</p>
                <h3 class="text-[18px] font-black text-primary">{{ $company->currency_symbol ?? '$' }}{{ number_format($stats['total_sales'] ?? 0, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-cash-stack text-[10px]"></i> Total store sales
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-cash-stack text-lg"></i>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Inventory</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['total_quantity']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Total stock on hand</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-boxes text-lg"></i>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Low Stock</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($stats['low_stock_total']) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-exclamation-triangle text-[10px]"></i> Restock required
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-exclamation-circle text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

        <!-- Filters -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" placeholder="Search stores..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <!-- Status Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="statusFilter"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="Active">Active Only</option>
                    <option value="Inactive">Inactive Only</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Branch Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="branchFilter"
                    class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Branches</option>
                    @foreach($stores->unique('branch_id') as $s)
                        <option value="{{ $s->branch_id }}">{{ $s->branch->name ?? 'Regional Hub' }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Clear Filters -->
            <button @click="searchTerm = ''; statusFilter = ''; branchFilter = '';" 
                    class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10" 
                    x-show="searchTerm !== '' || statusFilter !== '' || branchFilter !== ''"
                    x-transition
                    title="Clear All Filters">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Table Title -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Store List</h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Store</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Branch</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Location</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Inventory</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stores as $store)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                            x-show="(searchTerm === '' || '{{ strtolower($store->name) }}'.includes(searchTerm.toLowerCase()) || '{{ strtolower($store->code ?? '') }}'.includes(searchTerm.toLowerCase())) && (statusFilter === '' || '{{ $store->status ?? 'Active' }}' === statusFilter) && (branchFilter === '' || '{{ $store->branch_id }}' === branchFilter)">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-[12px] font-semibold text-primary-dark leading-tight">{{ $store->name }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $store->branch->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ $store->location ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[12px] font-semibold text-primary-dark">{{ number_format($store->total_quantity) }} Qty</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-semibold text-primary-dark capitalize">{{ $store->status ?? 'Active' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="openEditModal(@js($store))" class="btn-action-edit" title="Edit Store">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button @click="confirmDelete('{{ $store->id }}', '{{ $store->name }}')" class="btn-action-delete" title="Delete">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                    <form id="delete-form-{{ $store->id }}" action="{{ route('store.delete', $store->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center text-gray-400 text-xs italic">No stores found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($stores->count() > 0)
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                    Showing {{ $stores->firstItem() }} to {{ $stores->lastItem() }} of {{ $stores->total() }} entries
                </p>
                <div class="flex items-center gap-1">
                    @if ($stores->onFirstPage())
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                            <i class="bi bi-chevron-left text-xs"></i>
                        </button>
                    @else
                        <a href="{{ $stores->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </a>
                    @endif

                    @foreach ($stores->links()->elements as $element)
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $stores->currentPage())
                                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                                @else
                                    <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm text-xs font-bold">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($stores->hasMorePages())
                        <a href="{{ $stores->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
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

    <!-- STORE MODAL -->
    <div x-show="activeModal === 'store-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
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
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight" x-text="editMode ? 'Edit Store' : 'Add New Store'"></h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Fill in the required details below</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <form id="storeForm" :action="editMode ? '/store/update/' + storeData.id : '/store/store'" method="POST">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Store Name <span class="text-primary">*</span></label>
                                <div class="relative">
                                    <input type="text" name="name" x-model="storeData.name" required placeholder="e.g. Main Showroom"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    <i class="bi bi-shop absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch / Hub <span class="text-primary">*</span></label>
                                <div class="relative">
                                    <select name="branch_id" x-model="storeData.branch_id" required
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                        <option value="">Select a branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Unit Code <span class="text-primary">*</span></label>
                                <div class="relative">
                                    <input type="text" name="code" x-model="storeData.code" required placeholder="e.g. ST-001"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    <i class="bi bi-hash absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Operational Status</label>
                                <div class="relative">
                                    <select name="status" x-model="storeData.status" required
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                        <option value="Active">Operational (Active)</option>
                                        <option value="Inactive">Decommissioned (Inactive)</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Contact Email</label>
                                <div class="relative">
                                    <input type="email" name="email" x-model="storeData.email" placeholder="store@horntech.com"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    <i class="bi bi-envelope absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Contact Phone</label>
                                <div class="relative">
                                    <input type="text" name="phone" x-model="storeData.phone" placeholder="+966 5X XXX XXXX"
                                        inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    <i class="bi bi-telephone absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Specific Location / Internal Address</label>
                            <div class="relative">
                                <input type="text" name="location" x-model="storeData.location" placeholder="e.g. Ground Floor, Wing A"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <i class="bi bi-geo-alt absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <button type="button" @click="activeModal = null"
                    class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] uppercase tracking-wide shadow-sm min-w-[120px]">
                    Cancel
                </button>
                <button type="submit" form="storeForm"
                    class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] uppercase tracking-wide shadow-sm flex items-center justify-center gap-2 min-w-[150px]">
                    <i class="bi bi-check2-circle"></i>
                    <span x-text="editMode ? 'Update Store' : 'Save Store'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
