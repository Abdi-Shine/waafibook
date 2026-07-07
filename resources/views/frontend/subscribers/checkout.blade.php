@extends('admin.admin_master')
@section('page_title', 'Checkout — ' . $plan->name)

@section('admin')

@php
    use App\Models\SystemSetting;
    $currency           = $company->currency ?? '$';
    $isRenewal          = $currentSubscription && $currentSubscription->subscription_plan_id === $plan->id;
    $isSwitch           = $currentSubscription && $currentSubscription->subscription_plan_id !== $plan->id;
    $cycleLabel         = match($plan->billing_cycle) {
        'yearly'    => 'year',
        'quarterly' => 'quarter',
        default     => 'month',
    };
    $evcMerchant        = SystemSetting::get('evc_merchant_number', '—');
    $bankName           = SystemSetting::get('bank_name', '—');
    $bankAccountName    = SystemSetting::get('bank_account_name', '—');
    $bankAccountNumber  = SystemSetting::get('bank_account_number', '—');
    $bankSwift          = SystemSetting::get('bank_swift_code');
@endphp

<div class="px-4 py-10 md:px-10 md:py-14 bg-background min-h-screen">
    <div class="max-w-3xl mx-auto">

        {{-- Back --}}
        <a href="{{ route('subscribers.subscriptions.index') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-primary mb-8 transition-colors">
            <i class="bi bi-arrow-left"></i> Back to Plans
        </a>

        {{-- Flash --}}
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm font-semibold">
                <i class="bi bi-exclamation-circle-fill text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif
        @error('transaction_ref')
            <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm font-semibold">
                <i class="bi bi-exclamation-circle-fill text-red-500"></i> {{ $message }}
            </div>
        @enderror

        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-start">

            {{-- ── LEFT: Payment Form ──────────────────────────────── --}}
            <div class="md:col-span-3 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-base font-black text-primary uppercase tracking-tight">
                        {{ $isRenewal ? 'Renew Subscription' : ($isSwitch ? 'Switch Plan' : 'Subscribe to Plan') }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Submit your payment details for the {{ $plan->name }} plan. An administrator will review and activate your subscription.</p>
                </div>

                <form method="POST" action="{{ route('subscribers.checkout.pay', $plan->id) }}"
                      x-data="{ method: 'evc_mobile' }" class="p-6 space-y-6">
                    @csrf

                    {{-- Payment Method Selector --}}
                    <div>
                        <label class="block text-xs font-black text-primary uppercase tracking-wider mb-3">Payment Method</label>
                        <div class="grid grid-cols-2 gap-3">

                            {{-- EVC Mobile Money --}}
                            <label :class="method === 'evc_mobile'
                                ? 'border-accent bg-accent/5 ring-1 ring-accent'
                                : 'border-gray-200 hover:border-gray-300'"
                                class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all">
                                <input type="radio" name="payment_method" value="evc_mobile"
                                       x-model="method" class="hidden">
                                <div :class="method === 'evc_mobile' ? 'bg-accent' : 'bg-gray-100'"
                                     class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                                    <i class="bi bi-phone-fill text-sm"
                                       :class="method === 'evc_mobile' ? 'text-white' : 'text-gray-400'"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-primary leading-tight">EVC Plus</p>
                                    <p class="text-[10px] text-gray-400 font-medium">Mobile Money</p>
                                </div>
                            </label>

                            {{-- Bank Transfer --}}
                            <label :class="method === 'bank_transfer'
                                ? 'border-accent bg-accent/5 ring-1 ring-accent'
                                : 'border-gray-200 hover:border-gray-300'"
                                class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all">
                                <input type="radio" name="payment_method" value="bank_transfer"
                                       x-model="method" class="hidden">
                                <div :class="method === 'bank_transfer' ? 'bg-accent' : 'bg-gray-100'"
                                     class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                                    <i class="bi bi-bank2 text-sm"
                                       :class="method === 'bank_transfer' ? 'text-white' : 'text-gray-400'"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-primary leading-tight">Bank</p>
                                    <p class="text-[10px] text-gray-400 font-medium">Transfer</p>
                                </div>
                            </label>

                        </div>
                    </div>

                    {{-- EVC Mobile Money Fields --}}
                    <div x-show="method === 'evc_mobile'" x-transition>
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-4">
                            <p class="text-xs font-black text-blue-700 uppercase tracking-wider mb-1">How to Pay via EVC Plus</p>
                            <ol class="text-xs text-blue-600 space-y-1 font-medium list-decimal list-inside">
                                <li>Dial <strong>*712#</strong> or open your EVC Plus app</li>
                                <li>Select <strong>Send Money</strong></li>
                                <li>Enter merchant number: <strong class="text-blue-800">{{ $evcMerchant }}</strong></li>
                                <li>Enter amount: <strong class="text-blue-800">{{ $currency }}{{ number_format($plan->price, 2) }}</strong></li>
                                <li>Enter your PIN and confirm</li>
                                <li>Copy the <strong>transaction reference</strong> from your SMS and paste it below</li>
                            </ol>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-primary mb-1.5">Your EVC Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}"
                                   placeholder="e.g. 0615 123 456"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium text-primary focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition">
                        </div>
                    </div>

                    {{-- Bank Transfer Fields --}}
                    <div x-show="method === 'bank_transfer'" x-transition x-cloak>
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-4 space-y-2">
                            <p class="text-xs font-black text-amber-700 uppercase tracking-wider mb-2">Bank Transfer Details</p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                <div>
                                    <p class="text-amber-500 font-semibold">Bank Name</p>
                                    <p class="text-amber-800 font-black">{{ $bankName }}</p>
                                </div>
                                <div>
                                    <p class="text-amber-500 font-semibold">Account Name</p>
                                    <p class="text-amber-800 font-black">{{ $bankAccountName }}</p>
                                </div>
                                <div>
                                    <p class="text-amber-500 font-semibold">Account Number</p>
                                    <p class="text-amber-800 font-black">{{ $bankAccountNumber }}</p>
                                </div>
                                @if($bankSwift)
                                <div>
                                    <p class="text-amber-500 font-semibold">SWIFT / BIC</p>
                                    <p class="text-amber-800 font-black">{{ $bankSwift }}</p>
                                </div>
                                @endif
                                <div>
                                    <p class="text-amber-500 font-semibold">Amount</p>
                                    <p class="text-amber-800 font-black">{{ $currency }}{{ number_format($plan->price, 2) }}</p>
                                </div>
                            </div>
                            <p class="text-[10px] text-amber-600 mt-2 font-medium">
                                Use your company name as the transfer reference. After transferring, enter the bank reference number below.
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-primary mb-1.5">Bank Transfer Reference Number</label>
                            <input type="text" name="transaction_ref" value="{{ old('transaction_ref') }}"
                                   placeholder="e.g. TXN-20240701-0012"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium text-primary focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition">
                            <p class="text-[10px] text-gray-400 mt-1">Enter the reference or confirmation number from your bank receipt.</p>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full py-4 rounded-xl bg-accent text-white font-black text-sm uppercase tracking-widest hover:bg-accent/90 transition-all shadow-md shadow-accent/20">
                        <i class="bi bi-send me-2"></i> Submit Payment Request &mdash; {{ $currency }}{{ number_format($plan->price, 2) }}
                    </button>

                    <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl p-3 mt-2">
                        <i class="bi bi-info-circle-fill text-blue-400 mt-0.5 flex-shrink-0"></i>
                        <p class="text-[11px] text-blue-600 font-medium leading-relaxed">
                            Your payment request will be reviewed by the administrator. Your subscription will be activated once the payment is approved. You will see the status on your subscription page.
                        </p>
                    </div>
                </form>
            </div>

            {{-- ── RIGHT: Order Summary ─────────────────────────────── --}}
            <div class="md:col-span-2 space-y-4">

                {{-- Plan Summary --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Order Summary</p>

                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-base font-black text-primary uppercase">{{ $plan->name }}</h3>
                            <p class="text-xs text-gray-400 font-medium capitalize">Billed {{ $plan->billing_cycle }}</p>
                        </div>
                        <span class="text-base font-black text-primary">
                            {{ $currency }}{{ number_format($plan->price, 2) }}
                        </span>
                    </div>

                    <ul class="space-y-2 border-t border-gray-100 pt-4">
                        <li class="flex items-center gap-2 text-xs text-gray-600 font-medium">
                            <i class="bi bi-people text-accent"></i>
                            {{ $plan->max_users >= 9999 ? 'Unlimited' : $plan->max_users }} Users
                        </li>
                        <li class="flex items-center gap-2 text-xs text-gray-600 font-medium">
                            <i class="bi bi-hdd text-accent"></i>
                            {{ $plan->storage_limit_gb }} GB Storage
                        </li>
                        @foreach(($plan->features ?? []) as $feature)
                            <li class="flex items-center gap-2 text-xs text-gray-600 font-medium">
                                <i class="bi bi-check-lg text-accent"></i>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-gray-100 mt-4 pt-4 flex justify-between items-center">
                        <span class="text-xs font-black text-primary uppercase">Total Due</span>
                        <span class="text-xl font-black text-primary">{{ $currency }}{{ number_format($plan->price, 2) }}</span>
                    </div>

                    <p class="text-[10px] text-gray-400 font-medium mt-2">
                        Access valid for 1 {{ $cycleLabel }} from payment confirmation.
                    </p>
                </div>

                {{-- Current Plan Note --}}
                @if($currentSubscription)
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-xs text-gray-500 font-medium">
                        <p class="font-black text-primary mb-1 text-[11px] uppercase tracking-wider">Current Subscription</p>
                        <p>{{ $currentSubscription->plan->name ?? '—' }} plan, expires
                            <span class="font-bold text-primary">
                                {{ \Carbon\Carbon::parse($currentSubscription->expiry_date)->format('d M Y') }}
                            </span>
                        </p>
                        @if($isSwitch)
                            <p class="mt-1 text-amber-600 font-semibold">
                                Switching will replace your current plan immediately.
                            </p>
                        @endif
                    </div>
                @endif

            </div>
        </div>{{-- /grid --}}

    </div>
</div>

@endsection
