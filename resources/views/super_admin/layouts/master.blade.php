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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="{{ asset('css/super-admin.css') }}" rel="stylesheet">
    <style>
        @yield('extra_css')
    </style>
    @stack('css')
</head>
<body>

    {{-- Sidebar --}}
    <aside class="sa-sidebar">
        <div class="sa-brand">
            <a href="{{ route('host.dashboard') }}">
                <div class="sa-brand-logo">
                    <img src="{{ asset('upload/horntechlogo/horntech_logo.jpg') }}" alt="Waafibook" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                </div>
                <div>
                    <div class="sa-brand-text">Waafibook</div>
                    <div class="sa-brand-sub">Super Admin</div>
                </div>
            </a>
        </div>

        <ul class="sa-nav">
            <li>
                <a href="{{ route('host.dashboard') }}"
                   class="{{ request()->routeIs('host.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('host.companies') }}"
                   class="{{ request()->routeIs('host.companies') ? 'active' : '' }}">
                    <i class="bi bi-building"></i> Companies
                </a>
            </li>
            <li>
                <a href="{{ route('host.users') }}"
                   class="{{ request()->routeIs('host.users*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li>
                <a href="{{ route('host.subscriptions') }}"
                   class="{{ request()->routeIs('host.subscriptions*') || request()->routeIs('host.payments*') || request()->routeIs('host.plans*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i> Subscription &amp; Billing
                </a>
            </li>
            <li>
                <a href="{{ route('host.announcements') }}"
                   class="{{ request()->routeIs('host.announcements*') ? 'active' : '' }}">
                    <i class="bi bi-megaphone"></i> Announcements
                </a>
            </li>
            <li>
                <a href="{{ route('host.subscription-plans') }}"
                   class="{{ request()->routeIs('host.subscription-plans*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i> Subscription Plans
                </a>
            </li>
            <li>
                <a href="{{ route('host.settings') }}"
                   class="{{ request()->routeIs('host.settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> System Settings
                </a>
            </li>

            <li class="sa-nav-back">
                <a href="{{ route('host.logout') }}" onclick="event.preventDefault(); document.getElementById('sa-logout').submit();" class="sa-back-link" style="color:var(--accent);font-weight:600;">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
                <form id="sa-logout" action="{{ route('host.logout') }}" method="POST" class="d-none">@csrf</form>
            </li>
        </ul>
    </aside>

    {{-- Header --}}
    <header class="sa-header">
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
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    </script>
    @stack('js')
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
