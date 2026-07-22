@extends('admin.admin_master')
@section('page_title', 'Cash Loans')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    activeModal: null,
    saving: false,
    editMode: false,
    formAction: '{{ route('loan.store') }}',
    search: '',
    statusFilter: '',
    loanData: {
        id: '', loan_id: '', borrower_name: '', borrower_type: 'individual', phone: '',
        employee_id: '', employee: null, amount: '', start_date: '',
        type: 'personal', reason: '', recovered: 0, balance: 0, status: ''
    },
    paymentData: { loanId: null, borrowerName: '', balance: 0, totalAmount: 0, payAmount: '' },

    openCreateModal() {
        this.editMode = false;
        this.formAction = '{{ route('loan.store') }}';
        this.loanData = {
            id: '', loan_id: '', borrower_name: '', borrower_type: 'individual', phone: '',
            employee_id: '', employee: null, amount: '', start_date: '{{ date('Y-m-d') }}',
            type: 'personal', reason: '', recovered: 0, balance: 0, status: ''
        };
        this.activeModal = 'loan-modal';
    },
    openEditModal(loan) {
        this.editMode = true;
        this.formAction = '{{ url('/loans/update') }}/' + loan.id;
        this.loanData = { ...loan };
        this.activeModal = 'loan-modal';
    },
    openDetailsModal(loan) {
        this.loanData = { ...loan };
        this.activeModal = 'details-modal';
    },
    openPaymentModal(id, name, balance, total) {
        this.paymentData = { loanId: id, borrowerName: name, balance: parseFloat(balance), totalAmount: parseFloat(total), payAmount: parseFloat(balance).toFixed(2) };
        this.activeModal = 'payment-modal';
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
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ url('/loans/status') }}/' + id;
                form.innerHTML = `@csrf<input type="hidden" name="status" value="${status}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    },
    confirmDelete(id) {
        deleteRecordWithPassword('{{ url('/loans/delete') }}/' + id, 'this loan record', {
            title: 'Delete Loan Record?',
            text: 'This will permanently remove this loan. It cannot be undone.'
        });
    },
    matchLoan(name, status) {
        const q = this.search.toLowerCase();
        if (q && !name.toLowerCase().includes(q)) return false;
        if (this.statusFilter && status !== this.statusFilter) return false;
        return true;
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Cash Loans</h1>
        <button @click="openCreateModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Loan
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Total Given</p>
            <p class="text-[16px] font-black text-primary">{{ $currency }} {{ number_format($loans->sum('amount'), 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-graph-up-arrow text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Recovered</p>
            <p class="text-[16px] font-black text-primary">{{ $currency }} {{ number_format($totalRecovered, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Outstanding</p>
            <p class="text-[16px] font-black text-primary">{{ $currency }} {{ number_format($totalOutstanding, 0) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH BORROWER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select x-model="statusFilter"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="settled">Settled</option>
                <option value="rejected">Rejected</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </div>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Loan Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($loans as $loan)
            @php
                $displayName = $loan->borrower_name ?: ($loan->employee->full_name ?? 'Unknown');
                $displayType = $loan->borrower_type ?? 'individual';
                $loanPayload = $loan->load('employee')->toArray();
                $statusClass = match($loan->status) {
                    'active'   => 'bg-accent/10 text-accent',
                    'pending'  => 'bg-gray-100 text-gray-400',
                    'settled'  => 'bg-gray-100 text-gray-500',
                    'rejected' => 'bg-red-50 text-red-500',
                    default    => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <div x-show="matchLoan('{{ addslashes($displayName) }}', '{{ $loan->status }}')"
                class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $displayName }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $loan->loan_id }} · {{ ucfirst($displayType) }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $currency }} {{ number_format($loan->amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">Bal: {{ $currency }} {{ number_format($loan->balance, 2) }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusClass }}">{{ $loan->status }}</span>
                    <div class="flex items-center gap-2">
                        @if($loan->status === 'pending')
                            <button type="button" @click="openEditModal(@js($loanPayload))"
                                class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                                <i class="bi bi-pencil text-xs"></i>
                            </button>
                            <button type="button" @click="updateStatus({{ $loan->id }}, 'active')"
                                class="w-7 h-7 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent active:bg-accent/20">
                                <i class="bi bi-check-lg text-xs"></i>
                            </button>
                            <button type="button" @click="updateStatus({{ $loan->id }}, 'rejected')"
                                class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                            <button type="button" @click="confirmDelete({{ $loan->id }})"
                                class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                                <i class="bi bi-trash text-xs"></i>
                            </button>
                        @else
                            <button type="button" @click="openDetailsModal(@js($loanPayload))"
                                class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                                <i class="bi bi-eye text-xs"></i>
                            </button>
                            <a href="{{ route('loan.payslip', $loan->id) }}" target="_blank"
                                class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                                <i class="bi bi-printer text-xs"></i>
                            </a>
                            @if($loan->status === 'active')
                                <button type="button" @click="openPaymentModal({{ $loan->id }}, '{{ addslashes($displayName) }}', {{ $loan->balance }}, {{ $loan->amount }})"
                                    class="w-7 h-7 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent active:bg-accent/20">
                                    <i class="bi bi-cash-coin text-xs"></i>
                                </button>
                            @endif
                            @if(in_array($loan->status, ['rejected', 'settled']))
                                <button type="button" @click="confirmDelete({{ $loan->id }})"
                                    class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                                    <i class="bi bi-trash text-xs"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-cash-coin text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No loan records found.</p>
            </div>
        @endforelse
    </div>

    {{-- New / Edit Loan — mobile bottom sheet --}}
    <div x-show="activeModal === 'loan-modal'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'loan-modal'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]" x-text="editMode ? 'Edit Loan Request' : 'New Loan Request'"></h2>
            </div>

            <form id="loanForm" :action="formAction" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                <template x-if="editMode"><input type="hidden" name="_method" value="PUT"></template>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Full Name <span class="text-primary">*</span></label>
                    <input type="text" name="borrower_name" x-model="loanData.borrower_name" required placeholder="Borrower's full name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Borrower Type <span class="text-primary">*</span></label>
                        <div class="relative">
                            <select name="borrower_type" x-model="loanData.borrower_type"
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 outline-none appearance-none">
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
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Phone</label>
                        <input type="text" name="phone" x-model="loanData.phone"
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="61 234 5678"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div x-show="loanData.borrower_type === 'employee'" x-cloak>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Link Employee <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
                    <div class="relative">
                        <select name="employee_id" x-model="loanData.employee_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">— Select employee —</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <template x-if="loanData.borrower_type !== 'employee'">
                    <input type="hidden" name="employee_id" value="">
                </template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Loan Amount <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $currency }}</span>
                            <input type="number" step="0.01" min="0" name="amount" x-model="loanData.amount" required placeholder="0.00"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Loan Date <span class="text-primary">*</span></label>
                        <input type="date" name="start_date" x-model="loanData.start_date" required
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Loan Type</label>
                    <div class="relative">
                        <select name="type" x-model="loanData.type"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="personal">Personal</option>
                            <option value="advance">Advance</option>
                            <option value="emergency">Emergency</option>
                            <option value="business">Business</option>
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Reason <span class="text-primary">*</span></label>
                    <input type="text" name="reason" x-model="loanData.reason" required placeholder="Purpose of this loan"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : (editMode ? 'Update Request' : 'Submit Request')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Loan Details — mobile bottom sheet --}}
    <div x-show="activeModal === 'details-modal'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'details-modal'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Loan Details</h2>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="w-11 h-11 bg-primary/10 rounded-xl flex items-center justify-center text-primary font-black text-lg shrink-0"
                        x-text="(loanData.borrower_name || '?').substring(0,1).toUpperCase()"></div>
                    <div class="min-w-0">
                        <p class="text-[14px] font-black text-primary-dark truncate" x-text="loanData.borrower_name || (loanData.employee ? loanData.employee.full_name : '—')"></p>
                        <p class="text-[11px] text-gray-400 capitalize" x-text="loanData.borrower_type || 'individual'"></p>
                        <p class="text-[11px] text-accent font-bold" x-text="loanData.loan_id || ''"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Loan Amount</p>
                        <p class="text-[14px] font-black text-primary-dark" x-text="'{{ $currency }} ' + parseFloat(loanData.amount || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Loan Date</p>
                        <p class="text-[14px] font-black text-primary-dark" x-text="loanData.start_date || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Paid So Far</p>
                        <p class="text-[14px] font-black text-accent" x-text="'{{ $currency }} ' + parseFloat(loanData.recovered || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Balance</p>
                        <p class="text-[14px] font-black text-primary" x-text="'{{ $currency }} ' + parseFloat(loanData.balance || 0).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Status</p>
                        <p class="text-[14px] font-black text-primary-dark capitalize" x-text="loanData.status || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-0.5">Reason</p>
                        <p class="text-[13px] font-semibold text-gray-700" x-text="loanData.reason || '—'"></p>
                    </div>
                </div>

                <button type="button" @click="activeModal = null"
                    class="w-full py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Receive Payment — mobile bottom sheet --}}
    <div x-show="activeModal === 'payment-modal'" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="activeModal = null">
        <div x-show="activeModal === 'payment-modal'" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Receive Payment</h2>
                <p class="text-xs text-white/60 font-medium mt-0.5" x-text="'From: ' + paymentData.borrowerName"></p>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Outstanding</p>
                        <p class="text-[18px] font-black text-primary" x-text="'{{ $currency }} ' + parseFloat(paymentData.balance).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Loan Amount</p>
                        <p class="text-[13px] font-bold text-gray-600" x-text="'{{ $currency }} ' + parseFloat(paymentData.totalAmount).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                    </div>
                </div>

                <form :action="'{{ url('/loans/payment') }}/' + paymentData.loanId" method="POST" id="paymentForm" @submit="saving = true">
                    @csrf
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount Received <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">{{ $currency }}</span>
                            <input type="number" name="amount" step="0.01" min="0.01" :max="paymentData.balance"
                                x-model="paymentData.payAmount" required placeholder="0.00"
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-[15px] font-bold text-gray-800 outline-none">
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" @click="paymentData.payAmount = paymentData.balance"
                                class="px-3 py-1.5 text-[11px] font-bold text-accent border border-accent/30 bg-accent/5 rounded-lg">
                                Full Amount
                            </button>
                            <button type="button" @click="paymentData.payAmount = (paymentData.balance / 2).toFixed(2)"
                                class="px-3 py-1.5 text-[11px] font-bold text-gray-500 border border-gray-200 bg-white rounded-lg">
                                Half
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-primary/5 border border-primary/10 rounded-xl" x-show="paymentData.payAmount > 0">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Balance After Payment</p>
                        <p class="text-[15px] font-black text-primary"
                            x-text="'{{ $currency }} ' + Math.max(0, paymentData.balance - parseFloat(paymentData.payAmount || 0)).toLocaleString(undefined,{minimumFractionDigits:2})"></p>
                        <p class="text-[10px] text-accent font-bold mt-1" x-show="parseFloat(paymentData.payAmount || 0) >= paymentData.balance">
                            &#10003; This will fully settle the loan
                        </p>
                    </div>
                </form>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="activeModal = null"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" form="paymentForm" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-cash-coin'"></i>
                        <span x-text="saving ? 'Saving...' : 'Confirm Receipt'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
