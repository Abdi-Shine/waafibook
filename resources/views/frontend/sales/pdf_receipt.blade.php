<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $payment->receipt_no }}</title>
    <style>
        {!! file_get_contents(public_path('frontend/assets/css/receipt-pdf.css')) !!}
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="watermark">PAID</div>
        
        <div class="receipt-header">
            <div class="receipt-badge">Payment Receipt</div>
            <h1 class="receipt-title uppercase">Official Confirmation</h1>
            <p class="receipt-subtitle">Thank you for choosing {{ $company->name ?? '' }}</p>
        </div>

        <div class="company-bar">
            <div class="company-info-cell">
                <div class="company-logo-placeholder has-logo">
                    @php
                        // Resolve logo path: company logo or fallback to WaafiBook logo
                        $receiptLogoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
                            ? public_path($company->logo)
                            : public_path('upload/waafibooklogo/waafibook_logo.jpg');
                        $receiptLogoExt  = pathinfo($receiptLogoPath, PATHINFO_EXTENSION) ?: 'jpg';
                        $receiptLogoB64  = file_exists($receiptLogoPath)
                            ? 'data:image/' . $receiptLogoExt . ';base64,' . base64_encode(file_get_contents($receiptLogoPath))
                            : null;
                    @endphp
                    @if($receiptLogoB64)
                        <img src="{{ $receiptLogoB64 }}" class="company-logo">
                    @endif
                </div>
                <div class="inline-block vertical-middle">
                    <div class="company-name text-primary uppercase">{{ $company->name ?? '' }}</div>
                    <div class="company-tagline">{{ $company->company_tagline ?? 'Your Trusted Business Partner' }}</div>
                </div>
            </div>
            <div class="company-info-cell text-right">
                <div class="company-tax-label">Tax ID: {{ $company->tax_id ?? 'N/A' }}</div>
                <div class="company-tax-value">{{ $company->address ?? 'Mogadishu, Somalia' }}</div>
            </div>
        </div>

        <table class="w-100 receipt-info">
            <tr>
                <td width="50%">
                    <div class="receipt-number">#{{ $payment->receipt_no }}</div>
                    <div class="receipt-date">Issued on: {{ date('d M, Y', strtotime($payment->payment_date)) }}</div>
                </td>
                <td width="50%">
                    <table align="right">
                        <tr>
                            <td>
                                <div class="info-box info-box-mr">
                                    <div class="info-label">Status</div>
                                    <div class="info-value status-cleared uppercase">{{ $payment->status }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="info-box">
                                    <div class="info-label">Terminal</div>
                                    <div class="info-value uppercase">01-WEB</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="customer-card">
            <div class="section-title">Received From</div>
            <div class="customer-name uppercase">{{ $payment->customer->name ?? 'WALK-IN CUSTOMER' }}</div>
            <table class="customer-details-table">
                <tr class="detail-row">
                    <td width="20%">Phone:</td>
                    <td><strong>{{ $payment->customer->phone ?? 'N/A' }}</strong></td>
                </tr>
                <tr class="detail-row">
                    <td width="20%">Email:</td>
                    <td><strong>{{ $payment->customer->email ?? 'N/A' }}</strong></td>
                </tr>
                <tr class="detail-row">
                    <td width="20%">Payment Mode:</td>
                    <td><strong>{{ $payment->payment_method }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="payment-amount-card">
            <div class="payment-total-label">Total Amount Received</div>
            <div class="amount-value">
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
                {{ $symbol }} {{ number_format($payment->amount, 2) }}
            </div>
            <div class="payment-total-note">
                Amount recognized in {{ $company->currency ?? 'USD' }} currency.
            </div>
        </div>

        <div class="section-title">Settlement Details</div>
        <table class="invoices-table">
            <thead>
                <tr>
                    <th>Reference / Invoice</th>
                    <th>Payment Date</th>
                    <th>Payment Category</th>
                    <th class="right">Amount Settled</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->invoice_no ?? 'Direct Payment' }}</td>
                    <td>{{ date('d-m-Y', strtotime($payment->payment_date)) }}</td>
                    <td>{{ $payment->payment_method }} Payment</td>
                    <td class="amount-paid-cell">{{ $symbol }} {{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="clearfix">
            <div class="summary-table">
                <div class="summary-row">
                    <div class="summary-cell summary-label text-primary uppercase">Subtotal</div>
                    <div class="summary-cell summary-value text-primary font-bold">{{ $symbol }} {{ number_format($payment->amount, 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell summary-label text-primary uppercase">Tax Applied</div>
                    <div class="summary-cell summary-value text-primary font-bold">{{ $symbol }} 0.00</div>
                </div>
                <div class="summary-row total-credit-row">
                    <div class="summary-cell summary-label text-accent font-black uppercase">Grand Total</div>
                    <div class="summary-cell total-credit-value text-accent font-black">{{ $symbol }} {{ number_format($payment->amount, 2) }}</div>
                </div>
            </div>
        </div>

        @if($payment->notes)
            <div class="confirmation-box">
                <div class="confirmation-title">Additional Notes</div>
                <div class="confirmation-text">
                    {{ $payment->notes }}
                </div>
            </div>
        @endif

        <table class="signature-table">
            <tr>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name uppercase">{{ $payment->creator->name ?? 'AUTHORIZED PERSON' }}</div>
                    <div class="signature-role">Generated By</div>
                </td>
                <td width="10%"></td>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name uppercase">{{ $payment->customer->name ?? 'CUSTOMER' }}</div>
                    <div class="signature-role">Customer Signature</div>
                </td>
            </tr>
        </table>

        <div class="footer-meta">
            This is a computer-generated receipt. Generated on {{ date('d M, Y H:i A') }} • {{ $company->name ?? 'Waafibook' }}
        </div>
    </div>
</body>
</html>

