@extends('admin.admin_master')
@section('page_title', 'Cash Loans')



@section('admin')

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="loanApp()">
        
        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-accent/10 border border-accent/20 rounded-xl flex items-center gap-3 text-accent animate-in fade-in slide-in-from-top-2">
                <i class="bi bi-check-circle-fill"></i>
                <p class="text-sm font-bold">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-center gap-3 text-primary animate-in fade-in slide-in-from-top-2">
                <i class="bi bi-exclamation-circle-fill"></i>
                <p class="text-sm font-bold">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Staff Cash Loans</h1>
                <p class="text-xs text-gray-500 font-medium mt-1">Manage employee loan requests and repayments</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openCreateModal()"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    New Loan Request
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Loans Given -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Loans Given</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($loans->sum('amount'), 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">{{ $loans->count() }} total loans</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                    <i class="bi bi-wallet2 text-lg"></i>
                </div>
            </div>

            <!-- Total Recovered -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Recovered</p>
                    <h3 class="text-[18px] font-black text-primary">
                        {{ $currency }} {{ number_format($totalRecovered, 2) }}
                    </h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">{{ $recoveryRate }}% recovered</p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                    <i class="bi bi-graph-up-arrow text-lg"></i>
                </div>
            </div>

            <!-- Outstanding Balance -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Outstanding Balance</p>
                    <h3 class="text-[18px] font-black text-primary">
                        {{ $currency }} {{ number_format($totalOutstanding, 2) }}
                    </h3>
                    @php
                        $pendingPercent = $loans->sum('amount') > 0 ? round(($totalOutstanding / $loans->sum('amount')) * 100, 1) : 0;
                    @endphp
                    <p class="text-xs font-bold text-primary-dark mt-1.5">{{ $pendingPercent }}% pending</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>

            <!-- Active Employees -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Active Employees</p>
                    <h3 class="text-[18px] font-black text-primary">
                        {{ $activeCount }}
                    </h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5">With active loans</p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                    <i class="bi bi-people text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            
            <!-- Filters -->
            <div
                class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Search -->
                <div class="relative group min-w-[250px] flex-1">
                    <i
                        class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" x-model="searchTerm" placeholder="Search by employee name..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Filter by Status -->
                <div class="relative min-w-[150px]">
                    <select x-model="statusFilter"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">Filter by Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="settled">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <i
                        class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Filter by Type -->
                <div class="relative min-w-[150px]">
                    <select x-model="typeFilter"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Types</option>
                        <option value="personal">Personal</option>
                        <option value="advance">Advance</option>
                        <option value="emergency">Emergency</option>
                    </select>
                    <i
                        class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

            </div>

            <!-- Table Title -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Loan List</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Employee Name</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Loan Amount</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Monthly Deduction</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Total Paid</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Balance</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($loans as $loan)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group" x-show="(searchTerm === '' || '{{ strtolower($loan->employee->full_name ?? '') }}'.includes(searchTerm.toLowerCase())) && (statusFilter === '' || '{{ $loan->status }}' === statusFilter) && (typeFilter === '' || '{{ $loan->type }}' === typeFilter)">
                            <td class="px-5 py-4 text-[13px] font-black text-primary-dark text-center">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-[13px] font-black text-primary-dark leading-tight block">{{ $loan->employee->full_name ?? 'Deleted Employee' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-black text-primary-dark">{{ $currency }} {{ number_format($loan->amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-bold text-gray-500">{{ $currency }} {{ number_format($loan->monthly_deduction, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-black text-primary-dark">{{ $currency }} {{ number_format($loan->recovered, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-black text-primary-dark">{{ $currency }} {{ number_format($loan->balance, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[13px] font-black capitalize {{ $loan->status == 'active' ? 'text-accent' : ($loan->status == 'pending' ? 'text-primary' : ($loan->status == 'settled' ? 'text-primary' : 'text-primary')) }}">
                                    {{ $loan->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($loan->status == 'pending')
                                        <button @click="openEditModal({{ json_encode($loan->load('employee')) }})" class="btn-action-edit" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button @click="updateStatus({{ $loan->id }}, 'active')" class="btn-action-view" title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button @click="updateStatus({{ $loan->id }}, 'rejected')" class="btn-action-delete" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                        <button @click="confirmDelete({{ $loan->id }})" class="btn-action-delete" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <button @click="openDetailsModal({{ json_encode($loan->load('employee')) }})" class="btn-action-view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="{{ route('loan.payslip', $loan->id) }}" target="_blank" class="btn-action-view" title="Print Payslip">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        @if($loan->status == 'rejected')
                                            <button @click="confirmDelete({{ $loan->id }})" class="btn-action-delete" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-400 italic text-xs">No loan records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                    Showing 1 to {{ $loans->count() }} of {{ $loans->count() }} entries
                </p>
                <div class="flex items-center gap-2">
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">
                        1
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- NEW LOAN REQUEST MODAL -->
        <div x-show="activeModal === 'loan-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative"
                @click.away="activeModal = null">

                <!-- Modal Header -->
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-file-earmark-plus"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold text-white tracking-tight" x-text="editMode ? 'Edit Loan Request' : 'New Loan Request'"></h2>
                                <p class="text-xs text-primary font-medium mt-0.5">Fill in the required details below</p>
                            </div>
                        </div>
                        <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                    <form id="loanForm" :action="formAction" method="POST">
                        @csrf
                        <template x-if="editMode">
                            @method('PUT')
                        </template>
                        <div class="space-y-6">
                            <!-- Employee Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Select Employee <span class="text-primary">*</span></label>
                                    <div class="relative">
                                        <input type="text" 
                                               list="employee_list" 
                                               x-model="loanData.employee_name" 
                                               @input="const emp = employees.find(e => e.full_name === loanData.employee_name); if(emp) { loanData.employee_id = emp.id; updateEmployeeDetails(); }"
                                               placeholder="Type employee name..." 
                                               class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <datalist id="employee_list">
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->full_name }}">
                                            @endforeach
                                        </datalist>
                                        <input type="hidden" name="employee_id" x-model="loanData.employee_id">
                                        <i class="bi bi-search absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch</label>
                                    <input type="text" x-model="loanData.branch" readonly placeholder="N/A" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 outline-none cursor-not-allowed">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Basic Salary</label>
                                    <input type="text" x-model="loanData.salary" readonly placeholder="0.00" class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 outline-none cursor-not-allowed">
                                </div>
                            </div>

                            <!-- Loan Details Section -->
                            <div class="pt-6 border-t border-gray-100">
                                <h3 class="text-[11px] font-black text-primary-dark uppercase tracking-[0.1em] mb-4">Loan Financial Details</h3>
                                
                                <div class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Loan Amount <span class="text-primary">*</span></label>
                                            <input type="number" step="0.01" min="0" name="amount" x-model="loanData.amount" @input="calculateDuration()" required placeholder="0.00" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Monthly Deduction <span class="text-primary">*</span></label>
                                            <input type="number" step="0.01" min="0" name="monthly_deduction" x-model="loanData.monthly_deduction" @input="calculateDuration()" required placeholder="0.00" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Start Date <span class="text-primary">*</span></label>
                                            <input type="date" name="start_date" x-model="loanData.start_date" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="space-y-1.5">
                                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Duration (Months)</label>
                                            <input type="text" name="duration" x-model="loanData.duration" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 outline-none cursor-not-allowed">
                                            <p class="text-[9px] text-gray-400 mt-1 italic">Auto-calculated</p>
                                        </div>
                                        <div class="space-y-1.5 md:col-span-2">
                                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reason for Loan <span class="text-primary">*</span></label>
                                            <input type="text" name="reason" x-model="loanData.reason" required placeholder="Enter reason for requesting this loan..." class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="activeModal = null" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Cancel</button>
                    <button type="submit" form="loanForm" class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                        <span x-text="editMode ? 'Update Request' : 'Submit Request'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- DETAILS MODAL -->
        <div x-show="activeModal === 'details-modal'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-lg overflow-hidden shadow-2xl flex flex-col relative" @click.away="activeModal = null">
                <div class="px-6 py-6 bg-primary shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <h2 class="text-lg font-bold text-white">Loan Details</h2>
                        <button @click="activeModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm"><i class="bi bi-x-lg text-xs"></i></button>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-4 border-b pb-4">
                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary font-bold">
                            <span x-text="loanData.employee ? loanData.employee.full_name.substring(0,2).toUpperCase() : 'E'"></span>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900" x-text="loanData.employee ? loanData.employee.full_name : 'Employee'"></h3>
                            <p class="text-xs text-gray-500" x-text="loanData.loan_id"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b pb-4">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Branch</p>
                            <p class="text-xs font-bold text-gray-900" x-text="loanData.employee ? loanData.employee.branch : 'N/A'"></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Basic Salary</p>
                            <p class="text-xs font-bold text-gray-900" x-text="loanData.employee ? '{{ $currency }} ' + parseFloat(loanData.employee.salary).toLocaleString() : '0.00'"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b pb-4">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Total Amount</p>
                            <p class="font-bold text-primary-dark" x-text="'{{ $currency }} ' + parseFloat(loanData.amount).toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Monthly Deduction</p>
                            <p class="font-bold text-primary-dark" x-text="'{{ $currency }} ' + parseFloat(loanData.monthly_deduction).toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Start Date</p>
                            <p class="font-bold text-primary-dark" x-text="loanData.start_date"></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Duration</p>
                            <p class="font-bold text-primary-dark" x-text="loanData.duration + ' Months'"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Balance</p>
                            <p class="font-bold text-primary" x-text="'{{ $currency }} ' + parseFloat(loanData.balance).toLocaleString()"></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-400">Status</p>
                            <p class="font-bold capitalize" x-text="loanData.status"></p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                    <button @click="activeModal = null" class="px-4 py-2 bg-white border rounded-lg text-xs font-bold">Close</button>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loanApp', () => ({
            activeModal: null,
            searchTerm: '',
            statusFilter: '',
            typeFilter: '',
            editMode: false,
            formAction: '{{ route('loan.store') }}',
            employees: @json($employees),
            loanData: {
                id: '',
                employee_id: '',
                employee_name: '',
                branch: '',
                salary: '',
                amount: '',
                monthly_deduction: '',
                start_date: '',
                duration: '',
                type: 'personal',
                reason: ''
            },

            updateEmployeeDetails() {
                const emp = this.employees.find(e => e.id == this.loanData.employee_id);
                if (emp) {
                    this.loanData.branch = emp.branch || 'N/A';
                    this.loanData.salary = emp.salary ? parseFloat(emp.salary).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00';
                } else {
                    this.loanData.branch = '';
                    this.loanData.salary = '';
                }
            },

            calculateDuration() {
                if (this.loanData.amount > 0 && this.loanData.monthly_deduction > 0) {
                    this.loanData.duration = Math.ceil(this.loanData.amount / this.loanData.monthly_deduction);
                } else {
                    this.loanData.duration = '';
                }
            },

            openCreateModal() {
                this.editMode = false;
                this.formAction = '{{ route('loan.store') }}';
                const today = new Date().toISOString().split('T')[0];
                this.loanData = { 
                    id: '', 
                    employee_id: '', 
                    employee_name: '', 
                    branch: '', 
                    salary: '', 
                    amount: '', 
                    monthly_deduction: '', 
                    start_date: today, 
                    duration: '', 
                    type: 'personal', 
                    reason: '' 
                };
                this.activeModal = 'loan-modal';
            },

            openDetailsModal(loan) {
                this.loanData = loan;
                this.activeModal = 'details-modal';
            },

            openEditModal(loan) {
                this.editMode = true;
                this.formAction = '/loans/update/' + loan.id;
                this.loanData = { 
                    ...loan, 
                    employee_name: loan.employee ? loan.employee.full_name : '' 
                };
                this.updateEmployeeDetails();
                this.activeModal = 'loan-modal';
            },

            updateStatus(id, status) {
                const action = status === 'active' ? 'Approve' : (status === 'rejected' ? 'Reject' : 'Update');
                const color = status === 'active' ? '#10b981' : (status === 'rejected' ? '#f43f5e' : '#004161');
                
                Swal.fire({
                    title: `${action} Loan?`,
                    text: `Are you sure you want to ${status} this loan request?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: color,
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: `Yes, ${action} it!`,
                    customClass: {
                        popup: 'rounded-[1.25rem]',
                        title: 'font-bold text-primary-dark',
                        confirmButton: 'rounded-lg px-6 py-2.5 text-xs font-bold uppercase tracking-wider',
                        cancelButton: 'rounded-lg px-6 py-2.5 text-xs font-bold uppercase tracking-wider'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/loans/status/' + id;
                        form.innerHTML = `@csrf` + `<input type="hidden" name="status" value="${status}">`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            },

            confirmDelete(id) {
                deleteRecordWithPassword('/loans/delete/' + id, 'this loan record', {
                    title: 'Delete Loan Record?',
                    text: 'This action will permanently remove this loan request. It cannot be undone.'
                });
            }
        }));
    });
</script>
@endpush


