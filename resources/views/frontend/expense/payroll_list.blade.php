@extends('admin.admin_master')
@section('page_title', 'Payroll List')



@section('admin')

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="payrollActions()">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Payroll Management</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('payroll.generate') }}"
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all shadow-sm text-sm group">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    Generate Payroll
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Payroll -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Payroll (Month)</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($payrolls->where('status', 'Paid')->where('month_year', now()->format('Y-m'))->sum('total_net'), 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Paid this month</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>

            <!-- Gross Salary -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Gross</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($payrolls->sum('total_gross'), 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Before deductions</p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                    <i class="bi bi-calculator text-lg"></i>
                </div>
            </div>

            <!-- Total Deductions -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Deductions</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($payrolls->sum('total_deductions'), 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">Total reductions</p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary">
                    <i class="bi bi-dash-circle text-lg"></i>
                </div>
            </div>

            <!-- Total Net -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Net Payable</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $currency }} {{ number_format($payrolls->sum('total_net'), 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">To be disbursed</p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent">
                    <i class="bi bi-wallet2 text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            
            <!-- Filters -->
            <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Month Filter -->
                <div class="relative group min-w-[200px]">
                    <i class="bi bi-calendar-event absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="month" x-model="monthFilter"
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                <!-- Branch Filter -->
                <div class="relative min-w-[180px]">
                    <select x-model="branchFilter"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->branch_name }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[150px]">
                    <select x-model="statusFilter"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Approved">Approved</option>
                        <option value="Paid">Paid</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <!-- Table Title -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Payroll History</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Period</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Branch</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Staff</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Gross</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Deductions</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Net</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($payrolls as $payroll)
                            <tr class="hover:bg-gray-50/60 transition-colors bg-white group"
                                x-show="(branchFilter === '' || '{{ $payroll->branch->branch_name ?? '' }}' === branchFilter) && (statusFilter === '' || '{{ $payroll->status }}' === statusFilter)">
                                <td class="px-5 py-4 text-xs font-bold text-primary-dark text-center leading-tight">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs font-bold text-primary-dark leading-tight">{{ $payroll->month_year }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs font-bold text-primary-dark leading-tight">{{ $payroll->branch->branch_name ?? 'Global' }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-xs font-bold text-primary-dark leading-tight">{{ $payroll->total_employees }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-xs font-bold text-primary-dark leading-tight">{{ $currency }} {{ number_format($payroll->total_gross, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-xs font-bold text-primary leading-tight">- {{ $currency }} {{ number_format($payroll->total_deductions, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-xs font-bold text-accent leading-tight">{{ $currency }} {{ number_format($payroll->total_net, 2) }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @php
                                        $statusClasses = [
                                            'Paid' => 'bg-accent/10 text-accent border-accent/20',
                                            'Approved' => 'bg-primary/10 text-primary border-primary/20',
                                            'Draft' => 'bg-primary/10 text-primary border-primary/20',
                                            'Rejected' => 'bg-primary/10 text-primary border-primary/20'
                                        ];
                                        $class = $statusClasses[$payroll->status] ?? $statusClasses['Draft'];
                                    @endphp
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-[0.4rem] border {{ $class }}">
                                        {{ $payroll->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('payroll.show', $payroll->id) }}" class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($payroll->status == 'Draft')
                                            <form action="{{ route('payroll.approve', $payroll->id) }}" method="POST" class="inline">
                                                @csrf @method('PUT')
                                                <button type="submit" class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-accent hover:border-accent/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm" title="Approve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($payroll->status == 'Approved')
                                            <form action="{{ route('payroll.pay', $payroll->id) }}" method="POST" class="inline">
                                                @csrf @method('PUT')
                                                <button type="submit" class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm" title="Mark as Paid">
                                                    <i class="bi bi-wallet2"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($payroll->status == 'Paid')
                                            <form action="{{ route('payroll.repost', $payroll->id) }}" method="POST" class="inline" onsubmit="return confirm('Re-post cash journal entry for this payroll?')">
                                                @csrf
                                                <button type="submit" class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-accent hover:border-accent/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm" title="Re-post Cash Journal">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <button @click="confirmDelete({{ $payroll->id }})" class="w-7 h-7 rounded-md bg-gray-50 border border-gray-200 text-gray-400 hover:text-primary hover:border-primary/20 hover:bg-white transition-all flex items-center justify-center text-xs shadow-sm" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                                            <i class="bi bi-cash-stack text-xl"></i>
                                        </div>
                                        <p class="text-[13px] font-bold text-gray-400 uppercase tracking-widest">No payroll records found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    function payrollActions() {
        return {
            branchFilter: '',
            statusFilter: '',
            monthFilter: '',
            confirmDelete(id) {
                deleteRecordWithPassword('/payroll/delete/' + id, 'this payroll record', {
                    title: 'Delete Payroll?',
                    text: 'This will remove the selected payroll record. Paid payrolls cannot be deleted.'
                });
            }
        }
    }
</script>
@endpush


