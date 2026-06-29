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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── Brand tokens ── */
        :root {
            --primary: #004161;
            --primary-dark: #002d47;
            --accent: #99CC33;
            --accent-dark: #7aaa1e;
            --sidebar-w: 260px;
            --header-h: 64px;

            /* Override Bootstrap CSS variables with brand colors */
            --bs-primary:           #004161;
            --bs-primary-rgb:       0, 65, 97;
            --bs-primary-bg-subtle: #e0edf3;
            --bs-primary-border-subtle: #b3cfd9;
            --bs-primary-text-emphasis: #002d47;

            --bs-success:           #99CC33;
            --bs-success-rgb:       153, 204, 51;
            --bs-success-bg-subtle: #eef7d6;
            --bs-success-border-subtle: #c8e87a;
            --bs-success-text-emphasis: #4a6519;

            --bs-link-color:        #004161;
            --bs-link-hover-color:  #002d47;

            --bs-focus-ring-color: rgba(0, 65, 97, 0.25);
        }

        /* Bootstrap component overrides */
        .btn-primary,
        .btn-primary:visited {
            background-color: #004161 !important;
            border-color: #004161 !important;
            color: #fff !important;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: #002d47 !important;
            border-color: #002d47 !important;
        }

        .btn-outline-primary {
            color: #004161 !important;
            border-color: #004161 !important;
        }
        .btn-outline-primary:hover, .btn-outline-primary:active {
            background-color: #004161 !important;
            color: #fff !important;
        }

        .btn-success {
            background-color: #99CC33 !important;
            border-color: #99CC33 !important;
            color: #002d47 !important;
        }
        .btn-success:hover, .btn-success:focus, .btn-success:active {
            background-color: #7aaa1e !important;
            border-color: #7aaa1e !important;
            color: #002d47 !important;
        }

        .btn-outline-success {
            color: #5a7a1a !important;
            border-color: #99CC33 !important;
        }
        .btn-outline-success:hover {
            background-color: #99CC33 !important;
            color: #002d47 !important;
        }

        .text-primary  { color: #004161 !important; }
        .text-success  { color: #5a7a1a !important; }
        .bg-primary    { background-color: #004161 !important; }
        .bg-success    { background-color: #99CC33 !important; }

        /* Form focus rings */
        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus {
            border-color: #004161 !important;
            box-shadow: 0 0 0 0.25rem rgba(0, 65, 97, 0.2) !important;
        }

        /* Checkbox / radio checked state */
        .form-check-input:checked {
            background-color: #004161 !important;
            border-color: #004161 !important;
        }

        /* Switch (form-switch) */
        .form-check-input[type="checkbox"]:checked {
            background-color: #99CC33 !important;
            border-color: #99CC33 !important;
        }

        /* Progress bar */
        .progress-bar { background-color: #004161 !important; }

        /* Alert success */
        .alert-success {
            background-color: #eef7d6 !important;
            border-color: #c8e87a !important;
            color: #3d5a10 !important;
        }

        /* Badge overrides */
        .badge.bg-primary  { background-color: #004161 !important; }
        .badge.bg-success  { background-color: #99CC33 !important; color: #002d47 !important; }
        .badge.bg-warning  { background-color: #f59e0b !important; }

        /* Pagination */
        .page-link { color: #004161 !important; }
        .page-link:hover { color: #002d47 !important; }
        .page-item.active .page-link {
            background-color: #004161 !important;
            border-color: #004161 !important;
            color: #fff !important;
        }

        /* Table thead Bootstrap override */
        .table-primary { background-color: #e0edf3 !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; overflow-x: hidden; }

        /* ── Sidebar ── */
        .sa-sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: var(--primary);
            z-index: 1000; overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,.12);
        }
        .sa-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sa-brand a { display:flex; align-items:center; gap:.75rem; text-decoration:none; }
        .sa-brand-logo {
            width:38px; height:38px; background:white; border-radius:8px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .sa-brand-text { font-size:1rem; font-weight:700; color:white; line-height:1.2; }
        .sa-brand-sub  { font-size:.65rem; color:var(--accent); font-weight:700; letter-spacing:.05em; text-transform:uppercase; }

        .sa-nav { list-style:none; padding:.75rem 0; }
        .sa-nav li { margin:.1rem 0; }
        .sa-nav a {
            display:flex; align-items:center; gap:.75rem;
            padding:.65rem 1.25rem;
            color:rgba(255,255,255,.72); text-decoration:none;
            border-left:3px solid transparent;
            font-size:.875rem; font-weight:500;
            transition: all .2s;
        }
        .sa-nav a:hover, .sa-nav a.active {
            background:rgba(255,255,255,.1); color:white;
            border-left-color: var(--accent);
        }
        .sa-nav a i { font-size:1rem; width:18px; text-align:center; }

        .sa-nav-label {
            padding:.75rem 1.25rem .3rem;
            font-size:.65rem; font-weight:700; letter-spacing:.1em;
            text-transform:uppercase; color:rgba(255,255,255,.35);
        }

        /* ── Header ── */
        .sa-header {
            position:fixed; top:0; left:var(--sidebar-w); right:0;
            height:var(--header-h); background:white; z-index:999;
            display:flex; align-items:center; padding:0 1.75rem;
            box-shadow: 0 1px 0 #e5e7eb, 0 2px 8px rgba(0,0,0,.04);
        }
        .sa-header-title { font-weight:800; color:#111827; font-size:1.15rem; margin:0; }
        .sa-header-lastlogin { font-size:.85rem; color:#6b7280; }
        .sa-header-lastlogin strong { color:#111827; }

        .sa-bell-btn {
            width:38px; height:38px; border-radius:8px; border:1px solid #e5e7eb; background:#f9fafb;
            display:flex; align-items:center; justify-content:center; position:relative; color:#6b7280; flex-shrink:0;
        }
        .sa-bell-dot { position:absolute; top:6px; right:7px; width:6px; height:6px; border-radius:50%; background:#dc2626; }

        .sa-avatar {
            width:36px; height:36px; border-radius:50%;
            background:var(--primary); color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.8rem; flex-shrink:0;
        }

        /* ── Main content ── */
        .sa-main {
            margin-left: var(--sidebar-w);
            margin-top: var(--header-h);
            padding: 1.75rem 2rem;
            min-height: calc(100vh - var(--header-h));
        }

        /* ── Stat cards ── */
        .sa-stat {
            background:white; border-radius:12px; padding:1.25rem 1.5rem; position:relative;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04);
            transition: transform .2s, box-shadow .2s;
        }
        .sa-stat-link { font-size:.72rem; font-weight:700; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; text-transform:uppercase; letter-spacing:.03em; }
        .sa-stat-link:hover { color:var(--primary); }
        .sa-stat:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.09); }
        .sa-stat-icon {
            width:48px; height:48px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.25rem; margin-bottom:1rem;
        }
        .sa-stat-val  { font-size:1.65rem; font-weight:800; line-height:1; margin-bottom:.25rem; }
        .sa-stat-lbl  { font-size:.8rem; color:#6b7280; margin-bottom:.4rem; }
        .sa-stat-sub  { font-size:.75rem; font-weight:600; display:flex; align-items:center; gap:.25rem; }
        .sa-stat-sub.pos { color:#16a34a; }
        .sa-stat-sub.warn { color:#d97706; }

        /* ── Cards ── */
        .sa-card { background:white; border-radius:12px; margin-bottom:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
        .sa-card-head {
            display:flex; align-items:center; justify-content:space-between;
            padding:.9rem 1.25rem; border-bottom:1px solid #f3f4f6;
        }
        .sa-card-head h6 { font-weight:700; color:var(--primary); font-size:.875rem; margin:0; }

        /* ── Table ── */
        .sa-table { width:100%; border-collapse:collapse; }
        .sa-table thead th {
            background:#f9fafb; color:var(--primary); font-size:.72rem;
            font-weight:700; text-transform:uppercase; letter-spacing:.05em;
            padding:.75rem 1rem; border:none; white-space:nowrap;
        }
        .sa-table tbody td { padding:.75rem 1rem; font-size:.85rem; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .sa-table tbody tr:last-child td { border-bottom:none; }
        .sa-table tbody tr:hover td { background:#fafafa; }

        /* ── Badges ── */
        .sa-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .6rem; border-radius:6px; font-size:.72rem; font-weight:600; }
        .sa-badge-green  { background:#dcfce7; color:#15803d; }
        .sa-badge-yellow { background:#fef9c3; color:#a16207; }
        .sa-badge-red    { background:#fee2e2; color:#b91c1c; }
        .sa-badge-blue   { background:#dbeafe; color:#1d4ed8; }
        .sa-badge-gray   { background:#f3f4f6; color:#374151; }

        /* ── Action buttons ── */
        .sa-btn-icon {
            width:30px; height:30px; border-radius:6px; border:1px solid #e5e7eb; background:#f9fafb; cursor:pointer;
            display:inline-flex; align-items:center; justify-content:center; color:#6b7280;
            font-size:.8rem; transition:all .2s;
        }
        .sa-btn-icon:hover { background:var(--primary); color:#fff; border-color:var(--primary); }
        .sa-btn-icon.danger:hover { background:#dc2626; border-color:#dc2626; color:#fff; }
        .sa-btn-icon.warn:hover { background:#d97706; border-color:#d97706; color:#fff; }
        .sa-btn-icon.ok:hover { background:var(--accent); border-color:var(--accent); color:var(--primary-dark); }
        .sa-row-actions { display:flex; gap:.35rem; }

        /* ── Pill badge ── */
        .sa-pill {
            position:absolute; top:1.1rem; right:1.1rem; font-size:.6rem; font-weight:800;
            text-transform:uppercase; letter-spacing:.05em; padding:.25rem .55rem; border-radius:30px;
            background:#e0edf3; color:var(--primary);
        }

        /* ── Solid fill cards (Dashboard quick stats) ── */
        .sa-fill-card { border-radius:12px; padding:1.1rem 1.25rem; color:#fff; }
        .sa-fill-navy { background:var(--primary); }
        .sa-fill-lime { background:var(--accent); color:var(--primary-dark); }
        .sa-fill-card .icon { width:34px; height:34px; border-radius:8px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center; font-size:1rem; margin-bottom:1.4rem; }
        .sa-fill-lime .icon { background:rgba(0,45,71,.12); }
        .sa-fill-card .lbl-top { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; opacity:.85; margin-bottom:.2rem; }
        .sa-fill-card .val { font-size:1.35rem; font-weight:800; }

        /* ── Back to App link ── */
        .sa-nav-back { margin-top: 1.5rem; padding: .75rem 1.25rem; border-top: 1px solid rgba(255,255,255,.1); }
        .sa-back-link { color: rgba(255,255,255,.6); font-size: .8rem; display: flex; align-items: center; gap: .5rem; text-decoration: none; }
        .sa-back-link:hover { color: white; }

        /* ── Header user info ── */
        .sa-user-name  { font-weight: 700; font-size: .85rem; color: #111827; }
        .sa-user-role  { font-size: .72rem; color: #9ca3af; }

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
                <a href="{{ route('host.security') }}"
                   class="{{ request()->routeIs('host.security*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i> Security &amp; Audit Log
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
</body>
</html>
