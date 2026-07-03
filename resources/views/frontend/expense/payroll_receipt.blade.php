<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Receipt — {{ $item->employee->full_name ?? '' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

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

        /* ── Print button (hidden on print) ── */
        .print-bar {
            width: 560px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 14px;
        }
        .btn-print {
            display: inline-flex; align-items: center; gap-8px; gap: 8px;
            padding: 10px 22px;
            background: #004161;
            color: #fff;
            font-size: 13px; font-weight: 700;
            border: none; border-radius: 8px;
            cursor: pointer;
            letter-spacing: 0.2px;
        }
        .btn-print:hover { background: #005a85; }

        /* ── Payslip card ── */
        .payslip {
            width: 560px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,65,97,0.15);
        }

        /* ── HEADER ── */
        .header {
            background-color: #004161;
            background-image: radial-gradient(ellipse at top right, #005a85 0%, #004161 60%);
            padding: 32px 36px 0;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            right: -30px; top: -30px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .header::after {
            content: '';
            position: absolute;
            right: 60px; top: 60px;
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(153,204,51,0.12);
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-img { height: 52px; width: auto; max-width: 180px; object-fit: contain; }
        .logo-fallback {
            display: flex; align-items: center; gap: 10px;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: rgba(153,204,51,0.2);
            border: 1.5px solid rgba(153,204,51,0.4);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 900; color: #99CC33;
        }
        .logo-text { font-size: 18px; font-weight: 900; color: #fff; letter-spacing: -0.3px; }
        .logo-sub  { font-size: 9px; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 1px; }

        .receipt-badge {
            background: #99CC33;
            color: #004161;
            font-size: 9px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 2px;
            padding: 5px 14px; border-radius: 20px;
        }

        /* ── Meta row ── */
        .meta-row {
            display: flex;
            gap: 0;
            margin-top: 24px;
            position: relative; z-index: 1;
        }
        .meta-cell {
            flex: 1;
            padding-bottom: 20px;
            border-right: 1px solid rgba(255,255,255,0.08);
            padding-right: 20px;
            padding-left: 4px;
        }
        .meta-cell:first-child { padding-left: 0; }
        .meta-cell:last-child  { border-right: none; }
        .meta-label { font-size: 9px; font-weight: 700; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 5px; }
        .meta-value { font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.92); }

        /* ── Employee band ── */
        .employee-band {
            background: rgba(255,255,255,0.07);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 16px 36px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative; z-index: 1;
        }
        .emp-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: #99CC33;
            border: 2px solid rgba(153,204,51,0.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 800; color: #004161;
            flex-shrink: 0;
        }
        .emp-name { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -0.2px; }
        .emp-role { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 2px; font-weight: 500; }
        .emp-id   { font-size: 10px; color: rgba(153,204,51,0.8); margin-top: 3px; font-weight: 600; letter-spacing: 0.5px; }

        /* ── Tear line ── */
        .tearline {
            display: flex; align-items: center;
            background: #e8eef4;
            position: relative;
        }
        .tearline-circle {
            width: 28px; height: 28px; border-radius: 50%;
            background: #e8eef4;
            flex-shrink: 0;
        }
        .tearline-dashes {
            flex: 1;
            border-top: 2px dashed #cbd5e1;
        }

        /* ── Body ── */
        .body { padding: 28px 36px; }

        .section-title {
            font-size: 9px; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .section-title::after {
            content: ''; flex: 1; height: 1px; background: #f1f5f9;
        }

        .salary-table { width: 100%; border-collapse: collapse; }
        .salary-table tr { border-bottom: 1px solid #f8fafc; }
        .salary-table tr:last-child { border-bottom: none; }
        .salary-table td {
            padding: 11px 0;
            font-size: 13px;
        }
        .salary-table .item-name { font-weight: 600; color: #475569; }
        .salary-table .item-val  { font-weight: 700; color: #1e293b; text-align: right; }
        .salary-table .item-val.green { color: #4a7c12; }
        .salary-table .item-val.red   { color: #e11d48; }

        /* ── Net box ── */
        .net-box {
            background-color: #004161;
            background-image: linear-gradient(135deg, #004161 0%, #005a85 100%);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }
        .net-left  { }
        .net-label { font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 1.5px; }
        .net-sub   { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 3px; font-weight: 500; }
        .net-amount { font-size: 32px; font-weight: 900; color: #99CC33; letter-spacing: -1px; line-height: 1; }
        .net-currency { font-size: 16px; vertical-align: super; font-weight: 700; }

        /* ── Status + signature ── */
        .bottom-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
        }
        .status-pill {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(153,204,51,0.12);
            border: 1.5px solid rgba(153,204,51,0.35);
            border-radius: 20px;
            padding: 7px 16px;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #99CC33; }
        .status-text { font-size: 11px; font-weight: 800; color: #004161; text-transform: uppercase; letter-spacing: 1px; }

        .sig-area { text-align: right; }
        .sig-line { border-top: 1.5px solid #cbd5e1; width: 110px; margin-left: auto; padding-top: 6px; }
        .sig-label { font-size: 9px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        /* ── Footer ── */
        .footer {
            background: #f8fafc;
            border-top: 1px dashed #e2e8f0;
            padding: 14px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .footer-left p  { font-size: 10px; color: #94a3b8; font-weight: 500; line-height: 1.6; }
        .footer-left strong { color: #004161; font-weight: 700; }
        .footer-stamp {
            width: 44px; height: 44px; border-radius: 50%;
            border: 2px dashed rgba(153,204,51,0.5);
            display: flex; align-items: center; justify-content: center;
            font-size: 8px; font-weight: 800; color: rgba(153,204,51,0.7);
            text-transform: uppercase; letter-spacing: 1px; text-align: center;
            line-height: 1.3;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .payslip { box-shadow: none; border-radius: 0; width: 100%; }
            .print-bar { display: none; }
            .tearline { background: #fff; }
            .tearline-circle { background: #fff; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <div class="print-bar">
        <button class="btn-print" onclick="window.print()">&#128438;&nbsp; Print / Save PDF</button>
    </div>

    <div class="payslip">

        {{-- ══ HEADER ══ --}}
        <div class="header">
            <div class="header-top">
                <div class="logo-area">
                    @php
                        $companyLogoSrc = ($company->logo && file_exists(public_path($company->logo)))
                            ? asset($company->logo)
                            : asset('upload/waafibooklogo/waafibook_logo.jpg');
                    @endphp
                    <img src="{{ $companyLogoSrc }}" alt="{{ $company->name ?? 'WaafiBook' }}" class="logo-img">
                    <div>
                        <div class="logo-text">{{ $company->name ?? 'WaafiBook' }}</div>
                        <div class="logo-sub">ERP System</div>
                    </div>
                </div>
                <span class="receipt-badge">Salary Receipt</span>
            </div>

            <div class="meta-row">
                <div class="meta-cell">
                    <div class="meta-label">Pay Period</div>
                    <div class="meta-value">{{ optional($item->payroll)->month_year ?? '—' }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Payment Date</div>
                    <div class="meta-value">{{ $item->payment_date ? \Carbon\Carbon::parse($item->payment_date)->format('d M Y') : now()->format('d M Y') }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Receipt No.</div>
                    <div class="meta-value">SAL-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="meta-cell">
                    <div class="meta-label">Status</div>
                    <div class="meta-value" style="color:#99CC33;">&#10003; Paid</div>
                </div>
            </div>

            <div class="employee-band">
                <div class="emp-avatar">{{ strtoupper(substr($item->employee->full_name ?? '?', 0, 1)) }}</div>
                <div>
                    <div class="emp-name">{{ $item->employee->full_name ?? '—' }}</div>
                    <div class="emp-role">{{ $item->employee->designation ?? $item->employee->department ?? 'Employee' }}</div>
                    <div class="emp-id">ID #{{ str_pad($item->employee->id ?? 0, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
                @if($company->name ?? null)
                <div style="margin-left:auto; text-align:right;">
                    <div style="font-size:10px;color:rgba(255,255,255,0.4);font-weight:600;text-transform:uppercase;letter-spacing:1px;">Company</div>
                    <div style="font-size:13px;color:rgba(255,255,255,0.85);font-weight:700;margin-top:3px;">{{ $company->name }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- ══ TEAR LINE ══ --}}
        <div class="tearline">
            <div class="tearline-circle" style="margin-left:-14px;"></div>
            <div class="tearline-dashes"></div>
            <div class="tearline-circle" style="margin-right:-14px;"></div>
        </div>

        {{-- ══ SALARY BREAKDOWN ══ --}}
        <div class="body">

            <div class="section-title">Salary Breakdown</div>

            <table class="salary-table">
                <tr>
                    <td class="item-name">Basic Salary</td>
                    <td class="item-val">{{ $currency }} {{ number_format($item->basic_salary, 2) }}</td>
                </tr>
                @if($item->bonus > 0)
                <tr>
                    <td class="item-name">Bonus</td>
                    <td class="item-val green">+ {{ $currency }} {{ number_format($item->bonus, 2) }}</td>
                </tr>
                @endif
                @if($item->overtime > 0)
                <tr>
                    <td class="item-name">Overtime Pay</td>
                    <td class="item-val green">+ {{ $currency }} {{ number_format($item->overtime, 2) }}</td>
                </tr>
                @endif
                @if($item->deductions > 0)
                <tr>
                    <td class="item-name">Deductions</td>
                    <td class="item-val red">− {{ $currency }} {{ number_format($item->deductions, 2) }}</td>
                </tr>
                @endif
            </table>

            {{-- ── Net salary ── --}}
            <div class="net-box">
                <div class="net-left">
                    <div class="net-label">Net Salary Payable</div>
                    <div class="net-sub">{{ optional($item->payroll)->month_year ?? '' }} &bull; {{ $item->employee->full_name ?? '' }}</div>
                </div>
                <div class="net-amount">
                    <span class="net-currency">{{ $currency }}</span> {{ number_format($item->net_salary, 2) }}
                </div>
            </div>

            {{-- ── Status + Signature ── --}}
            <div class="bottom-row">
                <div class="status-pill">
                    <div class="status-dot"></div>
                    <span class="status-text">Payment Confirmed</span>
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
            <div class="footer-left">
                <p>Generated by <strong>WaafiBook ERP</strong> &bull; {{ now()->format('d M Y, H:i') }}</p>
                <p>This is a system-generated receipt and does not require a physical signature.</p>
            </div>
            <div class="footer-stamp">PAID<br>✓</div>
        </div>

    </div>

</body>
</html>
