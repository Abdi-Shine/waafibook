@extends('admin.admin_master')
@section('page_title', 'Supplier Payments')
@section('admin')

@php
    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{
    showModal: false,
    supplierName: 'SELECT SUPPLIER',
    supplierBalance: '0.00',
    paymentMethod: '{{ $bankAccounts->first() ? strtoupper($bankAccounts->first()->name) : 'SELECT ACCOUNT' }}',
    paymentAmount: '',
    paymentDate: '{{ date('Y-m-d') }}',
    reference: '{{ $suggestedVoucherNo }}'
}">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Payment Out (Vouchers)</h1>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showModal = true" class="btn-premium-primary group normal-case">
                <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                <span>New Payment Out</span>
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Today's Payments -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Total Payments</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-gray-400">{{ $symbol }}</span>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($todayPayments, 2) }}</h3>
                </div>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Today's outbound</p>
            </div>
            <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                <i class="bi bi-cash-stack text-lg"></i>
            </div>
        </div>

        <!-- Monthly Total -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Monthly Total</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-gray-400">{{ $symbol }}</span>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($monthPayments, 2) }}</h3>
                </div>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Current month volume</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-calendar-check text-lg"></i>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Amount</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-gray-400">{{ $symbol }}</span>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($pendingPayments, 2) }}</h3>
                </div>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Awaiting clearance</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-hourglass-split text-lg"></i>
            </div>
        </div>

        <!-- Voucher Count -->
        <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
            <div>
                <p class="text-[12px] text-gray-400 font-medium mb-1">Voucher Count</p>
                <h3 class="text-[18px] font-black text-primary">{{ $totalTransactions }}</h3>
                <p class="text-xs font-bold text-primary-dark mt-1.5">Total recorded records</p>
            </div>
            <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                <i class="bi bi-receipt-cutoff text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Table Section -->
    <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <!-- Filter Bar -->
        <form action="{{ route('view_payment_out') }}" method="GET">
            <div class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Search -->
                <div class="relative group flex-1 min-w-[200px]">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm group-focus-within:text-primary transition-colors"></i>
                    <input type="text" name="search" placeholder="Search vouchers or suppliers..." value="{{ request('search') }}"
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Supplier -->
                <div class="relative min-w-[150px]">
                    <select name="vendor_id" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('vendor_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
                </div>

                <!-- Status -->
                <div class="relative min-w-[150px]">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
                </div>

                <!-- Method -->
                <div class="relative min-w-[150px]">
                    <select name="method" onchange="this.form.submit()"
                        class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Methods</option>
                        <option value="Cash" {{ request('method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="Card" {{ request('method') == 'Card' ? 'selected' : '' }}>Card</option>
                        <option value="Bank Transfer" {{ request('method') == 'Bank Transfer' ? 'selected' : '' }}>Bank</option>
                        <option value="Cheque" {{ request('method') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[10px]"></i>
                </div>
            </div>
        </form>

        <!-- Table Title Bar -->
        <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-list-ul text-primary-dark text-sm"></i>
            <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Payment Records</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left">
                <thead>
                    <tr class="bg-white border-b border-gray-100">
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Voucher Info</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Supplier</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Products</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Method</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Amount</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $key => $item)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                            {{ str_pad($payments->firstItem() + $key, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                            {{ $item->voucher_no }}
                        </td>
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark">
                            {{ $item->supplier->name ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-col gap-1 max-w-[200px]">
                                @php $foundItems = false; @endphp
                                @if(isset($item->details) && $item->details->count() > 0)
                                    @foreach($item->details as $detail)
                                        @if($detail->bill && $detail->bill->items)
                                            @foreach($detail->bill->items as $bitem)
                                                @php $foundItems = true; @endphp
                                                <span class="text-[12px] font-semibold text-primary-dark whitespace-normal">{{ $bitem->product_name }}</span>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                                @if(!$foundItems)
                                    @php
                                        $billMatch = \App\Models\PurchaseBill::where('bill_number', 'like', '%'.$item->reference.'%')->first();
                                    @endphp
                                    @if($billMatch)
                                        @foreach($billMatch->items as $bitem)
                                            <span class="text-[12px] font-semibold text-primary-dark whitespace-normal">{{ $bitem->product_name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-[11px] text-gray-400 font-semibold italic">No items found</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                            {{ $item->payment_method }}
                        </td>
                        <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-right">
                            {{ $symbol }} {{ number_format($item->amount, 2) }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black border uppercase tracking-wider
                                @if($item->status === 'completed') bg-accent/10 text-primary border-accent/20
                                @elseif($item->status === 'pending') bg-gray-100 text-gray-400 border-gray-200
                                @else bg-primary/10 text-primary border-primary/20 @endif">
                                {{ $item->status === 'completed' ? 'PAID' : strtoupper($item->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('payment_out.view', $item->id) }}" target="_blank"
                                    class="btn-action-view" title="View Voucher">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('supplier.statement', $item->supplier_id) }}"
                                    class="btn-action-edit" title="Supplier Statement">
                                    <i class="bi bi-file-earmark-person"></i>
                                </a>
                                <button onclick="confirmDeletePayment('{{ route('payment_out.delete', $item->id) }}')"
                                    class="btn-action-delete" title="Delete Voucher">
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
                            <p class="text-[13px] font-bold uppercase tracking-widest text-gray-400">No payment records found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->total() > 0)
        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} entries
            </div>
            <div class="flex items-center gap-2">
                @if ($payments->onFirstPage())
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </button>
                @else
                    <a href="{{ $payments->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-[10px]"></i>
                    </a>
                @endif

                @foreach ($payments->getUrlRange(max(1, $payments->currentPage() - 2), min($payments->lastPage(), $payments->currentPage() + 2)) as $page => $url)
                    @if ($page == $payments->currentPage())
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                    @else
                        <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm text-xs font-semibold">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($payments->hasMorePages())
                    <a href="{{ $payments->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </a>
                @else
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm">
                        <i class="bi bi-chevron-right text-[10px]"></i>
                    </button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- MODAL -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative" @click.away="showModal = false">

            <!-- Modal Header -->
            <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                <div class="flex items-center justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-xl shadow-inner backdrop-blur-md text-white">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div class="flex flex-col text-white">
                            <h2 class="text-xl font-bold tracking-tight">New Payment Voucher</h2>
                            <p class="text-xs text-white/70 font-medium mt-0.5">Issue a new payment to supplier</p>
                        </div>
                    </div>
                    <button @click="showModal = false" class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                <form id="paymentForm" action="{{ route('payment_out.store') }}" method="POST" class="w-full">
                    @csrf
                    <div class="flex flex-col lg:flex-row gap-8 w-full">
                        <!-- Main Form Column -->
                        <div class="w-full lg:w-2/3 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                                <div class="space-y-1.5 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Select Supplier <span class="text-primary">*</span></label>
                                    <div class="relative group w-full">
                                        <select name="vendor_id" required
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer"
                                            @change="
                                                const opt = $event.target.options[$event.target.selectedIndex];
                                                supplierName = opt.text.toUpperCase();
                                                const balance = parseFloat(opt.getAttribute('data-balance')) || 0;
                                                supplierBalance = balance.toLocaleString(undefined, {minimumFractionDigits: 2});
                                                if(balance > 0 && (!paymentAmount || paymentAmount == '0.00' || paymentAmount == '')) {
                                                    paymentAmount = balance.toFixed(2);
                                                }
                                            ">
                                            <option value="">Search Supplier...</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" data-balance="{{ $supplier->amount_balance }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between bg-primary/10 px-3 py-1.5 rounded-md border border-primary/20">
                                        <span class="text-[10px] font-bold text-primary uppercase tracking-tighter">Current Payable:</span>
                                        <span class="text-[12px] font-black text-primary">{{ $symbol }} <span x-text="supplierBalance">0.00</span></span>
                                    </div>
                                </div>

                                <div class="space-y-1.5 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Reference / Bill #</label>
                                    <div class="relative group w-full">
                                        <input type="text" name="reference" placeholder="e.g. BILL-001" x-model="reference"
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        <i class="bi bi-hash absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <p class="text-[10px] text-gray-400 font-medium italic mt-1">* Suggested PV: {{ $suggestedVoucherNo }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 w-full">
                                <div class="space-y-1.5 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Bank / Cash Account <span class="text-primary">*</span></label>
                                    <div class="relative">
                                        <select name="bank_account_id" required
                                            class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer"
                                            @change="paymentMethod = $event.target.options[$event.target.selectedIndex].text.toUpperCase()">
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[10px]"></i>
                                    </div>
                                </div>
                                <div class="space-y-1.5 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount Paid <span class="text-primary">*</span></label>
                                    <div class="relative group w-full">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">{{ $symbol }}</span>
                                        <input type="number" name="amount" x-model="paymentAmount" step="0.01" required
                                            class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[15px] font-black text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    </div>
                                </div>
                                <div class="space-y-1.5 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Payment Date</label>
                                    <input type="date" name="payment_date" x-model="paymentDate" required
                                        class="w-full pl-4 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-2 w-full">
                                <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Internal Notes</label>
                                <textarea name="notes" rows="3" placeholder="Add any details about this transaction..."
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Preview Sidebar -->
                        <div class="w-full lg:w-1/3">
                            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 h-full flex flex-col gap-6 lg:sticky lg:top-0">
                                <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                                    <h4 class="text-[11px] font-black text-primary-dark uppercase tracking-widest">Live Voucher Preview</h4>
                                    <i class="bi bi-lightning-charge-fill text-primary text-sm"></i>
                                </div>
                                <div class="space-y-3">
                                    <div class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Pay To (Supplier)</p>
                                        <p class="text-[12px] font-black text-primary-dark truncate" x-text="supplierName">SELECT SUPPLIER</p>
                                    </div>
                                    <div class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Transaction Method</p>
                                        <p class="text-[11px] font-black text-gray-600 uppercase" x-text="paymentMethod">CASH PAYMENT</p>
                                    </div>
                                    <div class="p-3 bg-accent/10 border border-accent/20 rounded-xl shadow-sm flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-bold text-accent uppercase mb-1">System Status</p>
                                            <p class="text-[10px] font-black text-accent uppercase tracking-widest flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-accent/10 rounded-full animate-pulse"></span> Valid Voucher
                                            </p>
                                        </div>
                                        <i class="bi bi-shield-check text-accent text-xl"></i>
                                    </div>
                                </div>
                                <div class="mt-auto pt-6 border-t border-gray-200">
                                    <div class="bg-accent rounded-xl p-5 shadow-lg relative overflow-hidden group">
                                        <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                                        <p class="text-[10px] font-bold text-white uppercase tracking-widest mb-1.5 opacity-90">Amount to Debit</p>
                                        <div class="flex items-baseline gap-1 text-white">
                                            <span class="text-lg font-bold opacity-90">{{ $symbol }}</span>
                                            <h2 class="text-3xl font-black tracking-tight" x-text="parseFloat(paymentAmount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})">0.00</h2>
                                        </div>
                                        <div class="mt-4 flex items-center justify-between text-[10px] text-white font-bold uppercase">
                                            <span class="opacity-80" x-text="new Date(paymentDate).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })"></span>
                                            <i class="bi bi-qr-code text-lg opacity-40"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
                <button type="button" @click="showModal = false" class="btn-premium-accent">
                    Discard
                </button>
                <button type="submit" form="paymentForm" class="btn-premium-primary">
                    <i class="bi bi-check2-circle"></i>
                    <span>Confirm & Authorize Payment</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeletePayment(url) {
        deleteRecordWithPassword(url, 'this payment voucher', {
            title: 'Delete Payment Voucher?',
            text: 'This action is irreversible and will reverse the journal entries.'
        });
    }
</script>

@endsection
