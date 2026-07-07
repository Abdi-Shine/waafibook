@extends('admin.admin_master')
@section('page_title', 'My Subscription')

@section('admin')
@php
    $sub          = $latestSub ?? null;
    $subStatus    = $sub?->status;
    $activePlanId = $sub?->subscription_plan_id;
    $activePlanPrice = (float) ($sub?->plan?->price ?? 0);
    $isPending    = $subStatus === 'pending_payment';
    $isActive     = in_array($subStatus, ['active', 'trial']);
    $isExpired    = in_array($subStatus, ['expired', 'cancelled']);
    $pendingPmt   = $sub?->payments?->where('status', 'pending')->sortByDesc('id')->first();
    $lastPayment  = $sub?->payments?->where('status', 'completed')->sortByDesc('payment_date')->first();
    $expiryDate   = $sub?->expiry_date  ? \Carbon\Carbon::parse($sub->expiry_date)  : null;
    $startDate    = $sub?->start_date   ? \Carbon\Carbon::parse($sub->start_date)   : null;
    $daysLeft     = $expiryDate ? (int) now()->diffInDays($expiryDate, false) : null;
    $uPct  = $maxUsers  < 999 ? min(100, round(($usedUsers  / max(1, $maxUsers))       * 100)) : 0;
    $sPct  = $maxStorageGB < 999 ? min(100, round(($usedStorageGB / max(0.001, $maxStorageGB)) * 100)) : 0;
    $sUsed = $usedStorageGB < 0.01 ? round($usedStorageGB * 1024, 1) . ' MB' : round($usedStorageGB, 2) . ' GB';
@endphp

<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-[22px] font-bold text-primary-dark">My Subscription</h1>
        <p class="text-[13px] text-gray-400 font-medium mt-0.5">Manage your active subscription and billing</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-6 text-[13px] font-semibold flex items-center gap-2">
        <i class="bi bi-check-circle-fill text-green-500"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 mb-6 text-[13px] font-semibold flex items-center gap-2">
        <i class="bi bi-exclamation-circle-fill text-red-500"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Pending Approval Banner --}}
    @if($isPending && $pendingPmt)
    <div class="bg-amber-50 border border-amber-200 rounded-[1rem] px-5 py-4 mb-6 flex items-start gap-3">
        <i class="bi bi-hourglass-split text-amber-500 text-xl mt-0.5 flex-shrink-0"></i>
        <div>
            <p class="text-[13px] font-black text-amber-700">Payment Request Pending Approval</p>
            <p class="text-[12px] text-amber-600 mt-0.5">
                Your payment of <strong>${{ number_format($pendingPmt->amount, 2) }}</strong>
                via <strong>{{ str_replace('_', ' ', ucwords($pendingPmt->payment_method, '_')) }}</strong>
                @if($pendingPmt->transaction_id)(Ref: <code class="bg-amber-100 px-1 rounded text-[11px]">{{ $pendingPmt->transaction_id }}</code>)@endif
                is awaiting administrator approval. Subscription activates once approved.
            </p>
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════
         AVAILABLE PLANS
    ═══════════════════════════════════════════════════════════ --}}
    @if($plans->count())
    <div class="mb-8">
        <h2 class="text-[14px] font-black text-primary-dark mb-4 uppercase tracking-wider flex items-center gap-2">
            <i class="bi bi-grid-3x2-gap text-primary"></i> Available Plans
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($plans as $plan)
            @php
                $__cur      = ($activePlanId !== null) && ((int)$activePlanId === (int)$plan->id);
                $__paid     = ($sub !== null) && ($subStatus === 'active');
                $__pPrice   = (float)$plan->price;
                $__curPrice = (float)($sub?->plan?->price ?? 0);
                $__border   = $__cur ? '2px solid #004161' : ($plan->is_popular ? '2px solid rgba(153,204,51,.5)' : '2px solid #e5e7eb');
            @endphp

            <div class="relative flex flex-col bg-white rounded-[1.1rem] shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200"
                 style="border: {{ $__border }};">

                {{-- Badge --}}
                @if($__cur)
                <div class="absolute top-3.5 right-3.5 text-white text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full tracking-wider"
                     style="background:#004161;">Current Plan</div>
                @elseif($plan->is_popular)
                <div class="absolute top-3.5 right-3.5 text-white text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full tracking-wider"
                     style="background:#99CC33;">Popular</div>
                @endif

                <div class="p-5 flex flex-col h-full">
                    {{-- Name + desc --}}
                    <div class="mb-3 pr-16">
                        <p class="text-[15px] font-black text-primary-dark">{{ $plan->name }}</p>
                        @if($plan->description)
                        <p class="text-[11px] text-gray-400 mt-0.5 leading-snug">{{ $plan->description }}</p>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div class="mb-4">
                        <span class="text-[26px] font-black text-primary-dark">${{ number_format($plan->price, 0) }}</span>
                        <span class="text-[11px] text-gray-400 font-medium"> / {{ $plan->billing_cycle }}</span>
                    </div>

                    {{-- Features --}}
                    <ul class="flex flex-col gap-1.5 mb-5 flex-1">
                        <li class="flex items-center gap-2 text-[11px] text-gray-600">
                            <i class="bi bi-people-fill text-[10px]" style="color:#99CC33;"></i>
                            Up to <strong class="text-primary-dark">{{ $plan->max_users }}</strong> users
                        </li>
                        @if($plan->storage_limit_gb)
                        <li class="flex items-center gap-2 text-[11px] text-gray-600">
                            <i class="bi bi-hdd-fill text-[10px]" style="color:#99CC33;"></i>
                            <strong class="text-primary-dark">{{ $plan->storage_limit_gb }} GB</strong> storage
                        </li>
                        @endif
                        @foreach(array_slice($plan->features ?? [], 0, 4) as $__feat)
                        <li class="flex items-center gap-2 text-[11px] text-gray-600">
                            <i class="bi bi-check-lg text-[10px]" style="color:#99CC33;"></i>
                            {{ $__feat }}
                        </li>
                        @endforeach
                        @if(count($plan->features ?? []) > 4)
                        <li class="text-[10px] text-gray-400 pl-4">+{{ count($plan->features) - 4 }} more features</li>
                        @endif
                    </ul>

                    {{-- CTA button — inline styles to prevent Tailwind JIT purging --}}
                    @if($isPending)
                        <div class="w-full text-center py-2.5 px-4 font-bold rounded-lg text-[12px]"
                             style="background:#fffbeb;border:1px solid #fcd34d;color:#b45309;">
                            <i class="bi bi-hourglass-split me-1"></i> Payment Pending
                        </div>
                    @elseif($__paid && $__cur)
                        <div class="w-full text-center py-2.5 px-4 font-bold rounded-lg text-[12px]"
                             style="background:rgba(0,65,97,.07);color:#004161;border:1px solid rgba(0,65,97,.2);">
                            <i class="bi bi-check-circle me-1"></i> Active Plan
                        </div>
                    @elseif($__paid && $__pPrice > $__curPrice)
                        <a href="{{ route('subscribers.checkout', $plan->id) }}"
                           class="w-full text-center block py-2.5 px-4 font-bold rounded-lg text-[12px] transition-opacity hover:opacity-90"
                           style="background:#99CC33;color:#fff;">
                            <i class="bi bi-arrow-up-circle me-1"></i> Upgrade Plan
                        </a>
                    @elseif($__paid && $__pPrice < $__curPrice)
                        <a href="{{ route('subscribers.checkout', $plan->id) }}"
                           class="w-full text-center block py-2.5 px-4 font-bold rounded-lg text-[12px] transition-all hover:opacity-90"
                           style="background:#fff;border:2px solid #99CC33;color:#99CC33;">
                            <i class="bi bi-arrow-down-circle me-1"></i> Switch Plan
                        </a>
                    @else
                        {{-- No active paid plan (new / trial / expired / cancelled) --}}
                        <a href="{{ route('subscribers.checkout', $plan->id) }}"
                           class="w-full text-center block py-2.5 px-4 font-bold rounded-lg text-[12px] transition-opacity hover:opacity-90"
                           style="background:#99CC33;color:#fff;">
                            Choose Plan
                        </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════
         CURRENT SUBSCRIPTION
    ═══════════════════════════════════════════════════════════ --}}
    @if($sub)
    <div class="mb-8">
        <h2 class="text-[14px] font-black text-primary-dark mb-4 uppercase tracking-wider flex items-center gap-2">
            <i class="bi bi-receipt text-primary"></i> Your Subscription
        </h2>

        @php
            $statusColor = match($subStatus) {
                'active'          => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'icon-bg' => 'bg-green-100',  'icon-text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-700',  'icon' => 'check-circle-fill'],
                'trial'           => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'icon-bg' => 'bg-blue-100',   'icon-text' => 'text-blue-700',   'badge' => 'bg-blue-100 text-blue-700',   'icon' => 'clock-fill'],
                'pending_payment' => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200',  'icon-bg' => 'bg-amber-100',  'icon-text' => 'text-amber-700',  'badge' => 'bg-amber-100 text-amber-700', 'icon' => 'hourglass-split'],
                default           => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'icon-bg' => 'bg-red-100',    'icon-text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-600',     'icon' => 'x-circle-fill'],
            };
            $statusLabel = match($subStatus) {
                'active'          => 'Active',
                'trial'           => 'Trial',
                'pending_payment' => 'Pending Approval',
                'expired'         => 'Expired',
                'cancelled'       => 'Cancelled',
                default           => ucfirst($subStatus),
            };
        @endphp

        <div class="bg-white rounded-[1.1rem] border-2 {{ $statusColor['border'] }} shadow-sm overflow-hidden">

            {{-- Header row --}}
            <div class="px-5 py-4 flex items-center justify-between gap-3 {{ $statusColor['bg'] }} border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center {{ $statusColor['icon-bg'] }} {{ $statusColor['icon-text'] }} flex-shrink-0">
                        <i class="bi bi-{{ $statusColor['icon'] }} text-[14px]"></i>
                    </div>
                    <div>
                        <p class="text-[13px] font-black text-primary-dark">{{ $sub->plan->name ?? 'Unknown Plan' }}</p>
                        <p class="text-[11px] text-gray-400">{{ ucfirst($sub->plan->billing_cycle ?? 'monthly') }} billing</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColor['badge'] }}">
                    {{ $statusLabel }}
                </span>
            </div>

            {{-- Stats grid --}}
            <div class="p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Plan Price</p>
                    <p class="text-[17px] font-black text-primary-dark">
                        ${{ number_format($sub->plan->price ?? 0, 2) }}
                    </p>
                    @if($lastPayment)
                    <p class="text-[10px] text-gray-400 mt-0.5">Last paid {{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') }}</p>
                    @endif
                </div>

                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Started</p>
                    <p class="text-[13px] font-semibold text-gray-700">
                        {{ $startDate ? $startDate->format('d M Y') : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Expires</p>
                    <p class="text-[13px] font-semibold {{ $daysLeft !== null && $daysLeft <= 7 ? 'text-red-600' : 'text-gray-700' }}">
                        {{ $expiryDate ? $expiryDate->format('d M Y') : '—' }}
                    </p>
                    @if($daysLeft !== null && $daysLeft > 0)
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $daysLeft }}d remaining</p>
                    @elseif($daysLeft !== null && $daysLeft <= 0)
                        <p class="text-[10px] text-red-500 font-bold mt-0.5">Expired</p>
                    @endif
                </div>

                {{-- Users meter --}}
                @php $uColor = $uPct >= 100 ? 'bg-red-500' : ($uPct >= 80 ? 'bg-yellow-400' : 'bg-[#99CC33]'); @endphp
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Users</p>
                    <p class="text-[17px] font-black {{ $uPct >= 100 ? 'text-red-600' : 'text-primary-dark' }}">
                        {{ $usedUsers }}
                        <span class="text-[12px] font-semibold text-gray-400">/ {{ $maxUsers < 999 ? $maxUsers : '∞' }}</span>
                    </p>
                    @if($maxUsers < 999)
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                        <div class="h-1.5 rounded-full {{ $uColor }}" style="width:{{ $uPct }}%"></div>
                    </div>
                    <p class="text-[10px] mt-0.5 {{ $uPct >= 100 ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                        {{ $uPct >= 100 ? 'Limit reached' : "{$uPct}% of plan limit" }}
                    </p>
                    @endif
                </div>

                {{-- Storage meter --}}
                @php $sColor = $sPct >= 100 ? 'bg-red-500' : ($sPct >= 80 ? 'bg-yellow-400' : 'bg-[#99CC33]'); @endphp
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Storage</p>
                    <p class="text-[17px] font-black {{ $sPct >= 100 ? 'text-red-600' : 'text-primary-dark' }}">
                        {{ $sUsed }}
                        <span class="text-[12px] font-semibold text-gray-400">/ {{ $maxStorageGB < 999 ? $maxStorageGB . ' GB' : '∞' }}</span>
                    </p>
                    @if($maxStorageGB < 999)
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                        <div class="h-1.5 rounded-full {{ $sColor }}" style="width:{{ $sPct }}%"></div>
                    </div>
                    <p class="text-[10px] mt-0.5 {{ $sPct >= 100 ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                        {{ $sPct >= 100 ? 'Storage full' : "{$sPct}% used" }}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Limit warnings --}}
            @if($uPct >= 100 || $sPct >= 100)
            <div class="px-5 pb-5">
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-[12px] text-red-700 font-semibold flex items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5"></i>
                    <div>
                        @if($uPct >= 100)<div>User limit reached — you cannot add more users until you upgrade.</div>@endif
                        @if($sPct >= 100)<div>Storage limit reached — delete old backups or upgrade your plan.</div>@endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Included features --}}
            @if($sub->plan && count($sub->plan->features ?? []))
            <div class="px-5 pb-5">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Included Features</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($sub->plan->features as $feat)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#99CC33]/10 text-primary-dark rounded-full text-[11px] font-semibold">
                        <i class="bi bi-check-lg text-[#99CC33]"></i> {{ $feat }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="bg-white rounded-[1.1rem] border border-gray-100 shadow-sm p-10 text-center mb-8">
        <i class="bi bi-credit-card text-[3rem] text-gray-200 block mb-4"></i>
        <h3 class="text-[16px] font-black text-primary-dark mb-2">No Active Subscription</h3>
        <p class="text-[13px] text-gray-400 mb-6">Choose a plan above to get started.</p>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════
         PAYMENT HISTORY
    ═══════════════════════════════════════════════════════════ --}}
    @if(isset($paymentHistory) && $paymentHistory->count())
    <div>
        <h2 class="text-[14px] font-black text-primary-dark mb-4 uppercase tracking-wider flex items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Payment History
        </h2>

        <div class="bg-white rounded-[1.1rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="text-left px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="text-left px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Method</th>
                            <th class="text-left px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Reference</th>
                            <th class="text-right px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="text-center px-5 py-3 font-black text-[10px] text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentHistory as $pmt)
                        @php
                            $pmtPlan = $pmt->subscription?->plan?->name ?? '—';
                            $pmtBadge = match($pmt->status) {
                                'completed' => 'bg-green-100 text-green-700',
                                'pending'   => 'bg-amber-100 text-amber-700',
                                'rejected'  => 'bg-red-100 text-red-700',
                                default     => 'bg-gray-100 text-gray-600',
                            };
                            $pmtLabel = match($pmt->status) {
                                'completed' => 'Approved',
                                'pending'   => 'Pending',
                                'rejected'  => 'Rejected',
                                default     => ucfirst($pmt->status),
                            };
                            $pmtMethodLabel = str_replace('_', ' ', ucwords($pmt->payment_method, '_'));
                        @endphp
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-semibold text-primary-dark">{{ $pmtPlan }}</td>
                            <td class="px-5 py-3 text-gray-500">
                                {{ $pmt->payment_date ? \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 capitalize">{{ $pmtMethodLabel }}</td>
                            <td class="px-5 py-3">
                                @if($pmt->transaction_id)
                                <code class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[11px]">{{ $pmt->transaction_id }}</code>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-black text-primary-dark">
                                ${{ number_format($pmt->amount, 2) }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $pmtBadge }}">
                                    {{ $pmtLabel }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
