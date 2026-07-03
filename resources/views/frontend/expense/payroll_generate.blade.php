@extends('admin.admin_master')
@section('page_title', 'Generate Payroll')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="payrollGenerator()" x-init="init()">

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Generate Payroll</h1>
            <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Salary Computation &bull; {{ now()->format('F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('payroll.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-[0.5rem] hover:bg-gray-50 transition-all text-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button @click="submitForm()" :disabled="items.length === 0"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="bi bi-check2-circle"></i>
                Generate Payroll
            </button>
        </div>
    </div>

    <form id="payrollForm" action="{{ route('payroll.store') }}" method="POST">
    @csrf

    {{-- ── Config Bar ── --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 mb-5 flex flex-wrap gap-5 items-end">
        {{-- Month/Year --}}
        <div class="space-y-1.5 min-w-[200px]">
            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Pay Period <span class="text-primary">*</span>
            </label>
            <input type="month" name="month_year" x-model="month_year" required
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-[0.6rem] text-sm font-bold text-primary-dark focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none transition-all">
        </div>

        {{-- Branch --}}
        <div class="space-y-1.5 min-w-[220px]">
            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest">Branch Filter</label>
            <div class="relative">
                <select name="branch_id" x-model="branch_id" @change="filterEmployees()"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-[0.6rem] text-sm font-bold text-primary-dark focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none appearance-none transition-all">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" data-name="{{ $branch->name }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </div>

        {{-- Live Totals Summary --}}
        <div class="ml-auto flex flex-wrap items-center gap-6">
            <div class="text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Total Gross</p>
                <p class="text-sm font-black text-primary-dark">{{ $currency }} <span x-text="fmt(totalGross)"></span></p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Deductions</p>
                <p class="text-sm font-black text-red-500">−{{ $currency }} <span x-text="fmt(totalDeductions)"></span></p>
            </div>
            <div class="text-right border-l border-gray-100 pl-6">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Net Payable</p>
                <p class="text-[22px] font-black text-primary leading-none">{{ $currency }} <span x-text="fmt(totalNet)"></span></p>
            </div>
        </div>
    </div>

    {{-- ── Employee Salary Table ── --}}
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-24">
        <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-people-fill text-primary-dark text-sm"></i>
                <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Employee Salary Breakdown</span>
            </div>
            <span class="text-[11px] font-bold text-gray-400"
                x-text="items.length + ' employee' + (items.length !== 1 ? 's' : '')"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left" style="border-collapse:collapse">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center w-10">#</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Employee</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Basic Salary</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">
                            <span class="text-accent">Bonus</span>
                        </th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">
                            <span class="text-blue-500">Overtime</span>
                        </th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">
                            <span class="text-red-400">Deductions</span>
                        </th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Gross</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Net Payable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    <template x-for="(item, idx) in items" :key="item.employee_id">
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            {{-- Hidden fields --}}
                            <input type="hidden" :name="'items['+idx+'][employee_id]'"  :value="item.employee_id">
                            <input type="hidden" :name="'items['+idx+'][gross_salary]'" :value="item.gross_salary">
                            <input type="hidden" :name="'items['+idx+'][net_salary]'"   :value="item.net_salary">

                            <td class="px-5 py-3.5 text-center text-[11px] font-bold text-gray-400"
                                x-text="(idx+1).toString().padStart(2,'0')"></td>

                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-[10px] font-black text-primary shrink-0"
                                        x-text="item.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="text-xs font-bold text-primary-dark" x-text="item.name"></p>
                                        <p class="text-[10px] text-gray-400 mt-0.5" x-text="item.designation || 'Staff'"></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <input type="number" step="0.01" min="0"
                                    :name="'items['+idx+'][basic_salary]'"
                                    x-model.number="item.basic_salary"
                                    @input="recalc(item)"
                                    class="w-28 px-3 py-1.5 bg-gray-50 border border-transparent rounded-lg text-xs font-bold text-right text-primary-dark focus:bg-white focus:border-primary outline-none transition-all input-no-spinner">
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <input type="number" step="0.01" min="0"
                                    :name="'items['+idx+'][bonus]'"
                                    x-model.number="item.bonus"
                                    @input="recalc(item)"
                                    class="w-24 px-3 py-1.5 bg-accent/5 border border-transparent rounded-lg text-xs font-bold text-right text-accent focus:bg-white focus:border-accent outline-none transition-all input-no-spinner">
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <input type="number" step="0.01" min="0"
                                    :name="'items['+idx+'][overtime]'"
                                    x-model.number="item.overtime"
                                    @input="recalc(item)"
                                    class="w-24 px-3 py-1.5 bg-blue-50 border border-transparent rounded-lg text-xs font-bold text-right text-blue-600 focus:bg-white focus:border-blue-300 outline-none transition-all input-no-spinner">
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <input type="number" step="0.01" min="0"
                                    :name="'items['+idx+'][deductions]'"
                                    x-model.number="item.deductions"
                                    @input="recalc(item)"
                                    class="w-24 px-3 py-1.5 bg-red-50 border border-transparent rounded-lg text-xs font-bold text-right text-red-500 focus:bg-white focus:border-red-300 outline-none transition-all input-no-spinner">
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-bold text-primary-dark" x-text="'{{ $currency }} ' + fmt(item.gross_salary)"></span>
                            </td>

                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-accent" x-text="'{{ $currency }} ' + fmt(item.net_salary)"></span>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center">
                                        <i class="bi bi-person-x text-xl text-gray-300"></i>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400">No active employees found</p>
                                    <p class="text-xs text-gray-400">Select a branch or add active employees in HR settings</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Totals row --}}
                    <template x-if="items.length > 0">
                        <tr class="bg-gray-50/80 border-t-2 border-gray-200">
                            <td colspan="2" class="px-5 py-3.5">
                                <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">
                                    Totals — <span x-text="items.length"></span> employees
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-primary-dark" x-text="'{{ $currency }} ' + fmt(totalBasic)"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-accent" x-text="'{{ $currency }} ' + fmt(totalBonus)"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-blue-600" x-text="'{{ $currency }} ' + fmt(totalOvertime)"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-red-500" x-text="'−{{ $currency }} ' + fmt(totalDeductions)"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-xs font-black text-primary-dark" x-text="'{{ $currency }} ' + fmt(totalGross)"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-sm font-black text-accent" x-text="'{{ $currency }} ' + fmt(totalNet)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    </form>

    {{-- ── Sticky Bottom Bar ── --}}
    <div x-show="items.length > 0"
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur-sm shadow-xl">
        <div class="max-w-screen-2xl mx-auto px-6 py-3 flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-5 flex-1 overflow-x-auto">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Employees</p>
                    <p class="text-sm font-black text-primary-dark" x-text="items.length"></p>
                </div>
                <div class="w-px h-8 bg-gray-200"></div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Basic</p>
                    <p class="text-sm font-black text-primary-dark" x-text="'{{ $currency }} ' + fmt(totalBasic)"></p>
                </div>
                <div class="w-px h-8 bg-gray-200"></div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bonus & OT</p>
                    <p class="text-sm font-black text-accent" x-text="'{{ $currency }} ' + fmt(totalBonus + totalOvertime)"></p>
                </div>
                <div class="w-px h-8 bg-gray-200"></div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Deductions</p>
                    <p class="text-sm font-black text-red-500" x-text="'−{{ $currency }} ' + fmt(totalDeductions)"></p>
                </div>
                <div class="w-px h-8 bg-gray-200"></div>
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Gross Total</p>
                    <p class="text-sm font-black text-primary-dark" x-text="'{{ $currency }} ' + fmt(totalGross)"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Net Payable</p>
                    <p class="text-[20px] font-black text-primary leading-none" x-text="'{{ $currency }} ' + fmt(totalNet)"></p>
                </div>
                <button @click="submitForm()" :disabled="items.length === 0"
                    class="inline-flex items-center gap-2 px-7 py-2.5 bg-primary text-white font-bold rounded-[0.6rem] hover:bg-primary/90 transition-all shadow-lg text-sm disabled:opacity-40">
                    <i class="bi bi-check2-circle text-base"></i>
                    Generate Payroll
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function payrollGenerator() {
    const allEmployees = @json($employees);

    return {
        month_year: '{{ now()->format('Y-m') }}',
        branch_id: '',
        items: [],

        get totalBasic()      { return this.items.reduce((s, i) => s + (i.basic_salary || 0), 0); },
        get totalBonus()      { return this.items.reduce((s, i) => s + (i.bonus || 0), 0); },
        get totalOvertime()   { return this.items.reduce((s, i) => s + (i.overtime || 0), 0); },
        get totalDeductions() { return this.items.reduce((s, i) => s + (i.deductions || 0), 0); },
        get totalGross()      { return this.items.reduce((s, i) => s + (i.gross_salary || 0), 0); },
        get totalNet()        { return this.items.reduce((s, i) => s + (i.net_salary || 0), 0); },

        init() {
            this.filterEmployees();
        },

        filterEmployees() {
            let list = allEmployees;
            if (this.branch_id) {
                const sel = document.querySelector('select[name="branch_id"] option:checked');
                const branchName = sel ? sel.dataset.name : '';
                if (branchName) {
                    list = list.filter(e => e.branch === branchName);
                }
            }
            this.items = list.map(e => ({
                employee_id:  e.id,
                name:         e.full_name,
                designation:  e.designation,
                basic_salary: parseFloat(e.salary) || 0,
                bonus:        0,
                overtime:     0,
                deductions:   0,
                gross_salary: parseFloat(e.salary) || 0,
                net_salary:   parseFloat(e.salary) || 0,
            }));
        },

        recalc(item) {
            item.gross_salary = (item.basic_salary || 0) + (item.bonus || 0) + (item.overtime || 0);
            item.net_salary   = item.gross_salary - (item.deductions || 0);
        },

        fmt(n) {
            return parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        submitForm() {
            if (!this.month_year) {
                Swal.fire({ icon: 'warning', title: 'Select Pay Period', text: 'Please select a month/year before generating.', confirmButtonColor: '#004161' });
                return;
            }
            if (this.items.length === 0) {
                Swal.fire({ icon: 'warning', title: 'No Employees', text: 'There are no active employees to include in this payroll.', confirmButtonColor: '#004161' });
                return;
            }
            Swal.fire({
                title: 'Generate Payroll?',
                html: `<p>You are creating payroll for <strong>${this.items.length} employee${this.items.length !== 1 ? 's' : ''}</strong> for <strong>${this.month_year}</strong>.</p>
                       <p class="mt-2">Total Net Payable: <strong class="text-lg">{{ $currency }} ${this.fmt(this.totalNet)}</strong></p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#004161',
                confirmButtonText: 'Yes, Generate!',
                cancelButtonText: 'Cancel',
            }).then(r => {
                if (r.isConfirmed) document.getElementById('payrollForm').submit();
            });
        }
    };
}
</script>
@endpush
