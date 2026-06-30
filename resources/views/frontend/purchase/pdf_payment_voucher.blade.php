<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $payment->voucher_no }}</title>
    <style>
        {!! file_get_contents(public_path('frontend/assets/css/receipt-pdf.css')) !!}
        :root {
            --primary-color: #004161;
            --accent-color: #aadb40;
            --payment-out-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        @if($payment->status == 'completed')
            <div class="watermark">PAID</div>
        @elseif($payment->status == 'pending')
            <div class="watermark" style="color: rgba(255, 193, 7, 0.05);">PENDING</div>
        @endif
        
        <div class="receipt-header">
            <div class="receipt-badge" style="background: var(--payment-out-color); color: white;">Payment Voucher</div>
            <h1 class="receipt-title uppercase">Official Payment</h1>
            <p class="receipt-subtitle">Voucher for supplier payment authorization</p>
        </div>

        <div class="company-bar">
            <div class="company-info-cell">
                <div class="company-logo-placeholder" style="{{ ($company && $company->logo) ? 'background: transparent; line-height: 0;' : '' }}">
                    @if($company && $company->logo)
                        @php
                            $logoPath = $company->logo;
                            if (!str_starts_with($logoPath, 'uploads/')) {
                                $logoPath = 'uploads/company/' . $logoPath;
                            }
                            $fullPath = public_path($logoPath);
                        @endphp
                        @if(file_exists($fullPath))
                            @php
                                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                                $data = file_get_contents($fullPath);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <img src="{{ $base64 }}" class="company-logo" style="width: 60px; height: 60px; border-radius: 12px;">
                        @elseif($company)
                            {{ strtoupper(substr($company->name ?? 'B', 0, 1)) }}
                        @else
                            B
                        @endif
                    @elseif($company)
                        {{ strtoupper(substr($company->name ?? 'B', 0, 1)) }}
                    @else
                        B
                    @endif
                </div>
                <div class="inline-block vertical-middle">
                    <div class="company-name uppercase">{{ $company->name ?? '' }}</div>
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
                    <div class="receipt-number">{{ $payment->voucher_no }}</div>
                    <div class="receipt-date">Issued on: {{ date('d M, Y', strtotime($payment->payment_date)) }}</div>
                </td>
                <td width="50%">
                    <table align="right">
                        <tr>
                            <td>
                                <div class="info-box" style="margin-right: 10px;">
                                    <div class="info-label">Status</div>
                                    <div class="info-value uppercase {{ $payment->status == 'completed' ? 'status-cleared' : '' }}">{{ $payment->status }}</div>
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
            <div class="section-title">Pay To (Supplier)</div>
            <div class="customer-name uppercase">{{ $payment->supplier->name ?? 'N/A' }}</div>
            <table class="customer-details-table">
                <tr class="detail-row">
                    <td width="20%">Supplier Code:</td>
                    <td><strong>{{ $payment->supplier->supplier_code ?? 'N/A' }}</strong></td>
                </tr>
                <tr class="detail-row">
                    <td width="20%">Phone:</td>
                    <td><strong>{{ $payment->supplier->phone ?? 'N/A' }}</strong></td>
                </tr>
                <tr class="detail-row">
                    <td width="20%">Payment Mode:</td>
                    <td><strong>{{ $payment->payment_method }} Payment</strong></td>
                </tr>
            </table>
        </div>

        <div class="payment-amount-card" style="background: var(--primary-color);">
            <div class="payment-total-label">Total Amount Paid</div>
            <div class="amount-value">
                @php
                    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
                    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
                @endphp
                {{ $symbol }} {{ number_format($payment->amount, 2) }}
            </div>
            <div class="payment-total-note">
                Amount debited from accounts in {{ $company->currency ?? 'USD' }}.
            </div>
        </div>

        <div class="section-title">Payment Breakdown</div>
        <table class="invoices-table">
            <thead>
                <tr>
                    <th>Reference / Bill</th>
                    <th>Payment Date</th>
                    <th>Category</th>
                    <th style="text-align: right">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->reference ?? 'Direct Payment' }}</td>
                    <td>{{ date('d-m-Y', strtotime($payment->payment_date)) }}</td>
                    <td>Supplier Payment</td>
                    <td class="amount-paid-cell">{{ $symbol }} {{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="clearfix">
            <div class="summary-table">
                <div class="summary-row">
                    <div class="summary-cell summary-label uppercase">Subtotal</div>
                    <div class="summary-cell summary-value">{{ $symbol }} {{ number_format($payment->amount, 2) }}</div>
                </div>
                <div class="summary-row total-credit-row">
                    <div class="summary-cell summary-label font-black uppercase">Grand Total</div>
                    <div class="summary-cell total-credit-value font-black">{{ $symbol }} {{ number_format($payment->amount, 2) }}</div>
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
                    <div class="signature-name uppercase">{{ $payment->creator->name ?? 'ACCOUNTANT' }}</div>
                    <div class="signature-role">Prepared By</div>
                </td>
                <td width="10%"></td>
                <td class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name uppercase">_________________</div>
                    <div class="signature-role">Manager Approval</div>
                </td>
            </tr>
        </table>

        <div class="footer-meta">
            This is a computer-generated voucher. Generated on {{ date('d M, Y H:i A') }} • {{ $company->name ?? 'Waafibook' }}
        </div>
    </div>
</body>
</html>

