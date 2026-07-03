<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Receipt — {{ $loan->borrower_name ?? ($loan->employee->full_name ?? 'Borrower') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #e8eef4;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 60px;
        }

        /* ── Print bar ── */
        .print-bar {
            width: 600px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; font-size: 13px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #004161; color: #fff; }
        .btn-primary:hover { background: #005a85; }
        .btn-secondary { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #f8fafc; }

        /* ── Card ── */
        .card {
            width: 600px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,65,97,0.15);
        }

        /* ── HEADER ── */
        .header {
            background-color: #004161;
            background-image: radial-gradient(ellipse at top right, #005a85 0%, #004161 60%);
            padding: 32px 40px 0;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute; right: -30px; top: -30px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .header::after {
            content: '';
            position: absolute; right: 70px; top: 60px;
            width: 90px; height: 90px; border-radius: 50%;
            background: rgba(153,204,51,0.10);
        }

        .header-top { display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1; }

        .logo-area { display: flex; align-items: center; gap: 14px; }
        .logo-img  { height: 52px; width: auto; max-width: 160px; object-fit: contain; }
        .company-name-wrap { }
        .company-name { font-size: 17px; font-weight: 900; color: #fff; letter-spacing: -0.3px; }
        .company-sub  { font-size: 9px; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 2px; }

        .doc-badge {
            background: #99CC33;
            color: #004161;
            font-size: 9px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 2px;
            padding: 6px 16px; border-radius: 20px;
        }

        /* ── Meta row ── */
        .meta-row { display: flex; gap: 0; margin-top: 26px; position: relative; z-index: 1; }
        .meta-cell {
            flex: 1; padding-bottom: 22px;
            border-right: 1px solid rgba(255,255,255,0.08);
            padding-right: 18px; padding-left: 4px;
        }
        .meta-cell:first-child { padding-left: 0; }
        .meta-cell:last-child  { border-right: none; }
        .meta-label { font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 5px; }
        .meta-value { font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.92); }
        .meta-value.lime { color: #99CC33; }

        /* ── Borrower band ── */
        .borrower-band {
            background: rgba(255,255,255,0.07);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 18px 40px;
            display: flex; align-items: center; gap: 18px;
            position: relative; z-index: 1;
        }
        .borrower-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            background: #99CC33;
            border: 2px solid rgba(153,204,51,0.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 900; color: #004161;
            flex-shrink: 0;
        }
        .borrower-name { font-size: 17px; font-weight: 800; color: #fff; }
        .borrower-type { font-size: 10px; color: rgba(255,255,255,0.5); margin-top: 3px; font-weight: 600; text-transform: capitalize; }
        .borrower-phone { font-size: 10px; color: rgba(153,204,51,0.8); margin-top: 2px; font-weight: 600; }

        /* ── Tear line ── */
        .tearline { display: flex; align-items: center; background: #e8eef4; }
        .tearline-circle { width: 28px; height: 28px; border-radius: 50%; background: #e8eef4; flex-shrink: 0; }
        .tearline-dashes  { flex: 1; border-top: 2px dashed #cbd5e1; }

        /* ── Body ── */
        .body { padding: 30px 40px; }

        .section-title {
            font-size: 9px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }

        /* ── Loan summary table ── */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table tr { border-bottom: 1px solid #f8fafc; }
        .info-table tr:last-child { border-bottom: none; }
        .info-table td { padding: 11px 0; font-size: 13px; }
        .info-table .label { font-weight: 600; color: #64748b; }
        .info-table .value { font-weight: 700; color: #1e293b; text-align: right; }
        .info-table .value.lime { color: #4a7c12; }
        .info-table .value.red  { color: #e11d48; }

        /* ── Reason box ── */
        .reason-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #99CC33;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 18px;
        }
        .reason-label { font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 5px; }
        .reason-text  { font-size: 13px; font-weight: 600; color: #334155; }

        /* ── Amount box ── */
        .amount-box {
            background-color: #004161;
            background-image: linear-gradient(135deg, #004161 0%, #005a85 100%);
            border-radius: 14px;
            padding: 22px 28px;
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 22px;
        }
        .amount-left  { }
        .amount-label { font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 1.5px; }
        .amount-sub   { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 4px; font-weight: 500; }
        .amount-value { font-size: 34px; font-weight: 900; color: #99CC33; letter-spacing: -1px; line-height: 1; }
        .amount-currency { font-size: 18px; vertical-align: super; font-weight: 700; }

        /* ── Progress bar ── */
        .progress-section { margin-top: 20px; }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .progress-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; }
        .progress-pct   { font-size: 11px; font-weight: 800; color: #004161; }
        .progress-track { height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
        .progress-fill  { height: 100%; background: linear-gradient(90deg, #99CC33, #7aad1a); border-radius: 10px; transition: width 0.3s; }

        /* ── Stats grid ── */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
        .stat-cell  { background: #f8fafc; border: 1px solid #e8eef4; border-radius: 12px; padding: 14px 16px; text-align: center; }
        .stat-label { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .stat-value { font-size: 16px; font-weight: 900; color: #1e293b; }
        .stat-value.lime  { color: #4a7c12; }
        .stat-value.navy  { color: #004161; }
        .stat-value.slate { color: #64748b; }

        /* ── Status + Signature ── */
        .bottom-row { display: flex; align-items: center; justify-content: space-between; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .status-pill { display: inline-flex; align-items: center; gap: 7px; border-radius: 20px; padding: 7px 16px; border: 1.5px solid; }
        .status-pill.active   { background: rgba(153,204,51,0.1); border-color: rgba(153,204,51,0.35); }
        .status-pill.pending  { background: rgba(234,179,8,0.1); border-color: rgba(234,179,8,0.35); }
        .status-pill.settled  { background: rgba(100,116,139,0.1); border-color: rgba(100,116,139,0.3); }
        .status-pill.rejected { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); }
        .status-dot  { width: 8px; height: 8px; border-radius: 50%; }
        .status-dot.active   { background: #99CC33; }
        .status-dot.pending  { background: #eab308; }
        .status-dot.settled  { background: #64748b; }
        .status-dot.rejected { background: #ef4444; }
        .status-text { font-size: 11px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 1px; }

        .sig-area   { text-align: right; }
        .sig-line   { border-top: 1.5px solid #cbd5e1; width: 130px; margin-left: auto; padding-top: 6px; }
        .sig-label  { font-size: 9px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        /* ── Footer ── */
        .footer { background: #f8fafc; border-top: 1px dashed #e2e8f0; padding: 14px 40px; display: flex; align-items: center; justify-content: space-between; }
        .footer p  { font-size: 10px; color: #94a3b8; font-weight: 500; line-height: 1.6; }
        .footer strong { color: #004161; font-weight: 700; }
        .footer-stamp { width: 44px; height: 44px; border-radius: 50%; border: 2px dashed rgba(153,204,51,0.5); display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 800; color: rgba(153,204,51,0.7); text-transform: uppercase; letter-spacing: 1px; text-align: center; line-height: 1.3; }

        @media print {
            body { background: #fff; padding: 0; }
            .card { box-shadow: none; border-radius: 0; width: 100%; }
            .print-bar { display: none; }
            .tearline { background: #fff; }
            .tearline-circle { background: #fff; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <div class="print-bar">
        <a href="{{ route('loan.view') }}" class="btn btn-secondary">&#8592; Back to Loans</a>
        <button onclick="window.print()" class="btn btn-primary">&#128438; Print / Save PDF</button>
    </div>

    <div class="card">

        {{-- ══ HEADER ══ --}}
        <div class="header">
            <div class="header-top">
                <div class="logo-area">
                    @php
                        $loanLogoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
                            ? asset($company->logo)
                            : asset('upload/waafibooklogo/waafibook_logo.jpg');
                    @endphp
                    <img src="{{ $loanLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}" class="logo-img">
                    <div class="company-name-wrap">
                        <div class="company-name">{{ $company->name ?? 'WaafiBook' }}</div>
                        <div class="company-sub">{{ $company->city ?? '' }}{{ ($company->city && $company->country) ? ' • ' : '' }}{{ $company->country ?? 'ERP System' }}</div>
                    </div>
                </div>
                <span class="doc-badge">Loan Receipt</span>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <div class="meta-label">Loan Reference</div>
                    <div class="meta-value">{{ $loan->loan_id ?? 'LN-' . str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Issue Date</div>
                    <div class="meta-value">{{ \Carbon\Carbon::parse($loan->start_date)->format('d M Y') }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Loan Type</div>
                    <div class="meta-value" style="text-transform:capitalize;">{{ $loan->type ?? 'Personal' }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Status</div>
                    <div class="meta-value lime">&#10003; {{ ucfirst($loan->status ?? 'Pending') }}</div>
                </div>
            </div>

            <div class="borrower-band">
                @php $borrowerName = $loan->borrower_name ?: ($loan->employee->full_name ?? 'Unknown'); @endphp
                <div class="borrower-avatar">{{ strtoupper(substr($borrowerName, 0, 1)) }}</div>
                <div>
                    <div class="borrower-name">{{ $borrowerName }}</div>
                    <div class="borrower-type">{{ ucfirst($loan->borrower_type ?? 'Individual') }}</div>
                    @if($loan->phone)
                    <div class="borrower-phone">{{ $loan->phone }}</div>
                    @elseif($loan->employee)
                    <div class="borrower-phone">{{ $loan->employee->designation ?? '' }}</div>
                    @endif
                </div>
                <div style="margin-left:auto; text-align:right;">
                    <div style="font-size:9px;color:rgba(255,255,255,0.4);font-weight:700;text-transform:uppercase;letter-spacing:1px;">Total Loan</div>
                    <div style="font-size:22px;font-weight:900;color:#fff;margin-top:4px;">{{ $currency }} {{ number_format($loan->amount, 2) }}</div>
                </div>
            </div>
        </div>

        {{-- ══ TEAR LINE ══ --}}
        <div class="tearline">
            <div class="tearline-circle" style="margin-left:-14px;"></div>
            <div class="tearline-dashes"></div>
            <div class="tearline-circle" style="margin-right:-14px;"></div>
        </div>

        {{-- ══ BODY ══ --}}
        <div class="body">

            <div class="section-title">Loan Details</div>

            <table class="info-table">
                <tr>
                    <td class="label">Loan Amount</td>
                    <td class="value">{{ $currency }} {{ number_format($loan->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Paid</td>
                    <td class="value lime">{{ $currency }} {{ number_format($loan->recovered, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Remaining Balance</td>
                    <td class="value {{ $loan->balance > 0 ? 'red' : 'lime' }}">{{ $currency }} {{ number_format($loan->balance, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Loan Date</td>
                    <td class="value">{{ \Carbon\Carbon::parse($loan->start_date)->format('d M Y') }}</td>
                </tr>
            </table>

            @if($loan->reason)
            <div class="reason-box">
                <div class="reason-label">Purpose / Reason</div>
                <div class="reason-text">{{ $loan->reason }}</div>
            </div>
            @endif

            {{-- ── Balance box ── --}}
            <div class="amount-box">
                <div class="amount-left">
                    <div class="amount-label">Outstanding Balance</div>
                    <div class="amount-sub">{{ $borrowerName }} &bull; {{ $loan->loan_id ?? '' }}</div>
                </div>
                <div class="amount-value">
                    <span class="amount-currency">{{ $currency }}</span> {{ number_format($loan->balance, 2) }}
                </div>
            </div>

            {{-- ── Recovery progress ── --}}
            @php
                $pct = $loan->amount > 0 ? min(100, round(($loan->recovered / $loan->amount) * 100)) : 0;
            @endphp
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">Recovery Progress</span>
                    <span class="progress-pct">{{ $pct }}% recovered</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: {{ $pct }}%;"></div>
                </div>
            </div>

            {{-- ── Stats grid ── --}}
            <div class="stats-grid">
                <div class="stat-cell">
                    <div class="stat-label">Loan Amount</div>
                    <div class="stat-value navy">{{ $currency }} {{ number_format($loan->amount, 2) }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Total Paid</div>
                    <div class="stat-value lime">{{ $currency }} {{ number_format($loan->recovered, 2) }}</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-label">Balance Due</div>
                    <div class="stat-value {{ $loan->balance > 0 ? 'navy' : 'lime' }}">{{ $currency }} {{ number_format($loan->balance, 2) }}</div>
                </div>
            </div>

            {{-- ── Status + Signature ── --}}
            <div class="bottom-row">
                @php $st = $loan->status ?? 'pending'; @endphp
                <div class="status-pill {{ $st }}">
                    <div class="status-dot {{ $st }}"></div>
                    <span class="status-text">{{ ucfirst($st) }}</span>
                </div>
                <div class="sig-area">
                    <div class="sig-line">
                        <div class="sig-label">Authorized Signature</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ FOOTER ══ --}}
        <div class="footer">
            <div>
                <p>Generated by <strong>WaafiBook ERP</strong> &bull; {{ now()->format('d M Y, H:i') }}</p>
                <p>This is a system-generated loan receipt. Ref: <strong>{{ $loan->loan_id ?? '—' }}</strong></p>
            </div>
            <div class="footer-stamp">ISSUED<br>✓</div>
        </div>

    </div>

</body>
</html>
