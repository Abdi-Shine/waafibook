<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Waafibook') }} - Advanced Business Intelligence</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Global CSS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="auth-page-bg font-inter">

    {{-- Floating Decoration --}}
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-accent/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-accent/5 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-4xl px-6 py-12 flex flex-col items-center">
        
        {{-- Branding Section --}}
        <div class="text-center mb-12 animate-fadeIn">
            <div class="w-20 h-20 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-6 border border-white/20 shadow-xl">
                <i class="bi bi-cpu text-accent text-4xl"></i>
            </div>
            <h1 class="text-white text-5xl md:text-6xl font-black tracking-tighter mb-4 font-display">
                WAAFIBOOK <span class="text-accent">POS</span>
            </h1>
            <p class="text-white/60 text-lg md:text-xl font-medium max-w-2xl mx-auto">
                Next-generation point of sale and inventory management engineered for speed, accuracy, and enterprise scalability.
            </p>
        </div>

        {{-- Main Action Card --}}
        <div class="auth-card w-full max-w-md animate-fadeIn" style="animation-delay: 0.1s;">
            <div class="auth-header-gradient p-8 text-center text-white auth-header-border">
                <h2 class="text-xl font-black uppercase tracking-widest">Enterprise Access</h2>
                <p class="text-white/60 text-xs font-bold mt-1">SECURE TERMINAL GATEWAY</p>
            </div>

            <div class="p-10 bg-white">
                <div class="space-y-6">
                    @auth
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                                <i class="bi bi-person-check text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Active Session</p>
                                <p class="text-[15px] font-bold text-primary-dark">{{ Auth::user()->name }}</p>
                            </div>
                        </div>

                        <a href="{{ url('/dashboard') }}" class="btn-auth">
                            <i class="bi bi-speedometer2 text-lg mr-2"></i>
                            Launch Dashboard
                        </a>
                    @else
                        <div class="text-center mb-8">
                            <p class="text-gray-400 text-sm font-medium">Please sign in to your corporate account to access the POS terminal and inventory analytics.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <a href="{{ route('login') }}" class="btn-auth mt-0">
                                <i class="bi bi-box-arrow-in-right mr-2"></i>
                                Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-auth mt-0 bg-primary-dark hover:bg-black text-white">
                                    <i class="bi bi-person-plus mr-2"></i>
                                    Register
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full pulse-dot"></span>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">System Operational</span>
                    </div>
                    <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">v2.4.1-STABLE</span>
                </div>
            </div>
        </div>

        {{-- Feature Highlights --}}
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 w-full animate-fadeIn" style="animation-delay: 0.2s;">
            <div class="bg-white/5 backdrop-blur-sm p-6 rounded-2xl border border-white/10 hover:border-accent/30 transition-all group">
                <i class="bi bi-lightning-charge text-accent text-2xl mb-4 block group-hover:scale-110 transition-transform"></i>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-2">Real-time Sync</h3>
                <p class="text-white/40 text-xs leading-relaxed">Automatic inventory reconciliation across all branches and warehouses instantly.</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm p-6 rounded-2xl border border-white/10 hover:border-accent/30 transition-all group">
                <i class="bi bi-shield-lock text-accent text-2xl mb-4 block group-hover:scale-110 transition-transform"></i>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-2">Secure Ledger</h3>
                <p class="text-white/40 text-xs leading-relaxed">Enterprise-grade encryption for all financial transactions and audit logs.</p>
            </div>
            <div class="bg-white/5 backdrop-blur-sm p-6 rounded-2xl border border-white/10 hover:border-accent/30 transition-all group">
                <i class="bi bi-graph-up-arrow text-accent text-2xl mb-4 block group-hover:scale-110 transition-transform"></i>
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-2">Advanced BI</h3>
                <p class="text-white/40 text-xs leading-relaxed">Deep-dive analytics and automated reporting to drive your business growth.</p>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="mt-16 text-center">
            <p class="text-white/30 text-[11px] font-bold uppercase tracking-[0.3em]">
                &copy; {{ date('Y') }} WAAFIBOOK • ALL RIGHTS RESERVED
            </p>
        </footer>
    </div>

</body>
</html>
