<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Expense Receipt – {{ $expense->expense_name ?? 'EXP-'.$expense->id }}</title>
    <style>
        {!! file_get_contents(public_path('frontend/assets/css/receipt-pdf.css')) !!}
    </style>
</head>
<body>
    @php
        $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
        $symbol = $currencySymbols[$company_profile->currency ?? ''] ?? ($company_profile->currency ?? '$');
    @endphp

    <div class="exp-page">
        <!-- Header -->
        <div class="exp-header">
            <table>
                <tr>
                    <td class="exp-td-60">
                        @php
                            $expLogoSrc = null;
                            if (!empty($company_profile->logo) && file_exists(public_path($company_profile->logo))) {
                                $expLogoSrc = public_path($company_profile->logo);
                            } elseif (file_exists(public_path('upload/waafibooklogo/waafibook_logo.jpg'))) {
                                $expLogoSrc = public_path('upload/waafibooklogo/waafibook_logo.jpg');
                            }
                        @endphp
                        @if($expLogoSrc)
                            <img src="{{ $expLogoSrc }}" class="exp-logo" alt="{{ $company_profile->name ?? 'Logo' }}">
                        @endif
                    </td>
                    <td class="exp-header-cell-right">
                        <h1 class="exp-company-name">{{ $company_profile->name ?? '' }}</h1>
                        <div class="exp-company-contact">
                            Phone: {{ $company_profile->phone ?? '+252615539846' }} • Email: {{ $company_profile->email ?? 'info@horntech.com' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Document Title -->
        <div class="exp-title-wrap">
            <span class="exp-doc-title">Expense Receipt</span>
        </div>

        <!-- Details -->
        <div class="exp-details-container">
            <table class="w-100">
                <tr>
                    <td class="exp-td-60">
                        <div class="section-title">EXPENSE DETAILS</div>
                        <h2 class="exp-payee-name">{{ $expense->expense_name }}</h2>
                        <div class="exp-payee-id">Reference ID: #{{ $expense->reference_no ?? 'EXP-'.$expense->id }}</div>
                        <div class="exp-payee-id">Branch: {{ $expense->branch->name ?? 'Main Branch' }}</div>
                    </td>
                    <td class="exp-td-40">
                        <div class="exp-info-box">
                            <table>
                                <tr>
                                    <td class="exp-info-label">RECEIPT DATE:</td>
                                    <td class="exp-info-value">{{ \Carbon\Carbon::parse($expense->expense_date)->format('M j, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="exp-info-label">Voucher #:</td>
                                    <td class="exp-info-value">#{{ $expense->id }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">TRANSACTION HISTORY</div>
        <table class="transactions">
            <thead>
                <tr>
                    <th class="col-12">DATE</th>
                    <th class="col-15">TRANSACTION</th>
                    <th class="col-25">DESCRIPTION</th>
                    <th class="col-15">ITEM DETAILS</th>
                    <th class="col-15">PAYMENT INFO</th>
                    <th class="col-10">STATUS</th>
                    <th class="col-10 text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('M d, Y') }}</td>
                    <td><span class="badge">EXPENSE</span></td>
                    <td class="text-gray font-italic">{{ $expense->description }}</td>
                    <td class="center">-</td>
                    <td>{{ $expense->payment_method }} <br><span class="exp-tiny-gray">{{ $expense->bankAccount->name ?? '' }}</span></td>
                    <td class="center">-</td>
                    <td class="text-right font-bold">{{ $symbol }}{{ number_format($expense->amount, 2) }}</td>
                </tr>
                <tr class="exp-totals-row">
                    <td colspan="6" class="exp-totals-label">TOTALS:</td>
                    <td class="exp-totals-val">{{ $symbol }}{{ number_format($expense->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summary & Footer -->
        <table class="exp-bottom-grid">
            <tr>
                <td class="exp-td-half-top">
                    <div class="exp-summary-card">
                        <div class="section-title">
                            📋 ACCOUNT SUMMARY
                        </div>
                        <div class="exp-summary-item">
                            <span class="exp-summary-label">Expense Category</span>
                            <span class="exp-summary-val">{{ $expense->account->name ?? 'General' }}</span>
                        </div>
                        <div class="exp-summary-item">
                            <span class="exp-summary-label">Recorded By</span>
                            <span class="exp-summary-val">{{ $expense->createdBy->name ?? 'System' }}</span>
                        </div>
                        <div class="exp-summary-item exp-summary-item-last">
                            <span class="exp-summary-label">Approval Status</span>
                            <span class="exp-summary-val text-green">Approved</span>
                        </div>
                    </div>
                </td>
                <td class="exp-td-half-bottom">
                    <div class="exp-footer-thanks">
                        <div class="exp-thanks-bold">Thank you for your business!</div>
                        <p class="exp-disclaimer-text">
                            This is a computer-generated statement and does not require a signature. <br>
                            For any queries, please contact us at <span class="exp-support-contact">{{ $company_profile->email ?? 'info@horntech.com' }}</span> or call <span class="exp-support-contact">{{ $company_profile->phone ?? '+252615539846' }}</span>
                        </p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
