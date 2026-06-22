@extends('admin.admin_master')
@section('page_title', 'Roles & Permissions')

@push('css')
@endpush

@section('admin')

<div class="p-4 md:p-8 font-inter" x-data="{ 
    activeModal: null, 
    isEditMode: false,
    selectedRole: {
        id: '',
        name: '',
        description: '',
        permissions: {}
    },
    
    openCreateModal() {
        this.isEditMode = false;
        this.selectedRole = {
            id: '',
            name: '',
            description: '',
            permissions: {}
        };
        this.activeModal = 'role-modal';
        let form = document.getElementById('roleForm');
        form.action = '{{ route('role.store') }}';
        form.reset();
        // Clear all checkboxes
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    },

    openEditModal(role) {
        this.isEditMode = true;
        this.selectedRole = role;
        this.activeModal = 'role-modal';
        
        let form = document.getElementById('roleForm');
        form.action = '/roles/' + role.id;
        
        // Populate inputs
        document.getElementById('role_name').value = role.name;
        document.getElementById('role_description').value = role.description || '';
        
        // Reset checkboxes first
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        
        // Check corresponding checkboxes
        if (role.permissions) {
            Object.keys(role.permissions).forEach(module => {
                role.permissions[module].forEach(action => {
                    let checkbox = document.querySelector(`input[name='permissions[${module}][]'][value='${action}']`);
                    if (checkbox) checkbox.checked = true;
                });
            });
        }
    },
    
    closeModal() {
        this.activeModal = null;
    },

    confirmDelete(id, name) {
        deleteRecordWithPassword('{{ url('/roles') }}/' + id, name, {
            title: 'Delete Role?',
            text: `Are you sure you want to delete the '${name}' role? This action cannot be undone.`
        });
    },

    toggleAllPermissions(checked) {
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            cb.checked = checked;
        });
    },

    toggleModulePermissions(moduleName, checked) {
        document.querySelectorAll(`input[name='permissions[${moduleName}][]']`).forEach(cb => {
            cb.checked = checked;
        });
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Domain Architecture & Permissions</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openCreateModal()" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/95 transition-all shadow-sm text-sm group">
                <i class="bi bi-shield-plus group-hover:rotate-12 transition-transform duration-300"></i>
                Define New Role
            </button>
        </div>
    </div>

    <!-- Stats Summary Cards (Matching Customer Module) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Total Authority</p>
                <h3 class="text-[24px] font-black text-primary">{{ $roles->count() }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-shield-check text-[10px]"></i> Active Definitions
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/5 rounded-[0.6rem] flex items-center justify-center text-primary text-lg">
                <i class="bi bi-safe2"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Global Access</p>
                <h3 class="text-[24px] font-black text-primary">{{ count($modules) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-grid-fill text-[10px]"></i> Secured Modules
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary text-lg">
                <i class="bi bi-layers"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Security State</p>
                <h3 class="text-[24px] font-black text-primary">Active</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-patch-check text-[10px]"></i> Framework Sync
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent text-lg">
                <i class="bi bi-shield-lock"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between opacity-50">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">System Integrity</p>
                <h3 class="text-[24px] font-black text-primary">Locked</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5">Encrypted Nodes</p>
            </div>
            <div class="w-11 h-11 bg-slate-50 rounded-[0.6rem] flex items-center justify-center text-slate-400 text-lg">
                <i class="bi bi-cpu"></i>
            </div>
        </div>
    </div>

    <!-- Role Authority Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($roles as $role)
        <div class="report-premium-card group hover:border-accent/30 transition-all duration-300">
            <div class="p-6">
                <!-- Card Header -->
                <div class="flex justify-between items-start mb-6">
                    <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-accent group-hover:text-primary transition-all duration-300 shadow-inner">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div class="flex gap-1">
                        <button @click="openEditModal(@js($role))" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-primary hover:bg-gray-100 transition-all" title="Edit Domain">
                            <i class="bi bi-gear-fill text-[15px]"></i>
                        </button>
                        <form id="delete-form-{{ $role->id }}" action="{{ route('role.destroy', $role->id) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <button type="button" @click="confirmDelete('{{ $role->id }}', '{{ $role->name }}')" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-primary hover:bg-primary/10 transition-all" title="Retire Role">
                            <i class="bi bi-trash3 text-[15px]"></i>
                        </button>
                    </div>
                </div>

                <h3 class="text-lg font-black text-primary uppercase tracking-tight mb-2 leading-none" x-text="'@js($role->name)'">{{ $role->name }}</h3>
                <p class="text-[12px] text-slate-500 font-medium mb-6 line-clamp-2 h-10">{{ $role->description ?? 'No specific scope description provided.' }}</p>

                <!-- Tactical Grid Preview (Permissions) -->
                <div class="space-y-2 mb-6 min-h-[80px]">
                    @php $count = 0; @endphp
                    @foreach($role->permissions as $module => $actions)
                        @if($count < 3)
                        <div class="flex items-center gap-2.5 px-3 py-1.5 bg-gray-50 rounded-lg group-hover:bg-white transition-colors border border-transparent group-hover:border-gray-100">
                            <div class="w-1 h-1 rounded-full bg-accent"></div>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $module }}</span>
                            <span class="text-[10px] text-gray-400 font-bold ml-auto">{{ count($actions) }} Rights</span>
                        </div>
                        @php $count++; @endphp
                        @endif
                    @endforeach
                    @if(count($role->permissions) > 3)
                        <div class="text-[9px] text-gray-400 font-black uppercase tracking-widest pl-4">+ {{ count($role->permissions) - 3 }} Specialized Modules</div>
                    @endif
                </div>

                <button @click="openEditModal(@js($role))" class="w-full py-2.5 bg-primary text-white font-black rounded-lg hover:bg-primary/90 transition-all text-[11px] uppercase tracking-widest shadow-lg shadow-primary/10">
                    Modify Permissions
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Domain Authority Ecosystem (Standard Table Card) -->
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="bi bi-shield-lock text-primary text-xl"></i>
                <h2 class="text-[15px] font-bold text-primary-dark">Ecosystem Authorization Matrix</h2>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto max-h-[700px] custom-scrollbar">
            <table class="w-full text-left">
                <thead class="sticky top-0 z-20 bg-gray-50/95 backdrop-blur-md">
                    <tr>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider border-b border-gray-100 w-64">Authorization Node</th>
                        @foreach($roles as $role)
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center border-b border-gray-100 min-w-[150px]">
                            {{ $role->name }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($modules as $moduleName => $actions)
                    <tr class="bg-slate-50/30">
                        <td colspan="{{ count($roles) + 1 }}" class="p-4 pl-6 text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-100/50">
                            {{ $moduleName }} Module
                        </td>
                    </tr>
                    @foreach($actions as $action)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-6">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-accent"></span>
                                <span class="text-xs font-bold text-primary-dark leading-tight capitalize">{{ $action }}</span>
                            </div>
                        </td>
                        @foreach($roles as $role)
                        <td class="p-6 text-center">
                            @if(isset($role->permissions[$moduleName]) && in_array($action, $role->permissions[$moduleName]))
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-accent/10 text-accent">
                                <i class="bi bi-shield-fill-check text-lg"></i>
                            </div>
                            @else
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-50 text-slate-200">
                                <i class="bi bi-shield-slash text-lg"></i>
                            </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Domain Orchestration Modal -->
    <div x-show="activeModal === 'role-modal'" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="closeModal()"
        x-cloak>
        
        <div class="w-full max-w-4xl bg-white shadow-2xl rounded-[1.25rem] overflow-hidden flex flex-col relative animate-fadeIn max-h-[90vh]">
            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold text-white tracking-tight" x-text="isEditMode ? 'Authorize Domain Role' : 'Architect New Duty'"></h2>
                            <p class="text-xs text-primary font-medium mt-0.5">Map authorizations across specialized system modules</p>
                        </div>
                    </div>

                    <button @click="closeModal()"
                        class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-8 overflow-y-auto custom-scrollbar bg-white flex-grow">
                <form id="roleForm" method="POST">
                    @csrf
                    <template x-if="isEditMode">
                        @method('PUT')
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Role Designation <span class="text-primary">*</span></label>
                            <div class="relative group">
                                <input type="text" name="name" id="role_name" required 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10"
                                    placeholder="Inventory Manager">
                                <i class="bi bi-shield-shaded absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Quick Summary</label>
                            <div class="relative group">
                                <input type="text" name="description" id="role_description" 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10"
                                    placeholder="Briefly describe the responsibilities">
                                <i class="bi bi-card-text absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <div class="relative py-4 mb-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-100"></div>
                        </div>
                        <div class="relative flex justify-between items-center px-4">
                            <span class="px-4 bg-white text-[10px] font-black text-primary uppercase tracking-[0.25em] flex items-center gap-2">
                                <i class="bi bi-shield-check text-accent"></i>
                                Authority Node Configuration
                            </span>
                            <div class="flex items-center gap-2 bg-white px-2">
                                <button type="button" @click="toggleAllPermissions(true)" class="text-[9px] font-black text-accent uppercase tracking-widest hover:text-primary transition-colors flex items-center gap-1">
                                    <i class="bi bi-check-all text-xs"></i> Select All
                                </button>
                                <span class="text-gray-200">|</span>
                                <button type="button" @click="toggleAllPermissions(false)" class="text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-primary transition-colors flex items-center gap-1">
                                    <i class="bi bi-x text-xs"></i> Deselect
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($modules as $moduleName => $actions)
                        <div class="p-5 bg-gray-50/50 border border-gray-100 rounded-2xl group hover:border-accent/10 hover:bg-white transition-all">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-1.5 h-6 rounded-full bg-accent opacity-20 group-hover:opacity-100 transition-opacity"></div>
                                    <div class="flex flex-col">
                                        <span class="text-[12px] font-black text-primary uppercase tracking-tight">{{ $moduleName }} System</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <button type="button" @click="toggleModulePermissions('{{ $moduleName }}', true)" class="text-[8px] font-bold text-accent uppercase tracking-tighter hover:underline">Select All</button>
                                            <button type="button" @click="toggleModulePermissions('{{ $moduleName }}', false)" class="text-[8px] font-bold text-gray-400 uppercase tracking-tighter hover:underline">Clear</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 justify-start md:justify-end">
                                    @foreach($actions as $action)
                                    <label class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-accent hover:bg-accent/5 transition-all group/label">
                                        <input type="checkbox" name="permissions[{{ $moduleName }}][]" value="{{ $action }}" class="permission-checkbox w-3.5 h-3.5 rounded border-gray-300 text-accent focus:ring-accent/20 transition-all">
                                        <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest group-hover/label:text-primary">{{ $action }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </form>
            </div>

            <!-- Modal Footer (Customer Branding Standard) -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button type="button" @click="closeModal()"
                    class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/95 transition-all text-[13px] shadow-sm">
                    Discard Changes
                </button>
                <button type="submit" form="roleForm"
                    class="flex items-center gap-2 px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                    <i class="bi bi-shield-check text-base"></i>
                    <span x-text="isEditMode ? 'Authorize Domain Updates' : 'Architect Domain Role'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

