@php
    $headerCompany = \App\Models\Company::find(Auth::user()->company_id);
    $headerSubscription = $headerCompany
        ? \App\Models\Subscription::where('company_id', $headerCompany->id)->latest()->first()
        : null;
    $trialDaysLeft = null;
    $trialProgressPercent = 0;
    if ($headerSubscription && $headerSubscription->status === 'trial') {
        $trialStart = \Carbon\Carbon::parse($headerSubscription->start_date)->startOfDay();
        $trialEnd = \Carbon\Carbon::parse($headerSubscription->expiry_date)->startOfDay();
        $trialDaysLeft = max(0, now()->startOfDay()->diffInDays($trialEnd, false));
        $trialTotalDays = max(1, $trialStart->diffInDays($trialEnd));
        $trialProgressPercent = min(100, max(0, round((($trialTotalDays - $trialDaysLeft) / $trialTotalDays) * 100)));
    }
@endphp

<header class="bg-white border-b border-border h-20 sticky top-0 z-40 lg:ml-[260px]">
    <div class="flex items-center justify-between h-full px-8">
        <!-- Left Section: Company Badge & Name -->
        <div class="flex items-center gap-4">
            <button id="menuToggle" class="lg:hidden p-2 text-text-secondary hover:text-primary transition-colors">
                <i class="bi bi-list text-2xl"></i>
            </button>

            <div class="hidden md:block">
                <span class="text-[13px] font-bold text-primary-dark uppercase tracking-wide max-w-[180px] truncate">{{ $headerCompany->name ?? 'Horntech LTD' }}</span>
            </div>
        </div>

        <!-- Right Section -->
        <div class="flex items-center gap-6">
            <!-- Date -->
            <span class="hidden lg:inline text-[13px] font-semibold text-text-secondary">{{ now()->format('D, M j, Y') }}</span>

            <!-- Notification Bell & Dropdown -->
            @php $totalNotifs = $lowStockAlerts->count() + $globalBackups->count(); @endphp
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="relative p-2 text-gray-400 hover:text-primary transition-all focus:outline-none">
                    <i class="bi bi-bell text-2xl"></i>
                    @if($totalNotifs > 0)
                        <span class="absolute top-1 right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[9px] font-black rounded-full border-2 border-white flex items-center justify-center px-0.5">{{ $totalNotifs }}</span>
                    @endif
                </button>

                <!-- Notification Dropdown -->
                <div x-show="open"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                     class="absolute right-0 mt-3 w-[340px] bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">

                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-xs font-black text-primary-dark uppercase tracking-widest">Notifications</h3>
                        <span class="px-2 py-0.5 bg-red-50 text-red-600 text-[9px] font-bold rounded-full border border-red-100">{{ $totalNotifs }} Alert(s)</span>
                    </div>

                    <div class="max-h-[380px] overflow-y-auto custom-scrollbar">

                        {{-- Low Stock Alerts --}}
                        @if($lowStockAlerts->count() > 0)
                            <div class="px-5 py-2 bg-red-50/60 border-b border-red-100">
                                <span class="text-[9px] font-black text-red-500 uppercase tracking-widest">⚠ Low Stock — Critical</span>
                            </div>
                            @foreach($lowStockAlerts as $item)
                            <a href="{{ route('low-stock.view') }}" class="flex gap-3 px-5 py-3.5 hover:bg-red-50/40 border-b border-gray-50 transition-colors group">
                                <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center text-red-500 shrink-0 group-hover:bg-red-500 group-hover:text-white transition-all duration-200">
                                    <i class="bi bi-exclamation-triangle-fill text-sm"></i>
                                </div>
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    <p class="text-[12px] font-bold text-primary-dark leading-tight truncate">{{ $item->name }}</p>
                                    <p class="text-[10px] text-red-500 font-bold">Stock: {{ $item->current_stock }} / Min: {{ $item->low_stock_threshold }}</p>
                                    <span class="text-[9px] font-black text-white bg-red-500 rounded px-1.5 py-0.5 w-fit uppercase tracking-tighter">CRITICAL</span>
                                </div>
                            </a>
                            @endforeach
                        @endif

                        {{-- Backup Alerts --}}
                        @if($globalBackups->count() > 0)
                            <div class="px-5 py-2 bg-primary/5 border-b border-primary/10">
                                <span class="text-[9px] font-black text-primary uppercase tracking-widest">✓ Recent Backups</span>
                            </div>
                            @foreach($globalBackups as $notif)
                            <div class="px-5 py-3.5 hover:bg-slate-50 border-b border-gray-50 transition-colors group">
                                <div class="flex gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/5 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary group-hover:text-white transition-all duration-200">
                                        <i class="bi bi-shield-check text-sm"></i>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <p class="text-[12px] font-bold text-primary-dark leading-tight">Backup Verified</p>
                                        <p class="text-[10px] text-slate-500">{{ $notif->filename }}</p>
                                        <span class="text-[9px] text-slate-400 italic">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endif

                        @if($totalNotifs === 0)
                        <div class="px-5 py-10 text-center">
                            <div class="flex flex-col items-center gap-2 opacity-30">
                                <i class="bi bi-bell-slash text-3xl text-gray-400"></i>
                                <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">No Alerts</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($lowStockAlerts->count() > 0)
                    <a href="{{ route('low-stock.view') }}" class="block px-5 py-3 bg-red-50 text-center text-[10px] font-bold text-red-600 hover:text-red-700 transition-colors uppercase tracking-widest border-t border-red-100">
                        View All Stock Alerts →
                    </a>
                    @else
                    <a href="{{ route('backup-restore') }}" class="block px-5 py-3 bg-gray-50 text-center text-[10px] font-bold text-primary hover:text-primary-dark transition-colors uppercase tracking-widest border-t border-gray-100">
                        View Archival Ledger →
                    </a>
                    @endif
                </div>
            </div>
            
            <!-- Vertical Divider -->
            <div class="h-10 w-px bg-gray-100 hidden sm:block"></div>

            <!-- User Menu Profile with Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 group cursor-pointer focus:outline-none">
                    <!-- Avatar -->
                    <div class="w-11 h-11 bg-primary rounded-full flex items-center justify-center shadow-md shadow-primary/20 group-hover:scale-105 transition-all duration-300 border border-white overflow-hidden">
                        @if(Auth::user()->photo)
                            <img src="{{ url('upload/user_images/'.Auth::user()->photo) }}" class="w-full h-full object-cover" alt="User">
                        @else
                            <span class="text-white text-sm font-black uppercase tracking-tighter">{{ substr(Auth::user()->name, 0, 2) }}</span>
                        @endif
                    </div>
                    
                    <!-- Text Details -->
                    <div class="hidden md:flex flex-col text-left leading-tight">
                        <div class="flex items-center gap-2">
                            <span class="text-[15px] font-bold text-[#004161] tracking-tight">{{ Auth::user()->fullname ?? Auth::user()->name }}</span>
                            <i class="bi bi-chevron-down text-gray-400 text-[10px] transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                        </div>
                        <span class="text-[12px] text-gray-400 font-medium uppercase tracking-wide">{{ Auth::user()->role ?? 'Administrator' }}</span>
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                     class="absolute right-0 mt-2 w-[280px] bg-white rounded-[20px] overflow-hidden profile-dropdown-shadow z-50">

                    <!-- Dropdown Header -->
                    <div class="bg-gray-50 px-4 py-4 flex items-center gap-3">
                        <div class="w-11 h-11 bg-primary rounded-full flex items-center justify-center text-white border border-white overflow-hidden shrink-0">
                            @if(Auth::user()->photo)
                                <img src="{{ url('upload/user_images/'.Auth::user()->photo) }}" class="w-full h-full object-cover" alt="User">
                            @else
                                <span class="text-sm font-bold">{{ substr(Auth::user()->name, 0, 2) }}</span>
                            @endif
                        </div>
                        <div class="leading-tight min-w-0">
                            <h4 class="text-primary-dark font-bold text-sm leading-tight truncate">{{ Auth::user()->fullname ?? Auth::user()->name }}</h4>
                            <p class="text-gray-400 text-[12px] font-medium mt-0.5 truncate">{{ Auth::user()->email }}</p>
                            <p class="text-accent text-[11px] font-black mt-1 uppercase tracking-wider">{{ Auth::user()->role ?? 'Administrator' }}</p>
                        </div>
                    </div>

                    @if(!is_null($trialDaysLeft))
                    <div class="px-4 py-3">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="flex items-center gap-1.5 text-[13px] font-bold text-amber-700">
                                    <i class="bi bi-exclamation-triangle-fill text-[12px]"></i> {{ $trialDaysLeft }} {{ Str::plural('day', $trialDaysLeft) }} left
                                </span>
                                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">Trial</span>
                            </div>
                            <div class="w-full h-1.5 bg-amber-200/60 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $trialProgressPercent }}%"></div>
                            </div>
                            <p class="text-[11px] text-amber-700">Trial ending soon — contact support.</p>
                        </div>
                    </div>
                    @endif

                    <!-- Dropdown Links -->
                    <div class="py-2 px-1 border-t border-gray-100">
                        <a href="{{ route('profile-user') }}" class="flex items-center gap-3 px-3 py-2.5 text-primary font-bold hover:bg-gray-50 rounded-lg transition-colors">
                            <i class="bi bi-person text-lg"></i>
                            <span class="text-[14px]">My Profile</span>
                        </a>
                        <a href="{{ route('lock-screen') }}" class="flex items-center gap-3 px-3 py-2.5 text-primary font-bold hover:bg-gray-50 rounded-xl transition-colors">
                            <i class="bi bi-lock text-lg"></i>
                            <span class="text-[14px]">Lock Screen</span>
                        </a>
                        <button @click="open = false; $dispatch('open-contact-support')" class="w-full flex items-center gap-3 px-3 py-2.5 text-primary font-bold hover:bg-gray-50 rounded-xl transition-colors text-left cursor-pointer">
                            <i class="bi bi-chat-dots text-lg"></i>
                            <span class="text-[14px]">Contact Support</span>
                        </button>
                    </div>

                    <!-- Logout -->
                    <div class="border-t border-gray-100 py-2 px-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-error font-bold hover:bg-red-50 rounded-lg transition-colors text-left cursor-pointer">
                                <i class="bi bi-box-arrow-right text-lg"></i>
                                <span class="text-[14px]">Sign out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div x-data="{ show: true, contactOpen: false }" @open-contact-support.window="contactOpen = true">
    @if(!is_null($trialDaysLeft))
    <div x-show="show" x-cloak
         class="lg:ml-[260px] bg-amber-50 border-b border-amber-100 px-8 py-2.5 flex items-center justify-between gap-4">
        <p class="text-[13px] font-semibold text-amber-700">
            Your free trial ends in {{ $trialDaysLeft }} {{ Str::plural('day', $trialDaysLeft) }}. Subscribe now to keep your books running smoothly.
        </p>
        <div class="flex items-center gap-4 shrink-0">
            <a href="#" @click.prevent="contactOpen = true" class="text-[13px] font-bold text-amber-700 underline hover:text-amber-900 transition-colors">Contact us</a>
            <button @click="show = false" class="text-amber-500 hover:text-amber-700 transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    @endif

    <!-- Support Contact Popup -->
    <div x-show="contactOpen" x-cloak @click.away="contactOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="fixed bottom-6 right-6 w-[300px] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-[60]">

        <div class="bg-primary px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white rounded-xl flex items-center justify-center shadow-sm overflow-hidden shrink-0">
                    <img src="{{ asset('upload/horntechlogo/Waafibook_logo.jpg') }}" class="w-full h-full object-contain" alt="Waafibook">
                </div>
                <div class="leading-tight">
                    <h4 class="text-white font-bold text-[13px]">Waafibook Support</h4>
                    <p class="text-white/60 text-[10px] font-medium flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent"></span> We reply within a few hours
                    </p>
                </div>
            </div>
            <button @click="contactOpen = false" class="text-white/60 hover:text-white transition-colors">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <div class="p-5">
            <p class="text-[12px] text-text-secondary mb-4">Have a question or ready to subscribe? Pick the easiest way to reach us.</p>

            <a href="https://wa.me/252600000000" target="_blank" class="flex items-center justify-center gap-2 w-full bg-accent text-white font-bold text-[13px] rounded-xl py-2.5 mb-4 hover:opacity-90 transition-opacity">
                <i class="bi bi-whatsapp text-lg"></i> Chat on WhatsApp
            </a>

            <div class="flex items-center gap-3 mb-3">
                <i class="bi bi-envelope text-primary opacity-50"></i>
                <a href="mailto:support@horntech.com" class="text-[13px] font-semibold text-primary-dark hover:text-primary transition-colors">support@horntech.com</a>
            </div>
            <div class="flex items-center gap-3 mb-3">
                <i class="bi bi-telephone text-primary opacity-50"></i>
                <a href="tel:+252600000000" class="text-[13px] font-semibold text-primary-dark hover:text-primary transition-colors">+252 60 000 0000</a>
            </div>
            <div class="flex items-center gap-3">
                <i class="bi bi-telephone text-primary opacity-50"></i>
                <a href="tel:+252610000000" class="text-[13px] font-semibold text-primary-dark hover:text-primary transition-colors">+252 61 000 0000</a>
            </div>
        </div>
    </div>
</div>
