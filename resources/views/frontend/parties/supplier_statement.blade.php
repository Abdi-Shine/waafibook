@extends('admin.admin_master')
@section('page_title', 'Supplier Statement')
@section('admin')

<div x-data="{
    showPrintOptions: false,
    opts: {
        itemDetails: true,
        description: true,
        paymentInfo: true,
        paymentStatus: true
    },
    colspan() {
        let span = 6;
        if (!this.opts.paymentInfo) span--;
        if (!this.opts.paymentStatus) span--;
        if (!this.opts.itemDetails) span--;
        if (!this.opts.description) span--;
        return span;
    },
    sendEmailStatement() {
        Swal.fire({
            title: 'Sending Statement',
            text: 'Send PDF statement to {{ $supplier->email }}?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Send Now',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('{{ route('email.statement.supplier', $supplier->id) }}')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText)
                        }
                        return response.json()
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`)
                    })
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value.message,
                        icon: 'success',
                        confirmButtonColor: '#004161'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.value.message,
                        icon: 'error',
                        confirmButtonColor: '#004161'
                    });
                }
            }
        });
    }
}" class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen w-full max-w-full overflow-hidden font-inter">

    @php
        $currencySymbols = [
            'USD' => '$',
            'SAR' => 'SAR',
            'SOS' => 'SOS',
            'EUR' => '€',
            'GBP' => '£',
            'KES' => 'KSh',
        ];
        $symbol = '$'; // Force Dollar
    @endphp

    <!-- Top Action Bar (Hidden when printing) -->
    <div class="print:hidden flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('download.statement.supplier', $supplier->id) }}" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-semibold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-sm">
                <i class="bi bi-download text-primary"></i> Save PDF
            </a>
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-semibold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-sm">
                <i class="bi bi-printer text-primary"></i> Print
            </button>
            <button @click="sendEmailStatement()" class="flex items-center gap-2 px-4 py-2 bg-accent border border-accent text-primary-dark font-semibold rounded-lg hover:bg-accent/80 transition-all shadow-sm text-sm">
                <i class="bi bi-envelope text-primary"></i> Email PDF
            </button>
        </div>
        
        <div class="flex items-center gap-3">
            <button @click="showPrintOptions = true" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 font-semibold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-sm">
                <i class="bi bi-gear text-primary"></i> Options
            </button>
            <a href="{{ route('supplier.index') }}" class="flex items-center gap-2 px-5 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-all shadow-sm text-sm">
                <i class="bi bi-x-lg"></i> Close
            </a>
        </div>
    </div>

    <!-- Printable Area -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 md:p-8 print:p-0 print:border-none print:shadow-none print:rounded-none w-full max-w-full overflow-x-hidden">
        
        <!-- Brand Header -->
        <div class="flex justify-between items-end border-b border-gray-100 pb-6 mb-8">
            <div class="flex items-center gap-4">
                @if(isset($company_profile) && $company_profile->logo)
                    <img src="{{ asset($company_profile->logo) }}" alt="Logo" class="h-16 object-contain">
                @else
                    <div class="h-16 w-16 bg-primary rounded-xl flex items-center justify-center text-white">
                        <i class="bi bi-building text-3xl"></i>
                    </div>
                @endif
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-black text-primary-dark mb-1">{{ $company_profile->name ?? '' }}</h1>
                <p class="text-xs font-semibold text-gray-400">
                    Phone: {{ $company_profile->phone ?? '-' }} &bull; Email: {{ $company_profile->email ?? '-' }}
                </p>
            </div>
        </div>

        <!-- Statement Context Info & Supplier Details -->
        <div class="flex flex-col md:flex-row justify-between mb-8 gap-6">
            <div>
                <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-2">Supplier Details</h6>
                <h2 class="text-xl font-bold text-primary-dark">{{ $supplier->name }}</h2>
                <div class="text-xs font-semibold text-gray-400 mt-2 space-y-1">
                    <p>Supplier ID: <span class="text-primary">{{ $supplier->supplier_code ?? 'SUP-' . date('Y') . '-' . str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</span></p>
                    <p>Phone: <span class="text-gray-900">{{ $supplier->phone ?? '-' }}</span></p>
                </div>
            </div>
            <div class="md:text-right bg-gray-50 rounded-xl p-4 border border-gray-100 h-fit w-full sm:w-auto sm:min-w-[300px]">
                <div class="space-y-2">
                    <div class="flex justify-between gap-4 sm:gap-8 md:justify-end">
                        <span class="text-xs font-bold text-primary-dark uppercase tracking-wider">Statement Date:</span>
                        <span class="text-xs font-bold text-primary-dark">{{ \Carbon\Carbon::now()->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between gap-4 sm:gap-8 md:justify-end">
                        <span class="text-xs font-bold text-primary-dark uppercase tracking-wider">Statement #:</span>
                        <span class="text-xs font-bold text-primary-dark">STMT-{{ date('Y') }}-{{ $supplier->id }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="mb-8">
            <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-3">Transaction History</h6>
            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Transaction</th>
                            <th x-show="opts.description" class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Description</th>
                            <th x-show="opts.itemDetails" class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Item Details</th>
                            <th x-show="opts.paymentInfo" class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-center">Payment Info</th>
                            <th x-show="opts.paymentStatus" class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-right">Debit</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-right">Credit</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs font-medium text-gray-700">
                        @php
                            $transactions = collect();
                            
                            foreach($supplier->purchases ?? [] as $order) {
                                $desc = 'PO Created';
                                if(isset($order->discount_amount) && $order->discount_amount > 0) {
                                    $desc .= ' (Disc: ' . number_format($order->discount_amount, 2) . ')';
                                }

                                $transactions->push([
                                    'date' => $order->created_at,
                                    'timestamp' => $order->created_at->timestamp,
                                    'type' => 'Purchase',
                                    'ref' => $order->po_number,
                                    'description' => $desc,
                                    'item_details' => $order->items ? $order->items->count() . ' Items' : '0 Items',
                                    'payment_info' => '-',
                                    'status' => $order->status,
                                    'debit' => $order->total_amount,
                                    'credit' => 0,
                                    'download_route' => null
                                ]);
                            }
                            
                            foreach($supplier->payments ?? [] as $payment) {
                                $date = \Carbon\Carbon::parse($payment->payment_date);
                                $ts = $payment->created_at ? $payment->created_at->timestamp : $date->timestamp;
                                
                                $transactions->push([
                                    'date' => $date,
                                    'timestamp' => $ts,
                                    'type' => 'Payment',
                                    'ref' => $payment->voucher_no,
                                    'description' => 'Payment Issued',
                                    'item_details' => $payment->notes ?? '-',
                                    'payment_info' => $payment->payment_method,
                                    'status' => $payment->status,
                                    'debit' => 0,
                                    'credit' => $payment->amount,
                                    'download_route' => route('payment_out.view', $payment->id)
                                ]);
                            }
                            
                            // Let's sort all by timestamp exactly.
                            $sorted = $transactions->sortBy('timestamp');
                            
                            // Rehydrate running opening balance.
                            $totalDebits = $sorted->sum('debit');
                            $totalCredits = $sorted->sum('credit');
                            $calculatedOpening = ($supplier->amount_balance ?? 0) - $totalDebits + $totalCredits;
                            
                            $runningBalance = $calculatedOpening;
                            $totalDebit = 0;
                            $totalCredit = 0;
                        @endphp

                        @if(abs($calculatedOpening) > 0.01)
                        <tr class="bg-gray-50/50">
                            <td class="px-4 py-3 font-semibold">{{ \Carbon\Carbon::parse($supplier->created_at)->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-500">OPENING</span>
                            </td>
                            <td x-show="opts.description" class="px-4 py-3 text-gray-400 italic">Opening Balance</td>
                            <td x-show="opts.itemDetails" class="px-4 py-3 text-center text-gray-400">-</td>
                            <td x-show="opts.paymentInfo" class="px-4 py-3 text-center text-gray-400">-</td>
                            <td x-show="opts.paymentStatus" class="px-4 py-3 text-center text-gray-400">-</td>
                            <td class="px-4 py-3 text-right">
                                @if($calculatedOpening > 0) <span class="font-bold text-gray-900">{{ $symbol }}{{ number_format($calculatedOpening, 2) }}</span> @else <span class="text-gray-300">-</span> @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($calculatedOpening < 0) <span class="font-bold text-gray-900">{{ $symbol }}{{ number_format(abs($calculatedOpening), 2) }}</span> @else <span class="text-gray-300">-</span> @endif
                            </td>
                            <td class="px-4 py-3 text-right font-black text-primary-dark">
                                {{ $symbol }}{{ number_format($runningBalance, 2) }}
                            </td>
                        </tr>
                        @endif

                        @forelse($sorted as $txn)
                            @php
                                $runningBalance += $txn['debit'];
                                $runningBalance -= $txn['credit'];
                                $totalDebit += $txn['debit'];
                                $totalCredit += $txn['credit'];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-4 py-3 font-semibold">{{ $txn['date']->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $txn['type'] === 'Purchase' ? 'bg-primary/10 text-primary uppercase' : 'bg-accent/10 text-accent uppercase' }}">
                                        {{ $txn['type'] }}
                                    </span>
                                </td>
                                <td x-show="opts.description" class="px-4 py-3 whitespace-normal min-w-[200px]">
                                    <div class="flex items-center justify-between gap-4">
                                        <span>{{ $txn['description'] }} - <span class="font-bold text-primary-dark">{{ $txn['ref'] }}</span></span>
                                        @if($txn['download_route'])
                                        <div class="print:hidden opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ $txn['download_route'] }}" target="_blank" class="text-[10px] font-bold text-primary hover:underline bg-primary/5 px-2 py-1 rounded">
                                                <i class="bi bi-file-earmark-pdf mr-0.5"></i> View
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td x-show="opts.itemDetails" class="px-4 py-3">{{ $txn['item_details'] }}</td>
                                <td x-show="opts.paymentInfo" class="px-4 py-3 text-center">
                                    @if($txn['payment_info'] !== '-') 
                                        <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-600 rounded font-semibold text-[10px] uppercase">
                                            {{ $txn['payment_info'] }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td x-show="opts.paymentStatus" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border text-[10px] font-bold uppercase tracking-wider {{ $txn['status'] === 'completed' || $txn['status'] === 'success' ? 'border-accent/20 bg-accent/10 text-accent' : 'border-primary/20 bg-primary/10 text-primary' }}">
                                        {{ $txn['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($txn['debit'] > 0) <span class="font-bold text-gray-900">{{ $symbol }}{{ number_format($txn['debit'], 2) }}</span> @else <span class="text-gray-300">-</span> @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($txn['credit'] > 0) <span class="font-bold text-accent">{{ $symbol }}{{ number_format($txn['credit'], 2) }}</span> @else <span class="text-gray-300">-</span> @endif
                                </td>
                                <td class="px-4 py-3 text-right font-black text-primary-dark">
                                    {{ $symbol }}{{ number_format($runningBalance, 2) }}
                                </td>
                            </tr>
                        @empty
                            @if(abs($calculatedOpening) <= 0.01)
                            <tr>
                                <td :colspan="colspan() + 3" class="px-4 py-12 text-center text-gray-400">
                                    <i class="bi bi-inbox text-3xl mb-2 block text-gray-300"></i>
                                    <p class="text-sm font-semibold">No transactions found.</p>
                                </td>
                            </tr>
                            @endif
                        @endforelse
                        
                    </tbody>
                    <tfoot class="bg-gray-50/50 border-t border-gray-100">
                        <tr>
                            <td :colspan="colspan()" class="px-4 py-4 text-right text-xs font-bold text-primary-dark uppercase tracking-wider">TOTALS:</td>
                            <td class="px-4 py-4 text-right font-bold text-gray-900">
                                {{ $symbol }}{{ number_format($totalDebit, 2) }}
                            </td>
                            <td class="px-4 py-4 text-right font-bold text-accent">
                                {{ $symbol }}{{ number_format($totalCredit, 2) }}
                            </td>
                            <td class="px-4 py-4 text-right font-black text-xl text-primary-dark bg-white border-l border-gray-100 shadow-sm">
                                {{ $symbol }}{{ number_format($runningBalance, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Summary & Footer -->
        <div class="flex flex-col md:flex-row gap-6 mb-6">
            <div class="md:w-[40%] p-6 rounded-xl border border-gray-100 bg-gray-50/50">
                <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-4 border-b border-gray-100 pb-2"><i class="bi bi-graph-up mr-2"></i>Account Summary</h6>
                <div class="space-y-3 text-sm font-semibold text-gray-500">
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Total Transactions:</span>
                        <strong class="text-gray-800">{{ count($sorted ?? []) }}</strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Total Purchases:</span>
                        <strong class="text-gray-800">{{ $supplier->purchases ? $supplier->purchases->count() : 0 }}</strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Total Payments:</span>
                        <strong class="text-gray-800">{{ $supplier->payments ? $supplier->payments->count() : 0 }}</strong>
                    </div>
                </div>
            </div>

            <div class="md:w-[60%] flex flex-col justify-end text-center md:text-right print:text-center p-6">
                <p class="mb-2 text-primary-dark font-black text-lg">Thank you for your business!</p>
                <p class="text-xs font-semibold text-gray-400 max-w-sm md:ml-auto print:mx-auto">
                    This is a computer-generated statement and does not require a signature. <br>
                    For any queries, please contact us at <span class="text-gray-600">{{ $company_profile->email ?? '' }}</span> or call <span class="text-gray-600">{{ $company_profile->phone ?? '' }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Print Options Modal -->
    <div x-show="showPrintOptions" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm print:hidden">
        
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl flex flex-col overflow-hidden" @click.away="showPrintOptions = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5 border-b border-gray-100 pb-4">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <i class="bi bi-printer"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-primary-dark">Print Options</h3>
                        <p class="text-[11px] font-semibold text-gray-400">Select columns to display</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-sm font-semibold text-gray-600 group-hover:text-primary-dark transition-colors">Item Details</span>
                        <input type="checkbox" x-model="opts.itemDetails" class="rounded border-gray-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer transition-colors">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-sm font-semibold text-gray-600 group-hover:text-primary-dark transition-colors">Description</span>
                        <input type="checkbox" x-model="opts.description" class="rounded border-gray-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer transition-colors">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-sm font-semibold text-gray-600 group-hover:text-primary-dark transition-colors">Payment Info</span>
                        <input type="checkbox" x-model="opts.paymentInfo" class="rounded border-gray-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer transition-colors">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer group">
                        <span class="text-sm font-semibold text-gray-600 group-hover:text-primary-dark transition-colors">Payment Status</span>
                        <input type="checkbox" x-model="opts.paymentStatus" class="rounded border-gray-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer transition-colors">
                    </label>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" @click="showPrintOptions = false" class="px-5 py-2.5 bg-primary text-white font-semibold rounded-[0.5rem] hover:bg-primary/90 transition-all text-sm shadow-sm w-full">
                    Apply & Close
                </button>
            </div>
        </div>
    </div>

</div>

@endsection

