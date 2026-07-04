<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Service Order {{ $order->order_number }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a2733; background: #fff; }
  .page { padding: 30px 36px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #004161; padding-bottom: 16px; margin-bottom: 20px; }
  .brand-name { font-size: 22px; font-weight: 900; color: #004161; }
  .brand-sub  { font-size: 9px; color: #6b8499; letter-spacing: 0.12em; text-transform: uppercase; margin-top: 2px; }
  .doc-title  { font-size: 16px; font-weight: 900; color: #004161; text-align: right; }
  .doc-meta   { font-size: 10px; color: #6b8499; text-align: right; margin-top: 3px; }
  .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: #e8f5c8; color: #3d6400; margin-top: 4px; }
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
  .techns { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
  .techn-chip { padding: 2px 8px; background: #e4edf4; border-radius: 3px; font-size: 10px; font-weight: 700; color: #004161; }
</style>
</head>
<body>
<div class="page">

  <div class="header">
    <div>
      <div class="brand-name">{{ $company->company_name ?? 'WaafiBook' }}</div>
      <div class="brand-sub">Service Division</div>
      @if($company->phone ?? null)
        <div style="font-size:10px;color:#6b8499;margin-top:3px;">{{ $company->phone }}</div>
      @endif
      @if($company->address ?? null)
        <div style="font-size:10px;color:#6b8499;">{{ $company->address }}</div>
      @endif
    </div>
    <div>
      <div class="doc-title">SERVICE ORDER</div>
      <div class="doc-meta"># {{ $order->order_number }}</div>
      <div class="doc-meta">Created: {{ $order->created_at->format('d M Y') }}</div>
      <div><span class="status-badge">{{ strtoupper(str_replace('_',' ',$order->status)) }}</span></div>
    </div>
  </div>

  <div class="section">
    <div class="info-grid">
      <div class="info-block">
        <div class="section-title">Customer</div>
        <div class="info-value">{{ $order->customer->name ?? 'N/A' }}</div>
        @if($order->customer?->phone)
        <div style="font-size:10px;color:#6b8499;margin-top:2px;">{{ $order->customer->phone }}</div>
        @endif
        @if($order->customer?->address)
        <div style="font-size:10px;color:#6b8499;">{{ $order->customer->address }}</div>
        @endif
      </div>
      <div class="info-block">
        <div class="section-title">Job Details</div>
        <div class="info-label">Title</div>
        <div class="info-value">{{ $order->title }}</div>
        @if($order->scheduled_date)
        <div class="info-label" style="margin-top:6px;">Scheduled Date</div>
        <div class="info-value">{{ $order->scheduled_date->format('d M Y') }}</div>
        @endif
      </div>
      <div class="info-block">
        <div class="section-title">Priority</div>
        <div class="info-value" style="text-transform:uppercase;">{{ $order->priority }}</div>
        @if($order->employees->isNotEmpty())
        <div class="section-title" style="margin-top:10px;">Technicians</div>
        <div class="techns">
          @foreach($order->employees as $emp)
          <span class="techn-chip">{{ $emp->full_name }}</span>
          @endforeach
        </div>
        @endif
      </div>
    </div>
  </div>

  @if($order->description)
  <div class="section">
    <div class="section-title">Description</div>
    <p style="font-size:11px;color:#3e5d74;line-height:1.6;">{{ $order->description }}</p>
  </div>
  @endif

  <div class="section">
    <div class="section-title">Service Items</div>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Description</th>
          <th class="text-center">Qty</th>
          <th class="text-right">Unit Price</th>
          <th class="text-center">Disc%</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->items as $i => $item)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $item->description }}</td>
          <td class="text-center">{{ $item->quantity }}</td>
          <td class="text-right">{{ $symbol }} {{ number_format($item->unit_price, 2) }}</td>
          <td class="text-center">{{ $item->discount_pct > 0 ? $item->discount_pct.'%' : '—' }}</td>
          <td class="text-right">{{ $symbol }} {{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <table class="totals">
      <tr><td>Subtotal</td><td>{{ $symbol }} {{ number_format($order->subtotal, 2) }}</td></tr>
      @if($order->discount_amount > 0)
      <tr><td>Discount</td><td>- {{ $symbol }} {{ number_format($order->discount_amount, 2) }}</td></tr>
      @endif
      @if($order->tax_amount > 0)
      <tr><td>Tax</td><td>{{ $symbol }} {{ number_format($order->tax_amount, 2) }}</td></tr>
      @endif
      <tr class="total-row"><td>TOTAL</td><td>{{ $symbol }} {{ number_format($order->total_amount, 2) }}</td></tr>
    </table>
    <div style="clear:both"></div>
  </div>

  @if($order->notes)
  <div class="section" style="margin-top:20px;">
    <div class="section-title">Notes</div>
    <p style="font-size:11px;color:#3e5d74;line-height:1.6;">{{ $order->notes }}</p>
  </div>
  @endif

  <div class="footer">
    <span>Generated {{ now()->format('d M Y H:i') }}</span>
    <span>WaafiBook ERP — Service Module</span>
  </div>

</div>
</body>
</html>
