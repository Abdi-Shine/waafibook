@extends('admin.admin_master')
@section('page_title', 'Feature Locked')

@section('admin')
<div class="flex items-center justify-center min-h-[70vh] px-4">
    <div class="text-center max-w-sm w-full">

        {{-- Lock icon --}}
        <div class="flex justify-center mb-6">
            <div style="width:80px;height:80px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-lock-fill" style="font-size:2rem;color:#9ca3af;"></i>
            </div>
        </div>

        {{-- Title --}}
        <h2 style="font-size:1.25rem;font-weight:800;color:#111827;margin-bottom:.5rem;">
            {{ $lockTitle ?? 'Upgrade to unlock this feature' }}
        </h2>

        {{-- Description --}}
        <p style="font-size:.875rem;color:#6b7280;line-height:1.6;margin-bottom:1.75rem;">
            @if(!empty($requiredPlan))
                This feature is available on the <strong>{{ $requiredPlan }}</strong> plan and above.
                Upgrade to access it and grow your business with Waafibook.
            @else
                {{ $lockMessage ?? 'Your trial has expired. Please subscribe to a plan to continue using this feature. Your existing data remains safe and accessible.' }}
            @endif
        </p>

        {{-- Action button --}}
        <a href="{{ route('subscribers.plans.index') }}"
           style="display:inline-flex;align-items:center;gap:.5rem;background:#004161;color:#fff;border-radius:10px;padding:.7rem 1.75rem;font-size:.9rem;font-weight:700;text-decoration:none;margin-bottom:1rem;">
            {{ !empty($requiredPlan) ? 'Upgrade Plan →' : 'Subscribe Now →' }}
        </a>

        {{-- Current plan --}}
        @if(!empty($currentPlanName))
        <p style="font-size:.78rem;color:#9ca3af;margin-top:.75rem;">
            Current plan: <strong style="color:#374151;">{{ strtoupper($currentPlanName) }}</strong>
        </p>
        @endif

    </div>
</div>
@endsection
