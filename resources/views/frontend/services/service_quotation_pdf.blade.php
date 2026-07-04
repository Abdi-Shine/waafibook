<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Quotation {{ $quotation->quote_number }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a2733; background: #fff; }
  .page { padding: 30px 36px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #004161; padding-bottom: 16px; margin-bottom: 20px; }
  .brand-name { font-size: 22px; font-weight: 900; color: #004161; }
  .brand-sub  { font-size: 9px; color: #6b8499; letter-spacing: 0.12em; text-transform: uppercase; margin-top: 2px; }
  .doc-title  { font-size: 16px; font-weight: 900; color: #004161; text-align: right; }
  .doc-meta   { font-size: 10px; color: #6b8499; text-align: right; margin-top: 3px; }
  .section { margin-bottom: 18px; }
  .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b8499; border-bottom: 1px solid #dce8f0; padding-bottom: 4px; margin-bottom: 8px; }
  .info-grid { display: flex; gap: 30px; }
  .info-block { flex: 1; }
  .info-label { font-size: 9px; color: #6b8499; text-transform: uppercase; letter-spacing: 0.08em; }
  .info-value { font-size: 12px; font-weight: 700; color: #1a2733; margin-top: 1px; }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #004161; }
  thead th { color: #fff; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; }
  tbody tr:nth-child(even) { background: #f4f8fb; }
  tbody td { padding: 7px 10px; font-size: 11px; border-bottom: 1px solid #e4edf4; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }
  .totals { float: right; width: 240px; border-top: 2px solid #004161; margin-top: 10px; }
  .totals tr td { padding: 4px 0; font-size: 11px; }
  .totals tr td:last-child { text-align: right; font-weight: 700; }
  .total-row td { font-size: 14px; font-weight: 900; color: #004161; padding-top: 8px; border-top: 1px solid #dce8f0; }
  .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #dce8f0; display: flex; justify-content: space-between; font-size: 9px; color: #6b8499; }
  .validity-note { margin-top: 20px; padding: 10px 14px; background: #f4f8fb; border-left: 3px solid #004161; font-size: 11px; color: #3e5d74; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <div>
      <div class="brand-name">{{ $company->company_name ?? 'WaafiBook' }}</div>
      <div class="brand-sub">Service Division</div>
    </div>
    <div>
      <div class="doc-title">QUOTATION</div>
      <div class="doc-meta"># {{ $quotation->quote_number }}</div>
      <div class="doc-meta">Date: {{ $quotation->created_at->format('d M Y') }}</div>
      @if($quotation->valid_until)
      <div class="doc-meta">Valid Until: {{ $quotation->valid_until->format('d M Y') }}</div>
      @endif
    </div>
  </div>

  <div class="section">
    <div class="info-grid">
      <div class="info-block">
        <div class="section-title">Bill To</div>
        <div class="info-value">{{ $quotation->customer->name ?? 'N/A' }}</div>
        @if($quotation->customer?->phone)<div style="font-size:10px;color:#6b8499;margin-top:2px;">{{ $quotation->customer->phone }}</div>@endif
        @if($quotation->customer?->email)<div style="font-size:10px;color:#6b8499;">{{ $quotation->customer->email }}</div>@endif
      </div>
      <div class="info-block">
        <div class="section-title">From</div>
        <div class="info-value">{{ $company->company_name ?? '' }}</div>
        @if($company?->phone)<div style="font-size:10px;color:#6b8499;">{{ $company->phone }}</div>@endif
        @if($company?->address)<div style="font-size:10px;color:#6b8499;">{{ $company->address }}</div>@endif
      </div>
    </div>
  </div>

  @if($quotation->title)
  <div class="section">
    <div class="section-title">Subject</div>
    <p style="font-size:12px;font-weight:700;color:#1a2733;">{{ $quotation->title }}</p>
  </div>
  @endif

  <div class="section">
    <div class="section-title">Items</div>
    <table>
      <thead><tr>
        <th>#</th><th>Description</th><th class="text-center">Qty</th>
        <th class="text-right">Unit Price</th><th class="text-center">Disc%</th><th class="text-right">Total</th>
      </tr></thead>
      <tbody>
        @foreach($quotation->items as $i => $item)
        <tr>
          <td>{{ $i+1 }}</td><td>{{ $item->description }}</td><td class="text-center">{{ $item->quantity }}</td>
          <td class="text-right">{{ $symbol }} {{ number_format($item->unit_price,2) }}</td>
          <td class="text-center">{{ $item->discount_pct > 0 ? $item->discount_pct.'%' : '—' }}</td>
          <td class="text-right">{{ $symbol }} {{ number_format($item->total,2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <table class="totals">
      <tr><td>Subtotal</td><td>{{ $symbol }} {{ number_format($quotation->subtotal,2) }}</td></tr>
      @if($quotation->discount_amount > 0)
      <tr><td>Discount</td><td>- {{ $symbol }} {{ number_format($quotation->discount_amount,2) }}</td></tr>
      @endif
      @if($quotation->tax_amount > 0)
      <tr><td>Tax</td><td>{{ $symbol }} {{ number_format($quotation->tax_amount,2) }}</td></tr>
      @endif
      <tr class="total-row"><td>TOTAL</td><td>{{ $symbol }} {{ number_format($quotation->total_amount,2) }}</td></tr>
    </table>
    <div style="clear:both"></div>
  </div>

  @if($quotation->notes)
  <div class="validity-note">{{ $quotation->notes }}</div>
  @endif

  <div class="footer">
    <span>Generated {{ now()->format('d M Y H:i') }}</span>
    <span>WaafiBook ERP</span>
  </div>
</div>
</body>
</html>
