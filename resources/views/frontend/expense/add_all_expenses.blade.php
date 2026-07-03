@extends('admin.admin_master')
@section('page_title', 'Expenses')

@section('admin')
@php
    $totalThisMonth = $expenses->where('expense_date', '>=', now()->startOfMonth()->toDateString())->sum('amount');
    $totalAll       = $expenses->sum('amount');
    $pendingCount   = $expenses->where('status', 'Pending')->count();
    $approvedSum    = $expenses->where('status', 'Approved')->sum('amount');
    $daysInMonth    = now()->day ?: 1;
    $avgDaily       = $totalThisMonth / $daysInMonth;
    $totalCount     = $expenses->count();
@endphp

<div class="min-h-screen bg-gray-50/60" x-data="expenseManagement()">

    {{-- ── Hero Header ── --}}
    <div class="bg-primary relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 80% 50%, #99CC33 0%, transparent 60%)"></div>
        <div class="relative z-10 px-6 md:px-10 py-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-white text-2xl">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">Expenses Management</h1>
                    <p class="text-white/50 text-xs mt-0.5">Track and manage all business expenses</p>
                </div>
            </div>
            <button @click="openModal = 'add-expense'"
                class="flex items-center gap-2 px-5 py-2.5 bg-accent text-primary font-bold rounded-xl hover:bg-accent/90 transition-all text-[13px] shadow-lg shadow-accent/20 shrink-0">
                <i class="bi bi-plus-lg text-sm"></i> Add New Expense
            </button>
        </div>

        {{-- Stats strip --}}
        <div class="relative z-10 px-6 md:px-10 pb-0">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pb-0">
                {{-- Card 1 --}}
                <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-t-2xl px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">This Month</span>
                        <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-accent text-xs">
                            <i class="bi bi-calendar3"></i>
                        </div>
                    </div>
                    <p class="text-[20px] font-black text-white leading-none">{{ $curr }} {{ number_format($totalThisMonth, 2) }}</p>
                    <p class="text-[10px] text-white/40 mt-1.5">Total spent this month</p>
                </div>
                {{-- Card 2 --}}
                <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-t-2xl px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Approved</span>
                        <div class="w-7 h-7 rounded-lg bg-accent/20 flex items-center justify-center text-accent text-xs">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                    </div>
                    <p class="text-[20px] font-black text-white leading-none">{{ $curr }} {{ number_format($approvedSum, 2) }}</p>
                    <p class="text-[10px] text-white/40 mt-1.5">Verified payments</p>
                </div>
                {{-- Card 3 --}}
                <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-t-2xl px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Pending</span>
                        <div class="w-7 h-7 rounded-lg bg-yellow-400/20 flex items-center justify-center text-yellow-300 text-xs">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <p class="text-[20px] font-black text-white leading-none">{{ $pendingCount }} <span class="text-sm font-semibold text-white/50">items</span></p>
                    <p class="text-[10px] text-white/40 mt-1.5">Awaiting review</p>
                </div>
                {{-- Card 4 --}}
                <div class="bg-white/10 backdrop-blur-sm border border-white/10 rounded-t-2xl px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Daily Avg</span>
                        <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-white/60 text-xs">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                    </div>
                    <p class="text-[20px] font-black text-white leading-none">{{ $curr }} {{ number_format($avgDaily, 2) }}</p>
                    <p class="text-[10px] text-white/40 mt-1.5">Burn rate / day</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Content ── --}}
    <div class="px-6 md:px-10 py-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-[13px] font-semibold">
                <i class="bi bi-check-circle-fill text-green-500"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-[13px] font-semibold">
                <i class="bi bi-exclamation-triangle-fill text-red-500"></i> {{ session('error') }}
            </div>
        @endif

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">

            {{-- Toolbar --}}
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col md:flex-row items-start md:items-center gap-3">
                {{-- Search --}}
                <div class="relative flex-1 min-w-0">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search expenses..."
                        class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                </div>
                {{-- Category Filter --}}
                <div class="relative min-w-[160px]">
                    <select x-model="filterCategory"
                        class="w-full pl-3.5 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[13px] font-medium text-gray-600 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                        <option value="">All Categories</option>
                        @foreach($expenseAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                </div>
                {{-- Status Filter --}}
                <div class="relative min-w-[140px]">
                    <select x-model="filterStatus"
                        class="w-full pl-3.5 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-[13px] font-medium text-gray-600 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                        <option value="">All Status</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                </div>
                {{-- Count badge --}}
                <div class="shrink-0 px-3 py-2 bg-primary/5 rounded-xl border border-primary/10 text-[12px] font-black text-primary whitespace-nowrap">
                    {{ $totalCount }} Records
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider w-12 text-center">#</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider">Expense</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider">Paid From</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider text-right">Amount</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-black text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($expenses as $expense)
                            @php
                                $expAccId = $expense->expense_account_id ?? null;
                            @endphp
                            <tr x-show="matchExpense('{{ addslashes($expense->expense_name) }}', '{{ addslashes($expense->description) }}', '{{ $expense->expense_account_id }}', '{{ $expense->status }}')"
                                class="hover:bg-gray-50/70 transition-colors group">

                                {{-- # --}}
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[11px] font-black text-gray-400">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                </td>

                                {{-- Expense name + description --}}
                                <td class="px-5 py-4">
                                    <p class="text-[13px] font-bold text-primary-dark leading-tight">{{ $expense->expense_name }}</p>
                                    @if($expense->description)
                                        <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[180px]">{{ $expense->description }}</p>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-primary/5 flex flex-col items-center justify-center leading-none shrink-0">
                                            <span class="text-[9px] font-black text-primary/50 uppercase">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('M') : '' }}</span>
                                            <span class="text-[13px] font-black text-primary leading-none">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d') : 'N/A' }}</span>
                                        </div>
                                        <span class="text-[12px] font-semibold text-gray-500">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y') : '' }}</span>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary/5 text-primary rounded-lg text-[11px] font-bold">
                                        <i class="bi bi-tag-fill text-[9px]"></i>
                                        {{ $expense->account->name ?? 'Uncategorised' }}
                                    </span>
                                </td>

                                {{-- Paid From --}}
                                <td class="px-5 py-4">
                                    <span class="text-[12px] font-semibold text-gray-600">
                                        {{ $expense->bankAccount->name ?? ($expense->payment_method ?? 'Cash') }}
                                    </span>
                                </td>

                                {{-- Amount --}}
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <span class="text-[14px] font-black text-primary-dark">{{ $curr }} {{ number_format($expense->amount, 2) }}</span>
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4 text-center">
                                    @if(strtolower($expense->status) === 'approved')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[11px] font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Approved
                                        </span>
                                    @elseif(strtolower($expense->status) === 'pending')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[11px] font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-600 border border-gray-200 rounded-full text-[11px] font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> {{ $expense->status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('expenses.receipt', $expense->id) }}" target="_blank"
                                            class="w-8 h-8 rounded-lg bg-primary/5 hover:bg-primary hover:text-white text-primary flex items-center justify-center transition-all" title="View Receipt">
                                            <i class="bi bi-eye text-sm"></i>
                                        </a>
                                        <button @click="editExpense({{ json_encode($expense) }})"
                                            class="w-8 h-8 rounded-lg bg-accent/10 hover:bg-accent text-primary flex items-center justify-center transition-all" title="Edit">
                                            <i class="bi bi-pencil text-sm"></i>
                                        </button>
                                        <button @click="confirmDelete('{{ route('expenses.delete', $expense->id) }}')"
                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition-all" title="Delete">
                                            <i class="bi bi-trash3 text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-20 text-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="bi bi-receipt text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-[13px] font-bold text-gray-400 uppercase tracking-widest">No expenses recorded yet</p>
                                    <p class="text-[12px] text-gray-300 mt-1">Click "Add New Expense" to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                    Showing {{ $totalCount }} {{ Str::plural('record', $totalCount) }}
                </p>
                <div class="flex items-center gap-2">
                    <button class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </button>
                    <button class="w-8 h-8 rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">1</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div x-show="openModal === 'add-expense'" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.5rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col" @click.away="openModal = null">

            {{-- Header --}}
            <div class="px-8 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="absolute right-0 top-0 w-40 h-40 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/3"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Add New Expense</h2>
                            <p class="text-[11px] text-white/50 mt-0.5">Record a business expense</p>
                        </div>
                    </div>
                    <button @click="openModal = null" class="w-8 h-8 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            @php
                $cashOnHandAccount = $bankAccounts->first(fn($a) => stripos($a->name, 'Cash on Hand') !== false)
                    ?? $bankAccounts->first(fn($a) => ($a->type ?? '') === 'cash')
                    ?? $bankAccounts->first();
            @endphp
            <form action="{{ route('expenses.store') }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <input type="hidden" name="payment_method" value="Cash">
                <div class="px-8 py-6 overflow-y-auto flex-grow space-y-5">

                    {{-- Row 1: Name + Amount + Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5 md:col-span-1">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-red-400">*</span></label>
                            <input type="text" name="expense_name" required placeholder="e.g., Office Rent"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[12px] font-bold">{{ $curr }}</span>
                                <input type="number" step="0.01" min="0" name="amount" required placeholder="0.00"
                                    class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-red-400">*</span></label>
                            <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    {{-- Row 2: Category + Paid From --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select name="expense_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                    <option value="">-- Select Category --</option>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Paid From <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select name="bank_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" {{ ($cashOnHandAccount && $cashOnHandAccount->id == $account->id) ? 'selected' : '' }}>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description <span class="text-red-400">*</span></label>
                        <textarea name="description" required rows="2" placeholder="Enter expense details or notes..."
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between shrink-0">
                    <button type="button" @click="openModal = null"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px]">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div x-show="openModal === 'edit-expense'" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.5rem] w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col" @click.away="openModal = null">

            {{-- Header --}}
            <div class="px-8 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="absolute right-0 top-0 w-40 h-40 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/3"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Edit Expense Record</h2>
                            <p class="text-[11px] text-white/50 mt-0.5">Update existing entry</p>
                        </div>
                    </div>
                    <button @click="openModal = null" class="w-8 h-8 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            <form :action="editUrl" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                @method('PUT')
                <input type="hidden" name="payment_method" value="Cash">
                <div class="px-8 py-6 overflow-y-auto flex-grow space-y-5">

                    {{-- Row 1: Name + Amount + Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5 md:col-span-1">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Expense Name <span class="text-red-400">*</span></label>
                            <input type="text" name="expense_name" x-model="editData.expense_name" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[12px] font-bold">{{ $curr }}</span>
                                <input type="number" step="0.01" min="0" name="amount" x-model="editData.amount" required
                                    class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Date <span class="text-red-400">*</span></label>
                            <input type="date" name="expense_date" x-model="editData.expense_date" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    {{-- Row 2: Category + Paid From --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Category <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select name="expense_account_id" x-model="editData.expense_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Paid From <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <select name="bank_account_id" x-model="editData.bank_account_id" required
                                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none appearance-none transition-all">
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Description <span class="text-red-400">*</span></label>
                        <textarea name="description" x-model="editData.description" required rows="2"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between shrink-0">
                    <button type="button" @click="openModal = null"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all text-[13px]">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> Update Expense
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
        filterCategory: '',
        filterStatus: '',
        editData: {},
        editUrl: '',

        matchExpense(name, desc, accountId, status) {
            const q = this.searchQuery.toLowerCase();
            if (q && !name.toLowerCase().includes(q) && !desc.toLowerCase().includes(q)) return false;
            if (this.filterCategory && accountId != this.filterCategory) return false;
            if (this.filterStatus && status.toLowerCase() !== this.filterStatus.toLowerCase()) return false;
            return true;
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
