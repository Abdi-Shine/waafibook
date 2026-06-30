<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Supplier Statement</title>
    <style>
        {!! file_get_contents(public_path('frontend/assets/css/receipt-pdf.css')) !!}
    </style>
</head>
<body>
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

    <div class="stmt-header">
        <div class="stmt-header-left">
            @if(isset($company_profile) && $company_profile->logo)
                <img src="{{ public_path($company_profile->logo) }}" class="logo-h50" alt="Logo">
            @else
                <h1 class="stmt-title">{{ $company_profile->name ?? '' }}</h1>
            @endif
        </div>
        <div class="stmt-header-right">
            @if(isset($company_profile) && $company_profile->logo)
                <h1 class="stmt-title">{{ $company_profile->name ?? '' }}</h1>
            @endif
            <div class="stmt-company-info">
                Phone: {{ $company_profile->phone ?? '-' }} | Email: {{ $company_profile->email ?? '-' }}
            </div>
        </div>
    </div>

    <table class="stmt-party-card">
        <tr>
            <td class="stmt-td-60">
                <div class="label">Supplier Details</div>
                <h2 class="stmt-party-name">{{ $supplier->name }}</h2>
                <div class="stmt-party-detail">
                    Supplier ID: {{ $supplier->supplier_code ?? 'SUP-' . date('Y') . '-' . str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}<br>
                    Phone: {{ $supplier->phone ?? '-' }}
                </div>
            </td>
            <td class="stmt-td-40">
                <table class="stmt-meta-table">
                    <tr>
                        <td class="label stmt-meta-pb">Statement Date:</td>
                        <td class="text-right font-bold text-primary stmt-meta-pb">{{ \Carbon\Carbon::now()->format('M j, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Statement #:</td>
                        <td class="text-right font-bold text-primary">STMT-{{ date('Y') }}-{{ $supplier->id }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="label stmt-label-mb">Transaction History</div>
    <table class="transactions">
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction</th>
                <th>Description</th>
                <th class="text-center">Status</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
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
                        'status' => $order->status,
                        'debit' => $order->total_amount,
                        'credit' => 0
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
                        'status' => $payment->status,
                        'debit' => 0,
                        'credit' => $payment->amount
                    ]);
                }

                $sorted = $transactions->sortBy('timestamp');

                $totalDebits = $sorted->sum('debit');
                $totalCredits = $sorted->sum('credit');
                $calculatedOpening = ($supplier->amount_balance ?? 0) - $totalDebits + $totalCredits;

                $runningBalance = $calculatedOpening;
                $totalDebit = 0;
                $totalCredit = 0;
            @endphp

            @if(abs($calculatedOpening) > 0.01)
            <tr class="bg-gray">
                <td>{{ \Carbon\Carbon::parse($supplier->created_at)->format('M j, Y') }}</td>
                <td><span class="badge">OPENING</span></td>
                <td class="text-gray font-italic">Opening Balance</td>
                <td class="text-center text-gray">-</td>
                <td class="text-right font-bold">@if($calculatedOpening > 0) {{ $symbol }}{{ number_format($calculatedOpening, 2) }} @else - @endif</td>
                <td class="text-right font-bold">@if($calculatedOpening < 0) {{ $symbol }}{{ number_format(abs($calculatedOpening), 2) }} @else - @endif</td>
                <td class="text-right font-bold text-primary">{{ $symbol }}{{ number_format($runningBalance, 2) }}</td>
            </tr>
            @endif

            @forelse($sorted as $txn)
                @php
                    $runningBalance += $txn['debit'];
                    $runningBalance -= $txn['credit'];
                    $totalDebit += $txn['debit'];
                    $totalCredit += $txn['credit'];
                @endphp
                <tr>
                    <td>{{ $txn['date']->format('M j, Y') }}</td>
                    <td><span class="badge {{ $txn['type'] === 'Purchase' ? 'badge-primary' : 'badge-success' }}">{{ $txn['type'] }}</span></td>
                    <td>{{ $txn['description'] }} <br><span class="text-gray stmt-ref">{{ $txn['ref'] }}</span></td>
                    <td class="text-center"><span class="badge text-gray">{{ $txn['status'] }}</span></td>
                    <td class="text-right font-bold">@if($txn['debit'] > 0) {{ $symbol }}{{ number_format($txn['debit'], 2) }} @else <span class="text-gray">-</span> @endif</td>
                    <td class="text-right font-bold text-accent">@if($txn['credit'] > 0) {{ $symbol }}{{ number_format($txn['credit'], 2) }} @else <span class="text-gray">-</span> @endif</td>
                    <td class="text-right font-bold text-primary">{{ $symbol }}{{ number_format($runningBalance, 2) }}</td>
                </tr>
            @empty
                @if(abs($calculatedOpening) <= 0.01)
                <tr>
                    <td colspan="7" class="text-center text-gray stmt-empty-cell">
                        No transactions found.
                    </td>
                </tr>
                @endif
            @endforelse

            <tr class="stmt-totals bg-gray">
                <td colspan="4" class="text-right label">TOTALS:</td>
                <td class="text-right font-bold">{{ $symbol }}{{ number_format($totalDebit, 2) }}</td>
                <td class="text-right font-bold text-accent">{{ $symbol }}{{ number_format($totalCredit, 2) }}</td>
                <td class="text-right font-bold text-primary stmt-balance-total">{{ $symbol }}{{ number_format($runningBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="stmt-bottom-table">
        <tr>
            <td class="stmt-bottom-left">
                <div class="summary-box">
                    <div class="label stmt-summary-box-label">Account Summary</div>
                    <div class="summary-line">
                        <span class="stmt-summary-label text-gray">Total Transactions:</span>
                        <span class="summary-val">{{ ($supplier->payments ? $supplier->payments->count() : 0) + ($supplier->purchases ? $supplier->purchases->count() : 0) }}</span>
                    </div>
                    <div class="summary-line">
                        <span class="stmt-summary-label text-gray">Total Purchases:</span>
                        <span class="summary-val">{{ $supplier->purchases ? $supplier->purchases->count() : 0 }}</span>
                    </div>
                    <div class="summary-line stmt-summary-border-0">
                        <span class="stmt-summary-label text-gray">Total Payments:</span>
                        <span class="summary-val">{{ $supplier->payments ? $supplier->payments->count() : 0 }}</span>
                    </div>
                </div>
            </td>
            <td class="stmt-bottom-right">
                <div class="stmt-title stmt-title-sm">Thank you for your business!</div>
                <p class="stmt-company-info-mt">
                    This is a computer-generated statement and does not require a signature. <br>
                    For any queries, please contact us at {{ $company_profile->email ?? '' }} or call {{ $company_profile->phone ?? '' }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
