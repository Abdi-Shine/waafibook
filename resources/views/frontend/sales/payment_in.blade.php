@extends('admin.admin_master')
@section('page_title', 'Customer Payments')


@php
    $currencySymbols = [
        'USD' => '$', 'SAR' => 'SAR', 'SOS' => 'SOS', 'EUR' => '€', 'GBP' => '£', 'KES' => 'KSh',
    ];
    $symbol = '$'; // Force Dollar
@endphp

@section('admin')

    <div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen" x-data="{
            showModal: false,
            customerName: 'SELECT CUSTOMER',
            customerBalance: '0.00',
            paymentMethod: '{{ $bankAccounts->first() ? strtoupper($bankAccounts->first()->name . ' (' . $bankAccounts->first()->type . ')') : 'SELECT ACCOUNT' }}',
            paymentAmount: '',
            paymentDate: '{{ date('Y-m-d') }}'
        }">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div>
                <h1 class="text-[20px] font-bold text-primary-dark">Payment In (Receipts)</h1>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showModal = true" class="btn-premium-primary group normal-case">
                    <i class="bi bi-plus-lg group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>New Payment In</span>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Today's Receipts -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Today's Receipts</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($todayReceipts, 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-cash-coin text-[10px]"></i> Daily Total
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-currency-dollar text-lg"></i>
                </div>
            </div>

            <!-- This Month -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">This Month</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($monthReceipts, 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-calendar3 text-[10px]"></i> Monthly Volume
                    </p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-calendar-check text-lg"></i>
                </div>
            </div>

            <!-- Pending -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Pending Amount</p>
                    <h3 class="text-[18px] font-black text-primary">{{ $symbol }} {{ number_format($pendingPayments, 2) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-hourglass-split text-[10px]"></i> Awaiting verification
                    </p>
                </div>
                <div class="w-11 h-11 bg-primary/10 rounded-[0.6rem] flex items-center justify-center text-primary flex-shrink-0">
                    <i class="bi bi-clock-history text-lg"></i>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white p-5 rounded-[1rem] border border-gray-100 shadow-sm flex items-start justify-between hover:-translate-y-0.5 transition-transform duration-200">
                <div>
                    <p class="text-[12px] text-gray-400 font-medium mb-1">Total Transactions</p>
                    <h3 class="text-[18px] font-black text-primary">{{ number_format($totalTransactions) }}</h3>
                    <p class="text-xs font-bold text-primary-dark mt-1.5 flex items-center gap-1">
                        <i class="bi bi-receipt-cutoff text-[10px]"></i> Activity count
                    </p>
                </div>
                <div class="w-11 h-11 bg-accent/10 rounded-[0.6rem] flex items-center justify-center text-accent flex-shrink-0">
                    <i class="bi bi-check2-all text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Table Section -->
        <div class="bg-white rounded-[1rem] border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            
            <!-- Filter Bar -->
            <form action="{{ route('view_payment_in') }}" method="GET" class="p-4 border-b border-gray-100 flex items-center gap-3 overflow-x-auto custom-scrollbar whitespace-nowrap">
                <!-- Search -->
                <div class="relative group min-w-[250px] flex-1">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm group-focus-within:text-primary-dark"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search receipts or customers..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-700 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder-gray-400">
                </div>

                <!-- Customer Filter -->
                <div class="relative min-w-[150px]">
                    <select name="customer_id" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Customers</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[150px]">
                    <select name="status" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Method Filter -->
                <div class="relative min-w-[150px]">
                    <select name="method" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-[0.5rem] text-[13px] font-medium text-gray-600 focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all appearance-none cursor-pointer">
                        <option value="">All Methods</option>
                        <option value="Cash" {{ request('method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="Card" {{ request('method') == 'Card' ? 'selected' : '' }}>Card</option>
                        <option value="Bank Transfer" {{ request('method') == 'Bank Transfer' ? 'selected' : '' }}>Bank</option>
                        <option value="Cheque" {{ request('method') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                    </select>
                    <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <!-- Reset Button -->
                <div class="flex items-center gap-2">
                    @if(request()->hasAny(['search', 'customer_id', 'status', 'method']))
                    <button type="button" onclick="window.location.href='{{ route('view_payment_in') }}'"
                       class="w-9 h-9 flex items-center justify-center bg-primary/10 text-primary rounded-[0.5rem] hover:bg-primary hover:text-white transition-all shadow-sm border border-primary/10" 
                       title="Clear All Filters">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                    @endif
                </div>
            </form>

            <!-- Table List Header -->
            <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
                <i class="bi bi-list-ul text-primary-dark text-sm"></i>
                <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Payment Records</h2>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left border-collapse">
                    <thead>
                        <tr class="bg-white border-b border-gray-100">
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider w-16 text-center">#</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Receipt Info</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider">Customer</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Reference</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Account</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Amount</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-5 py-4 text-[12px] font-black text-primary-dark uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($payments as $key => $item)
                        <tr class="hover:bg-gray-50/60 transition-colors bg-white group">
                            <td class="px-5 py-4 text-[12px] font-semibold text-primary-dark text-center">
                                {{ str_pad($payments->firstItem() + $key, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-black text-primary uppercase leading-tight">{{ $item->receipt_no }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[12px] font-black text-primary leading-tight">{{ $item->customer->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-black text-primary leading-tight">
                                    {{ $item->invoice_no ?? 'DIRECT' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-black text-primary leading-tight">{{ $item->bankAccount->name ?? $item->payment_method }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="text-[12px] font-black text-primary leading-tight">{{ $symbol }}{{ number_format($item->amount, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <select class="status-dropdown bg-accent/20 border border-accent/20 text-[10px] font-black px-3 py-1 rounded-full focus:bg-white focus:ring-1 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer uppercase tracking-wider text-primary shadow-sm"
                                        data-id="{{ $item->id }}">
                                    <option value="completed" {{ $item->status == 'completed' ? 'selected' : '' }}>COMPLETED</option>
                                    <option value="pending"   {{ $item->status == 'pending'   ? 'selected' : '' }}>PENDING</option>
                                    <option value="failed"    {{ $item->status == 'failed'    ? 'selected' : '' }}>FAILED</option>
                                </select>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('payment_in.view', $item->id) }}" target="_blank" class="btn-action-view" title="View PDF">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button onclick="sendWhatsAppPayment('{{ $item->receipt_no }}','{{ addslashes($item->customer->name ?? 'N/A') }}','{{ $item->customer->phone ?? '' }}','{{ date('jS F Y', strtotime($item->payment_date)) }}','{{ number_format($item->amount, 2) }}','{{ $symbol }}','{{ addslashes($company->name ?? 'Waafibook') }}','{{ $company->phone ?? '' }}','{{ $company->email ?? '' }}')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-accent/10 text-accent border border-accent/20 hover:bg-accent/10 hover:text-white transition-all shadow-sm"
                                            title="WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </button>
                                    <a href="{{ route('customer.statement', $item->customer_id) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary border border-indigo-100 hover:bg-primary/10 hover:text-white transition-all shadow-sm" title="Statement">
                                        <i class="bi bi-file-earmark-person"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('Sales & POS', 'delete'))
                                    <button type="button" onclick="confirmDeletePaymentIn('{{ route('payment_in.delete', $item->id) }}', '{{ $item->receipt_no }}')" class="btn-action-delete" title="Delete Records">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-20 text-center text-gray-400 italic text-xs">No payment records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            @if($payments->count() > 0)
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">
                        Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }} entries
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($payments->onFirstPage())
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                                <i class="bi bi-chevron-left text-xs"></i>
                            </button>
                        @else
                            <a href="{{ $payments->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                                <i class="bi bi-chevron-left text-xs"></i>
                            </a>
                        @endif

                        @foreach ($payments->links()->elements as $element)
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $payments->currentPage())
                                        <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-white font-black text-xs shadow-md shadow-primary/20">{{ $page }}</button>
                                    @else
                                        <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm text-xs font-bold">{{ $page }}</a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        @if ($payments->hasMorePages())
                            <a href="{{ $payments->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                                <i class="bi bi-chevron-right text-xs"></i>
                            </a>
                        @else
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-300 cursor-not-allowed shadow-sm" disabled>
                                <i class="bi bi-chevron-right text-xs"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ── New Payment Modal ────────────────────────────────────── --}}
        <div x-show="showModal" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">

            <div class="bg-white rounded-[1.25rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col relative"
                 @click.away="showModal = false">

                {{-- Modal Header --}}
                <div class="px-6 py-6 bg-primary relative overflow-hidden shrink-0">
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-white text-xl shadow-inner backdrop-blur-md">
                                <i class="bi bi-plus-circle"></i>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-xl font-bold text-white tracking-tight">New Payment Receipt</h2>
                                <p class="text-xs text-primary font-medium mt-0.5">Issue a new payment confirmation to customer</p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                                class="w-8 h-8 bg-white/10 border border-white/10 text-white rounded-lg hover:bg-white/20 transition-all flex items-center justify-center shadow-sm">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-6 overflow-y-auto custom-scrollbar flex-grow bg-white">
                    <form id="paymentForm" action="{{ route('payment_in.store') }}" method="POST" class="w-full">
                        @csrf
                        <div class="flex flex-col lg:flex-row gap-8 w-full">

                            {{-- Left: Form Fields --}}
                            <div class="w-full lg:w-2/3 space-y-6">

                                {{-- Customer & Reference --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                                    <div class="space-y-1.5 w-full">
                                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Select Customer <span class="text-primary">*</span></label>
                                        <div class="relative group w-full">
                                            <select name="customer_id" required
                                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer"
                                                    @change="
                                                        const opt = $event.target.options[$event.target.selectedIndex];
                                                        customerName = opt.text.toUpperCase();
                                                        const balance = parseFloat(opt.getAttribute('data-balance')) || 0;
                                                        customerBalance = balance.toLocaleString(undefined, {minimumFractionDigits: 2});
                                                        if(balance > 0 && (!paymentAmount || paymentAmount == '0.00' || paymentAmount == '')) {
                                                            paymentAmount = balance.toFixed(2);
                                                        }
                                                    ">
                                                <option value="">Search Customer...</option>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}" data-balance="{{ $customer->amount_balance }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                            <i class="bi bi-person absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between bg-primary/10 px-3 py-1.5 rounded-md border border-primary/20">
                                            <span class="text-[10px] font-bold text-primary uppercase tracking-tighter">Balance:</span>
                                            <span class="text-[12px] font-black text-primary">{{ $symbol }} <span x-text="customerBalance">0.00</span></span>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 w-full">
                                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Invoice / Reference</label>
                                        <div class="relative group w-full">
                                            <input type="text" name="invoice_no" value="{{ $suggestedInvoiceNo }}"
                                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all pr-10">
                                            <i class="bi bi-hash absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                        <p class="text-[10px] text-gray-400 font-medium italic mt-1">* Recommended invoice #</p>
                                    </div>
                                </div>

                                {{-- Account, Amount, Date --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 w-full">
                                    <div class="space-y-1.5 w-full">
                                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Deposit To <span class="text-primary">*</span></label>
                                        <select name="bank_account_id" required
                                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none cursor-pointer"
                                                @change="paymentMethod = $event.target.options[$event.target.selectedIndex].text.toUpperCase()">
                                            @foreach($bankAccounts as $acc)
                                                <option value="{{ $acc->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="space-y-1.5 w-full">
                                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Amount <span class="text-primary">*</span></label>
                                        <div class="relative group w-full">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">{{ $symbol }}</span>
                                            <input type="number" name="amount" x-model="paymentAmount" step="0.01" required
                                                   class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[15px] font-black text-primary focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 w-full">
                                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Payment Date</label>
                                        <input type="date" name="payment_date" x-model="paymentDate" required
                                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div class="space-y-1.5 pt-2 w-full">
                                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Internal Notes</label>
                                    <textarea name="notes" rows="3" placeholder="Add any details about this transaction..."
                                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all resize-none"></textarea>
                                </div>
                            </div>

                            {{-- Right: Preview --}}
                            <div class="w-full lg:w-1/3">
                                <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 h-full flex flex-col gap-6 lg:sticky lg:top-0">
                                    <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                                        <h4 class="text-[11px] font-black text-primary-dark uppercase tracking-widest">Live Receipt Preview</h4>
                                        <i class="bi bi-lightning-charge-fill text-primary text-sm"></i>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Customer Account</p>
                                            <p class="text-[12px] font-black text-primary-dark truncate" x-text="customerName">SELECT CUSTOMER</p>
                                        </div>
                                        <div class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm">
                                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Transaction Method</p>
                                            <p class="text-[11px] font-black text-gray-600 uppercase" x-text="paymentMethod">CASH PAYMENT</p>
                                        </div>
                                        <div class="p-3 bg-accent/10 border border-accent/20 rounded-xl shadow-sm flex items-center justify-between">
                                            <div>
                                                <p class="text-[9px] font-bold text-accent uppercase mb-1">System Status</p>
                                                <p class="text-[10px] font-black text-accent uppercase tracking-widest flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 bg-accent/10 rounded-full animate-pulse"></span> Valid Receipt
                                                </p>
                                            </div>
                                            <i class="bi bi-shield-check text-accent text-xl"></i>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-6 border-t border-gray-200">
                                        <div class="bg-accent rounded-xl p-5 shadow-lg relative overflow-hidden group">
                                            <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                                            <p class="text-[10px] font-bold text-white uppercase tracking-widest mb-1.5 opacity-90">Amount to Credit</p>
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

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between">
                    <button type="button" @click="showModal = false"
                            class="px-5 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-primary/95 transition-all text-[13px] shadow-sm">
                        Cancel
                    </button>
                    <button type="submit" form="paymentForm"
                            class="flex items-center gap-2 px-6 py-2.5 bg-accent text-primary font-bold rounded-lg hover:bg-accent/90 transition-all text-[13px] shadow-sm">
                        <i class="bi bi-check2-circle text-base"></i>
                        <span>Confirm &amp; Process Payment</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $(document).on('change', '.status-dropdown', function(){
                const id     = $(this).data('id');
                const status = $(this).val();
                $.ajax({
                    url: "{{ url('/payment-in/update-status') }}/" + id,
                    type: "POST",
                    data: { status: status, _token: "{{ csrf_token() }}" },
                    success: function(response){
                        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        Toast.fire({ icon: 'success', title: 'Status Updated' });
                        location.reload();
                    }
                });
            });
        });

        function confirmDeletePaymentIn(url, receiptNo) {
            deleteRecordWithPassword(url, receiptNo, {
                title: 'Delete Payment Record?',
                text: `Are you sure you want to delete payment ${receiptNo}? This action cannot be undone.`
            });
        }

        function sendWhatsAppPayment(receiptNo, customer, phone, date, amount, symbol, companyName, companyPhone, companyEmail) {
            let message = `*${companyName}*\n\n`;
            message += `*Rasiidka Lacag Bixinta*\n`;
            message += `${symbol} ${amount}\n`;
            message += `taariikhda ${date}\n\n`;
            message += `Salaam ${customer},\n`;
            message += `Waxaan xaqiijinaynaa inaan helnay lacagtaada ${symbol} ${amount} (Rasiidh Lambar: ${receiptNo}).\n\n`;
            message += `Mahadsanid,\n${companyName}`;
            if (companyPhone) message += `\n${companyPhone}`;
            if (companyEmail) message += `\n${companyEmail}`;
            let url = phone
                ? `https://wa.me/${phone.replace(/\D/g,'')}?text=${encodeURIComponent(message)}`
                : `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(url, '_blank');
        }
    </script>

@endsection

