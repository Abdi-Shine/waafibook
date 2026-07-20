@extends('admin.admin_master')
@section('page_title', 'Direct Expenses')

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    showAddModal: false,
    showEditModal: false,
    saving: false,
    newExpense: { expense_name: '', purchase_id: '', amount: '', expense_date: '{{ date('Y-m-d') }}', description: '' },
    editExpense: {},

    openAddModal() {
        this.newExpense = { expense_name: '', purchase_id: '', amount: '', expense_date: '{{ date('Y-m-d') }}', description: '' };
        this.showAddModal = true;
    },
    openEditModal(expense) {
        this.editExpense = { ...expense };
        this.showEditModal = true;
    },
    deleteExpense(id, name) {
        Swal.fire({
            title: 'Delete Expense?',
            text: `Are you sure you want to delete '${name}'? This cannot be undone.`,
            icon: 'warning',
            input: 'password',
            inputPlaceholder: 'Enter your admin password to confirm',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#99CC33',
            confirmButtonText: 'Yes, delete it!',
            inputValidator: (value) => { if (!value) return 'Password is required.'; }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteExpenseForm');
                form.action = '{{ url('/purchase/expense') }}/' + id;
                form.querySelector('input[name=\'password\']').value = result.value;
                form.submit();
            }
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Direct Expenses</h1>
        <button @click="openAddModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> Add Expense
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-wallet2 text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Total Expenses</p>
            <p class="text-[16px] font-black text-primary">$ {{ number_format($expenses->sum('amount'), 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-clock-history text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Pending</p>
            <p class="text-[16px] font-black text-primary">0</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-shield-check text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Paid</p>
            <p class="text-[16px] font-black text-primary">$ {{ number_format($expenses->sum('amount'), 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('purchase.expense') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH NAME, BILL, SUPPLIER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="supplier_id" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[130px]">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Expense Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($expenses as $expense)
            @php $editPayload = $expense->toArray(); @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $expense->expense_name }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $expense->account->name ?? '—' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">$ {{ number_format($expense->amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') : '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-accent/10 text-accent">Paid</span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('purchase.expense.view', $expense->id) }}" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        <button type="button" @click="openEditModal(@js($editPayload))"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <button type="button" @click="deleteExpense({{ $expense->id }}, '{{ addslashes($expense->expense_name) }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-receipt text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No purchase expenses recorded yet.</p>
            </div>
        @endforelse
    </div>

    @if($expenses->hasPages())
        <div class="flex items-center justify-between px-5 py-4">
            <div class="text-[11px] text-gray-400">
                {{ $expenses->firstItem() ?? 0 }}–{{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }}
            </div>
            <div class="flex items-center gap-2">
                @if($expenses->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-left text-[10px]"></i></span>
                @else
                    <a href="{{ $expenses->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-left text-[10px]"></i></a>
                @endif
                <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs">{{ $expenses->currentPage() }}</span>
                @if($expenses->hasMorePages())
                    <a href="{{ $expenses->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500"><i class="bi bi-chevron-right text-[10px]"></i></a>
                @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300"><i class="bi bi-chevron-right text-[10px]"></i></span>
                @endif
            </div>
        </div>
    @endif

    {{-- Add Expense — mobile bottom sheet --}}
    <div x-show="showAddModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showAddModal = false">
        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Add New Expense</h2>
            </div>

            <form action="{{ route('purchase.expense.store') }}" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                @php $cogsAccount = $expenseAccounts->first(fn($a) => $a->code === '5110'); @endphp
                <input type="hidden" name="expense_account_id" value="{{ $cogsAccount?->id }}">
                @php $cashAccount = $bankAccounts->first(); @endphp
                <input type="hidden" name="bank_account_id" value="{{ $cashAccount?->id }}">

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Expense Name <span class="text-primary">*</span></label>
                    <input type="text" name="expense_name" required placeholder="Shipping Fee"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Linked Bill</label>
                    <div class="relative">
                        <select name="purchase_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Bill --</option>
                            @foreach($bills as $bill)
                                <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category</label>
                    <div class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700">
                        <i class="bi bi-tag text-primary mr-1"></i>
                        {{ $cogsAccount ? $cogsAccount->code . ' - ' . $cogsAccount->name : 'Product Cost (COGS)' }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount Paid <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $curr }}</span>
                            <input type="number" step="0.01" name="amount" required placeholder="0.00"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                        <input type="date" name="expense_date" required value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Bank Account</label>
                    <div class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700">
                        <i class="bi bi-cash-stack text-primary mr-1"></i>
                        {{ $cashAccount?->name ?? 'Cash on Hand' }}
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description & Notes</label>
                    <input type="text" name="description" placeholder="Details..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showAddModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Expense'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Expense — mobile bottom sheet --}}
    <div x-show="showEditModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showEditModal = false">
        <div x-show="showEditModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary flex items-center justify-between sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">Edit Expense</h2>
                <button @click="showEditModal = false" class="w-8 h-8 bg-white/10 rounded-lg text-white flex items-center justify-center">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <form :action="'{{ url('/purchase/expense') }}/' + editExpense.id" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf
                @method('PUT')

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Expense Name <span class="text-primary">*</span></label>
                    <input type="text" name="expense_name" required x-model="editExpense.expense_name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Linked Bill <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select required name="purchase_id" x-model="editExpense.purchase_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Bill --</option>
                            @foreach($bills as $bill)
                                <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Category <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select required name="expense_account_id" x-model="editExpense.expense_account_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Category --</option>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount Paid <span class="text-primary">*</span></label>
                        <input type="number" step="0.01" name="amount" required x-model="editExpense.amount"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Date <span class="text-primary">*</span></label>
                        <input type="date" name="expense_date" required x-model="editExpense.expense_date"
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Supplier <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select required name="supplier_id" x-model="editExpense.supplier_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Branch <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select required name="branch_id" x-model="editExpense.branch_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Bank Account <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select required name="bank_account_id" x-model="editExpense.bank_account_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">-- Select Bank Account --</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Description & Notes</label>
                    <input type="text" name="description" x-model="editExpense.description"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showEditModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Update Expense'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete form --}}
    <form id="deleteExpenseForm" method="POST" class="hidden">
        @csrf @method('DELETE')
        <input type="hidden" name="password">
    </form>
</div>
@endsection
