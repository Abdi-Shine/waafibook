@extends('admin.admin_master')
@section('page_title', 'Cash Loans')

@section('admin')

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="loanApp()">

    {{-- Alerts --}}
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

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Cash Loans</h1>
            <p class="text-xs text-gray-500 font-medium mt-1">Manage loan requests and repayments for anyone</p>
        </div>
        <button @click="openCreateModal()"
            class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
            <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
            New Loan Request
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-primary p-5 rounded-[1rem] shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-white/60 font-medium mb-1">Total Loans Given</p>
                <h3 class="text-[18px] font-black text-white">{{ $currency }} {{ number_format($loans->sum('amount'), 2) }}</h3>
                <p class="text-xs font-bold text-white/70 mt-1.5">{{ $loans->count() }} total loans</p>
            </div>
            <div class="w-11 h-11 bg-white/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Recovered</p>
                <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($totalRecovered, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">{{ $recoveryRate }}% recovered</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-graph-up-arrow text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Outstanding Balance</p>
                <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($totalOutstanding, 2) }}</h3>
                @php $pendingPercent = $loans->sum('amount') > 0 ? round(($totalOutstanding / $loans->sum('amount')) * 100, 1) : 0; @endphp
                <p class="text-xs font-bold text-primary-dark mt-1.5">{{ $pendingPercent }}% pending</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                <i class="bi bi-cash-stack text-lg"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Active Borrowers</p>
                <h3 class="text-[18px] font-black text-primary">{{ $activeCount }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">With active loans</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                <i class="bi bi-people text-lg"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">

        {{-- Filters --}}
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 flex-wrap">
            <div class="relative group min-w-[240px] flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary"></i>
                <input type="text" x-model="searchTerm" placeholder="Search by borrower name..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>
            <div class="relative min-w-[150px]">
                <select x-model="statusFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:border-primary outline-none appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="settled">Settled</option>
                    <option value="rejected">Rejected</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
            <div class="relative min-w-[150px]">
                <select x-model="typeFilter" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:border-primary outline-none appearance-none cursor-pointer">
                    <option value="">All Types</option>
                    <option value="personal">Personal</option>
                    <option value="advance">Advance</option>
                    <option value="emergency">Emergency</option>
                    <option value="business">Business</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </div>

        {{-- Table title --}}
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Loan List</h2>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider w-12 text-center">#</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider">Borrower</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider">Type</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Loan Amount</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Paid</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Balance</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($loans as $loan)
                    @php
                        $displayName = $loan->borrower_name ?: ($loan->employee->full_name ?? 'Unknown');
                        $displayType = $loan->borrower_type ?? 'individual';
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                        x-show="
                            (searchTerm === '' || '{{ strtolower($displayName) }}'.includes(searchTerm.toLowerCase())) &&
                            (statusFilter === '' || '{{ $loan->status }}' === statusFilter) &&
                            (typeFilter === '' || '{{ $loan->type }}' === typeFilter)
                        ">
                        <td class="px-5 py-4 text-[13px] font-black text-primary-dark text-center">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-xs flex-shrink-0">
                                    {{ strtoupper(substr($displayName, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-[13px] font-black text-primary-dark leading-tight">{{ $displayName }}</p>
                                    @if($loan->phone)
                                    <p class="text-[10px] text-gray-400">{{ $loan->phone }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide
                                @if($displayType === 'employee') bg-primary/10 text-primary
                                @elseif($displayType === 'customer') bg-blue-50 text-blue-600
                                @elseif($displayType === 'business') bg-purple-50 text-purple-600
                                @else bg-gray-100 text-gray-500 @endif">
                                {{ ucfirst($displayType) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black text-primary-dark">{{ $currency }} {{ number_format($loan->amount, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black text-primary-dark">{{ $currency }} {{ number_format($loan->recovered, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <span class="text-[13px] font-black {{ $loan->balance > 0 ? 'text-primary' : 'text-accent' }}">{{ $currency }} {{ number_format($loan->balance, 2) }}</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $statusClass = match($loan->status) {
                                    'active'   => 'bg-accent/10 text-accent border-accent/20',
                                    'pending'  => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                    'settled'  => 'bg-gray-100 text-gray-500 border-gray-200',
                                    'rejected' => 'bg-red-50 text-red-500 border-red-200',
                                    default    => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border capitalize {{ $statusClass }}">
                                {{ $loan->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($loan->status == 'pending')
                                    <button @click="openEditModal({{ json_encode($loan->load('employee')) }})"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-accent hover:border-accent/40 hover:bg-accent/5 flex items-center justify-center transition-all" title="Edit">
                                        <i class="bi bi-pencil text-xs"></i>
                                    </button>
                                    <button @click="updateStatus({{ $loan->id }}, 'active')"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-green-600 hover:border-green-200 hover:bg-green-50 flex items-center justify-center transition-all" title="Approve">
                                        <i class="bi bi-check-lg text-xs"></i>
                                    </button>
                                    <button @click="updateStatus({{ $loan->id }}, 'rejected')"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition-all" title="Reject">
                                        <i class="bi bi-x-lg text-xs"></i>
                                    </button>
                                    <button @click="confirmDelete({{ $loan->id }})"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition-all" title="Delete">
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                @else
                                    <button @click="openDetailsModal({{ json_encode($loan->load('employee')) }})"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/40 hover:bg-primary/5 flex items-center justify-center transition-all" title="View">
                                        <i class="bi bi-eye text-xs"></i>
                                    </button>
                                    <a href="{{ route('loan.payslip', $loan->id) }}" target="_blank"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/40 hover:bg-primary/5 flex items-center justify-center transition-all" title="Print Receipt">
                                        <i class="bi bi-printer text-xs"></i>
                                    </a>
                                    @if($loan->status == 'active')
                                    <button @click="openPaymentModal({{ $loan->id }}, '{{ addslashes($loan->borrower_name ?: ($loan->employee->full_name ?? 'Borrower')) }}', {{ $loan->balance }}, {{ $loan->amount }})"
                                        class="w-7 h-7 rounded-lg border border-accent/40 bg-accent/5 text-accent hover:bg-accent hover:text-primary flex items-center justify-center transition-all font-bold" title="Receive Payment">
                                        <i class="bi bi-cash-coin text-xs"></i>
                                    </button>
                                    @endif
                                    @if(in_array($loan->status, ['rejected', 'settled']))
                                    <button @click="confirmDelete({{ $loan->id }})"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition-all" title="Delete">
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400 italic text-xs">No loan records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer --}}
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $loans->count() }} entries
            </p>
        </div>
    </div>

    {{-- ══ NEW / EDIT LOAN MODAL ══ --}}
    <div x-show="activeModal === 'loan-modal'" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.5rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col" @click.away="activeModal = null">

            {{-- Modal Header --}}
            <div class="px-8 py-6 bg-primary shrink-0 relative overflow-hidden">
                <div class="absolute right-0 top-0 w-40 h-40 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/3"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl">
                            <i class="bi bi-file-earmark-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white" x-text="editMode ? 'Edit Loan Request' : 'New Loan Request'"></h2>
                            <p class="text-[11px] text-white/50 mt-0.5">Fill in the details below</p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-8 py-6 overflow-y-auto flex-grow">
                <form id="loanForm" :action="formAction" method="POST">
                    @csrf
                    <template x-if="editMode"><input type="hidden" name="_method" value="PUT"></template>

                    {{-- Borrower Info --}}
                    <div class="mb-6">
                        <p class="text-[10px] font-black text-primary-dark uppercase tracking-[0.15em] mb-4">Borrower Information</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Full Name <span class="text-red-400">*</span></label>
                                <input type="text" name="borrower_name" x-model="loanData.borrower_name" required
                                    placeholder="Enter borrower's full name"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Borrower Type <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select name="borrower_type" x-model="loanData.borrower_type"
                                        class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                        <option value="individual">Individual</option>
                                        <option value="employee">Employee</option>
                                        <option value="customer">Customer</option>
                                        <option value="vendor">Vendor</option>
                                        <option value="business">Business</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone Number</label>
                                <input type="text" name="phone" x-model="loanData.phone"
                                    inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    placeholder="e.g. +252 61 234 5678"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>

                        {{-- Optional Employee Link --}}
                        <div class="mt-4" x-show="loanData.borrower_type === 'employee'">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Link to Registered Employee <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                            <div class="relative mt-1.5">
                                <select name="employee_id" x-model="loanData.employee_id"
                                    class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                    <option value="">— Select employee —</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        {{-- send empty employee_id when borrower is not a staff member --}}
                        <template x-if="loanData.borrower_type !== 'employee'">
                            <input type="hidden" name="employee_id" value="">
                        </template>
                    </div>

                    {{-- Loan Details --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-[10px] font-black text-primary-dark uppercase tracking-[0.15em] mb-4">Loan Financial Details</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Loan Amount <span class="text-red-400">*</span></label>
                                <input type="number" step="0.01" min="0" name="amount" x-model="loanData.amount"
                                    required placeholder="0.00"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Loan Date <span class="text-red-400">*</span></label>
                                <input type="date" name="start_date" x-model="loanData.start_date" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Loan Type</label>
                                <div class="relative">
                                    <select name="type" x-model="loanData.type"
                                        class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                        <option value="personal">Personal</option>
                                        <option value="advance">Advance</option>
                                        <option value="emergency">Emergency</option>
                                        <option value="business">Business</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reason <span class="text-red-400">*</span></label>
                            <input type="text" name="reason" x-model="loanData.reason" required
                                placeholder="Purpose of this loan"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>
                </form>
            </div>

            {{-- Modal Footer --}}
            <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between shrink-0">
                <button type="button" @click="activeModal = null"
                    class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">
                    Cancel
                </button>
                <button type="submit" form="loanForm"
                    class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                    <span x-text="editMode ? 'Update Request' : 'Submit Request'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══ DETAILS MODAL ══ --}}
    <div x-show="activeModal === 'details-modal'" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.25rem] w-full max-w-md overflow-hidden shadow-2xl flex flex-col" @click.away="activeModal = null">
            <div class="px-6 py-5 bg-primary shrink-0 flex items-center justify-between">
                <h2 class="text-base font-bold text-white">Loan Details</h2>
                <button @click="activeModal = null" class="w-8 h-8 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center"><i class="bi bi-x-lg text-xs"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-4 border-b pb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary font-bold text-lg"
                        x-text="(loanData.borrower_name || '?').substring(0,1).toUpperCase()"></div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm" x-text="loanData.borrower_name || (loanData.employee ? loanData.employee.full_name : '—')"></h3>
                        <p class="text-[10px] text-gray-400 capitalize" x-text="loanData.borrower_type || 'individual'"></p>
                        <p class="text-[10px] text-gray-400" x-text="loanData.phone || ''"></p>
                        <p class="text-[10px] text-accent font-bold" x-text="loanData.loan_id || ''"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-b pb-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Loan Amount</p>
                        <p class="text-sm font-bold text-primary-dark" x-text="'{{ $currency }} ' + parseFloat(loanData.amount || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Monthly Repayment</p>
                        <p class="text-sm font-bold text-primary-dark" x-text="'{{ $currency }} ' + parseFloat(loanData.monthly_deduction || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Loan Date</p>
                        <p class="text-sm font-bold text-primary-dark" x-text="loanData.start_date || '—'"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Paid So Far</p>
                        <p class="text-sm font-bold text-accent" x-text="'{{ $currency }} ' + parseFloat(loanData.recovered || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Balance</p>
                        <p class="text-sm font-bold text-primary" x-text="'{{ $currency }} ' + parseFloat(loanData.balance || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Status</p>
                        <p class="text-sm font-bold capitalize" x-text="loanData.status || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Reason</p>
                        <p class="text-sm font-medium text-gray-700" x-text="loanData.reason || '—'"></p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                <button @click="activeModal = null" class="px-5 py-2 bg-white border rounded-lg text-xs font-bold hover:bg-gray-50 transition-all">Close</button>
            </div>
        </div>
    </div>

    {{-- ══ RECEIVE PAYMENT MODAL ══ --}}
    <div x-show="activeModal === 'payment-modal'" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

        <div class="bg-white rounded-[1.5rem] w-full max-w-md shadow-2xl flex flex-col overflow-hidden" @click.away="activeModal = null">

            {{-- Header --}}
            <div class="px-8 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="absolute right-0 top-0 w-36 h-36 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/3"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-accent/20 border border-accent/30 rounded-xl flex items-center justify-center text-accent text-xl">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Receive Payment</h2>
                            <p class="text-[11px] text-white/50 mt-0.5" x-text="'From: ' + paymentData.borrowerName"></p>
                        </div>
                    </div>
                    <button @click="activeModal = null" class="w-8 h-8 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-8 py-6">
                {{-- Balance summary --}}
                <div class="flex items-center justify-between mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Outstanding Balance</p>
                        <p class="text-xl font-black text-primary" x-text="'{{ $currency }} ' + parseFloat(paymentData.balance).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Loan Amount</p>
                        <p class="text-sm font-bold text-gray-600" x-text="'{{ $currency }} ' + parseFloat(paymentData.totalAmount).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                </div>

                <form :action="'/loans/payment/' + paymentData.loanId" method="POST" id="paymentForm">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                            Amount Received <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">{{ $currency }}</span>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                :max="paymentData.balance"
                                x-model="paymentData.payAmount"
                                required placeholder="0.00"
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-[15px] font-bold text-gray-800 focus:bg-white focus:border-accent outline-none transition-all">
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" @click="paymentData.payAmount = paymentData.balance"
                                class="px-3 py-1.5 text-[11px] font-bold text-accent border border-accent/30 bg-accent/5 rounded-lg hover:bg-accent/10 transition-all">
                                Full Amount
                            </button>
                            <button type="button" @click="paymentData.payAmount = (paymentData.balance / 2).toFixed(2)"
                                class="px-3 py-1.5 text-[11px] font-bold text-gray-500 border border-gray-200 bg-white rounded-lg hover:bg-gray-50 transition-all">
                                Half
                            </button>
                        </div>
                    </div>

                    {{-- After-payment preview --}}
                    <div class="mt-4 p-3 bg-primary/5 border border-primary/10 rounded-xl" x-show="paymentData.payAmount > 0">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Balance After Payment</p>
                        <p class="text-base font-black text-primary"
                            x-text="'{{ $currency }} ' + Math.max(0, paymentData.balance - parseFloat(paymentData.payAmount || 0)).toLocaleString(undefined,{minimumFractionDigits:2})">
                        </p>
                        <p class="text-[10px] text-accent font-bold mt-1" x-show="parseFloat(paymentData.payAmount || 0) >= paymentData.balance">
                            &#10003; This will fully settle the loan
                        </p>
                    </div>
                </form>
            </div>

            {{-- Footer --}}
            <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between shrink-0">
                <button type="button" @click="activeModal = null"
                    class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px]">
                    Cancel
                </button>
                <button type="submit" form="paymentForm"
                    class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center gap-2">
                    <i class="bi bi-cash-coin"></i> Confirm Receipt
                </button>
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
            id: '', loan_id: '',
            borrower_name: '', borrower_type: 'individual', phone: '',
            employee_id: '', employee: null,
            amount: '', start_date: '',
            type: 'personal', reason: '',
            recovered: 0, balance: 0, status: ''
        },

        paymentData: {
            loanId: null, borrowerName: '', balance: 0, totalAmount: 0, payAmount: ''
        },

        openPaymentModal(id, name, balance, total) {
            this.paymentData = {
                loanId: id, borrowerName: name,
                balance: parseFloat(balance),
                totalAmount: parseFloat(total),
                payAmount: parseFloat(balance).toFixed(2)
            };
            this.activeModal = 'payment-modal';
        },

        openCreateModal() {
            this.editMode = false;
            this.formAction = '{{ route('loan.store') }}';
            this.loanData = {
                id: '', loan_id: '',
                borrower_name: '', borrower_type: 'individual', phone: '',
                employee_id: '', employee: null,
                amount: '',
                start_date: new Date().toISOString().split('T')[0],
                type: 'personal', reason: '',
                recovered: 0, balance: 0, status: ''
            };
            this.activeModal = 'loan-modal';
        },

        openEditModal(loan) {
            this.editMode = true;
            this.formAction = '/loans/update/' + loan.id;
            this.loanData = { ...loan };
            this.activeModal = 'loan-modal';
        },

        openDetailsModal(loan) {
            this.loanData = { ...loan };
            this.activeModal = 'details-modal';
        },

        updateStatus(id, status) {
            const labels = { active: 'Approve', rejected: 'Reject', settled: 'Mark Settled' };
            const colors = { active: '#99CC33', rejected: '#f43f5e', settled: '#004161' };
            Swal.fire({
                title: labels[status] + ' Loan?',
                text: 'Are you sure you want to ' + status + ' this loan request?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: colors[status] || '#004161',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Yes, ' + (labels[status] || 'Update'),
                customClass: { popup: 'rounded-[1.25rem]', confirmButton: 'rounded-lg px-5 py-2 text-xs font-bold', cancelButton: 'rounded-lg px-5 py-2 text-xs font-bold' }
            }).then(result => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/loans/status/' + id;
                    form.innerHTML = `@csrf<input type="hidden" name="status" value="${status}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        },

        confirmDelete(id) {
            deleteRecordWithPassword('/loans/delete/' + id, 'this loan record', {
                title: 'Delete Loan Record?',
                text: 'This will permanently remove this loan. It cannot be undone.'
            });
        }
    }));
});
</script>
@endpush
