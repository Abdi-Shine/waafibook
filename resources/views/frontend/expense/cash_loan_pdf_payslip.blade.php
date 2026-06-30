<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Deduction Voucher - {{ $loan->employee->full_name ?? 'Employee' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0B5567',
                        'primary-dark': '#094555',
                        accent: '#A4D65E',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .shadow-lg { shadow: none !important; border: 1px solid #e5e7eb !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 no-print">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Loan Deduction Preview</h1>
                        <p class="text-sm text-gray-600 mt-1">Review the payslip format below</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="window.print()" class="bg-primary text-white px-6 py-2.5 rounded-lg font-bold hover:bg-primary-dark transition-all flex items-center gap-2">
                            <i class="bi bi-printer"></i>
                            Print / Download PDF
                        </button>
                        <a href="{{ route('loan.view') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition-all">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto p-4 md:p-8">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
                <!-- Company Header -->
                <div class="bg-primary p-10 text-white relative">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            @if(!empty($company->logo))
                                <div class="w-16 h-16 bg-white rounded-xl flex items-center justify-center overflow-hidden p-1 shadow-md">
                                    <img src="{{ asset($company->logo) }}" class="w-full h-full object-contain" alt="{{ $company->name }}">
                                </div>
                            @endif
                            <div>
                                <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter italic">{{ $company->name ?? 'HORNTECH LTD' }}</h2>
                                <p class="text-[11px] text-white/60 mt-1 uppercase tracking-widest font-bold">{{ $company->city ?? 'Riyadh' }} • {{ $company->country ?? 'Saudi Arabia' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-primary uppercase font-black tracking-widest">Document Type</p>
                            <p class="text-xl font-bold">LOAN PAYSLIP</p>
                            <p class="text-xs text-primary mt-1">{{ date('F Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Employee Details -->
                <div class="p-8 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="h-4 w-1 bg-accent rounded-full"></div>
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Employee Profile</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Full Name</p>
                            <p class="text-sm font-bold text-gray-900">{{ $loan->employee->full_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Employee ID</p>
                            <p class="text-sm font-bold text-gray-900">{{ $loan->employee->employee_id ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Designation</p>
                            <p class="text-sm font-bold text-gray-900">{{ $loan->employee->designation ?? 'Staff Member' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Branch</p>
                            <p class="text-sm font-bold text-gray-900">{{ $loan->employee->branch ?? 'Main Branch' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Department</p>
                            <p class="text-sm font-bold text-gray-900">{{ $loan->employee->department ?? 'General' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Issue Date</p>
                            <p class="text-sm font-bold text-gray-900">{{ now()->format('d M, Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 divide-x divide-gray-100">
                    <!-- Earnings Section -->
                    <div class="p-8">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="h-4 w-1 bg-accent/10 rounded-full"></div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Earnings Summary</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-600">Basic Monthly Salary</p>
                                <p class="text-sm font-bold text-gray-900">{{ $currency }} {{ number_format($loan->employee->salary ?? 0, 2) }}</p>
                            </div>
                            <!-- Static for design consistency as placeholders -->
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-600">Allowances (Estimate)</p>
                                <p class="text-sm font-bold text-gray-900">{{ $currency }} 0.00</p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-black text-gray-900 uppercase">Gross Salary</p>
                                <p class="text-lg font-black text-accent">{{ $currency }} {{ number_format($loan->employee->salary ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Section -->
                    <div class="p-8 bg-gray-50/50">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="h-4 w-1 bg-primary/10 rounded-full"></div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Loan Deductions</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-white border border-primary/20 rounded-xl p-5 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-black text-primary uppercase tracking-wider">Current Deduction</p>
                                    <span class="text-xl font-black text-gray-800">{{ $currency }} {{ number_format($loan->monthly_deduction, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] pt-3 border-t border-primary/20 mt-3">
                                    <p class="text-primary font-bold uppercase tracking-tight">Loan Balance After This Pay</p>
                                    <p class="font-black text-primary">{{ $currency }} {{ number_format(max(0, $loan->balance - $loan->monthly_deduction), 2) }}</p>
                                </div>
                                <p class="text-[9px] text-primary mt-2 font-bold uppercase tracking-widest italic">REF: {{ $loan->loan_id }} • {{ $loan->type }} LOAN</p>
                            </div>
                            
                            <div class="flex items-center justify-between pt-2">
                                <p class="text-gray-500 font-medium">This is a system-generated loan deduction voucher. The amount of <span class="font-bold text-gray-800">{{ $currency }} {{ number_format($loan->monthly_deduction, 2) }}</span> will be deducted from the employee's monthly salary until the balance is cleared.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Final Calculation -->
                <div class="p-10 bg-gradient-to-br from-primary-dark to-primary text-white text-center">
                    <p class="text-[10px] text-primary uppercase font-black tracking-[0.3em] mb-3">Estimated Net Monthly Payout</p>
                    <div class="flex items-center justify-center gap-4">
                        <div class="h-px w-12 bg-white/20"></div>
                        <h4 class="text-5xl font-black tracking-tighter italic">{{ $currency }} {{ number_format(($loan->employee->salary ?? 0) - $loan->monthly_deduction, 2) }}</h4>
                        <div class="h-px w-12 bg-white/20"></div>
                    </div>
                    <p class="text-[11px] text-primary mt-4 lowercase italic font-medium">calculated as: gross salary ({{ number_format($loan->employee->salary ?? 0, 2) }}) - current loan deduction ({{ number_format($loan->monthly_deduction, 2) }})</p>
                </div>

                <!-- Detailed Summary -->
                <div class="p-8 bg-white grid grid-cols-2 md:grid-cols-4 gap-6 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Total Loan Amount</p>
                        <p class="text-lg font-black text-gray-800">{{ $currency }} {{ number_format($loan->amount, 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Total Paid</p>
                        <p class="text-sm font-bold text-accent">{{ $currency }} {{ number_format($loan->recovered, 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Installments</p>
                        <p class="text-sm font-bold text-gray-900">{{ $loan->duration }} Months</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Remaining Balance</p>
                        <p class="text-lg font-black text-primary">{{ $currency }} {{ number_format($loan->balance, 2) }}</p>
                    </div>
                </div>

                <!-- Signature Footer -->
                <div class="px-8 py-12 flex justify-between items-end border-t border-gray-100 bg-gray-50/20">
                    <div class="text-center">
                        <div class="border-b border-gray-400 w-48 mb-2"></div>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Employee Signature</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] text-gray-400 font-medium mb-10">Electronically generated by {{ $company->name ?? 'Waafibook' }}</p>
                        <div class="border-b border-gray-400 w-48 mb-2"></div>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">HR/Finance Authority</p>
                    </div>
                </div>
            </div>
            
            <p class="text-[10px] text-center text-gray-400 mt-8 italic">This document serves as a notification of loan deduction and salary estimation. Official payslip might include GOSI and other variables.</p>
        </main>
    </div>
</body>
</html>

