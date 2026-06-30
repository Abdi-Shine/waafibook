@extends('admin.admin_master')
@section('admin')

<div x-data="{
    showApproveModal: false,
    rejectReason: '',
    sendEmailOrder() {
        Swal.fire({
            title: 'Sending Order',
            text: 'Send PDF order manifest to {{ $po->supplier->email }}?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Send Now',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return axios.post('/purchase/orders/email/{{ $po->id }}')
                    .then(response => {
                        return response.data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    Swal.fire({ title: 'Success!', text: result.value.message, icon: 'success', confirmButtonColor: '#004161' });
                } else {
                    Swal.fire({ title: 'Error!', text: result.value.message, icon: 'error', confirmButtonColor: '#004161' });
                }
            }
        });
    },
    confirmStatusAction(status) {
        let title = status === 'approved' ? 'Verify Sync Approval?' : 'Verify Sync Rejection?';
        let subText = `Confirm status transition to ${status.toUpperCase()}?`;
        
        Swal.fire({
            title: title,
            text: subText,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#004161',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'OK',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                let reason = status === 'cancelled' ? this.rejectReason : '';
                axios.post(`/purchase/orders/update-status/{{ $po->id }}`, { 
                    status: status,
                    notes: reason 
                })
                .then(response => {
                    if (response.data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', error.response?.data?.message || 'Something went wrong', 'error');
                });
            }
        });
    }
}" class="px-4 py-8 md:px-8 md:py-10 bg-[#f8fafc] min-h-screen w-full max-w-full overflow-hidden font-inter relative">

    @php
        $currencySymbols = ['SAR' => '﷼', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'د.إ', 'KWD' => 'د.ك', 'SOS' => 'SOS', 'KES' => 'KSh'];
        $symbol = $currencySymbols[$company->currency ?? ''] ?? ($company->currency ?? '$');
    @endphp

    <!-- Quick Action Toolbar (New Design) -->
    <div class="print:hidden flex items-center justify-between gap-4 mb-6 px-4 py-3 bg-white border-b border-gray-100">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase.order.download', $po->id) }}" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-bold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-xs">
                <i class="bi bi-download text-[#004161]"></i> Save PDF
            </a>
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-primary-dark font-bold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-xs">
                <i class="bi bi-printer text-[#004161]"></i> Print
            </button>
            <button @click="sendEmailOrder()" class="flex items-center gap-2 px-4 py-2 bg-[#A4D65E] border border-[#A4D65E] text-primary-dark font-bold rounded-lg hover:bg-[#A4D65E]/90 transition-all shadow-sm text-xs">
                <i class="bi bi-envelope text-[#004161]"></i> Email PDF
            </button>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Badges & Primary Actions -->
            @if($po->status === 'approved' || $po->status === 'received')
                <span class="px-3 py-1.5 rounded-lg bg-[#0B5567]/5 text-[#0B5567] text-[10px] font-bold border border-[#0B5567]/20 uppercase">Approved</span>
            @endif
            
            @if($po->status === 'pending')
                <button @click="confirmStatusAction('approved')" class="px-4 py-2 bg-white text-[#0B5567] text-xs font-bold rounded-lg border border-[#0B5567]/20 hover:bg-[#0B5567]/5 transition-all shadow-sm">
                    Approve
                </button>
            @endif

            @if($po->status === 'pending' || $po->status === 'approved')
                <button @click="confirmStatusAction('cancelled')" class="px-4 py-2 bg-white text-[#ef4444] text-xs font-bold rounded-lg border border-[#ef4444]/20 hover:bg-primary/10 transition-all shadow-sm">
                    Cancel PO
                </button>
            @endif

            <button class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 font-bold rounded-lg hover:bg-gray-50 transition-all shadow-sm text-xs">
                <i class="bi bi-gear text-[#004161]"></i> Options
            </button>
            <a href="{{ route('purchase.order.index') }}" class="flex items-center gap-2 px-5 py-2 bg-[#0B5567] text-white font-bold rounded-lg hover:bg-[#0B5567]/90 transition-all shadow-sm text-xs">
                <i class="bi bi-x-lg"></i> Close
            </a>
        </div>
    </div>

    <!-- Printable Area -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 md:p-8 print:p-0 print:border-none print:shadow-none print:rounded-none w-full max-w-full overflow-x-hidden">
        
        <!-- Brand Header -->
        <div class="flex justify-between items-end border-b border-gray-100 pb-6 mb-8">
            <div class="flex items-center gap-4">
                @if(isset($company) && $company->logo)
                    <img src="{{ asset($company->logo) }}" alt="Logo" class="h-16 object-contain">
                @else
                    <div class="h-16 w-16 bg-[#0B5567] rounded-xl flex items-center justify-center text-white">
                        <i class="bi bi-building text-3xl"></i>
                    </div>
                @endif
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-black text-[#0B5567] mb-1">{{ $company->name ?? '' }}</h1>
                <p class="text-xs font-semibold text-gray-400">
                    Phone: {{ $company->phone ?? '-' }} &bull; Email: {{ $company->email ?? '-' }}
                </p>
            </div>
        </div>

        <!-- Order Context Info & Supplier Details -->
        <div class="flex flex-col md:flex-row justify-between mb-8 gap-6">
            <div>
                <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-2">Supplier Details</h6>
                <h2 class="text-xl font-bold text-primary-dark">{{ $po->supplier->name ?? 'Unknown Supplier' }}</h2>
                <div class="text-xs font-semibold text-gray-400 mt-2 space-y-1">
                    <p>Supplier ID: <span class="text-primary">{{ $po->supplier->supplier_code ?? 'N/A' }}</span></p>
                    <p>Phone: <span class="text-gray-900">{{ $po->supplier->phone ?? '-' }}</span></p>
                </div>
            </div>
            <div class="md:text-right bg-gray-50 rounded-xl p-4 border border-gray-100 h-fit w-full sm:w-auto sm:min-w-[300px]">
                <div class="space-y-2">
                    <div class="flex justify-between gap-4 sm:gap-8 md:justify-end">
                        <span class="text-xs font-bold text-primary-dark uppercase tracking-wider">Order Date:</span>
                        <span class="text-xs font-bold text-primary-dark">{{ \Carbon\Carbon::parse($po->order_date)->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between gap-4 sm:gap-8 md:justify-end">
                        <span class="text-xs font-bold text-primary-dark uppercase tracking-wider">Order #:</span>
                        <span class="text-xs font-bold text-primary-dark">{{ $po->po_number }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Manifest -->
        <div class="mb-8">
            <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-3">Product Manifest</h6>
            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Transaction</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-center">SKU / Code</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wider text-center">Status</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wide text-right">Qty</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wide text-right">Unit cost</th>
                            <th class="px-4 py-3 text-xs font-bold text-primary-dark uppercase tracking-wide text-right">Total cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs font-medium text-gray-700">
                        @foreach($po->items as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-4 py-3 font-semibold">{{ \Carbon\Carbon::parse($po->order_date)->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#0B5567]/10 text-[#0B5567] uppercase">
                                    PURCHASE
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400">{{ $item->product->product_code ?? 'N/A' }}</td>
                            <td class="px-4 py-3 whitespace-normal min-w-[200px]">
                                <span class="font-bold text-primary-dark">{{ $item->product_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded border text-[10px] font-bold uppercase tracking-wider {{ $po->status === 'received' ? 'border-accent/20 bg-accent/10 text-accent' : 'border-primary/20 bg-primary/10 text-primary' }}">
                                    {{ $po->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($item->quantity, 0) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $symbol }}{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right font-black text-primary-dark">
                                {{ $symbol }}{{ number_format($item->total_amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50/50 border-t border-gray-100">
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-right text-xs font-bold text-primary-dark uppercase tracking-wider">TOTALS:</td>
                            <td class="px-4 py-4 text-right font-black text-xl text-primary-dark bg-white border-l border-gray-100 shadow-sm">
                                {{ $symbol }}{{ number_format($po->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Summary & Footer -->
        <div class="flex flex-col md:flex-row gap-6 mb-6">
            <div class="md:w-[40%] p-6 rounded-xl border border-gray-100 bg-gray-50/50">
                <h6 class="text-xs font-bold text-primary-dark uppercase tracking-wider mb-4 border-b border-gray-100 pb-2"><i class="bi bi-graph-up mr-2"></i>Order Summary</h6>
                <div class="space-y-3 text-sm font-semibold text-gray-500">
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Total Items:</span>
                        <strong class="text-gray-800">{{ $po->items->count() }}</strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Paid Amount:</span>
                        <strong class="text-accent">{{ $symbol }}{{ number_format($po->paid_amount ?? 0, 2) }}</strong>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100/70 pb-2">
                        <span>Balance Due:</span>
                        <strong class="text-primary">{{ $symbol }}{{ number_format($po->total_amount - ($po->paid_amount ?? 0), 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="md:w-[60%] flex flex-col justify-end text-center md:text-right print:text-center p-6">
                <p class="mb-2 text-primary-dark font-black text-lg">Thank you for your business!</p>
                <p class="text-xs font-semibold text-gray-400 max-w-sm md:ml-auto print:mx-auto">
                    This is a computer-generated statement and does not require a signature. <br>
                    For any queries, please contact us at <span class="text-gray-600">{{ $company->email ?? '' }}</span> or call <span class="text-gray-600">{{ $company->phone ?? '' }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Approval Modal (Screenshot Style) -->
    <div x-show="showApproveModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm print:hidden"
         class="alpine-hidden">
        
        <div class="bg-white rounded-[1.5rem] w-full max-w-[480px] p-8 shadow-2xl" @click.away="showApproveModal = false">
            <h2 class="text-[28px] font-black text-[#0B5567] mb-6">Approve Purchase Order</h2>
            
            <!-- Summary Card -->
            <div class="bg-[#f0f7ff] rounded-2xl p-6 mb-8 border border-[#d1e9ff]">
                <div class="grid grid-cols-2 gap-y-6">
                    <div>
                        <p class="text-[13px] font-bold text-primary mb-1">PO Number</p>
                        <p class="text-[18px] font-black text-[#0B5567]">{{ $po->po_number }}</p>
                    </div>
                    <div>
                        <p class="text-[13px] font-bold text-primary mb-1">Vendor</p>
                        <p class="text-[18px] font-black text-[#0B5567] truncate">{{ $po->supplier->name ?? 'Unknown' }}</p>
                    </div>
                    <div>
                        <p class="text-[13px] font-bold text-primary mb-1">Total Amount</p>
                        <p class="text-[18px] font-black text-[#0B5567]">{{ $symbol }} {{ number_format($po->total_amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-[13px] font-bold text-primary mb-1">Expected Date</p>
                        <p class="text-[18px] font-black text-[#0B5567]">{{ $po->expected_delivery ? date('M j, Y', strtotime($po->expected_delivery)) : 'TBD' }}</p>
                    </div>
                </div>
            </div>

            <!-- Reject Reason -->
            <div class="mb-8">
                <label class="block text-[15px] font-bold text-gray-600 mb-3">Reject Reason (Optional)</label>
                <textarea x-model="rejectReason" 
                          placeholder="Enter reason for rejection..." 
                          class="w-full h-32 p-4 rounded-xl border border-gray-200 focus:border-[#0B5567] focus:ring-0 text-[15px] text-gray-700 resize-none transition-all placeholder:text-gray-300 bg-gray-50/30"></textarea>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center gap-3">
                <button @click="confirmStatusAction('approved')" 
                        class="flex-1 py-4 bg-[#10b981] text-white font-bold text-[16px] rounded-xl hover:bg-[#059669] transition-all shadow-md">
                    Confirm Approve
                </button>
                <button @click="confirmStatusAction('cancelled')" 
                        class="flex-1 py-4 bg-[#ef4444] text-white font-bold text-[16px] rounded-xl hover:bg-[#dc2626] transition-all shadow-md">
                    Reject
                </button>
                <button @click="showApproveModal = false" 
                        class="px-8 py-4 bg-[#e5e7eb] text-gray-700 font-bold text-[16px] rounded-xl hover:bg-gray-300 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

