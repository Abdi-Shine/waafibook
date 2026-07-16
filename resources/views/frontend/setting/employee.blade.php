@extends('admin.admin_master')
@section('page_title', 'Employees')

@section('admin')

@push('css')
{{-- Using Global CSS from app.css --}}
@endpush

<div class="px-4 py-8 font-inter"
      x-data="{ 
        activeModal: null, 
        photoUrl: '',
        editMode: false,
        editId: null,
        showEditModal(employee) {
            this.editMode = true;
            this.editId = employee.id;
            this.activeModal = 'add-staff';
            this.photoUrl = employee.user?.photo ? '/upload/admin_images/' + employee.user.photo : '';

            // Fill form fields
            const form = document.getElementById('addEmployeeForm');
            if (form) {
                form.action = '/employees/' + employee.id;
                // Add _method value for PUT
                let methodInput = form.querySelector('input[name=_method]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = 'PUT';

                form.fullName.value = employee.full_name || '';
                form.phone.value = employee.phone || '';
                form.gender.value = employee.gender || '';
                form.companyName.value = employee.company || '';
                form.department.value = employee.department || '';
                form.designation.value = employee.designation || '';

                this.$nextTick(() => {
                    if (form.branch) form.branch.value = employee.branch || employee.store || '';
                });

                form.salary.value = employee.salary || '';
            }
        },
        showAddModal() {
            this.editMode = false;
            this.editId = null;
            this.activeModal = 'add-staff';
            this.photoUrl = '';
            const form = document.getElementById('addEmployeeForm');
            if (form) {
                form.reset();
                form.action = '{{ route('employee.store') }}';
                let methodInput = form.querySelector('input[name=_method]');
                if (methodInput) methodInput.value = 'POST';
            }
        }
      }">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[22px] font-bold text-primary-dark">Employee Management</h1>
                <p class="text-xs text-gray-500 mt-0.5">Manage your workforce records and account access</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showAddModal()"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.75rem] hover:bg-primary/90 transition-all shadow-md shadow-primary/20 text-sm group">
                    <i class="bi bi-person-plus group-hover:rotate-12 transition-transform duration-300"></i>
                    Add New Employee
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Employees -->
            <div class="bg-white p-5 rounded-[1.25rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[13px] font-medium text-gray-400 mb-1">Total Staff</p>
                    <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['total']) }}</h3>
                    <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Complete workforce</p>
                </div>
                <div class="w-12 h-12 bg-primary/10 rounded-[1rem] flex items-center justify-center text-primary">
                    <i class="bi bi-people text-xl"></i>
                </div>
            </div>

            <!-- Active Status -->
            <div class="bg-white p-5 rounded-[1.25rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[13px] font-medium text-gray-400 mb-1">Active Duty</p>
                    <h3 class="text-[24px] font-black text-primary">{{ number_format($stats['active']) }}</h3>
                    <p class="text-[12px] font-bold text-primary mt-1.5 flex items-center gap-1">Currently assigned</p>
                </div>
                <div class="w-12 h-12 bg-accent/10 rounded-[1rem] flex items-center justify-center text-accent">
                    <i class="bi bi-shield-check text-xl"></i>
                </div>
            </div>

            <!-- Total Departments -->
            <div class="bg-white p-5 rounded-[1.25rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-primary/20 transition-all duration-300">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Departments</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $employees->unique('department')->count() }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">Business Units</p>
                </div>
                <div class="w-12 h-12 bg-primary/5 rounded-[1rem] flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <i class="bi bi-diagram-3 text-xl"></i>
                </div>
            </div>

            <!-- Monthly Payroll -->
            <div class="bg-white p-5 rounded-[1.25rem] border border-gray-100 shadow-sm flex items-start justify-between group hover:border-accent/20 transition-all duration-300">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Monthly Payroll</p>
                    <h3 class="text-[18px] font-black text-primary">${{ number_format($employees->sum('salary'), 0) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">Est. Expenditure</p>
                </div>
                <div class="w-12 h-12 bg-accent/10 rounded-[1rem] flex items-center justify-center text-accent group-hover:scale-110 transition-transform">
                    <i class="bi bi-cash-stack text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Filter & Action Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-3 overflow-x-auto custom-scrollbar">
                <!-- Search -->
                <div class="relative group min-w-[280px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary transition-colors"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, ID, or email..." 
                           class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Unit Filter -->
                <div class="relative min-w-[180px]">
                    <select id="filterDepartment" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Business Units</option>
                        <option value="sales">Sales & Marketing</option>
                        <option value="warehouse">Warehouse Operations</option>
                        <option value="finance">Finance & Accounting</option>
                        <option value="hr">Human Resources</option>
                        <option value="it">IT & Tech</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[160px]">
                    <select id="filterStatus" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="active">Active Duty</option>
                        <option value="on-leave">On Leave</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>


            </div>

            <!-- Table Title Bar -->
            <div class="px-5 py-3 flex items-center justify-between border-b border-gray-100 bg-gray-50/30">
                <div class="flex items-center gap-2 text-primary font-bold">
                    <i class="bi bi-list-columns-reverse text-sm"></i>
                    <h2 class="text-xs uppercase tracking-widest">Master Employee Registry</h2>
                </div>
            </div>
        
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center w-12">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Full Name</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Phone Number</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Gender</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Position</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Basic Salary</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Active</th>
                            <th class="px-6 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody" class="divide-y divide-gray-100 bg-white">
                        @foreach($employees as $employee)
                        <tr class="hover:bg-gray-50/80 transition-all duration-300 group" 
                            data-department="{{ strtolower($employee->department) }}" 
                            data-status="{{ strtolower($employee->status) }}">
                            <td class="px-5 py-4 text-center">
                                <span class="text-xs font-bold text-primary-dark leading-tight">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <!-- Full Name -->
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->full_name }}</div>
                            </td>
                            <!-- Phone Number -->
                            <td class="px-5 py-3">
                                <div class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->phone ?? '—' }}</div>
                            </td>
                            <!-- Gender -->
                            <td class="px-5 py-3 text-center">
                                <div class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->gender ?? '—' }}</div>
                            </td>
                            <!-- Position -->
                            <td class="px-5 py-3">
                                <div class="text-xs font-bold text-primary-dark leading-tight">{{ $employee->designation }}</div>
                            </td>
                            <!-- Basic Salary -->
                            <td class="px-5 py-3 text-center">
                                <div class="text-xs font-bold text-primary-dark leading-tight">${{ number_format($employee->salary ?? 0, 2) }}</div>
                            </td>
                            <!-- Active -->
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black {{ strtolower($employee->status) === 'active' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                                    {{ strtolower($employee->status) === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-3">
                                <div class="flex gap-1.5 justify-center">
                                    <button @click="showEditModal({{ $employee->toJson() }})" class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-primary hover:border-primary hover:bg-primary/5 transition-all flex items-center justify-center text-sm shadow-sm" title="Edit Profile">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" onclick="deleteEmployee({{ $employee->id }})" class="w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-300 hover:text-primary hover:border-primary/20 hover:bg-primary/10 transition-all flex items-center justify-center text-sm shadow-sm" title="Delete Record">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($employees->count() > 0)
            <div class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                    Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} entries
                </div>
                <div class="flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($employees->onFirstPage())
                        <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-50 text-gray-400 border border-gray-200 cursor-not-allowed" disabled>
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </button>
                    @else
                        <a href="{{ $employees->previousPageUrl() }}" class="w-7 h-7 flex items-center justify-center rounded bg-white text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200 font-bold text-[11px]">
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($employees->links()->elements as $element)
                        @if (is_string($element))
                            <span class="px-1 text-gray-400 text-xs">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $employees->currentPage())
                                    <button class="w-7 h-7 flex items-center justify-center rounded bg-primary text-white font-bold text-[11px] shadow-sm">{{ $page }}</button>
                                @else
                                    <a href="{{ $url }}" class="w-7 h-7 flex items-center justify-center rounded bg-white text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200 font-bold text-[11px]">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($employees->hasMorePages())
                        <a href="{{ $employees->nextPageUrl() }}" class="w-7 h-7 flex items-center justify-center rounded bg-white text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200 font-bold text-[11px]">
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </a>
                    @else
                        <button class="w-7 h-7 flex items-center justify-center rounded bg-gray-50 text-gray-400 border border-gray-200 cursor-not-allowed" disabled>
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    
    <!-- Add Employee Modal -->
    <div id="employeeModal" 
         x-show="activeModal === 'add-staff'" 
         x-cloak
         class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-[1.5rem] shadow-2xl max-w-3xl w-full max-h-[85vh] overflow-hidden bg-white rounded-[1.25rem] w-full max-w-3xl max-h-[85vh] overflow-hidden shadow-2xl flex flex-col"
             @click.away="activeModal = null">
            <!-- Modal Header (Simple Branding Style) -->
            <div class="px-8 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-5">
                        <!-- Icon Container -->
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-2xl shadow-inner backdrop-blur-md">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-xl font-bold text-white tracking-tight leading-tight" x-text="editMode ? 'Edit Staff Profile' : 'Register Staff Member'"></h2>
                            <p class="text-[12px] text-primary font-medium mt-0.5">Complete all required information below</p>
                        </div>
                    </div>
                    
                    <!-- Close Button -->
                    <button @click="activeModal = null" 
                        class="w-9 h-9 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center group shadow-sm">
                        <i class="bi bi-x-lg text-xs group-hover:scale-110 transition-transform"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto modal-scroll employee-modal-scroll">
                <form id="addEmployeeForm" action="{{ route('employee.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Photo upload --}}
                    <div class="mb-5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1.5">Profile Photo</label>
                        <label for="photoUpload" class="flex flex-col items-center justify-center w-full py-4 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 hover:bg-gray-100 hover:border-primary/40 cursor-pointer transition-all">
                            <template x-if="!photoUrl">
                                <div class="flex flex-col items-center gap-1.5">
                                    <i class="bi bi-cloud-arrow-up text-2xl text-gray-400"></i>
                                    <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Upload Image</span>
                                </div>
                            </template>
                            <template x-if="photoUrl">
                                <img :src="photoUrl" class="h-16 w-16 object-cover rounded-xl">
                            </template>
                        </label>
                        <input type="file" id="photoUpload" name="photo" class="hidden" accept="image/*" @change="let file = $event.target.files[0]; if(file) { let reader = new FileReader(); reader.onload = (e) => photoUrl = e.target.result; reader.readAsDataURL(file); }">
                    </div>

                    {{-- Row 1: Full Name, Phone, Gender --}}
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Full Name <span class="text-primary">*</span></label>
                            <input type="text" name="fullName" required class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" placeholder="Enter full name">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone Number</label>
                            <input type="tel" name="phone" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" placeholder="+252 XXX XXX XXX">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Gender</label>
                            <select name="gender" class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Company, Department, Position --}}
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Company Name <span class="text-primary">*</span></label>
                            <select name="companyName" required class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->name }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Department</label>
                            <input type="text" name="department" class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" placeholder="e.g., Sales">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Position</label>
                            <input type="text" name="designation" class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" placeholder="e.g., Manager">
                        </div>
                    </div>

                    {{-- Row 3: Branch, Salary --}}
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="col-span-2">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-primary">*</span></label>
                            <select name="branch" required class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Basic Salary</label>
                            <input type="number" name="salary" class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" placeholder="0.00">
                        </div>
                    </div>

                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 p-6 border-t border-gray-100 flex justify-between items-center shrink-0">
                <button type="button" @click="activeModal = null" 
                    class="px-8 py-3 bg-white border border-gray-200 text-gray-500 rounded-xl font-bold hover:text-primary hover:border-primary transition-all text-sm shadow-sm">
                    Cancel
                </button>
                
                <button type="submit" form="addEmployeeForm" 
                    class="px-10 py-3 bg-accent text-primary font-bold rounded-xl hover:opacity-95 transition-all flex items-center gap-2 text-sm shadow-lg shadow-accent/20">
                    <i class="bi bi-check-circle-fill"></i>
                    <span x-text="editMode ? 'Update Staff Member' : 'Register Staff Member'"></span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Success Toast -->
    <div id="successToast" class="hidden fixed top-24 right-6 bg-accent text-primary-dark px-6 py-4 rounded-xl shadow-2xl z-50 flex items-center gap-3 transform transition-all">
        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
            <i class="bi bi-check-circle-fill text-2xl text-accent"></i>
        </div>
        <div>
            <div class="font-bold">Success!</div>
            <div class="text-sm">Employee added successfully</div>
        </div>
    </div>
    
@push('scripts')
    <script>
        // Enhanced filtering functionality
        function applyWorkforceFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const deptFilter = document.getElementById('filterDepartment').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
            
            const rows = document.querySelectorAll('#employeeTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowDept = row.getAttribute('data-department') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                
                const matchesSearch = text.includes(searchTerm);
                const matchesDept = deptFilter === '' || rowDept.includes(deptFilter);
                const matchesStatus = statusFilter === '' || rowStatus.includes(statusFilter);
                
                if (matchesSearch && matchesDept && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add event listeners for real-time filtering
        document.getElementById('searchInput')?.addEventListener('input', applyWorkforceFilters);
        document.getElementById('filterDepartment')?.addEventListener('change', applyWorkforceFilters);
        document.getElementById('filterStatus')?.addEventListener('change', applyWorkforceFilters);

        // Delete confirmation with SweetAlert2
        function deleteEmployee(id) {
            Swal.fire({
                title: 'Delete Employee?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#004161',
                cancelButtonColor: '#ff6b6b',
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'font-bold px-6 py-3 rounded-lg',
                    cancelButton: 'font-bold px-6 py-3 rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/employees/${id}`;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }




        
        // Success Toast Handling
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', () => {
                const toast = document.getElementById('successToast');
                if(toast) {
                    toast.classList.remove('hidden');
                    toast.classList.add('flex');
                    setTimeout(() => {
                        toast.classList.add('opacity-0');
                        setTimeout(() => toast.classList.add('hidden'), 500);
                    }, 5000);
                }
            });
        @endif
    </script>
@endpush

</div>
@endsection


