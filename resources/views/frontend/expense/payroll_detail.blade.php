@extends('admin.admin_master')
@section('page_title', 'Payroll — ' . $payroll->month_year)

@section('admin')

@php
    $statusBg = match($payroll->status) {
        'Paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Approved' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Rejected' => 'bg-red-50 text-red-600 border-red-200',
        default    => 'bg-amber-50 text-amber-700 border-amber-200',
    };
    $statusDot = match($payroll->status) {
        'Paid'     => 'bg-emerald-500',
        'Approved' => 'bg-blue-500',
        'Rejected' => 'bg-red-500',
        default    => 'bg-amber-400',
    };
@endphp

<style>
@media print {
    /* hide everything except the print-section */
    body > *:not(#print-section),
    .no-print { display: none !important; }
    #print-section { display: block !important; }

    .payslip-page {
        page-break-after: always;
        padding: 40px;
    }
    .payslip-page:last-child { page-break-after: auto; }
}
</style>

{{-- ─── Screen view ─── --}}
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen no-print">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-[20px] font-bold text-primary-dark">Payroll — {{ $payroll->month_year }}</h1>
                <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full border {{ $statusBg }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>{{ $payroll->status }}
                </span>
            </div>
            <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">
                {{ $payroll->branch->name ?? 'Global' }}
                @if($payroll->approvedBy)
                    &bull; Approved by {{ $payroll->approvedBy->name }}
                @endif
                @if($payroll->paid_date)
                    &bull; Paid {{ \Carbon\Carbon::parse($payroll->paid_date)->format('d M Y') }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('payroll.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-600 font-bold rounded-[0.5rem] hover:bg-gray-50 transition-all text-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>

            @if($payroll->status === 'Draft')
            <form action="{{ route('payroll.approve', $payroll->id) }}" method="POST" class="inline">
                @csrf @method('PUT')
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-bold rounded-[0.5rem] hover:bg-blue-700 transition-all text-sm shadow-sm">
                    <i class="bi bi-shield-check"></i> Approve
                </button>
            </form>
            @endif

            @if($payroll->status === 'Approved')
            <form action="{{ route('payroll.pay', $payroll->id) }}" method="POST" class="inline">
                @csrf @method('PUT')
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-[0.5rem] hover:bg-emerald-700 transition-all text-sm shadow-sm">
                    <i class="bi bi-wallet2"></i> Mark as Paid
                </button>
            </form>
            @endif

            <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-[0.5rem] hover:bg-primary/90 transition-all text-sm shadow-sm">
                <i class="bi bi-printer"></i> Print Payslips
            </button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Staff</p>
            <h3 class="text-[24px] font-black text-primary leading-none">{{ $payroll->total_employees }}</h3>
            <p class="text-[11px] text-gray-400 mt-1.5">employees</p>
        </div>
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Gross Salary</p>
            <h3 class="text-[22px] font-black text-primary-dark leading-none">{{ $currency }}&nbsp;{{ number_format($payroll->total_gross, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1.5">before deductions</p>
        </div>
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Deductions</p>
            <h3 class="text-[22px] font-black text-red-500 leading-none">{{ $currency }}&nbsp;{{ number_format($payroll->total_deductions, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1.5">total reductions</p>
        </div>
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 border-l-4 border-l-accent">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-1">Net Payable</p>
            <h3 class="text-[24px] font-black text-accent leading-none">{{ $currency }}&nbsp;{{ number_format($payroll->total_net, 2) }}</h3>
            <p class="text-[11px] text-gray-400 mt-1.5">disbursed to employees</p>
        </div>
    </div>

    {{-- Workflow Steps --}}
    <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm px-6 py-4 mb-5">
        <div class="flex items-center gap-0">
            @php $steps = ['Draft','Approved','Paid']; @endphp
            @foreach($steps as $idx => $step)
                @php
                    $statuses = ['Draft','Approved','Paid'];
                    $currentIdx = array_search($payroll->status, $statuses);
                    $isDone    = $idx < $currentIdx;
                    $isCurrent = $payroll->status === $step || ($payroll->status === 'Rejected' && $step === 'Draft');
                    $stepColors = $isDone ? 'bg-emerald-500 border-emerald-500 text-white' : ($isCurrent ? 'bg-primary border-primary text-white' : 'bg-white border-gray-200 text-gray-400');
                    $labelColor = $isDone || $isCurrent ? 'text-primary-dark font-bold' : 'text-gray-400 font-semibold';
                @endphp
                <div class="flex items-center flex-1 @if(!$loop->last) after:h-0.5 @endif">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full border-2 {{ $stepColors }} flex items-center justify-center text-xs font-black shrink-0">
                            @if($isDone)
                                <i class="bi bi-check-lg"></i>
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                        <div>
                            <p class="text-[11px] {{ $labelColor }}">{{ $step }}</p>
                            @if($step === 'Draft')
                                <p class="text-[10px] text-gray-400">{{ $payroll->created_at->format('d M Y') }}</p>
                            @elseif($step === 'Approved' && $payroll->approvedBy)
                                <p class="text-[10px] text-gray-400">{{ $payroll->approvedBy->name }}</p>
                            @elseif($step === 'Paid' && $payroll->paid_date)
                                <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($payroll->paid_date)->format('d M Y') }}</p>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                    <div class="flex-1 mx-3 h-0.5 {{ $isDone ? 'bg-emerald-400' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Detail Table --}}
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
            <i class="bi bi-table text-primary-dark text-sm"></i>
            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Salary Disbursement Report</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider w-10 text-center">#</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider">Employee</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Basic</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right text-accent">Bonus</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right text-blue-500">Overtime</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right text-red-400">Deductions</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Gross</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-right">Net Payable</th>
                        <th class="px-5 py-3.5 text-[11px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payroll->items as $i => $item)
                    <tr class="hover:bg-gray-50/30 transition-colors">
                        <td class="px-5 py-4 text-center text-[11px] font-bold text-gray-400">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-[10px] font-black text-primary shrink-0">
                                    {{ strtoupper(substr($item->employee->full_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-primary-dark">{{ $item->employee->full_name }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $item->employee->designation ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right text-xs font-bold text-primary-dark">{{ number_format($item->basic_salary, 2) }}</td>
                        <td class="px-5 py-4 text-right text-xs font-bold text-accent">{{ number_format($item->bonus, 2) }}</td>
                        <td class="px-5 py-4 text-right text-xs font-bold text-blue-500">{{ number_format($item->overtime, 2) }}</td>
                        <td class="px-5 py-4 text-right text-xs font-bold text-red-500">−&nbsp;{{ number_format($item->deductions, 2) }}</td>
                        <td class="px-5 py-4 text-right text-xs font-bold text-primary-dark">{{ $currency }}&nbsp;{{ number_format($item->gross_salary, 2) }}</td>
                        <td class="px-5 py-4 text-right text-xs font-black text-accent">{{ $currency }}&nbsp;{{ number_format($item->net_salary, 2) }}</td>
                        <td class="px-5 py-4 text-center">
                            @if($item->status === 'Paid')
                                <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wide px-2.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Paid
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wide px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Unpaid
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                {{-- Totals footer --}}
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                        <td colspan="2" class="px-5 py-3.5">
                            <span class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Grand Total</span>
                        </td>
                        <td class="px-5 py-3.5 text-right text-xs font-black text-primary-dark">{{ number_format($payroll->items->sum('basic_salary'), 2) }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-black text-accent">{{ number_format($payroll->items->sum('bonus'), 2) }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-black text-blue-500">{{ number_format($payroll->items->sum('overtime'), 2) }}</td>
                        <td class="px-5 py-3.5 text-right text-xs font-black text-red-500">−&nbsp;{{ number_format($payroll->items->sum('deductions'), 2) }}</td>
                        <td class="px-5 py-3.5 text-right text-sm font-black text-primary-dark">{{ $currency }}&nbsp;{{ number_format($payroll->total_gross, 2) }}</td>
                        <td class="px-5 py-3.5 text-right text-sm font-black text-accent">{{ $currency }}&nbsp;{{ number_format($payroll->total_net, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

{{-- ─── Print Section (Payslips) ─── --}}
<div id="print-section" style="display:none">
    @foreach($payroll->items as $item)
    <div class="payslip-page" style="font-family: Arial, sans-serif; max-width: 720px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">

        {{-- Payslip Header --}}
        <div style="background: #004161; color: #fff; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">
                    {{ $company->name ?? 'WaafiBook' }}
                </div>
                <div style="font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 2px; text-transform: uppercase; letter-spacing: 1px;">
                    Employee Payslip
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 13px; font-weight: 700; color: #99CC33;">{{ $payroll->month_year }}</div>
                <div style="font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 2px;">Pay Period</div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div style="background: #f8fafc; padding: 20px 32px; display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0;">
            <div>
                <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Employee</div>
                <div style="font-size: 16px; font-weight: 900; color: #0f2d45;">{{ $item->employee->full_name }}</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $item->employee->designation ?? '—' }}</div>
                @if($item->employee->department)
                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ $item->employee->department }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Payroll Status</div>
                <div style="font-size: 14px; font-weight: 900; color: {{ $payroll->status === 'Paid' ? '#059669' : '#004161' }}; text-transform: uppercase;">
                    {{ $payroll->status }}
                </div>
                @if($payroll->paid_date)
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Paid: {{ \Carbon\Carbon::parse($payroll->paid_date)->format('d M Y') }}</div>
                @endif
                @if($payroll->branch)
                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ $payroll->branch->name }}</div>
                @endif
            </div>
        </div>

        {{-- Earnings & Deductions --}}
        <div style="padding: 24px 32px; display: flex; gap: 24px;">

            {{-- Earnings --}}
            <div style="flex: 1; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px;">
                <div style="font-size: 11px; font-weight: 900; color: #166534; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #bbf7d0;">
                    Earnings
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="font-size: 12px; color: #374151; padding: 5px 0;">Basic Salary</td>
                        <td style="font-size: 12px; font-weight: 700; color: #111827; text-align: right; padding: 5px 0;">{{ $currency }}&nbsp;{{ number_format($item->basic_salary, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #374151; padding: 5px 0;">Bonus</td>
                        <td style="font-size: 12px; font-weight: 700; color: #059669; text-align: right; padding: 5px 0;">{{ $currency }}&nbsp;{{ number_format($item->bonus, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 12px; color: #374151; padding: 5px 0;">Overtime</td>
                        <td style="font-size: 12px; font-weight: 700; color: #2563eb; text-align: right; padding: 5px 0;">{{ $currency }}&nbsp;{{ number_format($item->overtime, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #bbf7d0; margin-top: 8px;">
                        <td style="font-size: 12px; font-weight: 900; color: #166534; padding: 8px 0 0 0;">Gross Total</td>
                        <td style="font-size: 14px; font-weight: 900; color: #166534; text-align: right; padding: 8px 0 0 0;">{{ $currency }}&nbsp;{{ number_format($item->gross_salary, 2) }}</td>
                    </tr>
                </table>
            </div>

            {{-- Deductions --}}
            <div style="flex: 1; background: #fff7f7; border: 1px solid #fecaca; border-radius: 8px; padding: 16px;">
                <div style="font-size: 11px; font-weight: 900; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #fecaca;">
                    Deductions
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="font-size: 12px; color: #374151; padding: 5px 0;">Total Deductions</td>
                        <td style="font-size: 12px; font-weight: 700; color: #dc2626; text-align: right; padding: 5px 0;">− {{ $currency }}&nbsp;{{ number_format($item->deductions, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Net Salary Banner --}}
        <div style="margin: 0 32px 24px; background: #004161; border-radius: 8px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px;">
                Net Salary Payable
            </div>
            <div style="font-size: 28px; font-weight: 900; color: #99CC33; letter-spacing: -0.5px;">
                {{ $currency }}&nbsp;{{ number_format($item->net_salary, 2) }}
            </div>
        </div>

        {{-- Signature Lines --}}
        <div style="margin: 0 32px 32px; display: flex; justify-content: space-between; gap: 40px;">
            <div style="flex: 1; border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700;">Employee Signature</div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ $item->employee->full_name }}</div>
            </div>
            <div style="flex: 1; border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700;">Authorized Signatory</div>
                @if($payroll->approvedBy)
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ $payroll->approvedBy->name }}</div>
                @endif
            </div>
            <div style="flex: 1; border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700;">Date</div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ now()->format('d M Y') }}</div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 10px 32px; text-align: center;">
            <div style="font-size: 10px; color: #94a3b8;">
                This is a computer-generated payslip. Powered by WaafiBook ERP &bull; {{ $company->name ?? '' }}
            </div>
        </div>

    </div>
    @endforeach
</div>

@endsection
