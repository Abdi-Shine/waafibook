@extends('admin.admin_master')
@section('page_title', 'Subscription Plans')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[22px] font-bold text-primary-dark">Choose Your Perfect Plan</h1>
            <p class="text-[13px] text-gray-400 font-medium mt-0.5">
                Flexible, scalable subscription plans designed to grow with your business.
            </p>
        </div>
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

    {{-- Pricing Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        @forelse($plans as $plan)
        @php
            $isPopular   = $plan->is_popular ?? false;
            $isEnterprise = strtolower($plan->billing_cycle ?? '') === 'custom' || $plan->price == 0;
        @endphp
        <div class="relative bg-white rounded-[1.2rem] border-2 {{ $isPopular ? 'border-accent shadow-xl' : 'border-gray-100 shadow-sm' }} flex flex-col transition-all hover:-translate-y-1 hover:shadow-lg duration-200">
            @if($isPopular)
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-accent text-primary-dark text-[11px] font-black uppercase tracking-wider px-4 py-1 rounded-full shadow">
                ⭐ Most Popular
            </div>
            @endif

            <div class="p-6 flex flex-col h-full">
                {{-- Plan name & price --}}
                <h3 class="text-[18px] font-black text-primary-dark mb-1">{{ $plan->name }}</h3>
                @if($plan->description)
                <p class="text-[12px] text-gray-400 mb-4">{{ $plan->description }}</p>
                @endif

                <div class="mb-5">
                    @if($plan->price > 0)
                    <div class="flex items-end gap-1">
                        <span class="text-[13px] font-semibold text-gray-500 mb-1">$</span>
                        <span class="text-[2.4rem] font-black text-primary-dark leading-none">{{ number_format($plan->price, 0) }}</span>
                        <span class="text-[13px] text-gray-400 mb-1">/{{ $plan->billing_cycle }}</span>
                    </div>
                    @else
                    <div class="text-[2rem] font-black text-primary-dark">Custom</div>
                    <div class="text-[12px] text-gray-400 mt-1">Contact us for pricing</div>
                    @endif
                </div>

                {{-- Limits --}}
                <ul class="space-y-2 mb-6 flex-1">
                    <li class="flex items-center gap-2 text-[12px] text-gray-600">
                        <i class="bi bi-people-fill text-accent text-sm"></i>
                        Up to <strong>{{ $plan->max_users }}</strong> users
                    </li>
                    <li class="flex items-center gap-2 text-[12px] text-gray-600">
                        <i class="bi bi-hdd-fill text-accent text-sm"></i>
                        <strong>{{ $plan->storage_limit_gb }} GB</strong> cloud storage
                    </li>
                    @if($plan->features && count($plan->features))
                        @foreach($plan->features as $feature)
                        <li class="flex items-center gap-2 text-[12px] text-gray-600">
                            <i class="bi bi-check-circle-fill text-accent text-sm"></i>
                            {{ $feature }}
                        </li>
                        @endforeach
                    @endif
                </ul>

                {{-- CTA --}}
                @if($plan->price > 0)
                <form method="POST" action="{{ route('subscribers.plans.store') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button type="submit"
                        class="w-full py-3 rounded-[0.6rem] font-bold text-[13px] transition-all duration-200
                        {{ $isPopular
                            ? 'bg-accent text-primary-dark hover:bg-accent/90'
                            : 'bg-primary text-white hover:bg-primary-dark' }}">
                        Select This Plan
                    </button>
                </form>
                @else
                <a href="{{ route('demo.request') }}"
                    class="block w-full py-3 text-center rounded-[0.6rem] font-bold text-[13px] border-2 border-primary text-primary hover:bg-primary hover:text-white transition-all duration-200">
                    Contact Sales
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-16 text-gray-400">
            <i class="bi bi-tags text-[3rem] block mb-3 opacity-20"></i>
            <p class="text-[14px] font-semibold">No plans available at this time.</p>
        </div>
        @endforelse
    </div>

    {{-- Comparison Table --}}
    @if($plans->count() > 0)
    <div class="bg-white rounded-[1.2rem] border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 bg-background/50">
            <h2 class="text-[13px] font-black text-primary-dark uppercase tracking-wider">Detailed Feature Comparison</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead>
                    <tr class="bg-primary text-white">
                        <th class="text-left px-5 py-3 font-bold">Feature</th>
                        @foreach($plans as $plan)
                        <th class="text-center px-5 py-3 font-bold">{{ $plan->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-gray-50">
                        <td colspan="{{ $plans->count() + 1 }}" class="px-5 py-2 font-black text-primary-dark uppercase text-[11px] tracking-wider">
                            Capacity & Limits
                        </td>
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3 font-semibold text-gray-700">Max Users</td>
                        @foreach($plans as $plan)
                        <td class="px-5 py-3 text-center font-semibold text-primary-dark">{{ $plan->max_users }}</td>
                        @endforeach
                    </tr>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3 font-semibold text-gray-700">Cloud Storage</td>
                        @foreach($plans as $plan)
                        <td class="px-5 py-3 text-center font-semibold text-primary-dark">{{ $plan->storage_limit_gb }} GB</td>
                        @endforeach
                    </tr>
                    @php
                        $allFeatures = $plans->flatMap(fn($p) => $p->features ?? [])->unique()->values();
                    @endphp
                    @if($allFeatures->count())
                    <tr class="bg-gray-50">
                        <td colspan="{{ $plans->count() + 1 }}" class="px-5 py-2 font-black text-primary-dark uppercase text-[11px] tracking-wider">
                            Included Features
                        </td>
                    </tr>
                    @foreach($allFeatures as $feat)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3 font-semibold text-gray-700">{{ $feat }}</td>
                        @foreach($plans as $plan)
                        <td class="px-5 py-3 text-center">
                            @if(in_array($feat, $plan->features ?? []))
                                <i class="bi bi-check-circle-fill text-accent text-base"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-gray-300 text-base"></i>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
