@extends('admin.admin_master')
@section('page_title', 'My Subscription')

@section('admin')
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

    {{-- Available Plans from Super Admin --}}
    @if($plans->count())
    <div class="mb-8">
        <h2 class="text-[14px] font-black text-primary-dark mb-3 uppercase tracking-wider flex items-center gap-2">
            <i class="bi bi-grid-3x2-gap text-primary"></i> Available Plans
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($plans as $plan)
            @php $isCurrent = $plan->id === $activePlanId; @endphp
            <div class="bg-white rounded-[1rem] border-2 {{ $isCurrent ? 'border-primary shadow-md' : 'border-gray-100 shadow-sm' }} p-5 flex flex-col relative hover:-translate-y-0.5 transition-transform duration-200">
                @if($isCurrent)
                <span class="absolute top-3 right-3 bg-primary text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-full tracking-wider">Current</span>
                @endif
                @if($plan->is_popular && !$isCurrent)
                <span class="absolute top-3 right-3 bg-accent text-primary-dark text-[9px] font-black uppercase px-2 py-0.5 rounded-full tracking-wider">Popular</span>
                @endif

                <div class="mb-3">
                    <p class="text-[15px] font-black text-primary-dark">{{ $plan->name }}</p>
                    @if($plan->description)
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $plan->description }}</p>
                    @endif
                </div>

                <div class="mb-3">
                    <span class="text-[24px] font-black text-primary">${{ number_format($plan->price, 0) }}</span>
                    <span class="text-[11px] text-gray-400 font-medium"> / {{ $plan->billing_cycle }}</span>
                </div>

                <div class="flex flex-col gap-1.5 mb-4 flex-1">
                    <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                        <i class="bi bi-people text-primary text-[10px]"></i>
                        Up to <strong class="text-primary-dark">{{ $plan->max_users }}</strong> users
                    </div>
                    @if($plan->storage_limit_gb)
                    <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                        <i class="bi bi-hdd text-primary text-[10px]"></i>
                        <strong class="text-primary-dark">{{ $plan->storage_limit_gb }} GB</strong> storage
                    </div>
                    @endif
                    @if($plan->features && count($plan->features))
                        @foreach(array_slice($plan->features, 0, 3) as $feature)
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                            <i class="bi bi-check-lg text-accent text-[10px]"></i> {{ $feature }}
                        </div>
                        @endforeach
                        @if(count($plan->features) > 3)
                        <p class="text-[10px] text-gray-400 pl-4">+{{ count($plan->features) - 3 }} more features</p>
                        @endif
                    @endif
                </div>

                @if(!$isCurrent)
                <a href="{{ route('subscribers.checkout', $plan->id) }}"
                   class="mt-auto w-full text-center py-2 px-4 bg-primary text-white font-bold rounded-lg text-[12px] hover:bg-primary/90 transition-all">
                    Choose Plan
                </a>
                @else
                <div class="mt-auto w-full text-center py-2 px-4 bg-primary/10 text-primary font-bold rounded-lg text-[12px]">
                    Active Plan
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Current Subscription Details --}}
    @if($subscriptions->count())
    <h2 class="text-[14px] font-black text-primary-dark mb-3 uppercase tracking-wider flex items-center gap-2">
        <i class="bi bi-receipt text-primary"></i> Your Subscription
    </h2>
    @endif

    @forelse($subscriptions as $sub)
    @php
        $isActive    = $sub->status === 'active';
        $isTrial     = $sub->status === 'trial';
        $isPending   = $sub->status === 'pending_payment';
        $isExpired   = $sub->status === 'expired';
        $expiryDate  = $sub->expiry_date ? \Carbon\Carbon::parse($sub->expiry_date) : null;
        $startDate   = $sub->start_date  ? \Carbon\Carbon::parse($sub->start_date)  : null;
        $daysLeft    = $expiryDate ? now()->diffInDays($expiryDate, false) : null;
        $lastPayment = $sub->payments()->where('status','completed')->latest('payment_date')->first();
        $pendingPmt  = $sub->payments()->where('status','pending')->latest()->first();
    @endphp

    {{-- Pending Approval Banner --}}
    @if($isPending && $pendingPmt)
    <div class="bg-amber-50 border border-amber-200 rounded-[1rem] px-5 py-4 mb-4 flex items-start gap-3">
        <i class="bi bi-hourglass-split text-amber-500 text-xl mt-0.5 flex-shrink-0"></i>
        <div>
            <p class="text-[13px] font-black text-amber-700">Payment Request Pending Approval</p>
            <p class="text-[12px] text-amber-600 mt-0.5">
                Your payment of <strong>${{ number_format($pendingPmt->amount, 2) }}</strong>
                via <strong>{{ $pendingPmt->payment_method }}</strong>
                (Ref: <code class="bg-amber-100 px-1 rounded text-[11px]">{{ $pendingPmt->transaction_id ?? '—' }}</code>)
                has been submitted and is awaiting administrator approval.
                Your subscription will be activated once approved.
            </p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-[1.2rem] border-2 {{ $isActive || $isTrial ? 'border-accent/40' : ($isPending ? 'border-amber-300' : 'border-red-200') }} shadow-sm mb-6 overflow-hidden">
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3
                    {{ $isActive || $isTrial ? 'bg-accent/5' : ($isPending ? 'bg-amber-50' : 'bg-red-50') }} border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                            {{ $isActive ? 'bg-accent/20 text-primary-dark' : ($isTrial ? 'bg-blue-100 text-blue-700' : ($isPending ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')) }}">
                    <i class="bi bi-{{ $isActive ? 'check-circle-fill' : ($isTrial ? 'clock-fill' : ($isPending ? 'hourglass-split' : 'x-circle-fill')) }}"></i>
                </div>
                <div>
                    <p class="text-[13px] font-black text-primary-dark">{{ $sub->plan->name ?? 'Unknown Plan' }}</p>
                    <p class="text-[11px] text-gray-400">
                        {{ ucfirst($sub->plan->billing_cycle ?? 'monthly') }} billing
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider
                        {{ $isActive ? 'bg-green-100 text-green-700' : ($isTrial ? 'bg-blue-100 text-blue-700' : ($isPending ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')) }}">
                {{ $isPending ? 'Pending Approval' : ucfirst($sub->status) }}
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                    {{ $lastPayment ? 'Last Payment' : 'Plan Price' }}
                </p>
                <p class="text-[18px] font-black text-primary-dark">
                    ${{ $lastPayment ? number_format($lastPayment->amount, 2) : number_format($sub->plan->price ?? 0, 2) }}
                </p>
                @if($lastPayment)
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        {{ $lastPayment->payment_method }} · {{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d M Y') }}
                    </p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Started</p>
                <p class="text-[14px] font-semibold text-gray-700">
                    {{ $startDate ? $startDate->format('d M Y') : '—' }}
                </p>
            </div>

            {{-- Users meter --}}
            @php
                $uPct   = $maxUsers < 999 ? min(100, round(($usedUsers / max(1,$maxUsers)) * 100)) : 0;
                $uColor = $uPct >= 100 ? 'bg-red-500' : ($uPct >= 80 ? 'bg-yellow-400' : 'bg-accent');
            @endphp
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Users</p>
                <p class="text-[18px] font-black {{ $uPct >= 100 ? 'text-red-600' : 'text-primary-dark' }}">
                    {{ $usedUsers }} <span class="text-[13px] font-semibold text-gray-400">/ {{ $maxUsers < 999 ? $maxUsers : '∞' }}</span>
                </p>
                @if($maxUsers < 999)
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                    <div class="h-1.5 rounded-full {{ $uColor }} transition-all" style="width:{{ $uPct }}%"></div>
                </div>
                <p class="text-[10px] mt-0.5 {{ $uPct >= 100 ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                    {{ $uPct >= 100 ? 'Limit reached' : "{$uPct}% of plan limit" }}
                </p>
                @endif
            </div>

            {{-- Storage meter --}}
            @php
                $sPct   = $maxStorageGB < 999 ? min(100, round(($usedStorageGB / max(0.001,$maxStorageGB)) * 100)) : 0;
                $sColor = $sPct >= 100 ? 'bg-red-500' : ($sPct >= 80 ? 'bg-yellow-400' : 'bg-accent');
                $sUsed  = $usedStorageGB < 0.01 ? round($usedStorageGB * 1024, 1) . ' MB' : round($usedStorageGB, 2) . ' GB';
            @endphp
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Storage</p>
                <p class="text-[18px] font-black {{ $sPct >= 100 ? 'text-red-600' : 'text-primary-dark' }}">
                    {{ $sUsed }} <span class="text-[13px] font-semibold text-gray-400">/ {{ $maxStorageGB < 999 ? $maxStorageGB . ' GB' : '∞' }}</span>
                </p>
                @if($maxStorageGB < 999)
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                    <div class="h-1.5 rounded-full {{ $sColor }} transition-all" style="width:{{ $sPct }}%"></div>
                </div>
                <p class="text-[10px] mt-0.5 {{ $sPct >= 100 ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                    {{ $sPct >= 100 ? 'Storage full' : "{$sPct}% used" }}
                </p>
                @endif
            </div>
        </div>

        {{-- Limit warnings --}}
        @if($uPct >= 100 || $sPct >= 100)
        <div class="px-6 pb-5">
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-[12px] text-red-700 font-semibold flex items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5"></i>
                <div>
                    @if($uPct >= 100) <div>User limit reached — you cannot add more users until you upgrade.</div> @endif
                    @if($sPct >= 100) <div>Storage limit reached — delete old backups or upgrade your plan to upload files.</div> @endif
                </div>
            </div>
        </div>
        @endif

        @if($sub->plan && $sub->plan->features && count($sub->plan->features))
        <div class="px-6 pb-6">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Included Features</p>
            <div class="flex flex-wrap gap-2">
                @foreach($sub->plan->features as $feature)
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-accent/10 text-primary-dark rounded-full text-[11px] font-semibold">
                    <i class="bi bi-check-lg text-accent"></i> {{ $feature }}
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @empty
    {{-- No subscription --}}
    <div class="bg-white rounded-[1.2rem] border border-gray-100 shadow-sm p-10 text-center">
        <i class="bi bi-credit-card text-[3rem] text-gray-200 block mb-4"></i>
        <h3 class="text-[16px] font-black text-primary-dark mb-2">No Active Subscription</h3>
        <p class="text-[13px] text-gray-400 mb-6">You are not subscribed to any plan yet. Choose a plan to get started.</p>
        <a href="{{ route('subscribers.plans.index') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-[0.6rem] text-[13px] hover:bg-primary-dark transition-all">
            <i class="bi bi-rocket-takeoff"></i> View Plans
        </a>
    </div>
    @endforelse


</div>
@endsection
