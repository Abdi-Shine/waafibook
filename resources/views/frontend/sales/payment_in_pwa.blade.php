@extends('admin.admin_master')
@section('page_title', 'Customer Payments')

@php
    $symbol = '$'; // Force Dollar — matches desktop payment_in.blade.php
@endphp

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    showModal: false,
    saving: false,
    customerBalance: 0,
    form: { customer_id: '', invoice_no: '{{ $suggestedInvoiceNo }}', bank_account_id: '{{ $bankAccounts->first()->id ?? '' }}', amount: '', payment_date: '{{ date('Y-m-d') }}', notes: '' },
    customers: @js($customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'balance' => (float) ($c->amount_balance ?? 0)])),

    openModal() {
        this.form = { customer_id: '', invoice_no: '{{ $suggestedInvoiceNo }}', bank_account_id: '{{ $bankAccounts->first()->id ?? '' }}', amount: '', payment_date: '{{ date('Y-m-d') }}', notes: '' };
        this.customerBalance = 0;
        this.showModal = true;
    },
    onCustomerSelect() {
        const c = this.customers.find(c => c.id == this.form.customer_id);
        this.customerBalance = c ? c.balance : 0;
        if (this.customerBalance > 0 && !this.form.amount) {
            this.form.amount = this.customerBalance.toFixed(2);
        }
    },
    sendWhatsApp(receiptNo, customer, phone, date, amount, companyName, companyPhone, companyEmail) {
        let message = `*${companyName}*\n\n`;
        message += `*Rasiidka Lacag Bixinta*\n`;
        message += `{{ $symbol }} ${amount}\n`;
        message += `taariikhda ${date}\n\n`;
        message += `Salaam ${customer},\n`;
        message += `Waxaan xaqiijinaynaa inaan helnay lacagtaada {{ $symbol }} ${amount} (Rasiidh Lambar: ${receiptNo}).\n\n`;
        message += `Mahadsanid,\n${companyName}`;
        if (companyPhone) message += `\n${companyPhone}`;
        if (companyEmail) message += `\n${companyEmail}`;
        const url = phone
            ? `https://wa.me/${phone.replace(/\D/g, '')}?text=${encodeURIComponent(message)}`
            : `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
    },
    deletePayment(id, receiptNo) {
        deleteRecordWithPassword('{{ url('/payment-in/delete') }}/' + id, receiptNo, {
            title: 'Delete Payment Record?',
            text: 'Are you sure you want to delete payment ' + receiptNo + '? This action cannot be undone.'
        });
    }
}" x-cloak>

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <h1 class="text-[16px] font-black text-primary-dark">Payment In</h1>
        <button @click="openModal()"
            class="flex items-center gap-1 px-3 py-2 bg-accent text-primary font-bold rounded-xl text-[13px] shrink-0 whitespace-nowrap">
            <i class="bi bi-plus-lg text-base"></i> New Payment
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 px-5 pt-4">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-currency-dollar text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Today</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($todayReceipts, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent mb-2">
                <i class="bi bi-calendar-check text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">This Month</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($monthReceipts, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-2">
                <i class="bi bi-hourglass-split text-xs"></i>
            </div>
            <p class="text-[9px] text-gray-400 font-semibold uppercase tracking-wide mb-1">Pending</p>
            <p class="text-[16px] font-black text-primary">{{ $symbol }} {{ number_format($pendingPayments, 0) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('view_payment_in') }}" class="flex items-center gap-2 px-5 pt-4 mb-2">
        <div class="flex-1 flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-xl">
            <i class="bi bi-search text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="SEARCH RECEIPT OR CUSTOMER"
                class="flex-1 text-[13px] text-gray-700 font-medium tracking-wide placeholder-gray-400 outline-none border-none ring-0 bg-transparent"
                autocomplete="off">
        </div>
        <div class="relative shrink-0">
            <select name="status" onchange="this.form.submit()"
                class="pl-3 pr-8 py-2.5 bg-gray-100 border-none rounded-xl text-[13px] font-medium text-gray-700 outline-none appearance-none max-w-[120px]">
                <option value="">All Status</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
        </div>
    </form>

    <div class="px-5 pt-2">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Payment Records</p>
    </div>

    <div class="bg-white border-t border-b border-gray-100">
        @forelse($payments as $item)
            @php
                $statusColor = match($item->status) {
                    'completed' => 'bg-accent/10 text-accent',
                    'pending' => 'bg-gray-100 text-gray-400',
                    default => 'bg-red-50 text-red-500',
                };
            @endphp
            <div class="px-5 py-4 border-b border-gray-100 last:border-0">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 pr-3">
                        <p class="text-[14px] font-black text-text-primary leading-tight truncate">{{ $item->customer->name ?? 'N/A' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $item->receipt_no }} · {{ $item->payment_method }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[14px] font-black text-text-primary">{{ $symbol }} {{ number_format($item->amount, 2) }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ \Carbon\Carbon::parse($item->payment_date)->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor }}">
                        {{ ucfirst($item->status) }}
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('payment_in.view', $item->id) }}" target="_blank"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-eye text-xs"></i>
                        </a>
                        <button type="button"
                            @click="sendWhatsApp('{{ $item->receipt_no }}', '{{ addslashes($item->customer->name ?? 'N/A') }}', '{{ $item->customer->phone ?? '' }}', '{{ \Carbon\Carbon::parse($item->payment_date)->format('jS F Y') }}', '{{ number_format($item->amount, 2) }}', '{{ addslashes($company->name ?? 'Waafibook') }}', '{{ $company->phone ?? '' }}', '{{ $company->email ?? '' }}')"
                            class="w-7 h-7 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent active:bg-accent/20">
                            <i class="bi bi-whatsapp text-xs"></i>
                        </button>
                        <a href="{{ route('customer.statement', $item->customer_id) }}"
                            class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 active:bg-gray-100">
                            <i class="bi bi-file-earmark-person text-xs"></i>
                        </a>
                        <button type="button" @click="deletePayment({{ $item->id }}, '{{ $item->receipt_no }}')"
                            class="w-7 h-7 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-red-500 active:bg-red-100">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-text-secondary mt-2 font-semibold">No payment records found.</p>
            </div>
        @endforelse
    </div>

    {{-- New Payment In — mobile bottom sheet --}}
    <div x-show="showModal" x-cloak x-transition.opacity
        class="fixed inset-0 z-[70] bg-slate-900/40" @click.self="showModal = false">
        <div x-show="showModal" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[1.5rem] max-h-[90vh] overflow-y-auto">

            <div class="px-5 py-4 bg-primary sticky top-0 z-10">
                <h2 class="text-white font-bold text-[16px]">New Payment In</h2>
            </div>

            <form action="{{ route('payment_in.store') }}" method="POST" class="p-5 flex flex-col gap-4" @submit="saving = true">
                @csrf

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Select Customer <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="customer_id" x-model="form.customer_id" @change="onCustomerSelect()" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            <option value="">Select customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between bg-primary/10 px-3 py-1.5 rounded-md border border-primary/20">
                        <span class="text-[10px] font-bold text-primary uppercase tracking-tighter">Balance</span>
                        <span class="text-[12px] font-black text-primary">{{ $symbol }} <span x-text="customerBalance.toFixed(2)"></span></span>
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Invoice / Reference</label>
                    <input type="text" name="invoice_no" x-model="form.invoice_no"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Deposit To <span class="text-primary">*</span></label>
                    <div class="relative">
                        <select name="bank_account_id" x-model="form.bank_account_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none appearance-none">
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Amount <span class="text-primary">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[13px] font-bold">{{ $symbol }}</span>
                            <input type="number" step="0.01" name="amount" x-model="form.amount" required placeholder="0.00"
                                class="w-full pl-8 pr-2 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Payment Date <span class="text-primary">*</span></label>
                        <input type="date" name="payment_date" x-model="form.payment_date" required
                            class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5 block">Internal Notes</label>
                    <textarea name="notes" x-model="form.notes" rows="3" placeholder="Add any details about this transaction..."
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[14px] font-medium text-gray-700 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="showModal = false"
                        class="flex-1 py-3.5 bg-accent text-primary font-bold rounded-xl text-[13px] uppercase tracking-wide">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving"
                        class="flex-1 py-3.5 bg-primary text-white font-bold rounded-xl text-[13px] uppercase tracking-wide flex items-center justify-center gap-2"
                        :class="saving ? 'opacity-60' : ''">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
