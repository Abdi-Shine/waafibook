@extends('admin.admin_master')
@section('page_title', 'Purchase Bill Detail')

@php
    $symbol = '$'; // Force Dollar — matches sales_invoice_detail_pwa.blade.php
    $due = $bill->total_amount - $bill->paid_amount;
    $statusColor = match($bill->status) {
        'paid' => 'bg-accent/10 text-accent',
        'partial' => 'bg-yellow-50 text-yellow-600',
        default => 'bg-red-50 text-red-500',
    };
    $statusLabel = ucfirst($bill->status);
    $billLogoSrc = (!empty($company->logo) && file_exists(public_path($company->logo)))
        ? asset($company->logo)
        : asset('upload/waafibooklogo/waafibook_logo.jpg');
    $publicPdfUrl = \App\Support\PublicUrl::temporarySigned('purchase.bill.public-pdf', now()->addDays(7), ['id' => $bill->id]);
@endphp

@section('admin')
<div class="pb-28 bg-background min-h-screen" x-data="{
    sharing: false,
    async shareBill() {
        if (this.sharing) return;
        const url = @js($publicPdfUrl);
        const title = 'Purchase Bill {{ $bill->bill_number }}';
        const text = 'Purchase Bill {{ $bill->bill_number }} — {{ addslashes($company->name ?? 'Waafibook') }}\nTotal: {{ $symbol }} {{ number_format($bill->total_amount, 2) }}';
        this.sharing = true;
        try {
            // Share the actual PDF file (attaches as a document in WhatsApp),
            // falling back to a link share only if file sharing is unsupported.
            const resp = await fetch('{{ route('purchase.bill.pdf', $bill->id) }}');
            if (!resp.ok) throw new Error('pdf fetch failed');
            const blob = await resp.blob();
            const file = new File([blob], 'PurchaseBill_{{ $bill->bill_number }}.pdf', { type: 'application/pdf' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: title, text: text });
                return;
            }
            throw new Error('file share unsupported');
        } catch (e) {
            if (e && e.name === 'AbortError') return; // user closed the share sheet
            if (navigator.share) {
                navigator.share({ title: title, text: text + '\n' + url }).catch(() => {});
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    Swal.fire({ icon: 'success', title: 'Link Copied', text: 'Bill link copied to clipboard.', timer: 1600, showConfirmButton: false });
                });
            }
        } finally {
            this.sharing = false;
        }
    },
    async sendWhatsApp() {
        if (this.sharing) return;
        const companyName = '{{ addslashes($company->name ?? 'Waafibook') }}';
        const supplier = '{{ addslashes($bill->supplier->name ?? 'Supplier') }}';
        const phone = '{{ $bill->supplier->phone ?? '' }}';
        const pdfUrl = @js($publicPdfUrl);
        let message = `*${companyName}*\n\n`;
        message += `*Qaansheegta Iibsiga*\n`;
        message += `{{ $symbol }} {{ number_format($bill->total_amount, 2) }}\n`;
        message += `taariikhda {{ \Carbon\Carbon::parse($bill->bill_date)->format('jS F Y') }}\n\n`;
        message += `Salaam ${supplier},\n`;
        message += `Kani waa qaansheegta {{ $bill->bill_number }}.\n\n`;
        message += `Mahadsanid,\n${companyName}`;
        @if($company->phone ?? false) message += `\n{{ $company->phone }}`; @endif
        @if($company->email ?? false) message += `\n{{ $company->email }}`; @endif

        // Attach the actual PDF document via the share sheet (pick WhatsApp →
        // contact), falling back to a wa.me text+link message when the
        // browser can't share files.
        this.sharing = true;
        try {
            const resp = await fetch('{{ route('purchase.bill.pdf', $bill->id) }}');
            if (resp.ok) {
                const blob = await resp.blob();
                const file = new File([blob], 'PurchaseBill_{{ $bill->bill_number }}.pdf', { type: 'application/pdf' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], text: message });
                    return;
                }
            }
        } catch (e) {
            if (e && e.name === 'AbortError') return; // user closed the share sheet
        } finally {
            this.sharing = false;
        }
        const url = phone
            ? `https://wa.me/${phone.replace(/\D/g, '')}?text=${encodeURIComponent(message + '\nHalkan ka eeg: ' + pdfUrl)}`
            : `https://wa.me/?text=${encodeURIComponent(message + '\nHalkan ka eeg: ' + pdfUrl)}`;
        window.open(url, '_blank');
    }
}">

    <div class="flex items-center justify-between gap-3 px-5 pt-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('purchase.bill') }}"
                class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 bg-white shadow-sm shrink-0">
                <i class="bi bi-arrow-left text-sm"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-[16px] font-black text-primary-dark truncate">{{ $bill->bill_number }}</h1>
                <p class="text-[11px] font-bold text-gray-400">Purchase Bill Detail</p>
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0 {{ $statusColor }}">{{ $statusLabel }}</span>
    </div>

    {{-- Actions --}}
    <div class="grid grid-cols-4 gap-2 px-5 pt-3">
        <button type="button" @click="shareBill()" :disabled="sharing"
            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-primary text-white font-bold rounded-xl text-[10px] disabled:opacity-60">
            <i class="bi text-sm" :class="sharing ? 'bi-arrow-repeat animate-spin' : 'bi-share'"></i>
            <span x-text="sharing ? 'Preparing…' : 'Share'">Share</span>
        </button>
        <button type="button" @click="sendWhatsApp()"
            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-accent/10 border border-accent/20 text-accent font-bold rounded-xl text-[10px]">
            <i class="bi bi-whatsapp text-sm"></i> WhatsApp
        </button>
        <a href="{{ route('purchase.bill.pdf', $bill->id) }}" target="_blank"
            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border border-gray-200 text-primary-dark font-bold rounded-xl text-[10px]">
            <i class="bi bi-file-earmark-pdf text-sm text-primary"></i> PDF
        </a>
        <a href="{{ route('purchase.bill.edit', $bill->id) }}"
            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border border-gray-200 text-primary-dark font-bold rounded-xl text-[10px]">
            <i class="bi bi-pencil text-sm text-primary"></i> Edit
        </a>
    </div>

    {{-- Bill Document --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

        {{-- Brand Header --}}
        <div class="px-4 py-4 flex items-center justify-between bg-gradient-to-r from-primary to-primary/80">
            <div class="flex items-center gap-2.5 min-w-0">
                <img src="{{ $billLogoSrc }}" alt="{{ $company->name ?? 'Logo' }}" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5 shrink-0">
                <div class="min-w-0">
                    <h2 class="text-[13px] font-black text-white leading-tight truncate">{{ $company->name ?? '' }}</h2>
                    <p class="text-[10px] text-white/70 font-semibold truncate">{{ $company->phone ?? '' }}</p>
                </div>
            </div>
            <div class="text-right shrink-0 pl-2">
                <p class="text-[9px] font-black text-white/60 uppercase tracking-wider">Purchase Bill</p>
                <p class="text-[13px] font-black text-white">{{ $bill->bill_number }}</p>
            </div>
        </div>

        {{-- Supplier + Meta --}}
        <div class="px-4 py-4 flex justify-between gap-4 border-b border-gray-100">
            <div class="min-w-0">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Supplier</p>
                <p class="text-[13px] font-black text-primary-dark leading-tight">{{ $bill->supplier->name ?? '—' }}</p>
                @if($bill->supplier?->phone)
                    <p class="text-[11px] font-bold text-gray-400 mt-0.5">{{ $bill->supplier->phone }}</p>
                @endif
            </div>
            <div class="text-right shrink-0">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Date</p>
                <p class="text-[12px] font-black text-primary-dark">{{ \Carbon\Carbon::parse($bill->bill_date)->format('d M, Y') }}</p>
                @if($bill->supplier_invoice_no)
                    <p class="text-[11px] font-bold text-gray-400 mt-0.5">Ref: {{ $bill->supplier_invoice_no }}</p>
                @endif
            </div>
        </div>

        {{-- Items (mobile rows, no sideways scroll) --}}
        <div class="px-4 pt-3 pb-1 flex items-center justify-between">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Items ({{ $bill->items->count() }})</p>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Amount</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($bill->items as $i => $item)
                <div class="px-4 py-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[13px] font-black text-primary-dark leading-tight">
                            <span class="text-gray-300 font-bold mr-1">{{ $i + 1 }}.</span>{{ $item->product_name ?? ($item->product->product_name ?? 'Product') }}
                        </p>
                        <p class="text-[11px] font-bold text-gray-400 mt-1">
                            {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} {{ $item->unit ?? '' }} × {{ $symbol }} {{ number_format($item->unit_price, 2) }}
                            @if($item->discount > 0)
                                <span class="text-primary ml-1">− {{ $symbol }} {{ number_format($item->discount, 2) }} disc</span>
                            @endif
                        </p>
                    </div>
                    <p class="text-[13px] font-black text-primary-dark shrink-0">{{ $symbol }} {{ number_format($item->total_amount, 2) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Totals --}}
        <div class="border-t border-dashed border-gray-200 px-4 py-4 space-y-2">
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Subtotal</span>
                <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($bill->subtotal, 2) }}</span>
            </div>
            @if(($bill->vat ?? 0) > 0)
                <div class="flex justify-between items-center text-[12px]">
                    <span class="font-bold text-gray-400">VAT</span>
                    <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($bill->vat, 2) }}</span>
                </div>
            @endif

            <div class="bg-primary rounded-xl px-4 py-3 flex items-center justify-between !mt-3">
                <span class="text-[11px] font-black text-white uppercase tracking-wider">Grand Total</span>
                <span class="text-[16px] font-black text-accent">{{ $symbol }} {{ number_format($bill->total_amount, 2) }}</span>
            </div>

            <div class="flex justify-between items-center text-[12px] !mt-3">
                <span class="font-bold text-gray-400">Amount Paid</span>
                <span class="font-black text-primary-dark">{{ $symbol }} {{ number_format($bill->paid_amount, 2) }}</span>
            </div>
            <div class="flex justify-between items-center px-3 py-2.5 rounded-lg {{ $due > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
                <span class="font-black uppercase tracking-tight text-[11px] {{ $due > 0 ? 'text-red-500' : 'text-gray-500' }}">Balance Due</span>
                <span class="font-black text-[13px] {{ $due > 0 ? 'text-red-500' : 'text-primary-dark' }}">{{ $symbol }} {{ number_format($due, 2) }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 bg-background/50 border-t border-gray-100 text-center">
            <p class="text-[10px] font-bold text-gray-400">Thank you for your business! · {{ $company->name ?? 'Waafibook' }}</p>
        </div>
    </div>

    {{-- Notes --}}
    @if($bill->notes)
        <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Notes</p>
            <p class="text-[13px] font-bold text-gray-600 leading-relaxed whitespace-pre-line">{{ $bill->notes }}</p>
        </div>
    @endif

    {{-- Record Info --}}
    <div class="mx-5 mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-gray-100 bg-background/50">
            <i class="bi bi-info-circle text-primary-dark text-sm"></i>
            <h2 class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Record Info</h2>
        </div>
        <div class="px-4 py-4 space-y-3">
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Branch</span>
                <span class="font-black text-primary-dark">{{ $bill->branch->name ?? '—' }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Created By</span>
                <span class="font-black text-primary-dark">{{ $bill->creator->name ?? 'System' }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Created On</span>
                <span class="font-black text-primary-dark">{{ $bill->created_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="flex justify-between items-center text-[12px]">
                <span class="font-bold text-gray-400">Last Updated</span>
                <span class="font-black text-primary-dark">{{ $bill->updated_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
