@extends('admin.admin_master')
@section('page_title', 'Expenses')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="expenseManagement()">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Expenses Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="openModal = 'add-expense'" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all text-[13px] shadow-sm uppercase">
                <i class="bi bi-plus-lg"></i>
                Add New Expense
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $totalThisMonth = $expenses->where('expense_date', '>=', now()->startOfMonth())->sum('amount');
            $pendingCount = $expenses->where('status', 'Pending')->count();
            $approvedSum = $expenses->where('status', 'Approved')->sum('amount');
            $daysInMonth = now()->day;
            $avgDaily = $daysInMonth > 0 ? $totalThisMonth / $daysInMonth : 0;
        @endphp

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Expenses</p>
                <h3 class="text-[18px] font-black text-primary">{{ $curr }} {{ number_format($totalThisMonth, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-calendar-check-fill text-[10px]"></i> This Month
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-wallet2 text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Review</p>
                <h3 class="text-[18px] font-black text-primary">{{ $pendingCount }} Items</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-hourglass-split text-[10px]"></i> Awaiting Action
                </p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-exclamation-octagon-fill text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Verified Total</p>
                <h3 class="text-[18px] font-black text-primary">{{ $curr }} {{ number_format($approvedSum, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                    <i class="bi bi-patch-check-fill text-[10px]"></i> Approved Payments
                </p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-check-circle-fill text-lg"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Avg. Burn Rate</p>
                <h3 class="text-[18px] font-black text-primary">{{ $curr }} {{ number_format($avgDaily, 2) }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Daily Average</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-speedometer2 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <!-- Filter Bar -->
        <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
            <div class="relative group min-w-[250px] flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm group-focus-within:text-primary transition-colors"></i>
                <input type="text" x-model="searchQuery" placeholder="Search by name, reference or description..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
            </div>

            <div class="flex items-center gap-2 bg-gray-50/50 px-3 py-1.5 rounded-lg border border-gray-100 shrink-0">
                <input type="date" class="bg-transparent border-none text-[12px] font-bold text-primary-dark outline-none p-0 focus:ring-0 w-24">
                <span class="text-gray-300">-</span>
                <input type="date" class="bg-transparent border-none text-[12px] font-bold text-primary-dark outline-none p-0 focus:ring-0 w-24">
            </div>

            <div class="relative min-w-[160px]">
                <select class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Categories</option>
                    @foreach($expenseAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
            </div>

            <div class="relative min-w-[140px]">
                <select class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="Approved">Verified</option>
                    <option value="Pending">Pending</option>
                </select>
                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
            </div>
        </div>

        <!-- Table Title Bar -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary text-sm"></i>
            <h2 class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Expense Records</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Date</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Ref No.</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Category</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Description</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Pay Method</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Amount</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $expense)
                        <tr x-show="searchExpense('{{ addslashes($expense->description) }} {{ addslashes($expense->expense_name) }}')"
                            class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                {{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d Mar Y') : 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary">
                                #{{ $expense->reference_no ?? 'EXP-'.$expense->id }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                {{ $expense->account->name ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                                {{ $expense->expense_name }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark capitalize">
                                {{ $expense->payment_method ?? $expense->bankAccount->name ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right font-black">
                                {{ $curr }} {{ number_format($expense->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ $expense->status }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank" class="btn-action-view" title="View Receipt">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button @click="editExpense({{ json_encode($expense) }})" class="btn-action-edit" title="Edit Entry">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button @click="confirmDelete('{{ route('expenses.delete', $expense->id) }}')" class="btn-action-delete" title="Delete Entry">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center text-gray-400">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                    <i class="bi bi-inbox text-2xl text-accent"></i>
                                </div>
                                <p class="text-[13px] font-bold uppercase tracking-widest text-gray-400">No expenses found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing 1 to {{ count($expenses) }} of {{ count($expenses) }} entries
            </div>
            <div class="flex items-center gap-2">
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                    <i class="bi bi-chevron-left text-[10px]"></i>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                    <i class="bi bi-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div x-show="openModal === 'add-expense'" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md text-white">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="flex flex-col text-white">
                            <h2 class="text-xl font-bold tracking-tight">Add New Expense</h2>
                            <p class="text-xs text-white/70 font-medium mt-0.5">General Business Expense</p>
                        </div>
                    </div>
                    <button @click="openModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-primary">*</span></label>
                            <input type="text" name="expense_name" required placeholder="e.g., Office Rent"
                                class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount ({{ $curr }}) <span class="text-primary">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[12px] font-bold">{{ $curr }}</span>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-primary">*</span></label>
                            <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}"
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="expense_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Category --</option>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="branch_id" required x-model="selectedBranch"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Branch --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Paid From <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="bank_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Select Account --</option>
                                    <template x-for="account in filteredBankAccounts" :key="account.id">
                                        <option :value="account.id" x-text="account.name"></option>
                                    </template>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reference No.</label>
                            <input type="text" name="reference_no" placeholder="Invoice / Receipt #"
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Payment Method</label>
                            <div class="relative">
                                <select name="payment_method"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description <span class="text-primary">*</span></label>
                            <input type="text" name="description" required placeholder="Enter expense details..."
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Attach Receipt</label>
                        <div class="relative group cursor-pointer" onclick="document.getElementById('receipt_upload').click()">
                            <input type="file" name="receipt" id="receipt_upload" class="hidden">
                            <div class="w-full px-6 py-8 border-2 border-dashed border-gray-200 rounded-xl flex flex-col items-center justify-center gap-2 group-hover:border-primary/50 group-hover:bg-primary/5 transition-all">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 group-hover:text-primary transition-colors">
                                    <i class="bi bi-cloud-arrow-up text-xl"></i>
                                </div>
                                <div class="text-[12px] font-bold text-gray-500">Click to upload or drag & drop</div>
                                <div class="text-[10px] text-gray-400 uppercase font-black">PDF, JPG, PNG (Max 5MB)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="openModal = null" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center gap-2">
                        <i class="bi bi-check2-circle text-base"></i>
                        Identify & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div x-show="openModal === 'edit-expense'" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md text-white">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="flex flex-col text-white">
                            <h2 class="text-xl font-bold tracking-tight">Edit Expense Record</h2>
                            <p class="text-xs text-white/70 font-medium mt-0.5">Update existing entry</p>
                        </div>
                    </div>
                    <button @click="openModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form :action="editUrl" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-primary">*</span></label>
                            <input type="text" name="expense_name" x-model="editData.expense_name" required
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount ({{ $curr }}) <span class="text-primary">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[12px] font-bold">{{ $curr }}</span>
                                <input type="number" step="0.01" name="amount" x-model="editData.amount" required
                                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-primary">*</span></label>
                            <input type="date" name="expense_date" x-model="editData.expense_date" required
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="expense_account_id" x-model="editData.expense_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Branch <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="branch_id" x-model="editData.branch_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Paid From <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="bank_account_id" x-model="editData.bank_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <template x-for="account in filteredBankAccounts" :key="account.id">
                                        <option :value="account.id" x-text="account.name" :selected="account.id == editData.bank_account_id"></option>
                                    </template>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reference No.</label>
                            <input type="text" name="reference_no" x-model="editData.reference_no"
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Payment Method</label>
                            <div class="relative">
                                <select name="payment_method" x-model="editData.payment_method"
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Credit Card">Credit Card</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description <span class="text-primary">*</span></label>
                            <input type="text" name="description" x-model="editData.description" required
                                class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">New Receipt (Optional)</label>
                        <input type="file" name="receipt"
                            class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-400 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="openModal = null" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px] shadow-sm">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center gap-2">
                        <i class="bi bi-check2-circle text-base"></i>
                        Update & Verify
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div x-show="openModal === 'view-expense'" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.25rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="openModal = null">

            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md text-white">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="flex flex-col text-white">
                            <h2 class="text-xl font-bold tracking-tight">Expense Details</h2>
                        </div>
                    </div>
                    <button @click="openModal = null" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name</label>
                        <p class="text-[14px] font-bold text-primary-dark" x-text="editData.expense_name"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount</label>
                        <p class="text-[16px] font-black text-primary" x-text="'{{ $curr }} ' + parseFloat(editData.amount).toLocaleString()"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date</label>
                        <p class="text-[14px] font-bold text-primary-dark" x-text="editData.expense_date"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reference No.</label>
                        <p class="text-[14px] font-bold text-primary-dark" x-text="editData.reference_no || 'N/A'"></p>
                    </div>
                    <div class="space-y-1.5 col-span-2">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description</label>
                        <p class="text-[14px] font-medium text-primary-dark" x-text="editData.description"></p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <button @click="openModal = null" class="w-full px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-all text-[13px] shadow-sm flex items-center justify-center gap-2">
                    Close Details
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function expenseManagement() {
    return {
        openModal: null,
        searchQuery: '',
        selectedBranch: '',
        allBankAccounts: @json($bankAccounts),
        editData: {},
        editUrl: '',

        get filteredBankAccounts() {
            if (this.editData.branch_id) {
                return this.allBankAccounts.filter(acc => !acc.branch_id || acc.branch_id == this.editData.branch_id);
            }
            if (!this.selectedBranch) return this.allBankAccounts;
            return this.allBankAccounts.filter(acc => !acc.branch_id || acc.branch_id == this.selectedBranch);
        },

        searchExpense(content) {
            if (!this.searchQuery) return true;
            return content.toLowerCase().includes(this.searchQuery.toLowerCase());
        },

        editExpense(expense) {
            this.editData = {...expense};
            this.editUrl = `/expenses/update/${expense.id}`;
            this.openModal = 'edit-expense';
        },

        viewExpense(expense) {
            this.editData = {...expense};
            this.openModal = 'view-expense';
        },

        confirmDelete(url) {
            deleteRecordWithPassword(url, 'this expense', {
                title: 'Delete Expense?',
                text: 'Are you sure? This will also remove the associated journal entry. This action cannot be undone.'
            });
        },

        init() {}
    }
}
</script>
@endsection
