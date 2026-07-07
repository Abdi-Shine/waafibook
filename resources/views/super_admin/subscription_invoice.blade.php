<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoiceNo }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f8; min-height:100vh; display:flex; flex-direction:column; align-items:center; padding:2rem 1rem; }

        .inv-wrap { width:100%; max-width:780px; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 32px rgba(0,65,97,.10); }

        /* ── Header bar ── */
        .inv-header { background:#004161; padding:2rem 2.5rem; display:flex; justify-content:space-between; align-items:flex-start; }
        .inv-brand { display:flex; align-items:center; gap:.75rem; }
        .inv-logo  { width:44px; height:44px; background:#fff; border-radius:10px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .inv-logo img { width:100%; height:100%; object-fit:contain; }
        .inv-brand-text h2 { color:#fff; font-size:1.1rem; font-weight:800; line-height:1.1; }
        .inv-brand-text p  { color:#99CC33; font-size:.65rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .inv-title-block { text-align:right; }
        .inv-title-block .label { color:rgba(255,255,255,.55); font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .inv-title-block .num   { color:#fff; font-size:1.5rem; font-weight:900; letter-spacing:.02em; margin-top:.15rem; }
        .inv-title-block .date  { color:rgba(255,255,255,.6); font-size:.78rem; font-weight:500; margin-top:.3rem; }

        /* ── Accent strip ── */
        .inv-accent { height:5px; background:linear-gradient(to right,#99CC33,#7aaa1e); }

        /* ── Body ── */
        .inv-body { padding:2.5rem; }

        /* Bill to / From */
        .inv-parties { display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem; }
        .party-block h4 { font-size:.62rem; font-weight:800; color:#9ca3af; text-transform:uppercase; letter-spacing:.1em; margin-bottom:.6rem; }
        .party-block .name { font-size:.97rem; font-weight:800; color:#111827; margin-bottom:.25rem; }
        .party-block .detail { font-size:.8rem; color:#6b7280; line-height:1.6; }

        /* Meta grid */
        .inv-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; background:#f9fafb; border-radius:10px; padding:1.25rem 1.5rem; margin-bottom:2rem; }
        .meta-item .lbl { font-size:.62rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.3rem; }
        .meta-item .val { font-size:.875rem; font-weight:700; color:#111827; }

        /* Items table */
        .inv-table { width:100%; border-collapse:collapse; margin-bottom:2rem; }
        .inv-table thead th { background:#004161; color:#fff; font-size:.65rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:.7rem 1rem; text-align:left; }
        .inv-table thead th:last-child { text-align:right; }
        .inv-table tbody td { padding:.85rem 1rem; font-size:.85rem; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .inv-table tbody td:last-child { text-align:right; font-weight:700; color:#111827; }
        .inv-table tbody tr:last-child td { border-bottom:none; }

        /* Totals */
        .inv-totals { display:flex; justify-content:flex-end; margin-bottom:2rem; }
        .totals-box { width:260px; }
        .totals-row { display:flex; justify-content:space-between; padding:.45rem 0; font-size:.85rem; color:#6b7280; border-bottom:1px solid #f3f4f6; }
        .totals-row.total { border-bottom:none; padding-top:.75rem; font-size:1.05rem; font-weight:900; color:#111827; }
        .totals-row .t-lbl { font-weight:600; }
        .totals-row.total .t-amt { color:#004161; }

        /* Status badge */
        .inv-status-row { display:flex; justify-content:flex-end; margin-bottom:2.5rem; }
        .status-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem 1rem; border-radius:30px; font-size:.75rem; font-weight:700; }
        .status-badge.paid { background:#dcfce7; color:#15803d; }
        .status-badge.unpaid { background:#fee2e2; color:#b91c1c; }

        /* Footer */
        .inv-footer { border-top:2px solid #f3f4f6; padding-top:1.5rem; display:flex; justify-content:space-between; align-items:center; }
        .inv-footer .note { font-size:.75rem; color:#9ca3af; }
        .inv-footer .brand-note { font-size:.7rem; color:#9ca3af; text-align:right; }
        .inv-footer .brand-note strong { color:#004161; }

        /* Actions bar (screen only) */
        .inv-actions { width:100%; max-width:780px; display:flex; justify-content:flex-end; gap:.75rem; margin-bottom:1rem; }
        .btn-back    { padding:.55rem 1.25rem; border-radius:8px; border:1px solid #e5e7eb; background:#fff; color:#374151; font-size:.85rem; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
        .btn-print   { padding:.55rem 1.25rem; border-radius:8px; border:none; background:#004161; color:#fff; font-size:.85rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; }
        .btn-print:hover { background:#002d47; }

        @media print {
            body { background:#fff; padding:0; }
            .inv-actions { display:none; }
            .inv-wrap { box-shadow:none; border-radius:0; }
        }
    </style>
</head>
<body>

<div class="inv-actions">
    <a href="{{ route('host.subscriptions') }}" class="btn-back">
        ← Back to Subscriptions
    </a>
    <a href="{{ route('host.subscriptions.invoice.pdf', $subscription->id) }}" class="btn-print">
        ⬇ Download PDF
    </a>
</div>

<div class="inv-wrap">

    {{-- Header --}}
    <div class="inv-header">
        <div class="inv-brand">
            <div class="inv-logo">
                <img src="/icons/icon-192.png" alt="Logo" style="width:48px;height:48px;object-fit:contain;border-radius:8px;">
            </div>
            <div class="inv-brand-text">
                <h2>Waafibook</h2>
                <p>Platform Provider</p>
            </div>
        </div>
        <div class="inv-title-block">
            <div class="label">Subscription Invoice</div>
            <div class="num">{{ $invoiceNo }}</div>
            <div class="date">Issued: {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="inv-accent"></div>

    <div class="inv-body">

        {{-- Parties --}}
        <div class="inv-parties">
            <div class="party-block">
                <h4>From</h4>
                <div class="name">Waafibook</div>
                <div class="detail">
                    Subscription &amp; Billing Department<br>
                    support@waafibook.com
                </div>
            </div>
            <div class="party-block" style="text-align:right;">
                <h4>Billed To</h4>
                <div class="name">{{ $subscription->company->name ?? '—' }}</div>
                <div class="detail">
                    {{ $subscription->company->email ?? '' }}<br>
                    {{ $subscription->company->phone ?? '' }}<br>
                    {{ $subscription->company->country ?? '' }}
                </div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="inv-meta">
            <div class="meta-item">
                <div class="lbl">Invoice No.</div>
                <div class="val">{{ $invoiceNo }}</div>
            </div>
            <div class="meta-item">
                <div class="lbl">Plan</div>
                <div class="val">{{ $subscription->plan->name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="lbl">Status</div>
                <div class="val" style="color:{{ $subscription->status === 'active' ? '#15803d' : ($subscription->status === 'trial' ? '#1d4ed8' : '#b91c1c') }};">
                    {{ ucfirst($subscription->status) }}
                </div>
            </div>
            <div class="meta-item">
                <div class="lbl">Start Date</div>
                <div class="val">{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="lbl">Expiry Date</div>
                <div class="val">{{ $subscription->expiry_date ? \Carbon\Carbon::parse($subscription->expiry_date)->format('d M Y') : '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="lbl">Billing</div>
                <div class="val">{{ ucfirst($subscription->plan->billing_cycle ?? 'monthly') }}</div>
            </div>
        </div>

        {{-- Items: current plan as single line item --}}
        @php
            $planPrice = (float) ($subscription->plan->price ?? 0);
            $planName  = $subscription->plan->name ?? 'Subscription Plan';
        @endphp
        <table class="inv-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Period</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="color:#9ca3af;font-size:.78rem;">01</td>
                    <td>
                        <div style="font-weight:700;color:#111827;">{{ $planName }}</div>
                        @if($subscription->plan?->description)
                        <div style="font-size:.75rem;color:#9ca3af;margin-top:.15rem;">{{ $subscription->plan->description }}</div>
                        @endif
                    </td>
                    <td style="color:#6b7280;">
                        {{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') : '—' }}
                        →
                        <span style="color:#004161;font-weight:600;">{{ $subscription->expiry_date ? \Carbon\Carbon::parse($subscription->expiry_date)->format('d M Y') : '—' }}</span>
                    </td>
                    <td>${{ number_format($planPrice, 2) }}</td>
                    <td>${{ number_format($planPrice, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="inv-totals">
            <div class="totals-box">
                <div class="totals-row">
                    <span class="t-lbl">Subtotal</span>
                    <span>${{ number_format($planPrice, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span class="t-lbl">Tax</span>
                    <span>$0.00</span>
                </div>
                <div class="totals-row total">
                    <span class="t-lbl">Total</span>
                    <span class="t-amt">${{ number_format($planPrice, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Payment status --}}
        <div class="inv-status-row">
            @if($lastPayment)
                <span class="status-badge paid">
                    ✓ PAID — {{ $lastPayment->payment_method }} on {{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') }}
                    @if($lastPayment->transaction_id)· Ref: {{ $lastPayment->transaction_id }}@endif
                </span>
            @else
                <span class="status-badge unpaid">⚠ PAYMENT PENDING</span>
            @endif
        </div>

        {{-- Footer --}}
        <div class="inv-footer">
            <div class="note">
                Thank you for choosing Waafibook.<br>
                For billing enquiries: support@waafibook.com
            </div>
            <div class="brand-note">
                <strong>Waafibook</strong> Subscription Platform<br>
                Generated {{ now()->format('d M Y, H:i') }}
            </div>
        </div>

    </div>
</div>

</body>
</html>
