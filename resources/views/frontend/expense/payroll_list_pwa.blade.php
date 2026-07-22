@extends('admin.admin_master')
@section('page_title', 'Payroll')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    showModal: false,
    saving: false,
    editMode: false,
    editId: null,
    editEmployeeName: '',
    search: '',
    monthFilter: '',
    empData: @js($employees->map(fn($e) => ['id' => $e->id, 'salary' => (float) ($e->salary ?? 0)])),
    form: { month_year: '{{ now()->format('Y-m') }}', employee_id: '', basic_salary: '' },

    openAddModal() {
        this.editMode = false;
        this.editId = null;
        this.editEmployeeName = '';
        this.form = { month_year: '{{ now()->format('Y-m') }}', employee_id: '', basic_salary: '' };
        this.showModal = true;
    },
    openEditModal(id, month, salary, empName) {
        this.editMode = true;
        this.editId = id;
        this.editEmployeeName = empName;
        this.form = { month_year: month, employee_id: '', basic_salary: salary };
        this.showModal = true;
    },
    prefillSalary() {
        const emp = this.empData.find(e => e.id == this.form.employee_id);
        if (emp && emp.salary > 0) this.form.basic_salary = emp.salary;
    },
    fmt(n) {
        return parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    matchesFilter(name, month) {
        const nameOk = !this.search || name.toLowerCase().includes(this.search.toLowerCase());
        const monthOk = !this.monthFilter || month === this.monthFilter;
        return nameOk && monthOk;
    },
    deleteItem(id, name, month) {
        deleteRecordWithPassword('{{ url('/payroll/item') }}/' + id, name, {
            title: 'Delete Salary Record?',
            text: 'Remove ' + name + '\'s salary payment for ' + month + '?'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Payroll</h1>
        <button @click="openAddModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Record Salary
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-cash-stack text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">This Month</p>
            <p class="text-[16px] font-black text-primary">{{ $currency }} {{ number_format($paidThisMonth, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-person-badge text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Employees</p>
            <p class="text-[16px] font-black text-primary">{{ $employees->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">All Time</p>
            <p class="text-[16px] font-black text-primary">{{ $currency }} {{ number_format($ytdTotal, 0) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" x-model="search" placeholder="SEARCH EMPLOYEE"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <input type="month" x-model="monthFilter"
                class="pl-3 pr-3 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none max-w-[130px]">
        </div>
    </div>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Salary Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($items as $item)
            @php
                $empName = $item->employee->full_name ?? '—';
                $monthVal = optional($item->payroll)->month_year ?? '';
            @endphp
            <div x-show="matchesFilter('{{ addslashes($empName) }}', '{{ $monthVal }}')"
                class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $empName }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $item->employee->designation ?? $item->employee->department ?? '—' }} · {{ $monthVal }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $currency }} {{ number_format($item->net_salary, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $item->payment_date ? \Carbon\Carbon::parse($item->payment_date)->format('d M Y') : '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-accent/10 text-accent">Paid</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('payroll.item.receipt', $item->id) }}" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-printer text-xs"></i>
                        </a>
                        <button type="button" @click="openEditModal({{ $item->id }}, '{{ $monthVal }}', {{ $item->basic_salary }}, '{{ addslashes($empName) }}')"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteItem({{ $item->id }}, '{{ addslashes($empName) }}', '{{ $monthVal }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-people text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No salary payments recorded yet.</p>
            </div>
        @endforelse
    </div>

    {{-- Record/Edit Salary — mobile bottom sheet --}}
    <div x-show="showModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showModal = false">
        <div x-show="showModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]" x-text="editMode ? 'Edit Salary Payment' : 'Record Salary Payment'"></h2>
            </div>

            <form :action="editMode ? '{{ url('/payroll/item') }}/' + editId : '{{ route('payroll.store') }}'" method="POST"
                class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                <template x-if="editMode"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Pay Period <span x-show="!editMode" class="text-primary">*</span></label>
                        <input type="month" name="month_year" x-model="form.month_year" :required="!editMode" :disabled="editMode"
                            :class="editMode ? 'opacity-60' : ''"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 outline-none">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Employee <span x-show="!editMode" class="text-primary">*</span></label>
                        <div x-show="editMode" class="w-full px-3 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-500 truncate" x-text="editEmployeeName"></div>
                        <div x-show="!editMode" class="relative">
                            <select name="employee_id" x-model="form.employee_id" @change="prefillSalary()" :required="!editMode"
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 outline-none appearance-none">
                                <option value="">Select...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Basic Salary <span class="text-primary">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $currency }}</span>
                        <input type="number" step="0.01" min="0" name="basic_salary" x-model.number="form.basic_salary" required placeholder="0.00"
                            class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div class="rounded-xl px-4 py-3 flex items-center justify-between bg-primary">
                    <span class="text-[11px] font-black uppercase tracking-wider text-white">Salary Payable</span>
                    <span class="text-[15px] font-black text-accent" x-text="'{{ $currency }} ' + fmt(form.basic_salary || 0)"></span>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving || !(form.basic_salary > 0)"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="(saving || !(form.basic_salary > 0)) ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : (editMode ? 'Update Payment' : 'Record Payment')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
