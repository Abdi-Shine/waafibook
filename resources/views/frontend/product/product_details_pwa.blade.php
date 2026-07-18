@extends('admin.admin_master')
@section('page_title', 'Products')

@php
    $symbol = '$';
@endphp

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    search: '',
    showAddModal: false,
    saving: false,
    editMode: false,
    formErrors: {},
    categories: @js($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])),
    units: @js($units->pluck('name')),
    productData: { id: '', product_name: '', product_code: '', category_id: '', unit: 'Piece', purchase_price: '', selling_price: '', stock_products: '', description: '', product_type: 'product' },
    products: @js($products->map(fn($p) => [
        'id'          => $p->id,
        'name'        => $p->product_name,
        'code'        => $p->product_code,
        'category_id' => $p->category_id,
        'category'    => $p->category->name ?? null,
        'unit'        => $p->unit,
        'purchase_price' => (float) $p->purchase_price,
        'stock'       => $p->product_type === 'service' ? null : (float) ($p->stocks_sum_quantity ?? 0),
        'price'       => (float) $p->selling_price,
        'description' => $p->description,
        'type'        => $p->product_type,
        'image'       => $p->image,
        'url'         => route('product.ledger', ['type' => $p->product_type, 'product_id' => $p->id]),
    ])),
    get filtered() {
        if (!this.search) return this.products;
        const q = this.search.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q));
    },
    openAddModal() {
        this.editMode = false;
        this.formErrors = {};
        this.productData = { id: '', product_name: '', product_code: '', category_id: '', unit: 'Piece', purchase_price: '', selling_price: '', stock_products: '', description: '', product_type: 'product' };
        this.showAddModal = true;
        this.$nextTick(() => {
            this.$refs.previewImg.src = '';
            this.$refs.previewPlaceholder.classList.remove('hidden');
            this.$refs.previewImg.classList.add('hidden');
        });
    },
    openEditModal(product) {
        this.editMode = true;
        this.formErrors = {};
        this.productData = {
            id: product.id,
            product_name: product.name,
            product_code: product.code,
            category_id: product.category_id || '',
            unit: product.unit || 'Piece',
            purchase_price: product.purchase_price || '',
            selling_price: product.price,
            stock_products: product.stock ?? '',
            description: product.description || '',
            product_type: product.type || 'product',
        };
        this.showAddModal = true;
        this.$nextTick(() => {
            if (product.image) {
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
    async submitProduct() {
        this.saving = true;
        this.formErrors = {};
        try {
            const url = this.editMode
                ? '{{ url('/products/update') }}/' + this.productData.id
                : '{{ route('product.store') }}';
            // File uploads with PUT don't populate $_FILES in PHP, so always
            // POST and let Laravel's _method spoofing (below) route it as PUT.
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                },
                body: new FormData(this.$refs.productForm),
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
            const p = data.product;
            const row = {
                id: p.id,
                name: p.product_name,
                code: p.product_code,
                category_id: p.category_id,
                category: this.categories.find(c => c.id == p.category_id)?.name || null,
                unit: p.unit,
                purchase_price: parseFloat(p.purchase_price) || 0,
                stock: p.product_type === 'service' ? null : parseFloat(this.productData.stock_products) || 0,
                price: parseFloat(p.selling_price) || 0,
                description: p.description,
                type: p.product_type,
                image: p.image,
                url: '{{ url('/products/ledger') }}?type=' + p.product_type + '&product_id=' + p.id,
            };
            if (this.editMode) {
                const idx = this.products.findIndex(x => x.id === p.id);
                if (idx !== -1) this.products[idx] = row;
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: p.product_name + ' updated', timer: 1500, showConfirmButton: false });
            } else {
                this.products.unshift(row);
                Swal.fire({ toast: true, position: 'top', icon: 'success', title: p.product_name + ' added', timer: 1500, showConfirmButton: false });
            }
            this.showAddModal = false;
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save product. Please try again.' });
        } finally {
            this.saving = false;
        }
    }
}">
    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Product Management</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('product.index', ['action' => 'import', 'desktop' => 1]) }}"
                class="w-8 h-8 rounded-lg bg-accent/15 text-accent flex items-center justify-center" title="Import CSV">
                <i class="bi bi-download text-sm"></i>
            </a>
            <a href="{{ route('product.export') }}"
                class="w-8 h-8 rounded-lg bg-accent/15 text-accent flex items-center justify-center" title="Export">
                <i class="bi bi-file-earmark-excel text-sm"></i>
            </a>
        </div>
    </div>

    <div class="flex gap-3 px-5 pt-4 overflow-x-auto no-scrollbar">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-box-seam text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Total Items</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-x-circle text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Out of Stock</p>
            <p class="text-[16px] font-black text-primary">{{ number_format($stats['out_of_stock']) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm shrink-0 w-[120px]">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1 whitespace-nowrap">Stock Values</p>
            <p class="text-[16px] font-black text-primary">${{ number_format($stats['stock_value'], 0) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH PRODUCT"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
            <button x-show="search" @click="search = ''" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-base"></i>
            </button>
        </div>
        <button @click="openAddModal()"
           class="flex items-center gap-1 px-3 py-2.5 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Add Product
        </button>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        <template x-for="product in filtered" :key="product.id">
            <a :href="product.url"
               class="flex items-center justify-between px-5 py-4 border-b border-gray-100 last:border-0 active:bg-gray-50 transition-colors">
                <div class="min-w-0 pr-3">
                    <p class="text-[15px] font-black text-text-primary leading-tight truncate" x-text="product.name.toUpperCase()"></p>
                    <p class="text-xs text-text-secondary mt-0.5" x-text="product.category || (product.type === 'service' ? 'Service' : 'Uncategorized')"></p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="text-[15px] font-black text-text-primary" x-text="'$ ' + parseFloat(product.price).toFixed(2)"></p>
                        <p class="text-xs font-bold mt-0.5 text-gray-400" x-text="product.stock === null ? 'Service' : 'Stock: ' + parseFloat(product.stock).toFixed(2)"></p>
                    </div>
                    <button type="button" @click.stop.prevent="openEditModal(product)"
                        class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                        <i class="bi bi-pencil text-xs"></i>
                    </button>
                </div>
            </a>
        </template>
        <template x-if="!filtered.length">
            <div class="py-10 text-center">
                <i class="bi bi-box-seam text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold" x-text="search ? 'No items match your search' : 'No products yet'"></p>
            </div>
        </template>
    </div>

    {{-- Add/Edit Product — mobile bottom sheet --}}
    <div x-show="showAddModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddModal = false">
        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]" x-text="editMode ? 'Edit Product' : 'Add Product'"></h2>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold cursor-pointer" @click="productData.product_type = 'product'" :class="productData.product_type === 'product' ? 'text-white' : 'text-white/50'">Product</span>
                        <button type="button" @click="productData.product_type = productData.product_type === 'product' ? 'service' : 'product'"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0"
                            :class="productData.product_type === 'service' ? 'bg-accent' : 'bg-white/20'">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                :class="productData.product_type === 'service' ? 'translate-x-4' : 'translate-x-1'"></span>
                        </button>
                        <span class="text-xs font-semibold cursor-pointer" @click="productData.product_type = 'service'" :class="productData.product_type === 'service' ? 'text-white' : 'text-white/50'">Service</span>
                    </div>
                </div>
            </div>

            <form x-ref="productForm" @submit.prevent="submitProduct()" enctype="multipart/form-data" class="p-5 flex flex-col gap-4">
                <input type="hidden" name="product_code" :value="productData.product_code">
                <input type="hidden" name="product_type" :value="productData.product_type">
                <input type="hidden" name="location_type" value="branch">
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block"><span x-text="productData.product_type === 'service' ? 'Service Photo' : 'Product Photo'"></span></label>
                    <label for="mobileImageUpload" class="flex items-center justify-center w-full px-4 py-2.5 border-2 border-dashed border-gray-200 rounded-lg bg-gray-50/50 cursor-pointer overflow-hidden">
                        <div x-ref="previewPlaceholder" class="flex items-center gap-3">
                            <i class="bi bi-cloud-arrow-up text-xl text-primary/60"></i>
                            <span class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Upload Image</span>
                        </div>
                        <img x-ref="previewImg" class="hidden w-full h-16 object-contain">
                        <input id="mobileImageUpload" type="file" name="image" class="hidden" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (e) => { $refs.previewImg.src = e.target.result; $refs.previewPlaceholder.classList.add('hidden'); $refs.previewImg.classList.remove('hidden'); }
                                reader.readAsDataURL(file);
                            }
                        ">
                    </label>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block"><span x-text="productData.product_type === 'service' ? 'Service Name' : 'Product Name'"></span> <span class="text-primary">*</span></label>
                    <input type="text" name="product_name" x-model="productData.product_name" required :placeholder="productData.product_type === 'service' ? 'Enter service name' : 'Enter product name'"
                        :class="formErrors.product_name ? 'border-red-400' : 'border-gray-200'"
                        class="w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    <p x-show="formErrors.product_name" x-text="formErrors.product_name?.[0]" class="text-red-500 font-bold text-[11px] mt-1"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category</label>
                        <div class="relative">
                            <select name="category_id" x-model="productData.category_id"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                                <option value="">Select Category</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>
                            <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <div x-show="productData.product_type === 'product'" x-transition>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Base Unit</label>
                        <div class="relative">
                            <select name="unit" x-model="productData.unit" :required="productData.product_type === 'product'"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                                <template x-for="u in units" :key="u">
                                    <option :value="u" x-text="u"></option>
                                </template>
                            </select>
                            <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div x-show="productData.product_type === 'product'" x-transition>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Purchase Price</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="purchase_price" x-model="productData.purchase_price" placeholder="0.00"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-[13px]">{{ $symbol }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block"><span x-text="productData.product_type === 'service' ? 'Service Price' : 'Selling Price'"></span> <span class="text-primary">*</span></label>
                        <div class="relative">
                            <input type="number" step="0.01" name="selling_price" x-model="productData.selling_price" required placeholder="0.00"
                                :class="formErrors.selling_price ? 'border-red-400' : 'border-gray-200'"
                                class="w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-[13px]">{{ $symbol }}</span>
                        </div>
                        <p x-show="formErrors.selling_price" x-text="formErrors.selling_price?.[0]" class="text-red-500 font-bold text-[11px] mt-1"></p>
                    </div>
                </div>

                <div x-show="productData.product_type === 'product'" x-transition>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block" x-text="editMode ? 'Quantity' : 'Initial Stock'"></label>
                    <input type="number" step="1" name="stock_products" x-model="productData.stock_products" placeholder="Opening quantity"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description</label>
                    <textarea name="description" x-model="productData.description" rows="2" :placeholder="productData.product_type === 'service' ? 'Enter service details...' : 'Enter product details...'"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : (editMode ? 'Save Changes' : 'Save Product')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
