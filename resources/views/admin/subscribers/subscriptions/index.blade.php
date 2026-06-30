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

    @forelse($subscriptions as $sub)
    @php
        $isActive    = $sub->status === 'active';
        $isTrial     = $sub->status === 'trial';
        $isExpired   = $sub->status === 'expired';
        $expiryDate  = $sub->expiry_date ? \Carbon\Carbon::parse($sub->expiry_date) : null;
        $startDate   = $sub->start_date  ? \Carbon\Carbon::parse($sub->start_date)  : null;
        $daysLeft    = $expiryDate ? now()->diffInDays($expiryDate, false) : null;
        $lastPayment = $sub->payments()->where('status','completed')->latest('payment_date')->first();
    @endphp
    <div class="bg-white rounded-[1.2rem] border-2 {{ $isActive || $isTrial ? 'border-accent/40' : 'border-red-200' }} shadow-sm mb-6 overflow-hidden">
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3
                    {{ $isActive || $isTrial ? 'bg-accent/5' : 'bg-red-50' }} border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                            {{ $isActive ? 'bg-accent/20 text-primary-dark' : ($isTrial ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') }}">
                    <i class="bi bi-{{ $isActive ? 'check-circle-fill' : ($isTrial ? 'clock-fill' : 'x-circle-fill') }}"></i>
                </div>
                <div>
                    <p class="text-[13px] font-black text-primary-dark">{{ $sub->plan->name ?? 'Unknown Plan' }}</p>
                    <p class="text-[11px] text-gray-400">
                        {{ ucfirst($sub->plan->billing_cycle ?? 'monthly') }} billing
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider
                        {{ $isActive ? 'bg-green-100 text-green-700' : ($isTrial ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') }}">
                {{ ucfirst($sub->status) }}
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
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Renews / Expires</p>
                <p class="text-[14px] font-semibold {{ $daysLeft !== null && $daysLeft <= 7 ? 'text-red-600' : 'text-gray-700' }}">
                    {{ $expiryDate ? $expiryDate->format('d M Y') : '—' }}
                    @if($daysLeft !== null && $daysLeft > 0)
                        <span class="text-[11px] text-gray-400">({{ $daysLeft }}d left)</span>
                    @elseif($daysLeft !== null && $daysLeft <= 0)
                        <span class="text-[11px] text-red-500">(Expired)</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Max Users</p>
                <p class="text-[14px] font-semibold text-gray-700">
                    {{ $sub->plan->max_users ?? '—' }}
                </p>
            </div>
        </div>

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
