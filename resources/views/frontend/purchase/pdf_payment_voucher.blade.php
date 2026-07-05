@extends('admin.admin_master')
@section('page_title', 'Payment Voucher')

@push('css')
<style>
@media print {
    body * { visibility: hidden !important; }
    #voucher-printable,
    #voucher-printable * { visibility: visible !important; }
    #voucher-printable {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
        box-shadow: none;
        border-radius: 0;
    }
}
</style>
@endpush

@section('admin')

@php
    $numToWords = function(int $n) use (&$numToWords): string {
        if ($n === 0) return '';
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                 'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                 'Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $w = '';
        if ($n >= 1000000) { $w .= $numToWords(intdiv($n, 1000000)) . ' Million '; $n %= 1000000; }
        if ($n >= 1000)    { $w .= $numToWords(intdiv($n, 1000)) . ' Thousand '; $n %= 1000; }
        if ($n >= 100)     { $w .= $ones[intdiv($n, 100)] . ' Hundred '; $n %= 100; }
        if ($n >= 20)      { $w .= $tens[intdiv($n, 10)] . ' '; $n %= 10; }
        if ($n > 0)        { $w .= $ones[$n] . ' '; }
        return $w;
    };

    $dollars     = (int) floor((float) $payment->amount);
    $cents       = (int) round(((float) $payment->amount - $dollars) * 100);
    $amountWords = trim($numToWords($dollars)) . ' Dollar' . ($dollars !== 1 ? 's' : '');
    if ($cents > 0) {
        $amountWords .= ' and ' . trim($numToWords($cents)) . ' Cent' . ($cents !== 1 ? 's' : '');
    }
    $amountWords .= ' only';

    $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
    $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');

    $logoPath = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? public_path($company->logo)
        : public_path('upload/waafibooklogo/waafibook_logo.jpg');
    $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'jpg');
    $logoB64 = file_exists($logoPath)
        ? 'data:image/' . $logoExt . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp

<div class="px-4 py-6 md:px-8 bg-background min-h-screen">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('view_payment_out') }}"
               class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition-all bg-white shadow-sm">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-[20px] font-black text-primary-dark tracking-tight">Payment Voucher</h1>
                <p class="text-[11px] font-bold text-gray-400">{{ $payment->voucher_no }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-bold rounded-lg hover:bg-gray-50 transition-all text-xs shadow-sm">
                <i class="bi bi-printer text-primary"></i> Print
            </button>
            <a href="{{ route('payment_out.download', $payment->id) }}"
               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-bold rounded-lg hover:bg-gray-50 transition-all text-xs shadow-sm">
                <i class="bi bi-download text-primary"></i> Download PDF
            </a>
        </div>
    </div>

    {{-- Voucher Card --}}
    <div id="voucher-printable" class="max-w-3xl mx-auto bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">

        {{-- Title --}}
        <div class="text-center py-4 border-b border-gray-200">
            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Payment Voucher</h2>
        </div>

        {{-- Company --}}
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-4">
            @if($logoB64)
                <img src="{{ $logoB64 }}" alt="{{ $company->name ?? '' }}"
                     class="w-16 h-16 object-contain border border-dashed border-gray-300 rounded">
            @endif
            <div>
                <div class="text-xl font-black text-gray-800">{{ $company->name ?? '' }}</div>
                <div class="text-sm text-gray-500 mt-1">Phone:&nbsp;&nbsp;{{ $company->phone ?? '' }}</div>
            </div>
        </div>

        {{-- Pay To / Voucher Details --}}
        <div class="grid grid-cols-2 divide-x divide-gray-200 border-b border-gray-200">
            <div class="px-6 py-4">
                <div class="text-xs font-bold text-gray-500 mb-2">Pay To (Supplier):</div>
                <div class="text-base font-black text-gray-800 uppercase">{{ $payment->supplier->name ?? 'N/A' }}</div>
                <div class="text-sm text-gray-500 mt-1">Phone:&nbsp;&nbsp;<strong class="text-gray-700">{{ $payment->supplier->phone ?? 'N/A' }}</strong></div>
                <div class="text-sm text-gray-500 mt-1">Method:&nbsp;&nbsp;<strong class="text-gray-700">{{ $payment->payment_method ?? 'Cash' }}</strong></div>
            </div>
            <div class="px-6 py-4">
                <div class="text-xs font-bold text-gray-500 mb-2">Voucher Details:</div>
                <div class="text-sm text-gray-700">Voucher No.:&nbsp;&nbsp;<strong>{{ $payment->voucher_no }}</strong></div>
                <div class="text-sm text-gray-700 mt-1">Date:&nbsp;&nbsp;<strong>{{ date('d/m/Y', strtotime($payment->payment_date)) }}</strong></div>
                <div class="text-sm text-gray-700 mt-1">Status:&nbsp;&nbsp;<strong class="{{ $payment->status === 'completed' ? 'text-green-600' : 'text-yellow-600' }}">{{ ucfirst($payment->status) }}</strong></div>
            </div>
        </div>

        {{-- Amount Paid --}}
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm text-gray-700">Amount Paid</span>
            <span class="text-gray-400 text-sm">:</span>
            <span class="text-sm font-black text-gray-800">{{ $symbol }}&nbsp;{{ number_format($payment->amount, 2) }}</span>
        </div>

        {{-- Amount in Words label --}}
        <div class="px-6 py-2 border-b border-gray-200 bg-gray-50">
            <span class="text-sm font-black text-gray-700">Amount in Words:</span>
        </div>

        {{-- Amount in Words value --}}
        <div class="px-6 py-3 border-b border-gray-200">
            <span class="text-sm text-gray-700">{{ $amountWords }}</span>
        </div>

        @if($payment->notes)
        {{-- Notes label --}}
        <div class="px-6 py-2 border-b border-gray-200 bg-gray-50">
            <span class="text-sm font-black text-gray-700">Notes:</span>
        </div>
        {{-- Notes value --}}
        <div class="px-6 py-3 border-b border-gray-200">
            <span class="text-sm text-gray-700">{{ $payment->notes }}</span>
        </div>
        @endif

        {{-- Authorized Signatory --}}
        <div class="grid grid-cols-2 border-b border-gray-200">
            <div></div>
            <div class="px-6 py-5">
                <div class="border border-gray-300 p-4 rounded">
                    <div class="text-sm font-black text-gray-800 mb-14">For {{ $company->name ?? '' }}:</div>
                    <div class="text-center text-xs text-gray-500 border-t border-gray-200 pt-2">
                        Authorized Signatory
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
