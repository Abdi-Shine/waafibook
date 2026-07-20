@extends('admin.admin_master')
@section('page_title', 'Customer Statement')

@section('admin')

@php
    $symbol = '$'; // Force Dollar

    $transactions = collect();

    foreach ($customer->orders as $order) {
        $desc = 'Invoice Created';
        if (isset($order->discount_amount) && $order->discount_amount > 0) {
            $desc .= ' (Disc: ' . number_format($order->discount_amount, 2) . ')';
        }

        $transactions->push([
            'date' => $order->created_at,
            'timestamp' => $order->created_at->timestamp,
            'type' => 'Invoice',
            'ref' => $order->invoice_no,
            'description' => $desc,
            'item_details' => $order->items->count() . ' Items',
            'payment_info' => '-',
            'status' => $order->status,
            'debit' => $order->total_amount,
            'credit' => 0,
        ]);

        if ($order->paid_amount > 0) {
            $transactions->push([
                'date' => $order->created_at,
                'timestamp' => $order->created_at->timestamp + 1,
                'type' => 'Payment',
                'ref' => $order->invoice_no . '-PAY',
                'description' => 'Payment at Sale',
                'item_details' => '-',
                'payment_info' => 'Cash/Card',
                'status' => 'completed',
                'debit' => 0,
                'credit' => $order->paid_amount,
            ]);
        }
    }

    foreach ($customer->payments as $payment) {
        $date = \Carbon\Carbon::parse($payment->payment_date);
        $ts = $payment->created_at ? $payment->created_at->timestamp : $date->timestamp;

        $transactions->push([
            'date' => $date,
            'timestamp' => $ts,
            'type' => 'Payment',
            'ref' => $payment->receipt_no,
            'description' => 'Payment Received',
            'item_details' => $payment->notes ?? '-',
            'payment_info' => $payment->payment_method,
            'status' => $payment->status,
            'debit' => 0,
            'credit' => $payment->amount,
        ]);
    }

    $sorted = $transactions->sortBy('timestamp')->values();

    $totalDebits = $sorted->sum('debit');
    $totalCredits = $sorted->sum('credit');
    $calculatedOpening = $customer->amount_balance - $totalDebits + $totalCredits;

    $runningBalance = $calculatedOpening;
    $totalDebit = 0;
    $totalCredit = 0;

    $rows = [];
    if (abs($calculatedOpening) > 0.01) {
        $rows[] = [
            'date' => \Carbon\Carbon::parse($customer->created_at)->format('d M Y'),
            'type' => 'Opening',
            'ref' => null,
            'description' => 'Opening Balance',
            'item_details' => null,
            'debit' => $calculatedOpening > 0 ? $calculatedOpening : null,
            'credit' => $calculatedOpening < 0 ? abs($calculatedOpening) : null,
            'balance' => $runningBalance,
            'status' => null,
        ];
    }

    foreach ($sorted as $txn) {
        $runningBalance += $txn['debit'];
        $runningBalance -= $txn['credit'];
        $totalDebit += $txn['debit'];
        $totalCredit += $txn['credit'];

        $rows[] = [
            'date' => $txn['date']->format('d M Y'),
            'type' => $txn['type'],
            'ref' => $txn['ref'],
            'description' => $txn['description'],
            'item_details' => $txn['item_details'],
            'debit' => $txn['debit'] > 0 ? $txn['debit'] : null,
            'credit' => $txn['credit'] > 0 ? $txn['credit'] : null,
            'balance' => $runningBalance,
            'status' => $txn['status'],
        ];
    }
@endphp

<div class="pb-28 bg-background min-h-screen" x-data="{
    menuOpen: false,
    sendStatement() {
        const phone = ('{{ $customer->phone ?? '' }}').replace(/[^0-9]/g, '');
        const name = '{{ addslashes($customer->name) }}';
        const url = '{{ route('customer.statement.public-pdf', $customer->id) }}';
        if (!phone) {
            Swal.fire({ icon: 'warning', title: 'No Phone Number', text: 'This customer has no phone number saved.' });
            return;
        }
        let digits = phone;
        if (!digits.startsWith('252')) {
            digits = digits.replace(/^0+/, '');
            digits = digits.length <= 9 ? '252' + digits : digits;
        }
        const msg = 'Dear ' + name + ',\n\nPlease find your account statement below:\n\n' + url + '\n\nThank you for your business!';
        const a = document.createElement('a');
        a.href = 'https://wa.me/' + digits + '?text=' + encodeURIComponent(msg);
        a.target = '_blank';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    },
    sendEmailStatement() {
        Swal.fire({
            title: 'Sending Statement',
            text: 'Send PDF statement to {{ $customer->email }}?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Send Now',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('{{ route('email.statement.customer', $customer->id) }}')
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: result.value.success ? 'Success!' : 'Error!',
                    text: result.value.message,
                    icon: result.value.success ? 'success' : 'error',
                    confirmButtonColor: '#004161'
                });
            }
        });
    }
}">

    {{-- Header --}}
    <div class="sticky top-0 z-20 bg-white border-b border-gray-100 shadow-sm px-4 py-3 flex items-center gap-2">
        <a href="{{ route('customer.index') }}" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors shrink-0">
            <i class="bi bi-arrow-left text-primary-dark text-lg"></i>
        </a>
        <h1 class="flex-1 text-[16px] font-black text-primary-dark">Statement</h1>
        <div class="relative shrink-0">
            <button @click="menuOpen = !menuOpen" @click.away="menuOpen = false"
                class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
                <i class="bi bi-three-dots-vertical text-gray-400"></i>
            </button>
            <div x-show="menuOpen" x-cloak x-transition
                class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 z-30 overflow-hidden">
                <a href="{{ route('download.statement.customer', $customer->id) }}"
                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 flex items-center gap-2">
                    <i class="bi bi-download text-gray-400"></i> Save PDF
                </a>
                <button @click="sendEmailStatement(); menuOpen = false"
                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 border-t border-gray-50 flex items-center gap-2">
                    <i class="bi bi-envelope text-gray-400"></i> Email PDF
                </button>
                <button @click="sendStatement(); menuOpen = false"
                    class="w-full text-left px-4 py-2.5 text-[13px] font-semibold text-primary-dark hover:bg-gray-50 border-t border-gray-50 flex items-center gap-2">
                    <i class="bi bi-whatsapp text-green-500"></i> Share via WhatsApp
                </button>
            </div>
        </div>
    </div>

    {{-- Company + Customer Info --}}
    <div class="mx-4 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
            <div class="flex items-center gap-2.5">
                @if(isset($company_profile) && $company_profile->logo)
                    <img src="{{ asset($company_profile->logo) }}" alt="Logo" class="h-9 w-9 object-contain rounded-lg">
                @else
                    <div class="h-9 w-9 bg-primary rounded-lg flex items-center justify-center text-white">
                        <i class="bi bi-building text-base"></i>
                    </div>
                @endif
                <div>
                    <p class="text-[13px] font-black text-primary-dark leading-tight">{{ $company_profile->name ?? '' }}</p>
                    <p class="text-[10px] text-gray-400">{{ $company_profile->phone ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">STMT-{{ date('Y') }}-{{ $customer->id }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ \Carbon\Carbon::now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Customer</p>
                <p class="text-[15px] font-black text-primary-dark truncate">{{ $customer->name }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ $customer->customer_code ?? 'CUS-' . date('Y') . '-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT) }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ $customer->phone ?? '-' }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Balance</p>
                <p class="text-[18px] font-black {{ $runningBalance >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $symbol }}{{ number_format(abs($runningBalance), 2) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Transaction Timeline --}}
    <div class="px-4 mt-4">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-2">Transaction History</p>
    </div>

    <div class="px-4 space-y-3">
        @forelse($rows as $row)
            @php
                $typeBadge = match($row['type']) {
                    'Invoice' => 'bg-primary/10 text-primary',
                    'Payment' => 'bg-accent/10 text-accent',
                    default => 'bg-gray-100 text-gray-500',
                };
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3.5">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider {{ $typeBadge }}">
                        {{ $row['type'] }}
                    </span>
                    <span class="text-[11px] text-gray-400">{{ $row['date'] }}</span>
                </div>
                <p class="text-[13px] font-semibold text-text-primary mt-2">
                    {{ $row['description'] }}
                    @if($row['ref'])
                        <span class="font-black text-primary-dark">· {{ $row['ref'] }}</span>
                    @endif
                </p>
                @if($row['item_details'] && $row['item_details'] !== '-')
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $row['item_details'] }}</p>
                @endif
                <div class="flex items-center gap-5 mt-3 pt-3 border-t border-gray-50">
                    <div>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Debit</p>
                        <p class="text-[13px] font-black text-text-primary mt-0.5">
                            {{ $row['debit'] ? $symbol . number_format($row['debit'], 2) : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Credit</p>
                        <p class="text-[13px] font-black text-accent mt-0.5">
                            {{ $row['credit'] ? $symbol . number_format($row['credit'], 2) : '-' }}
                        </p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Balance</p>
                        <p class="text-[13px] font-black text-primary-dark mt-0.5">
                            {{ $symbol }}{{ number_format($row['balance'], 2) }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-400">
                <i class="bi bi-inbox text-4xl block mb-2"></i>
                <p class="text-[13px] font-semibold">No transactions found</p>
            </div>
        @endforelse
    </div>

    {{-- Summary --}}
    <div class="mx-4 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-wide mb-3">Account Summary</p>
        <div class="space-y-2.5 text-[13px] font-semibold text-gray-500">
            <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                <span>Total Transactions:</span>
                <strong class="text-gray-800">{{ count($rows) }}</strong>
            </div>
            <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                <span>Total Invoices:</span>
                <strong class="text-gray-800">{{ $customer->orders->count() }}</strong>
            </div>
            <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                <span>Total Payments:</span>
                <strong class="text-gray-800">{{ $customer->payments->count() }}</strong>
            </div>
            <div class="flex justify-between items-center pt-1">
                <span class="text-primary-dark font-black uppercase text-[11px] tracking-wide">Final Balance:</span>
                <strong class="text-primary-dark text-[16px]">{{ $symbol }}{{ number_format($runningBalance, 2) }}</strong>
            </div>
        </div>
    </div>

    <p class="text-center text-[11px] text-gray-400 mt-5 px-6">
        This is a computer-generated statement and does not require a signature.
    </p>

</div>

@endsection
