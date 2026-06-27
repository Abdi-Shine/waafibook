@extends('admin.admin_master')
@section('page_title', 'Products')
@section('admin')


@php
    $symbol = '$';
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{ 
    activeModal: null,
    categories: @js($categories),
    searchTerm: '{{ request('search') }}',
    categoryFilter: '{{ request('category_id') }}',
    locationFilter: '',
    statusFilter: '{{ request('status') }}',
    searchDebounceTimer: null,
    editMode: false,

    init() {
        this.$watch('categoryFilter', () => this.applyServerFilters());
        this.$watch('statusFilter', () => this.applyServerFilters());
        if ('{{ request('action') }}' === 'create') {
            this.openCreateModal();
        }
    },

    applyServerFilters() {
        const params = new URLSearchParams();
        if (this.searchTerm) params.set('search', this.searchTerm);
        if (this.categoryFilter) params.set('category_id', this.categoryFilter);
        if (this.statusFilter) params.set('status', this.statusFilter);
        window.location.href = '{{ url('/products') }}' + (params.toString() ? '?' + params.toString() : '');
    },

    onSearchInput() {
        clearTimeout(this.searchDebounceTimer);
        this.searchDebounceTimer = setTimeout(() => this.applyServerFilters(), 500);
    },
    isImporting: false,
    isSavingCategory: false,
    saving: false,
    savedProduct: null,
    formErrors: {},
    categoryData: {
        name: '',
        description: ''
    },
    productData: {
        id: '',
        product_name: '',
        product_code: '',
        category_id: '',
        unit: 'Piece',
        purchase_price: '',
        selling_price: '',
        stock_products: '',
        description: '',
        account_code: '',
        branch_id: '',
        product_type: 'product'
    },

    openCreateModal() {
        this.editMode = false;
        this.savedProduct = null;
        this.formErrors = {};
        this.productData = {
            id: '', product_name: '', product_code: '', category_id: '',
            unit: 'Piece', purchase_price: '', selling_price: '',
            stock_products: '', description: '', account_code: '', branch_id: '',
            product_type: 'product'
        };
        this.activeModal = 'product-modal';
        document.getElementById('productForm').reset();
        this.$nextTick(() => {
            this.$refs.previewImg.src = '';
            this.$refs.previewPlaceholder.classList.remove('hidden');
            this.$refs.previewImg.classList.add('hidden');
        });
    },

    async submitProduct() {
        this.saving = true;
        this.formErrors = {};
        try {
            const form = document.getElementById('productForm');
            const url = this.editMode
                ? '{{ url('/products/update') }}/' + this.productData.id
                : '{{ route('product.store') }}';
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content },
                body: new FormData(form)
            });
            const data = await response.json();

            if (response.status === 422) {
                this.formErrors = data.errors || {};
                if (data.message && !data.errors) {
                    Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message });
                }
                return;
            }
            if (!response.ok) {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: data.message || 'Please try again.' });
                return;
            }

            if (this.editMode) {
                window.location.reload();
                return;
            }

            this.savedProduct = data.product;
            setTimeout(() => {
                window.location.href = window.location.pathname + '?action=create';
            }, 900);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save product. Please try again.' });
        } finally {
            this.saving = false;
        }
    },

    openEditModal(product) {
        this.editMode = true;
        this.savedProduct = null;
        this.formErrors = {};
        this.productData = {
            id: product.id,
            product_name: product.product_name,
            product_code: product.product_code,
            category_id: product.category_id || '',
            unit: product.unit || 'Piece',
            purchase_price: product.purchase_price,
            selling_price: product.selling_price,
            stock_products: product.stocks_sum_quantity || 0,
            description: product.description || '',
            account_code: product.account_code || '',
            branch_id: product.stocks && product.stocks.length > 0 ? product.stocks[0].branch_id || '' : '',
            product_type: product.product_type || 'product'
        };
        this.activeModal = 'product-modal';
        this.$nextTick(() => {
            if(product.image) {
                this.$refs.previewImg.src = '/' + product.image;
                this.$refs.previewPlaceholder.classList.add('hidden');
                this.$refs.previewImg.classList.remove('hidden');
            } else {
                this.$refs.previewImg.src = '';
                this.$refs.previewPlaceholder.classList.remove('hidden');
                this.$refs.previewImg.classList.add('hidden');
            }
        });
    },

    confirmDelete(id, name) {
        deleteRecordWithPassword('{{ url('/products/delete') }}/' + id, name, {
            title: 'Delete Product?',
            text: `Are you sure you want to delete ${name}? This action cannot be undone.`
        });
    },

    openImportModal() {
        this.activeModal = 'import-modal';
        this.isImporting = false;
        this.$nextTick(() => {
            const form = document.getElementById('importForm');
            if (form) form.reset();
        });
    },

    openCategoryModal() {
        this.categoryData = { name: '', description: '' };
        this.activeModal = 'category-modal';
        this.isSavingCategory = false;
    },

    async saveCategory() {
        if (!this.categoryData.name) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Category name is required' });
            return;
        }

        this.isSavingCategory = true;
        try {
            const response = await fetch('{{ route('category.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(this.categoryData)
            });

            const data = await response.json();
            
            if (response.ok && data.success) {
                // Add new category to the local categories list
                this.categories.push(data.category);
                // Also optionally, select it automatically in the form
                this.productData.category_id = data.category.id;
                
                this.activeModal = 'product-modal'; // Return to product modal
                
                // Show tiny success toast
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Category added successfully',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to add category' });
            }
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong' });
        } finally {
            this.isSavingCategory = false;
        }
    }
}">
    <!-- Header Section -->
    <div class="animate-in fade-in slide-in-from-top-4 duration-500 flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Product Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openImportModal()" class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case">
                    <i class="bi bi-download group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Import CSV</span>
                </button>
             <a href="{{ route('product.export') }}" class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-semibold rounded-[0.5rem] hover:bg-accent/90 transition-all shadow-sm text-sm group normal-case">
                    <i class="bi bi-file-earmark-excel group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Export</span>
                </a>
            <button @click="openCreateModal()" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                Add Product
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Products -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Products</p>
                <h3 class="text-[18px] font-black text-primary">{{ number_format($totalProducts) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-box-seam text-[10px]"></i> Total active items
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-box-seam text-lg"></i>
            </div>
        </div>
        
        <!-- Stock Value -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Value</p>
                <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($totalStockValue, 0) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Based on purchase price</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-currency-dollar text-lg"></i>
            </div>
        </div>
        
        <!-- Low Stock -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Low Stock Items</p>
                <h3 class="text-[18px] font-black text-primary">{{ $lowStockItems }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-exclamation-triangle text-[10px]"></i> Requires restocking
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-exclamation-triangle text-lg"></i>
            </div>
        </div>
        
        <!-- Out of Stock -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Out of Stock</p>
                <h3 class="text-[18px] font-black text-primary">{{ $outOfStock }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-x-circle text-[10px]"></i> Immediate action needed
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-x-circle text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6" :class="activeModal ? 'z-0' : 'z-10'">

        <!-- Filters -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <!-- Search -->
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                <input type="text" x-model="searchTerm" @input="onSearchInput()" placeholder="Search products..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <!-- Category Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="categoryFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Categories</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Location Filter -->
            <div class="relative min-w-[150px]">
                <select x-model="locationFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Locations</option>
                    @if($branches->count() > 0)
                        <optgroup label="Branches">
                            @foreach($branches as $branch)
                                <option value="branch_{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>

            <!-- Clear Filters -->
            <button @click="searchTerm = ''; categoryFilter = ''; locationFilter = ''; statusFilter = ''; applyServerFilters();"
                    class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-primary/10" 
                    x-show="searchTerm !== '' || categoryFilter !== '' || locationFilter !== '' || statusFilter !== ''"
                    x-transition
                    title="Clear All Filters">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Table Title Bar -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Product Inventory List</h2>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider leading-tight">Product</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Category</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Branch</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Selling Price</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Purchase Price</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Stock</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50/60 transition-colors bg-white group" x-show="
                        locationFilter === '' ||
                            @if($product->stocks->count() > 0)
                                @php $s = $product->stocks->first(); @endphp
                                (locationFilter === 'branch_{{ $s->branch_id }}')
                            @else
                                false
                            @endif
                    ">
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                            {{ str_pad(($products->currentPage() - 1) * $products->perPage() + $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-[12px] font-semibold text-primary-dark leading-tight">{{ $product->product_name }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-[12px] font-semibold text-primary-dark">{{ $product->category->name ?? 'General' }}</span>
                        </td>
                        <td class="px-5 py-4">
                            @if($product->stocks->count() > 0)
                                @php $stock = $product->stocks->firstWhere('branch_id', '!=', null) ?? $product->stocks->first(); $extra = $product->stocks->count() - 1; @endphp
                                <div class="flex items-center gap-1 text-[12px] font-semibold text-primary-dark">
                                    @if($stock->branch_id && $stock->branch)
                                        <span>{{ $stock->branch->name }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                    @if($extra > 0)
                                        <span class="text-gray-400 font-medium" title="{{ $extra }} more locations">(+{{ $extra }})</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[12px] font-semibold text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[12px] font-semibold text-primary-dark">{{ $symbol }} {{ number_format($product->selling_price, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[12px] font-semibold text-primary-dark">{{ $symbol }} {{ number_format($product->purchase_price, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $currentStock = $product->stocks_sum_quantity ?? 0;
                                $stockStatus = $currentStock <= 0 ? 'text-red-600 bg-red-50' : ($currentStock <= $product->low_stock_threshold ? 'text-yellow-600 bg-yellow-50' : 'text-accent bg-accent/10');
                            @endphp
                            <span class="px-2 py-1 rounded text-[11px] font-bold {{ $stockStatus }}">
                                {{ number_format($currentStock) }} {{ $product->unit ?? 'pcs' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5 transition-opacity">
                                <a href="{{ route('product.ledger', ['product_id' => $product->id]) }}" class="btn-action-edit" title="View Transactions">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button @click="openEditModal(@js($product))" class="btn-action-edit" title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button @click="confirmDelete('{{ $product->id }}', '{{ $product->product_name }}')" class="btn-action-delete" title="Delete Product">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                <i class="bi bi-box-seam text-2xl"></i>
                            </div>
                            <p class="text-[13px] font-bold uppercase tracking-widest text-gray-400">No products found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->count() > 0)
            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} entries
                </div>
                <div class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($products->onFirstPage())
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </button>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($products->links()->elements as $element)
                        @if (is_string($element))
                            <span class="px-1 text-gray-400 text-xs">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $products->currentPage())
                                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                                @else
                                    <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </a>
                    @else
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- PRODUCT MODAL -->
    <div x-show="activeModal === 'product-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative">

            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i :class="editMode ? 'bi bi-pencil-square' : 'bi bi-box-seam'"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight" x-text="editMode ? 'Edit Product' : 'Add New Product'"></h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Fill in the required product details below</p>
                        </div>
                    </div>

                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">

                <!-- Saved Confirmation Banner -->
                <div x-show="savedProduct" x-cloak x-transition
                     class="mb-5 flex items-start gap-3 bg-accent/10 border border-accent/30 rounded-xl px-4 py-3">
                    <i class="bi bi-check-circle-fill text-accent text-lg mt-0.5"></i>
                    <div class="text-[13px]">
                        <p class="font-bold text-primary-dark">Your product have been saved. Thank you!</p>
                        <p class="text-text-secondary mt-0.5" x-text="savedProduct?.product_name"></p>
                    </div>
                </div>

                <form id="productForm" :action="editMode ? '{{ url('/products/update') }}/' + productData.id : '{{ route('product.store') }}'" method="POST" enctype="multipart/form-data"
                      @submit.prevent="submitProduct()">
                    @csrf
                    <template x-if="editMode">
                        @method('PUT')
                    </template>
                    
                    <div class="flex flex-col gap-4">
                        <!-- Top Row: Switch and Photo -->
                        <div class="flex items-center justify-between pb-2">
                            <input type="hidden" name="product_type" value="product">
                        </div>

                         <!-- Bottom: Form Fields (3-Column Grid) -->
                         <!-- Bottom: Form Fields (3-Column Grid) -->
                        <div class="space-y-6 pt-5 border-t border-dashed border-slate-100">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Row 1 -->
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Product Name <span class="text-primary">*</span></label>
                                    <div class="relative group">
                                        <input type="text" name="product_name" x-model="productData.product_name" required placeholder="Enter product name"
                                            :class="formErrors.product_name ? 'border-red-400' : 'border-gray-200'"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-tag absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <p x-show="formErrors.product_name" x-text="formErrors.product_name?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>

                                <input type="hidden" name="product_code" x-model="productData.product_code">

                                <div class="space-y-1.5 flex flex-col justify-end">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category</label>
                                    <div class="relative flex items-center gap-2">
                                        <div class="relative flex-1 group">
                                            <select name="category_id" x-model="productData.category_id"
                                                :class="formErrors.category_id ? 'border-red-400' : 'border-gray-200'"
                                                class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                                <option value="">Select Category</option>
                                                <template x-for="cat in categories" :key="cat.id">
                                                    <option :value="cat.id" x-text="cat.name"></option>
                                                </template>
                                            </select>
                                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                        </div>
                                        <button type="button" @click="openCategoryModal()" title="Add New Category"
                                            class="w-[42px] h-[42px] shrink-0 border border-primary/20 bg-primary/5 text-primary hover:bg-primary hover:text-white rounded-lg flex items-center justify-center transition-all shadow-sm group">
                                            <i class="bi bi-plus-lg group-hover:scale-110 transition-transform text-sm font-bold"></i>
                                        </button>
                                    </div>
                                    <p x-show="formErrors.category_id" x-text="formErrors.category_id?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>
                                <!-- Product Image Upload -->
                            <div class="w-64 shrink-0">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Product Photo</label>
                                <label for="image_upload" class="flex items-center justify-center w-full px-4 py-2.5 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50/50 cursor-pointer hover:bg-white hover:border-primary/30 transition-all group overflow-hidden">
                                    <div x-ref="previewPlaceholder" class="flex items-center gap-3">
                                        <i class="bi bi-cloud-arrow-up text-xl text-primary/60 group-hover:scale-110 transition-transform"></i>
                                        <span class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Upload Image</span>
                                    </div>
                                    <img x-ref="previewImg" class="hidden w-full h-8 object-contain">
                                    <input id="image_upload" type="file" name="image" class="hidden" accept="image/*" @change="
                                        const file = $event.target.files[0];
                                        if(file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => { $refs.previewImg.src = e.target.result; $refs.previewPlaceholder.classList.add('hidden'); $refs.previewImg.classList.remove('hidden'); }
                                            reader.readAsDataURL(file);
                                        }
                                    " />
                                </label>
                            </div>
                              <!-- Row 2 -->
                                <div class="space-y-1.5" x-show="productData.product_type === 'product'" x-transition>
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider text-[#00A5DF]">BASE UNIT</label>
                                    <div class="relative">
                                        <select name="unit" x-model="productData.unit" :required="productData.product_type === 'product'"
                                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none border-primary/20 !border-2">
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Purchase Price</label>
                                    <div class="relative group">
                                        <input type="number" step="0.01" name="purchase_price" x-model="productData.purchase_price" placeholder="0.00"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-[13px]">{{ $symbol }}</span>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Selling Price <span class="text-primary">*</span></label>
                                    <div class="relative group">
                                        <input type="number" step="0.01" name="selling_price" x-model="productData.selling_price" required placeholder="0.00"
                                            :class="formErrors.selling_price ? 'border-red-400' : 'border-gray-200'"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all !text-accent">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 !text-accent font-bold text-[13px]">{{ $symbol }}</span>
                                    </div>
                                    <p x-show="formErrors.selling_price" x-text="formErrors.selling_price?.[0]" class="text-red-500 font-bold text-[11px]"></p>
                                </div>

                                <div class="space-y-1.5" x-show="productData.product_type === 'product'" x-transition>
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider" x-text="editMode ? 'Quantity' : 'Initial Stock'"></label>
                                    <div class="relative group">
                                        <input type="number" step="1" name="stock_products" x-model="productData.stock_products" placeholder="Opening quantity"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-box absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                </div>


                                <!-- Row 3 -->
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description</label>
                                    <textarea name="description" x-model="productData.description" placeholder="Enter product details..." rows="1"
                                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all min-h-[44px] resize-none overflow-hidden"
                                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"></textarea>
                                </div>

                                <input type="hidden" name="location_type" value="branch">

                            </div>
                        </div><!-- end space-y-5 -->
                    </div><!-- end flex flex-col gap-4 -->
                </form>
            </div><!-- end modal content -->

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <button type="button" @click="activeModal = null" 
                    class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] uppercase tracking-wide shadow-sm min-w-[120px]">
                    Cancel
                </button>
                <button type="submit" form="productForm" :disabled="saving"
                    class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] uppercase tracking-wide shadow-sm flex items-center justify-center gap-2 min-w-[150px]"
                    :class="saving ? 'opacity-60 cursor-not-allowed' : ''">
                    <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                    <span x-text="saving ? 'Saving...' : (editMode ? 'Update Product' : 'Save & New ')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="activeModal === 'import-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm z-top">
        
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">
            
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4 text-white">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-filetype-csv"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold tracking-tight">Import Products</h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Upload CSV to bulk add products</p>
                        </div>
                    </div>
                    
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white p-0">
                <form id="importForm" action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col h-full">
                    @csrf
                    
                    <div class="px-6 py-2 space-y-4 flex-grow">
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-2">Structure Requirements</h4>
                            <ul class="text-[11px] text-gray-500 list-disc list-inside space-y-1">
                                <li><strong>Column 1:</strong> Product Name (Required)</li>
                                <li><strong>Column 2:</strong> SKU/Code</li>
                                <li><strong>Column 3:</strong> Category Name</li>
                                <li><strong>Column 4:</strong> Base Unit</li>
                                <li><strong>Column 5:</strong> Purchase Price</li>
                                <li><strong>Column 6:</strong> Selling Price</li>
                                <li><strong>Column 7:</strong> Stock Quantity</li>
                                <li><strong>Column 8:</strong> Description</li>
                            </ul>
                            <p class="text-[10px] text-primary mt-2 font-medium">Download the <a href="{{ route('product.download-template') }}" class="underline decoration-accent font-black">Sample Template</a> for reference.</p>
                        </div>

                        <div class="space-y-1.5 pt-2">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Choose CSV File <span class="text-primary">*</span></label>
                            <input type="file" name="csv_file" required accept=".csv"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        </div>
                    </div>

                    <!-- Modal Footer (Now inside Form) -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                        <button type="button" @click="activeModal = null" 
                            class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] uppercase tracking-wide shadow-sm min-w-[120px]">
                            Cancel
                        </button>
                        <button type="submit" @click="isImporting = true" 
                            class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] uppercase tracking-wide shadow-sm flex items-center justify-center gap-2 min-w-[150px]">
                            <i class="bi bi-arrow-repeat animate-spin" x-show="isImporting" x-cloak></i>
                            <i class="bi bi-upload text-base" x-show="!isImporting"></i>
                            <span x-text="isImporting ? 'Importing...' : 'Confirm Import'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div x-show="activeModal === 'category-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm z-top">
        
        <div class="bg-white rounded-[1.25rem] w-full max-w-md overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = 'product-modal'">
            
            <div class="px-5 py-4 bg-primary relative overflow-hidden shrink-0 border-b border-white/10">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-3 text-white">
                        <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-lg backdrop-blur-md">
                            <i class="bi bi-folder-plus"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-base font-bold tracking-tight">Quick Add Category</h2>
                        </div>
                    </div>
                    
                    <button @click="activeModal = 'product-modal'" class="w-7 h-7 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="p-5 flex-grow bg-white">
                <form @submit.prevent="saveCategory" class="flex flex-col gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category Name <span class="text-primary">*</span></label>
                        <div class="relative group">
                            <input type="text" x-model="categoryData.name" required placeholder="Enter category name"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            <i class="bi bi-folder absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description (Optional)</label>
                        <textarea x-model="categoryData.description" placeholder="Enter optional description..." rows="2"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-2 pt-4 border-t border-gray-100">
                        <button type="button" @click="activeModal = 'product-modal'" :disabled="isSavingCategory"
                            class="px-5 py-2.5 text-gray-500 font-bold rounded-lg hover:bg-gray-100 transition-all text-[12px] uppercase tracking-wide">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSavingCategory"
                            class="px-5 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[12px] uppercase tracking-wide flex items-center gap-2">
                            <i class="bi" :class="isSavingCategory ? 'bi-hourglass-split animate-spin' : 'bi-check2-circle'"></i>
                            <span x-text="isSavingCategory ? 'Saving...' : 'Add Category'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
