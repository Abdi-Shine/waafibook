<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoiceNo }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#374151; background:#fff; }

        /* ── Header ── */
        .header { background:#004161; padding:22px 28px; width:100%; }
        .header-table { width:100%; border-collapse:collapse; }
        .brand-logo { width:46px; height:46px; background:#ffffff; border-radius:10px; vertical-align:middle; }
        .brand-name { color:#ffffff; font-size:15px; font-weight:700; display:block; margin-top:2px; }
        .brand-sub  { color:#99CC33; font-size:8px; font-weight:700; text-transform:uppercase; letter-spacing:1px; display:block; }
        .inv-label  { color:rgba(255,255,255,0.6); font-size:8px; text-transform:uppercase; letter-spacing:1px; text-align:right; }
        .inv-num    { color:#ffffff; font-size:20px; font-weight:700; text-align:right; display:block; margin-top:3px; }
        .inv-date   { color:rgba(255,255,255,0.6); font-size:10px; text-align:right; display:block; margin-top:4px; }

        /* ── Accent strip ── */
        .accent { height:5px; background:#99CC33; width:100%; }

        /* ── Body ── */
        .body { padding:28px 30px; }

        /* Parties */
        .parties-table { width:100%; border-collapse:collapse; margin-bottom:22px; }
        .party-label { font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; padding-bottom:6px; display:block; }
        .party-name  { font-size:13px; font-weight:700; color:#111827; display:block; margin-bottom:3px; }
        .party-info  { font-size:10px; color:#6b7280; line-height:1.6; }

        /* Meta grid — using table */
        .meta-box { background:#f9fafb; border-radius:8px; padding:14px 16px; margin-bottom:22px; width:100%; }
        .meta-table { width:100%; border-collapse:collapse; }
        .meta-lbl { font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; padding-bottom:4px; display:block; }
        .meta-val { font-size:12px; font-weight:700; color:#111827; display:block; }

        /* Items table */
        .items-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        .items-table thead th { background:#004161; color:#fff; font-size:8px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:8px 10px; text-align:left; }
        .items-table thead th.right { text-align:right; }
        .items-table tbody td { padding:10px 10px; font-size:11px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:top; }
        .items-table tbody td.right { text-align:right; font-weight:700; color:#111827; }
        .item-name { font-weight:700; color:#111827; font-size:12px; }
        .item-desc { font-size:10px; color:#9ca3af; margin-top:2px; }

        /* Totals */
        .totals-table { width:240px; border-collapse:collapse; margin-left:auto; margin-bottom:16px; }
        .totals-table td { padding:5px 0; font-size:11px; color:#6b7280; border-bottom:1px solid #f3f4f6; }
        .totals-table td.right { text-align:right; }
        .totals-table tr.total td { border-bottom:none; padding-top:10px; font-size:13px; font-weight:700; color:#111827; }
        .totals-table tr.total td.right { color:#004161; }

        /* Status badge */
        .paid-badge   { background:#dcfce7; color:#15803d; padding:5px 14px; border-radius:20px; font-size:10px; font-weight:700; display:inline-block; }
        .unpaid-badge { background:#fee2e2; color:#b91c1c; padding:5px 14px; border-radius:20px; font-size:10px; font-weight:700; display:inline-block; }
        .badge-row { text-align:right; margin-bottom:20px; }

        /* Footer */
        .footer { border-top:2px solid #f3f4f6; padding-top:16px; width:100%; }
        .footer-table { width:100%; border-collapse:collapse; }
        .footer-note  { font-size:10px; color:#9ca3af; line-height:1.7; }
        .footer-brand { font-size:9px; color:#9ca3af; text-align:right; line-height:1.7; }
        .footer-brand strong { color:#004161; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td style="vertical-align:middle; width:60%;">
                <table><tr>
                    <td style="vertical-align:middle; padding-right:10px;">
                        <img src="{{ public_path('icons/icon-192.png') }}"
                             width="42" height="42"
                             style="border-radius:8px; background:#fff; display:block;">
                    </td>
                    <td style="vertical-align:middle;">
                        <span class="brand-name">Waafibook</span>
                        <span class="brand-sub">Platform Provider</span>
                    </td>
                </tr></table>
            </td>
            <td style="vertical-align:top; text-align:right;">
                <span class="inv-label">Subscription Invoice</span>
                <span class="inv-num">{{ $invoiceNo }}</span>
                <span class="inv-date">Issued: {{ now()->format('d M Y') }}</span>
            </td>
        </tr>
    </table>
</div>
<div class="accent"></div>

<div class="body">

    {{-- Parties --}}
    <table class="parties-table">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <span class="party-label">From</span>
                <span class="party-name">Waafibook</span>
                <span class="party-info">Subscription &amp; Billing Department<br>support@waafibook.com</span>
            </td>
            <td style="width:50%; vertical-align:top; text-align:right;">
                <span class="party-label">Billed To</span>
                <span class="party-name">{{ $subscription->company->name ?? '—' }}</span>
                <span class="party-info">
                    {{ $subscription->company->email ?? '' }}<br>
                    {{ $subscription->company->phone ?? '' }}<br>
                    {{ $subscription->company->country ?? '' }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Meta --}}
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width:33%; vertical-align:top;">
                    <span class="meta-lbl">Invoice No.</span>
                    <span class="meta-val">{{ $invoiceNo }}</span>
                </td>
                <td style="width:33%; vertical-align:top;">
                    <span class="meta-lbl">Plan</span>
                    <span class="meta-val">{{ $subscription->plan->name ?? '—' }}</span>
                </td>
                <td style="width:33%; vertical-align:top;">
                    <span class="meta-lbl">Status</span>
                    <span class="meta-val" style="color:{{ $subscription->status === 'active' ? '#15803d' : ($subscription->status === 'trial' ? '#1d4ed8' : '#b91c1c') }};">
                        {{ ucfirst($subscription->status) }}
                    </span>
                </td>
            </tr>
            <tr><td colspan="3" style="padding-top:10px;"></td></tr>
            <tr>
                <td style="vertical-align:top;">
                    <span class="meta-lbl">Start Date</span>
                    <span class="meta-val">{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '—' }}</span>
                </td>
                <td style="vertical-align:top;">
                    <span class="meta-lbl">Expiry Date</span>
                    <span class="meta-val">{{ $subscription->expiry_date ? \Carbon\Carbon::parse($subscription->expiry_date)->format('d M Y') : '—' }}</span>
                </td>
                <td style="vertical-align:top;">
                    <span class="meta-lbl">Billing</span>
                    <span class="meta-val">{{ ucfirst($subscription->plan->billing_cycle ?? 'monthly') }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Items --}}
    @php
        $payments      = $subscription->payments->where('status','completed')->sortBy('payment_date');
        $planPrice     = $subscription->plan->price ?? 0;
        $planName      = $subscription->plan->name  ?? 'Subscription Plan';
        $totalPaid     = $payments->sum('amount');
        $invoiceTotal  = $totalPaid > 0 ? $totalPaid : $planPrice;
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:30%;">Description</th>
                <th style="width:25%;">Payment Date</th>
                <th style="width:20%;">Method</th>
                <th class="right" style="width:20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $i => $pmt)
            <tr>
                <td style="color:#9ca3af; font-size:10px;">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td>
                    <div class="item-name">{{ $planName }}</div>
                    @if($pmt->transaction_id)
                    <div class="item-desc">Ref: {{ $pmt->transaction_id }}</div>
                    @endif
                </td>
                <td style="color:#6b7280; font-size:11px;">{{ \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') }}</td>
                <td style="color:#6b7280; font-size:11px;">{{ $pmt->payment_method ?? '—' }}</td>
                <td class="right">${{ number_format($pmt->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td style="color:#9ca3af; font-size:10px;">01</td>
                <td>
                    <div class="item-name">{{ $planName }}</div>
                    @if($subscription->plan?->description)
                    <div class="item-desc">{{ $subscription->plan->description }}</div>
                    @endif
                </td>
                <td style="color:#6b7280; font-size:11px;">
                    {{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '—' }}
                    &rarr;
                    {{ $subscription->expiry_date ? \Carbon\Carbon::parse($subscription->expiry_date)->format('d M Y') : '—' }}
                </td>
                <td style="color:#9ca3af; font-size:11px;">—</td>
                <td class="right">${{ number_format($planPrice, 2) }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    @php $amount = $invoiceTotal; @endphp
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="right">${{ number_format($amount, 2) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="right">$0.00</td>
        </tr>
        <tr class="total">
            <td>Total</td>
            <td class="right">${{ number_format($amount, 2) }}</td>
        </tr>
    </table>

    {{-- Badge --}}
    <div class="badge-row">
        @if($lastPayment)
            <span class="paid-badge">
                &#10003; PAID &mdash; {{ $lastPayment->payment_method }} on {{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') }}
                @if($lastPayment->transaction_id) &middot; Ref: {{ $lastPayment->transaction_id }} @endif
            </span>
        @else
            <span class="unpaid-badge">&#9888; PAYMENT PENDING</span>
        @endif
    </div>

    {{-- Footer --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-note">
                    Thank you for choosing Waafibook.<br>
                    For billing enquiries: support@waafibook.com
                </td>
                <td class="footer-brand">
                    <strong>Waafibook</strong> Subscription Platform<br>
                    Generated {{ now()->format('d M Y, H:i') }}
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
