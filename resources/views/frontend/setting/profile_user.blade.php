@extends('admin.admin_master')
@section('page_title', 'My Profile')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">My Profile</h1>
            <p class="text-[13px] text-gray-400 font-medium mt-0.5">Your account details and recent activity</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Profile Card -->
        <div class="lg:col-span-4 bg-white rounded-2xl p-6 shadow-sm border border-border text-center">
            <div class="w-20 h-20 mx-auto bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-black uppercase shadow-md overflow-hidden">
                @if(auth()->user()->photo)
                    <img src="{{ url('upload/user_images/'.auth()->user()->photo) }}" class="w-full h-full object-cover" alt="User">
                @else
                    {{ substr(auth()->user()->name, 0, 2) }}
                @endif
            </div>
            <h2 class="text-[16px] font-bold text-primary-dark mt-4">{{ auth()->user()->fullname ?? auth()->user()->name }}</h2>
            <p class="text-[13px] text-gray-400 mt-0.5">{{ auth()->user()->email }}</p>
            <span class="inline-block mt-2 px-3 py-1 bg-blue-50 text-blue-600 text-[11px] font-black uppercase tracking-widest rounded-full">
                {{ auth()->user()->role ?? 'Administrator' }}
            </span>

            <a href="{{ route('company-settings') }}" class="mt-6 flex items-center justify-center gap-2 w-full bg-primary text-white font-bold text-[13px] rounded-xl py-2.5 hover:bg-primary-dark transition-colors">
                <i class="bi bi-pencil-square"></i> Edit Profile
            </a>
        </div>

        <!-- Stats + Activity -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-border">
                    <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary mb-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <p class="text-[20px] font-black text-primary-dark">{{ $stats['transactions'] }}</p>
                    <p class="text-[11px] text-gray-400 font-medium">Transactions</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-border">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent mb-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <p class="text-[20px] font-black text-primary-dark">{{ $stats['products'] }}</p>
                    <p class="text-[11px] text-gray-400 font-medium">Products Added</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-border">
                    <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <p class="text-[20px] font-black text-primary-dark">{{ $stats['customers'] }}</p>
                    <p class="text-[11px] text-gray-400 font-medium">Total Customers</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-border">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent mb-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <p class="text-[20px] font-black text-primary-dark">{{ $stats['logs_count'] }}</p>
                    <p class="text-[11px] text-gray-400 font-medium">Activity Logs</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-border">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-text-primary">Recent Activity</h3>
                    <a href="{{ route('audit-logs') }}" class="text-[11px] font-bold text-primary opacity-50 hover:opacity-100">VIEW ALL</a>
                </div>
                @forelse($activities as $activity)
                    <div class="flex items-start gap-3 py-2.5 border-b border-gray-50 last:border-0">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                            <i class="bi bi-pencil-square text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-primary-dark">
                                {{ ucfirst($activity->action) }} <span class="text-text-secondary font-normal">in {{ $activity->module }}</span>
                            </p>
                            @if($activity->description)
                                <p class="text-[12px] text-text-secondary truncate">{{ $activity->description }}</p>
                            @endif
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-[13px] text-text-secondary text-center py-6">No activity recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
