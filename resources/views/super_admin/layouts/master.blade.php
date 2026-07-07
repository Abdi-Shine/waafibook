@php
    // Second-most-recent LOGIN entry — the most recent one is the current
    // session itself, so "last login" means the one before it.
    $lastLogin = \App\Models\AuditLog::where('user_id', auth()->id())
        ->where('module', 'Authentication')
        ->where('action', 'LOGIN')
        ->orderByDesc('created_at')
        ->skip(1)
        ->first()?->created_at;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page_title', 'Dashboard') — Super Admin</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#004161">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WaafiBook">
    <link rel="apple-touch-icon"                    href="/icons/icon-180.png">
    <link rel="apple-touch-icon" sizes="152x152"    href="/icons/icon-152.png">
    <link rel="apple-touch-icon" sizes="167x167"    href="/icons/icon-167.png">
    <link rel="apple-touch-icon" sizes="180x180"    href="/icons/icon-180.png">
    <link rel="apple-touch-icon" sizes="192x192"    href="/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512.png">

    {{-- Tailwind first (sidebar), then Bootstrap (content area) --}}
    @vite(['resources/css/app.css'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Alpine.js for sidebar dropdowns --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <link href="{{ asset('css/super-admin.css') }}" rel="stylesheet">
    <style>
        /* Bootstrap overrides for sidebar */
        #sa-sidebar a,
        #sa-sidebar a:hover,
        #sa-sidebar a:visited,
        #sa-sidebar a:focus { text-decoration: none !important; }
        #sa-sidebar h4, #sa-sidebar p { margin: 0 !important; }
        #sa-sidebar button { background: transparent !important; border: none !important; box-shadow: none !important; outline: none !important; cursor: pointer; }
        #sa-sidebar { scrollbar-width: none !important; -ms-overflow-style: none !important; overflow-y: auto !important; }
        #sa-sidebar::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }

        /* ── Mobile hamburger button ── */
        .sa-hamburger {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #374151;
            cursor: pointer;
            font-size: 1.2rem;
            flex-shrink: 0;
            padding: 0;
        }

        /* ── Mobile backdrop overlay ── */
        .sa-overlay {
            position: fixed !important;
            inset: 0 !important;
            background: rgba(0,0,0,.50) !important;
            z-index: 1040 !important;
            opacity: 0 !important;
            pointer-events: none !important;
            transition: opacity .25s ease !important;
        }
        .sa-overlay.active {
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        /* ── Tablet ── */
        @media (min-width: 768px) and (max-width: 991.98px) {
            :root { --sidebar-w: 220px; }
            .sa-main { padding: 1.25rem 1.1rem !important; }
        }

        /* ── Mobile (≤ 767.98px) ── */
        @media (max-width: 767.98px) {
            #sa-sidebar {
                left: -280px !important;
                transition: left .28s cubic-bezier(.4,0,.2,1) !important;
                z-index: 1050 !important;
            }
            #sa-sidebar.open {
                left: 0 !important;
            }
            .sa-header {
                left: 0 !important;
                padding: 0 .9rem !important;
            }
            .sa-hamburger {
                display: flex !important;
            }
            .sa-header-lastlogin {
                display: none !important;
            }
            .sa-user-name,
            .sa-user-role {
                display: none !important;
            }
            .sa-main {
                margin-left: 0 !important;
                padding: 1rem .85rem !important;
            }
        }

        @yield('extra_css')
    </style>
    @stack('css')
</head>
<body>

    {{-- ── Sidebar (Tailwind + Alpine — identical structure to normal user panel) ── --}}
    <aside id="sa-sidebar"
           x-data="{ reportsOpen: {{ request()->routeIs('host.reports*') ? 'true' : 'false' }} }"
           class="sa-sidebar">

        {{-- Brand --}}
        <div class="sticky top-0 bg-primary/95 backdrop-blur-sm z-10 px-6 py-6 border-b border-white/10">
            <a href="{{ route('host.dashboard') }}" class="flex items-center gap-3 no-underline">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg overflow-hidden p-1">
                    @include('partials.logo_svg', ['width' => 36, 'height' => 36])
                </div>
                <div>
                    <h4 class="text-white text-lg font-bold tracking-tight mb-0">Waafi Book</h4>
                    <p class="text-accent text-[10px] font-bold uppercase tracking-widest mb-0">Super Admin</p>
                </div>
            </a>
        </div>

        {{-- Nav items --}}
        <div class="px-4 py-4 space-y-1">

            <a href="{{ route('host.dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-speedometer2 text-lg"></i>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('host.companies') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.companies') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-building text-lg"></i>
                <span>Companies</span>
            </a>

            <a href="{{ route('host.users') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.users*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Users</span>
            </a>

            <a href="{{ route('host.subscriptions') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.subscriptions*') || request()->routeIs('host.payments*') || request()->routeIs('host.plans*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-credit-card text-lg"></i>
                <span>Subscription &amp; Billing</span>
            </a>

            <a href="{{ route('host.announcements') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.announcements*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-megaphone text-lg"></i>
                <span>Announcements</span>
            </a>

            <a href="{{ route('host.subscription-plans') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.subscription-plans*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-tags text-lg"></i>
                <span>Subscription Plans</span>
            </a>

            {{-- Reports dropdown --}}
            <div class="space-y-1">
                <button @click="reportsOpen = !reportsOpen"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full"
                        :class="reportsOpen ? 'bg-white/10' : 'hover:bg-white/5'"
                        :style="{ color: reportsOpen ? '#ffffff' : 'rgba(255,255,255,0.7)' }">
                    <i class="bi bi-bar-chart-line text-lg"></i>
                    <span>Reports</span>
                    <i class="bi bi-chevron-down ml-auto text-[10px] transition-transform duration-200"
                       :class="reportsOpen ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="reportsOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 max-h-0"
                     x-transition:enter-end="opacity-100 max-h-[500px]"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 max-h-[500px]"
                     x-transition:leave-end="opacity-0 max-h-0"
                     class="space-y-1 overflow-hidden transition-all duration-300">
                    <a href="{{ route('host.reports') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports') && !request()->routeIs('host.reports.*') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> Overview
                    </a>
                    <a href="{{ route('host.reports.companies') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports.companies') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> Companies
                    </a>
                    <a href="{{ route('host.reports.subscriptions') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports.subscriptions') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> Subscriptions
                    </a>
                    <a href="{{ route('host.reports.revenue') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports.revenue') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> Revenue
                    </a>
                    <a href="{{ route('host.reports.users') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports.users') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> Users
                    </a>
                    <a href="{{ route('host.reports.activity') }}"
                       class="flex items-center gap-3 pl-12 pr-4 py-2 text-[13px] font-medium transition-all duration-200 no-underline
                              {{ request()->routeIs('host.reports.activity') ? 'text-white' : 'text-white/50 hover:text-white' }}">
                        <i class="bi bi-plus text-lg"></i> System Activity
                    </a>
                </div>
            </div>

            <a href="{{ route('host.settings') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] transition-all duration-200 w-full no-underline
                      {{ request()->routeIs('host.settings*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <i class="bi bi-gear text-lg"></i>
                <span>System Settings</span>
            </a>

            {{-- Sign Out --}}
            <div class="pt-2 mt-2 border-t border-white/10">
                <a href="{{ route('host.logout') }}"
                   onclick="event.preventDefault(); document.getElementById('sa-logout').submit();"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-[14px] text-accent hover:bg-white/5 hover:text-white transition-all duration-200 w-full no-underline">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                    <span>Sign Out</span>
                </a>
                <form id="sa-logout" action="{{ route('host.logout') }}" method="POST" class="d-none">@csrf</form>
            </div>

        </div>
    </aside>

    {{-- Mobile backdrop overlay --}}
    <div class="sa-overlay" id="sa-overlay" onclick="closeSidebar()"></div>

    {{-- Header --}}
    <header class="sa-header">
        {{-- hamburger — hidden on desktop, visible on mobile --}}
        <button class="sa-hamburger me-2" id="sa-hamburger" type="button"
                onclick="openSidebar()" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="sa-header-title">@yield('page_title', 'Dashboard')</h1>

        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="sa-header-lastlogin">
                Last login: <strong>{{ $lastLogin?->format('d M Y, H:i') ?? '—' }}</strong>
            </div>
            <button type="button" class="sa-bell-btn"><i class="bi bi-bell"></i><span class="sa-bell-dot"></span></button>
            <div class="d-flex align-items-center gap-2">
                <div class="sa-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'SA', 0, 2)) }}</div>
                <div>
                    <div class="sa-user-name">{{ auth()->user()->name }}</div>
                    <div class="sa-user-role">Platform Owner</div>
                </div>
                <button type="button" class="sa-btn-icon" data-bs-toggle="tooltip" title="Sign Out"
                        onclick="document.getElementById('sa-logout').submit();"><i class="bi bi-box-arrow-right"></i></button>
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main class="sa-main">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            confirmButtonColor: '#004161',
            confirmButtonText: 'OK',
            timer: 4000,
            timerProgressBar: true,
        });
        @endif

        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
            confirmButtonColor: '#004161',
        });
        @endif
    </script>
    @stack('js')
    <script>
    /* ── Sidebar slide-in/out for mobile / PWA ── */
    function openSidebar() {
        document.getElementById('sa-sidebar').classList.add('open');
        document.getElementById('sa-overlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        document.getElementById('sa-sidebar').classList.remove('open');
        document.getElementById('sa-overlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-close on nav link tap (PWA-friendly)
        document.querySelectorAll('#sa-sidebar a[href]').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth < 768) closeSidebar();
            });
        });
        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
        // Swipe-left to close sidebar on touch devices
        var touchStartX = 0;
        document.getElementById('sa-sidebar').addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        document.getElementById('sa-sidebar').addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (dx < -60) closeSidebar();
        }, { passive: true });
    });
    </script>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .catch(err => console.warn('SW:', err));
        });
    }
    </script>
</body>
</html>
