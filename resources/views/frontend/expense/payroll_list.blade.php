@extends('admin.admin_master')
@section('page_title', 'Payroll')

@section('admin')
<div class="bg-background min-h-screen" x-data="payrollPage()">

    {{-- ══ TOP BANNER ══ --}}
    <div class="bg-white border-b border-gray-100 px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-[0.9rem] bg-primary flex items-center justify-center shadow-md shrink-0">
                <i class="bi bi-people-fill text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-[18px] font-black text-primary-dark leading-tight">Payroll</h1>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">HR &bull; Monthly Salary Payments</p>
            </div>
        </div>
        <button @click="openModal = true"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white font-bold rounded-[0.6rem] hover:bg-primary/90 active:scale-95 transition-all shadow-md text-sm shrink-0">
            <i class="bi bi-plus-lg text-base"></i>
            Record Salary Payment
        </button>
    </div>

    <div class="px-6 py-6">

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="flex items-center gap-3 bg-accent/10 border border-accent/30 text-primary-dark text-sm font-semibold rounded-xl px-5 py-3.5 mb-5 shadow-sm">
            <i class="bi bi-check-circle-fill text-accent text-base shrink-0"></i>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 text-sm font-semibold rounded-xl px-5 py-3.5 mb-5 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill text-red-500 text-base shrink-0"></i>
            {{ session('error') }}
        </div>
        @endif

        {{-- ══ STATS CARDS ══ --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            {{-- Paid This Month --}}
            <div class="bg-primary rounded-[1.1rem] p-6 flex items-center gap-5 shadow-md">
                <div class="w-14 h-14 rounded-[1rem] bg-white/10 flex items-center justify-center shrink-0">
                    <i class="bi bi-cash-stack text-accent text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-white/60 uppercase tracking-widest mb-1">Paid This Month</p>
                    <h3 class="text-[26px] font-black text-white leading-none">{{ $currency }}&thinsp;{{ number_format($paidThisMonth, 2) }}</h3>
                    <p class="text-[11px] text-white/50 mt-1.5 font-medium">{{ $countThisMonth }} employee{{ $countThisMonth !== 1 ? 's' : '' }} &bull; {{ now()->format('F Y') }}</p>
                </div>
            </div>

            {{-- Active Employees --}}
            <div class="bg-white rounded-[1.1rem] border border-gray-100 shadow-sm p-6 flex items-center gap-5 hover:shadow-md transition-shadow duration-200">
                <div class="w-14 h-14 rounded-[1rem] bg-accent flex items-center justify-center shadow-md shrink-0">
                    <i class="bi bi-person-badge-fill text-primary text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Active Employees</p>
                    <h3 class="text-[26px] font-black text-primary-dark leading-none">{{ $employees->count() }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1.5 font-medium">on payroll roster</p>
                </div>
            </div>

            {{-- Total All Time --}}
            <div class="bg-white rounded-[1.1rem] border border-gray-100 shadow-sm p-6 flex items-center gap-5 hover:shadow-md transition-shadow duration-200">
                <div class="w-14 h-14 rounded-[1rem] bg-primary/10 flex items-center justify-center shrink-0">
                    <i class="bi bi-wallet2 text-primary text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Paid (All Time)</p>
                    <h3 class="text-[26px] font-black text-primary leading-none">{{ $currency }}&thinsp;{{ number_format($ytdTotal, 2) }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1.5 font-medium">{{ $items->count() }} record{{ $items->count() !== 1 ? 's' : '' }} total</p>
                </div>
            </div>

        </div>

        {{-- ══ SALARY RECORDS TABLE ══ --}}
        <div class="bg-white rounded-[1.1rem] border border-gray-100 shadow-sm overflow-hidden">

            {{-- Table Toolbar --}}
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="bi bi-receipt text-primary text-base"></i>
                    <span class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Salary Records</span>
                    <span class="ml-1 bg-accent/20 text-primary text-[10px] font-black px-2 py-0.5 rounded-full">{{ $items->count() }}</span>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-52">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <input type="text" x-model="search" placeholder="Search employee…"
                            class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-[0.5rem] text-[12px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none bg-white transition-all">
                    </div>
                    <input type="month" x-model="monthFilter"
                        class="pl-3 pr-3 py-2 border border-gray-200 rounded-[0.5rem] text-[12px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none bg-white w-36">
                    <button x-show="search || monthFilter" @click="search=''; monthFilter=''"
                        class="w-8 h-8 rounded-[0.5rem] border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/30 hover:bg-primary/5 flex items-center justify-center transition-all shrink-0"
                        title="Clear filters">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="border-collapse:collapse">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom: 1px solid #f1f5f9;">
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-10">#</th>
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Employee</th>
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Pay Period</th>
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Basic Salary</th>
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Net Paid</th>
                            <th class="px-5 py-3.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-5 py-3.5 w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $item)
                        @php
                            $empName  = $item->employee->full_name ?? '—';
                            $monthVal = optional($item->payroll)->month_year ?? '';
                            $initial  = strtoupper(substr($item->employee->full_name ?? '?', 0, 1));
                            $avatarBg = $i % 2 === 0 ? 'background:#004161' : 'background:#3a6a8a';
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors"
                            style="border-bottom: 1px solid #f1f5f9;"
                            x-show="matchesFilter('{{ addslashes($empName) }}', '{{ $monthVal }}')">

                            <td class="px-5 py-4 text-center">
                                <span class="text-[11px] font-bold text-gray-300">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-[11px] font-black shrink-0 shadow-sm"
                                        style="{{ $avatarBg }}">
                                        {{ $initial }}
                                    </div>
                                    <div>
                                        <p class="text-[13px] font-bold text-primary-dark leading-tight">{{ $item->employee->full_name ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $item->employee->designation ?? $item->employee->department ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-primary bg-primary/10 px-2.5 py-1 rounded-full border border-primary/15">
                                    <i class="bi bi-calendar3 text-[9px]"></i>{{ $monthVal }}
                                </span>
                                @if($item->payment_date)
                                <p class="text-[10px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($item->payment_date)->format('d M Y') }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <span class="text-[13px] font-bold text-gray-700">{{ $currency }}&thinsp;{{ number_format($item->basic_salary, 2) }}</span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <span class="text-[15px] font-black text-primary">{{ $currency }}&thinsp;{{ number_format($item->net_salary, 2) }}</span>
                            </td>

                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wide px-3 py-1 rounded-full bg-accent/15 text-primary border border-accent/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>Paid
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Receipt --}}
                                    <a href="{{ route('payroll.item.receipt', $item->id) }}" target="_blank"
                                        title="Print Receipt"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/40 hover:bg-primary/5 flex items-center justify-center transition-all">
                                        <i class="bi bi-printer text-xs"></i>
                                    </a>
                                    {{-- Edit --}}
                                    <button @click="openEdit({{ $item->id }}, '{{ $monthVal }}', {{ $item->basic_salary }}, '{{ addslashes($empName) }}')"
                                        title="Edit"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 hover:text-accent hover:border-accent/40 hover:bg-accent/5 flex items-center justify-center transition-all"
                                        style="--tw-hover-text-opacity:1">
                                        <i class="bi bi-pencil text-xs"></i>
                                    </button>
                                    {{-- Delete --}}
                                    <button onclick="deletePayrollItem({{ $item->id }}, '{{ addslashes($empName) }}', '{{ $monthVal }}')"
                                        title="Delete"
                                        class="w-7 h-7 rounded-lg border border-gray-200 text-gray-300 hover:text-red-500 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition-all">
                                        <i class="bi bi-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-24 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 bg-primary/5 rounded-2xl flex items-center justify-center border border-primary/10">
                                        <i class="bi bi-people text-3xl text-primary/30"></i>
                                    </div>
                                    <div>
                                        <p class="text-[14px] font-bold text-gray-400">No salary payments recorded yet</p>
                                        <p class="text-[12px] text-gray-300 mt-1">Click the button above to record a salary payment</p>
                                    </div>
                                    <button @click="openModal = true"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-[0.6rem] hover:bg-primary/90 transition-all text-sm shadow-sm mt-1">
                                        <i class="bi bi-plus-lg"></i> Record First Payment
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ══ MODAL ══ --}}
    <div x-show="openModal" x-cloak
        class="fixed inset-0 bg-primary/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300">

        <div class="bg-white rounded-[1.5rem] shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col"
            @click.away="closeModal()">

            {{-- Header --}}
            <div class="px-8 py-6 bg-primary shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-2xl shadow-inner">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white tracking-tight leading-tight" x-text="editMode ? 'Edit Salary Payment' : 'Record Salary Payment'">Record Salary Payment</h2>
                            <p class="text-[12px] text-white/50 font-medium mt-0.5" x-text="editMode ? 'Update the salary amount below' : 'Complete all required information below'">Complete all required information below</p>
                        </div>
                    </div>
                    <button @click="closeModal()"
                        class="w-9 h-9 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center group shadow-sm">
                        <i class="bi bi-x-lg text-xs group-hover:scale-110 transition-transform"></i>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            <form id="payrollForm" :action="editMode ? '/payroll/item/' + editId : '{{ route('payroll.store') }}'" method="POST" @submit="submitting = true">
                @csrf
                <template x-if="editMode"><input type="hidden" name="_method" value="PUT"></template>

                <div class="p-6 overflow-y-auto modal-scroll space-y-4">

                    {{-- Row 1: Pay Period + Employee (read-only in edit mode) --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1.5">Pay Period <span x-show="!editMode" class="text-primary">*</span></label>
                            <input type="month" name="month_year" x-model="form.month_year" :required="!editMode" :disabled="editMode"
                                :class="editMode ? 'opacity-60 cursor-not-allowed' : ''"
                                class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            @error('month_year')
                                <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1.5">Employee <span x-show="!editMode" class="text-primary">*</span></label>
                            <div x-show="editMode" class="w-full pl-3 pr-3 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 opacity-60" x-text="editEmployeeName">—</div>
                            <select x-show="!editMode" name="employee_id" x-model="form.employee_id" @change="prefillSalary()" :required="!editMode"
                                class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                <option value="">— Select Employee —</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-salary="{{ $emp->salary ?? 0 }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Basic Salary (full width) --}}
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1.5">Basic Salary <span class="text-primary">*</span></label>
                        <input type="number" name="basic_salary" x-model.number="form.basic_salary"
                            step="0.01" min="0" required placeholder="0.00"
                            class="w-full pl-3 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all input-no-spinner">
                        @error('basic_salary')
                            <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Net Salary Bar --}}
                    <div class="bg-primary rounded-xl px-5 py-4 flex items-center justify-between">
                        <p class="text-[11px] font-bold text-white/60 uppercase tracking-wider">Salary Payable</p>
                        <p class="text-[26px] font-bold text-accent leading-none" x-text="'{{ $currency }} ' + fmt(form.basic_salary||0)">{{ $currency }} 0.00</p>
                    </div>

                </div>
            </form>

            {{-- Footer --}}
            <div class="bg-gray-50 px-6 py-5 border-t border-gray-100 flex justify-between items-center shrink-0">
                <button type="button" @click="closeModal()"
                    class="px-8 py-3 bg-white border border-gray-200 text-gray-500 rounded-xl font-bold hover:text-primary hover:border-primary transition-all text-sm shadow-sm">
                    Cancel
                </button>
                <button type="submit" form="payrollForm"
                    :disabled="submitting || !(form.basic_salary > 0)"
                    class="px-10 py-3 bg-accent text-primary font-bold rounded-xl hover:opacity-95 transition-all flex items-center gap-2 text-sm shadow-lg shadow-accent/20 disabled:opacity-40 disabled:cursor-not-allowed">
                    <template x-if="!submitting"><i class="bi bi-check-circle-fill"></i></template>
                    <template x-if="submitting">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <span x-text="submitting ? 'Saving…' : (editMode ? 'Update Payment' : 'Record Payment')"></span>
                </button>
            </div>

        </div>
    </div>

    {{-- Hidden delete form --}}
    <form id="deleteItemForm" method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>

</div>
@endsection

@push('scripts')
<script>
function payrollPage() {
    const empData = @json($employees->map(fn($e) => ['id' => $e->id, 'salary' => floatval($e->salary ?? 0)]));

    return {
        openModal: {{ ($errors->any() || old('employee_id')) ? 'true' : 'false' }},
        submitting: false,
        editMode: false,
        editId: null,
        editEmployeeName: '',
        search: '',
        monthFilter: '',

        form: {
            month_year:   '{{ now()->format('Y-m') }}',
            employee_id:  '{{ old('employee_id', '') }}',
            basic_salary: {{ floatval(old('basic_salary', 0)) }},
        },

        openEdit(id, month, salary, empName) {
            this.editMode         = true;
            this.editId           = id;
            this.editEmployeeName = empName;
            this.form.month_year  = month;
            this.form.basic_salary = salary;
            this.openModal        = true;
        },

        prefillSalary() {
            const emp = empData.find(e => e.id == this.form.employee_id);
            if (emp && emp.salary > 0) this.form.basic_salary = emp.salary;
        },

        fmt(n) {
            return parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        closeModal() {
            this.openModal        = false;
            this.submitting       = false;
            this.editMode         = false;
            this.editId           = null;
            this.editEmployeeName = '';
        },

        matchesFilter(name, month) {
            const nameOk  = !this.search      || name.toLowerCase().includes(this.search.toLowerCase());
            const monthOk = !this.monthFilter || month === this.monthFilter;
            return nameOk && monthOk;
        },
    };
}

function deletePayrollItem(id, name, month) {
    Swal.fire({
        title: 'Delete Salary Record?',
        html: `Remove <strong>${name}</strong>'s salary payment for <strong>${month}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({
            title: 'Enter Password to Confirm',
            input: 'password',
            inputPlaceholder: 'Your account password',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            confirmButtonText: 'Confirm & Delete',
            inputAttributes: { autocomplete: 'current-password' },
        }).then(r2 => {
            if (!r2.isConfirmed || !r2.value) return;
            const form = document.getElementById('deleteItemForm');
            form.action = '/payroll/item/' + id;
            let pw = form.querySelector('input[name="delete_password"]');
            if (!pw) { pw = document.createElement('input'); pw.type = 'hidden'; pw.name = 'delete_password'; form.appendChild(pw); }
            pw.value = r2.value;
            form.submit();
        });
    });
}
</script>
@endpush
