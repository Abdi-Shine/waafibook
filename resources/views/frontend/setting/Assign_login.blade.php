@extends('admin.admin_master')
@section('page_title', 'Access Login')

@section('admin')

@push('css')
{{-- Using Global CSS from app.css --}}
@endpush

<div class="px-4 py-8 md:px-8 bg-background min-h-screen font-inter" x-data="{ 
    activeModal: null, 
    isEditMode: false,
    selectedEmployee: {},
    photoUrl: null,
    
    openAssignModal(employee) {
        this.selectedEmployee = employee;
        this.isEditMode = !!employee.user_id;
        this.activeModal = 'assign-user';
        
        let form = document.getElementById('assignUserForm');
        form.action = '/employees/assign-login/' + employee.id;
        
        const roleSelect = document.getElementById('userRole');
        const roleHidden = document.getElementById('userRoleHidden');
        const roleLockedNote = document.getElementById('userRoleLockedNote');

        if (this.isEditMode) {
            document.getElementById('username').value = employee.user.username;
            document.getElementById('email').value = employee.user.email;
            document.getElementById('password').value = '';
            document.getElementById('password').removeAttribute('required');

            roleSelect.value = employee.user.role;

            // An admin's role can't be reassigned from this screen — locking
            // it here is just the UI half; the same rule is enforced again
            // server-side so it can't be bypassed.
            const isAdmin = employee.user.role === 'admin';
            roleSelect.disabled = isAdmin;
            roleHidden.disabled = !isAdmin;
            roleHidden.value = isAdmin ? 'admin' : '';
            roleLockedNote.classList.toggle('hidden', !isAdmin);
        } else {
            form.reset();
            document.getElementById('username').value = employee.full_name.toLowerCase().replace(/\s+/g, '.');
            document.getElementById('email').value = '';
            document.getElementById('password').setAttribute('required', 'required');

            roleSelect.disabled = false;
            roleHidden.disabled = true;
            roleHidden.value = '';
            roleLockedNote.classList.add('hidden');
        }
    },
    
    closeModal() {
        this.activeModal = null;
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark tracking-tight">Access Management</h1>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Employees -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Total Workforce</p>
                <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['total']) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Registered staff members</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-people text-lg"></i>
            </div>
        </div>

        <!-- Users Assigned -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Active Credentials</p>
                <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['assigned']) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-check2-circle text-[10px]"></i> {{ $stats['total'] > 0 ? number_format(($stats['assigned'] / $stats['total']) * 100, 1) : 0 }}% coverage
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-person-check text-lg"></i>
            </div>
        </div>

        <!-- Pending Access -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Access Required</p>
                <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['pending']) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle text-[10px]"></i> Needs attention
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-person-x text-lg"></i>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-400 mb-1">Live Sessions</p>
                <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['active_sessions']) }}</h3>
                <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Currently online</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-shield-check text-lg"></i>
            </div>
        </div>
    </div>
    
    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <!-- Filters Area -->
        <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-3 bg-gray-50/30">
            <!-- Search -->
            <div class="relative group min-w-[300px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary"></i>
                <input type="text" id="searchInput" placeholder="Search by name, ID, or email..." 
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <!-- Status Filter -->
            <div class="relative min-w-[150px]">
                <select id="statusFilter" class="w-full pl-3 pr-10 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 outline-none appearance-none cursor-pointer focus:border-primary transition-all">
                    <option value="">All Status</option>
                    <option value="assigned">User Assigned</option>
                    <option value="pending">Pending Access</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
            </div>

            <!-- Role Filter -->
            <div class="relative min-w-[150px]">
                <select id="roleFilter" class="w-full pl-3 pr-10 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 outline-none appearance-none cursor-pointer focus:border-primary transition-all">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="report-premium-table">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-10 text-center">S/N</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Employee Identity</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Department</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">System Username</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Assigned Role</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Security Status</th>
                        <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Control</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @foreach($employees as $employee)
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition-all" 
                        data-status="{{ $employee->user_id ? 'assigned' : 'pending' }}" 
                        data-role="{{ $employee->user ? $employee->user->role : '' }}">
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-primary-dark leading-tight">{{ $loop->iteration }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->full_name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->department ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-primary-dark leading-tight italic">
                                {{ $employee->user ? $employee->user->username : 'Not assigned' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-primary-dark leading-tight">
                                {{ $employee->user ? $employee->user->role : '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-primary-dark leading-tight">
                                {{ $employee->user_id ? 'Active' : 'No Access' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center">
                                <button @click="openAssignModal({{ $employee->toJson() }})" 
                                    class="flex items-center gap-1.5 px-4 py-2 {{ $employee->user_id ? 'bg-white border border-gray-200 text-primary-dark font-bold' : 'bg-primary text-white font-bold' }} rounded-[0.5rem] transition-all text-[11px] uppercase tracking-wider shadow-sm hover:scale-105">
                                    <i class="bi {{ $employee->user_id ? 'bi-shield-shaded' : 'bi-plus-circle' }}"></i>
                                    {{ $employee->user_id ? 'Manage' : 'Assign' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="activeModal === 'assign-user'" 
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="closeModal()"
        x-cloak>
        
        <div class="w-full max-w-2xl bg-white shadow-2xl rounded-[1.25rem] overflow-hidden flex flex-col relative animate-fadeIn">
                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold text-white tracking-tight">Access Credentials</h2>
                                <p class="text-xs text-primary font-medium mt-0.5">Configure system security and role permissions</p>
                            </div>
                        </div>

                        <button @click="closeModal()"
                            class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar bg-white">
                    <form id="assignUserForm" method="POST" action="">
                        @csrf

                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Target Employee</label>
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-primary" x-text="selectedEmployee.full_name"></div>
                        </div>
                        <div class="space-y-1.5 text-right md:text-left">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block">Access Status</label>
                            <div class="inline-flex items-center px-4 py-2 bg-accent/5 text-accent rounded-full text-[10px] font-black uppercase tracking-widest border border-accent/10 shadow-sm" x-text="isEditMode ? 'Updating Credentials' : 'New System Access'"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">System Username <span class="text-primary">*</span></label>
                            <div class="relative group">
                                <input type="text" name="username" id="username" required 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10"
                                    placeholder="Enter username">
                                <i class="bi bi-person absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Official Email <span class="text-primary">*</span></label>
                            <div class="relative group">
                                <input type="email" name="email" id="email" required 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10"
                                    placeholder="Enter email address">
                                <i class="bi bi-envelope absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex justify-between">
                                Security Password
                                <span class="text-[9px] text-gray-400 normal-case font-medium italic" x-show="isEditMode">(Optional if unchanged)</span>
                            </label>
                            <div class="relative group">
                                <input type="password" name="password" id="password" 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10"
                                    placeholder="••••••••">
                                <i class="bi bi-shield-lock absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="bi bi-shield-check text-accent"></i>
                                Role Assignment
                            </label>
                            <div class="relative group">
                                <select name="userRole" id="userRole" required 
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer pr-10">
                                    <option value="" disabled selected>Select Access Level</option>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                            <input type="hidden" name="userRole" id="userRoleHidden" disabled>
                            <p id="userRoleLockedNote" class="hidden text-[10px] text-gray-400 italic mt-1">
                                <i class="bi bi-lock-fill"></i> Admin role is protected and can't be changed here.
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="closeModal()"
                        class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/95 transition-all text-[13px] shadow-sm">
                        Cancel
                    </button>
                    <button type="submit" form="assignUserForm"
                        class="flex items-center gap-2 px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                        <i class="bi bi-check2-circle text-base"></i>
                        <span x-text="isEditMode ? 'Update Credentials' : 'Save Credentials'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('#employeeTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    document.getElementById('statusFilter')?.addEventListener('change', function(e) {
        const val = e.target.value;
        document.querySelectorAll('#employeeTableBody tr').forEach(row => {
            row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
        });
    });

    document.getElementById('roleFilter')?.addEventListener('change', function(e) {
        const val = e.target.value;
        document.querySelectorAll('#employeeTableBody tr').forEach(row => {
            row.style.display = (!val || row.dataset.role === val) ? '' : 'none';
        });
    });
</script>
@endpush

@endsection

