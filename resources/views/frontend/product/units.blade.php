@extends('admin.admin_master')
@section('page_title', 'Units')

@section('admin')
    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter" x-data="{
        activeModal: null,
        isEditMode: false,
        selectedUnit: { id: '', name: '', status: 'active' },

        openCreateModal() {
            this.isEditMode = false;
            this.selectedUnit = { id: '', name: '', status: 'active' };
            this.activeModal = 'unit-modal';
            let form = document.getElementById('unitForm');
            form.action = '{{ route('units.store') }}';
            form.reset();
        },

        openEditModal(unit) {
            this.isEditMode = true;
            this.selectedUnit = unit;
            this.activeModal = 'unit-modal';

            let form = document.getElementById('unitForm');
            form.action = '/units/update/' + unit.id;

            document.getElementById('unit_name').value = unit.name;
            document.getElementById('unit_status').value = unit.status || 'active';
        },

        closeModal() {
            this.activeModal = null;
        },

        confirmDelete(id, name) {
            deleteRecordWithPassword('/units/delete/' + id, name, {
                title: 'Delete Unit?',
                text: `Are you sure you want to delete '${name}'?`
            });
        }
    }">
        @include('frontend.product.partials.product_tabs', ['active' => 'units'])

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark uppercase">Unit Management</h1>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openCreateModal()" class="btn-premium-primary group normal-case">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Add Unit</span>
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1 uppercase">Total Units</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totalUnits) }}</h3>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0 shadow-sm">
                    <i class="bi bi-rulers text-lg"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1 uppercase">Active Units</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($activeUnits) }}</h3>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0 shadow-sm">
                    <i class="bi bi-check-circle text-lg"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1 uppercase">Inactive Units</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($inactiveUnits) }}</h3>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0 shadow-sm">
                    <i class="bi bi-x-circle text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6" :class="activeModal ? 'z-0' : 'z-10'">

            <!-- Filters -->
            <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <form action="{{ route('units.index') }}" method="GET" class="flex flex-wrap items-center gap-4 w-full">
                    <div class="relative group min-w-[250px] flex-1">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search units..."
                            class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                    </div>

                    <div class="relative min-w-[150px]">
                        <select name="status" onchange="this.form.submit()"
                            class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>

                    @if(request('search') || request('status'))
                        <a href="{{ route('units.index') }}"
                            class="w-9 h-9 flex items-center justify-center bg-primary/5 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all flex-shrink-0 shadow-sm border border-black/5 group"
                            title="Clear All Filters">
                            <i class="bi bi-x-lg text-xs group-hover:rotate-90 transition-transform"></i>
                        </a>
                    @endif
                </form>
            </div>

            <!-- Table Title Bar -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Unit List</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Unit Name</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Products</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($units as $unit)
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                                <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                    {{ str_pad($loop->iteration + (($units->currentPage() ?? 1) - 1) * ($units->perPage() ?? 10), 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-[12px] font-semibold text-primary-dark leading-tight">{{ $unit->name }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[12px] font-semibold text-primary-dark">{{ number_format($unit->products_count) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($unit->status === 'active')
                                        <span class="px-2 py-1 rounded text-[11px] font-bold text-accent bg-accent/10">Active</span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[11px] font-bold text-gray-400 bg-gray-50">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5 transition-opacity">
                                        <button type="button" @click="openEditModal(@js($unit))" class="btn-action-edit" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" @click="confirmDelete('{{ $unit->id }}', '{{ addslashes($unit->name) }}')" class="btn-action-delete" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-16">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                        <i class="bi bi-rulers text-2xl text-gray-300"></i>
                                    </div>
                                    <p class="text-[13px] font-bold uppercase tracking-widest text-gray-400">No units found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($units->hasPages())
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Showing {{ $units->firstItem() }} to {{ $units->lastItem() }} of {{ $units->total() }} entries</span>
                    {{ $units->links() }}
                </div>
            @endif
        </div>

        <!-- Add/Edit Unit Modal -->
        <div x-show="activeModal === 'unit-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-md max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">

                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4 text-white">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-rulers"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold tracking-tight uppercase" x-text="isEditMode ? 'Edit Unit' : 'Create Unit'"></h2>
                                <p class="text-xs text-primary font-medium mt-0.5 uppercase">Manage measurement units</p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                    <form id="unitForm" method="POST">
                        @csrf
                        <template x-if="isEditMode">
                            @method('PUT')
                        </template>

                        <div class="flex flex-col gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Unit Name <span class="text-primary">*</span></label>
                                <input type="text" name="name" id="unit_name" required placeholder="e.g. Piece, Box, kg"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Status</label>
                                <div class="relative">
                                    <select name="status" id="unit_status"
                                        class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="closeModal()"
                        class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] uppercase tracking-wide shadow-sm min-w-[120px]">
                        Cancel
                    </button>
                    <button type="submit" form="unitForm"
                        class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] uppercase tracking-wide shadow-sm flex items-center justify-center gap-2 min-w-[150px]">
                        <i class="bi bi-check2-circle"></i>
                        <span x-text="isEditMode ? 'Update Unit' : 'Save Unit'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
