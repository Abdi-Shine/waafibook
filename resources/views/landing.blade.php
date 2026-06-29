<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waafibook — Complete Business Management Solution</title>
    <meta name="description" content="Cloud-based ERP platform for growing businesses. Manage inventory, sales, purchases, accounting and HR in one place.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #004161;
            --primary-dark: #002d47;
            --accent: #99CC33;
            --accent-dark: #88bb22;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#fff; overflow-x:hidden; }

        /* ── Navbar ── */
        .lp-nav {
            background:white;
            box-shadow:0 2px 20px rgba(0,0,0,.07);
            padding:.9rem 0;
            position:sticky; top:0; z-index:1000;
        }
        .lp-brand {
            display:flex; align-items:center; gap:.75rem;
            font-size:1.4rem; font-weight:800; color:var(--primary); text-decoration:none;
        }
        .lp-brand-icon {
            width:42px; height:42px;
            background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            color:white; font-size:1.3rem;
            box-shadow:0 4px 12px rgba(153,204,51,.35);
        }
        .lp-nav .nav-link { color:#374151; font-weight:500; padding:.45rem 1rem !important; transition:color .2s; }
        .lp-nav .nav-link:hover { color:var(--primary); }
        .btn-lp-primary {
            background:var(--primary); color:white; border:none;
            padding:.55rem 1.4rem; border-radius:8px; font-weight:600;
            transition:all .2s; text-decoration:none; font-size:.9rem;
        }
        .btn-lp-primary:hover { background:var(--primary-dark); color:white; transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,65,97,.3); }
        .btn-lp-accent {
            background:var(--accent); color:var(--primary-dark); border:none;
            padding:.55rem 1.4rem; border-radius:8px; font-weight:700;
            transition:all .2s; text-decoration:none; font-size:.9rem;
        }
        .btn-lp-accent:hover { background:var(--accent-dark); color:var(--primary-dark); transform:translateY(-1px); box-shadow:0 4px 12px rgba(153,204,51,.4); }

        /* ── Hero ── */
        .lp-hero {
            background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
            padding:5.5rem 0 4.5rem; color:white; position:relative; overflow:hidden;
        }
        .lp-hero::before {
            content:''; position:absolute; top:-30%; right:-20%;
            width:70%; height:140%;
            background:radial-gradient(circle,rgba(153,204,51,.12) 0%,transparent 65%);
            pointer-events:none;
        }
        .lp-hero-title {
            font-family:'Poppins',sans-serif; font-size:3.2rem; font-weight:800; line-height:1.2; margin-bottom:1.5rem;
        }
        .lp-hero-title span { color:var(--accent); }
        .lp-hero-sub { font-size:1.15rem; color:rgba(255,255,255,.88); line-height:1.7; margin-bottom:2rem; }
        .lp-stats { display:flex; gap:3rem; margin-top:3rem; flex-wrap:wrap; }
        .lp-stat-num { font-size:2.2rem; font-weight:800; color:var(--accent); }
        .lp-stat-lbl { font-size:.82rem; color:rgba(255,255,255,.75); margin-top:.1rem; }

        /* ── Section helpers ── */
        .lp-section { padding:5rem 0; }
        .lp-section-gray { background:#f8f9fa; }
        .lp-section-title {
            font-family:'Poppins',sans-serif; font-size:2.25rem; font-weight:700;
            color:var(--primary); text-align:center; margin-bottom:.75rem;
        }
        .lp-section-sub { text-align:center; color:#6b7280; font-size:1.05rem; margin-bottom:3rem; }

        /* ── Feature cards ── */
        .lp-feature {
            background:white; border-radius:14px; padding:2rem;
            box-shadow:0 2px 12px rgba(0,0,0,.07); height:100%;
            border:2px solid transparent; transition:all .25s;
        }
        .lp-feature:hover {
            transform:translateY(-6px); box-shadow:0 10px 30px rgba(0,0,0,.13);
            border-color:var(--accent);
        }
        .lp-feature-icon {
            width:60px; height:60px; border-radius:14px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.75rem; margin-bottom:1.25rem;
        }
        .lp-feature h4 { font-weight:700; color:var(--primary); font-size:1.1rem; margin-bottom:.6rem; }
        .lp-feature p  { color:#6b7280; line-height:1.65; font-size:.92rem; margin:0; }

        /* ── Module grid ── */
        .lp-module {
            background:linear-gradient(135deg,#f8f9fa,#f0f4f8);
            border-radius:12px; padding:1.5rem 1rem;
            text-align:center; height:100%;
            border:2px solid transparent; transition:all .25s; cursor:default;
        }
        .lp-module:hover {
            background:white; border-color:var(--primary);
            transform:scale(1.04); box-shadow:0 6px 20px rgba(0,0,0,.1);
        }
        .lp-module i { font-size:2.2rem; color:var(--primary); display:block; margin-bottom:.75rem; }
        .lp-module span { font-weight:600; color:var(--primary); font-size:.9rem; }

        /* ── Pricing ── */
        .lp-plan {
            background:white; border-radius:16px; padding:2.25rem;
            box-shadow:0 4px 20px rgba(0,0,0,.09); height:100%;
            border:2px solid transparent; transition:all .3s; position:relative;
        }
        .lp-plan:hover { transform:translateY(-8px); box-shadow:0 14px 40px rgba(0,0,0,.14); }
        .lp-plan.featured { border-color:var(--accent); }
        .lp-plan-badge {
            position:absolute; top:-14px; right:22px;
            background:var(--accent); color:var(--primary-dark);
            padding:.35rem 1.25rem; border-radius:20px; font-weight:700; font-size:.8rem;
        }
        .lp-plan-name  { font-size:1.4rem; font-weight:700; color:var(--primary); margin-bottom:.75rem; }
        .lp-plan-price { font-size:2.75rem; font-weight:800; color:var(--primary); line-height:1; }
        .lp-plan-price sup { font-size:1.2rem; vertical-align:top; margin-top:.5rem; font-weight:600; }
        .lp-plan-period { color:#6b7280; font-size:.85rem; margin:.3rem 0 1.75rem; }
        .lp-plan ul { list-style:none; margin-bottom:1.75rem; }
        .lp-plan ul li {
            padding:.6rem 0; border-bottom:1px solid #f3f4f6;
            display:flex; align-items:center; gap:.65rem; font-size:.9rem; color:#374151;
        }
        .lp-plan ul li:last-child { border-bottom:none; }
        .lp-plan ul li i { color:var(--accent); font-size:1.1rem; flex-shrink:0; }

        /* ── CTA ── */
        .lp-cta {
            background:linear-gradient(135deg,var(--primary),var(--primary-dark));
            padding:5rem 0; color:white; text-align:center;
        }
        .lp-cta h2 { font-family:'Poppins',sans-serif; font-size:2.4rem; font-weight:700; margin-bottom:1rem; }
        .lp-cta p  { font-size:1.15rem; color:rgba(255,255,255,.88); margin-bottom:2rem; }

        /* ── Footer ── */
        .lp-footer { background:#111827; color:white; padding:4rem 0 2rem; }
        .lp-footer-title { font-weight:700; color:var(--accent); margin-bottom:1.25rem; font-size:.95rem; }
        .lp-footer a { color:rgba(255,255,255,.65); text-decoration:none; display:block; padding:.35rem 0; font-size:.88rem; transition:color .2s; }
        .lp-footer a:hover { color:var(--accent); }
        .lp-footer-bottom {
            border-top:1px solid rgba(255,255,255,.08);
            padding-top:1.75rem; margin-top:2.75rem;
            text-align:center; color:rgba(255,255,255,.4); font-size:.83rem;
        }

        @media(max-width:768px) {
            .lp-hero-title { font-size:2.1rem; }
            .lp-stats { gap:1.5rem; }
            .lp-section-title { font-size:1.75rem; }
        }
    </style>
</head>
<body>

{{-- ── Navigation ── --}}
<nav class="lp-nav navbar navbar-expand-lg">
    <div class="container">
        <a class="lp-brand" href="{{ route('landing') }}">
            <img src="{{ asset('upload/horntechlogo/horntech_logo.jpg') }}" alt="Waafibook Logo" style="width:42px;height:42px;object-fit:contain;border-radius:8px;">
            Waafibook
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#lpNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="lpNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#modules">Modules</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                <li class="nav-item ms-2">
                    <a href="{{ route('login') }}" class="btn-lp-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                </li>
                <li class="nav-item ms-1">
                    <a href="{{ route('register') }}" class="btn-lp-accent">
                        Get Started Free
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{-- ── Hero ── --}}
<section class="lp-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" style="position:relative;z-index:1;">
                <h1 class="lp-hero-title">
                    Complete ERP Solution for <span>Growing Businesses</span>
                </h1>
                <p class="lp-hero-sub">
                    Streamline operations with our cloud-based ERP platform. Manage inventory, sales, purchases, accounting, HR and more — all in one place.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('register') }}" class="btn-lp-accent" style="padding:.75rem 1.75rem;font-size:1rem;">
                        <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
                    </a>
                    <a href="{{ route('demo.request') }}" class="btn btn-outline-light" style="border-radius:8px;font-weight:600;padding:.75rem 1.75rem;">
                        <i class="bi bi-camera-video me-2"></i>Request Demo
                    </a>
                </div>
                <div class="lp-stats">
                    <div>
                        <div class="lp-stat-num">500+</div>
                        <div class="lp-stat-lbl">Active Companies</div>
                    </div>
                    <div>
                        <div class="lp-stat-num">10K+</div>
                        <div class="lp-stat-lbl">Daily Transactions</div>
                    </div>
                    <div>
                        <div class="lp-stat-num">99.9%</div>
                        <div class="lp-stat-lbl">Uptime</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex justify-content-center" style="position:relative;z-index:1;">
                <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:2rem;width:100%;max-width:480px;">
                    <div class="row g-3">
                        @foreach([
                            ['bi-graph-up-arrow','Sales Revenue',$currency.' 142,850','↑ 15.3%','#99CC33'],
                            ['bi-box-seam','Products In Stock','4,230 items','↑ 8.1%','#60a5fa'],
                            ['bi-people','Active Customers','1,847','↑ 12.4%','#a78bfa'],
                            ['bi-receipt','Pending Orders','38 orders','↓ 3.2%','#fb923c'],
                        ] as [$icon,$label,$val,$chg,$col])
                        <div class="col-6">
                            <div style="background:rgba(255,255,255,.1);border-radius:12px;padding:1rem;">
                                <i class="bi {{ $icon }}" style="font-size:1.4rem;color:{{ $col }};"></i>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.6);margin:.4rem 0 .2rem;">{{ $label }}</div>
                                <div style="font-size:1.1rem;font-weight:700;color:white;">{{ $val }}</div>
                                <div style="font-size:.72rem;color:{{ $col }};font-weight:600;">{{ $chg }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Features ── --}}
<section class="lp-section lp-section-gray" id="features">
    <div class="container">
        <h2 class="lp-section-title">Why Choose Waafibook?</h2>
        <p class="lp-section-sub">Powerful features designed to help your business grow</p>
        <div class="row g-4">
            @foreach([
                ['bi-cloud-check','rgba(59,130,246,.1)','#3b82f6','Cloud-Based Platform','Access your data anytime, anywhere. No installation required — just sign in and start working.'],
                ['bi-building','rgba(153,204,51,.12)','#6a9e00','Multi-Tenant Architecture','Complete data isolation between companies. Each tenant has their own secure environment.'],
                ['bi-shield-check','rgba(220,38,38,.08)','#dc2626','Enterprise Security','Role-based access control, audit logs, and encrypted backups keep your data protected.'],
                ['bi-lightning-charge','rgba(245,158,11,.1)','#d97706','Real-Time Sync','Instant updates across all devices and users the moment data changes.'],
                ['bi-graph-up-arrow','rgba(139,92,246,.1)','#7c3aed','Advanced Analytics','Comprehensive dashboards and reports to drive data-based decisions.'],
                ['bi-people','rgba(16,185,129,.1)','#059669','Flexible Permissions','Grant or restrict access per module, per user, per role — full granular control.'],
            ] as [$icon,$bg,$color,$title,$desc])
            <div class="col-md-6 col-lg-4">
                <div class="lp-feature">
                    <div class="lp-feature-icon" style="background:{{ $bg }};color:{{ $color }};">
                        <i class="bi {{ $icon }}"></i>
                    </div>
                    <h4>{{ $title }}</h4>
                    <p>{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Modules ── --}}
<section class="lp-section" id="modules">
    <div class="container">
        <h2 class="lp-section-title">Complete Business Modules</h2>
        <p class="lp-section-sub">Everything you need to run your business efficiently</p>
        <div class="row g-3">
            @foreach([
                ['bi-building','Company Management'],
                ['bi-box-seam','Inventory Management'],
                ['bi-graph-up','Sales & POS'],
                ['bi-cart-plus','Purchase Management'],
                ['bi-calculator','Accounting & Finance'],
                ['bi-receipt','Expense Tracking'],
                ['bi-people','CRM & Customers'],
                ['bi-person-badge','HR & Payroll'],
                ['bi-file-earmark-bar-graph','Reports & Analytics'],
                ['bi-truck','Supplier Management'],
                ['bi-arrow-left-right','Stock Transfers'],
                ['bi-gear','System Settings'],
            ] as [$icon,$name])
            <div class="col-6 col-md-4 col-lg-3">
                <div class="lp-module">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ $name }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Pricing ── --}}
<section class="lp-section lp-section-gray" id="pricing">
    <div class="container">
        <h2 class="lp-section-title">Choose Your Plan</h2>
        <p class="lp-section-sub">Flexible pricing for businesses of all sizes</p>

        @if($plans->isEmpty())
        {{-- Fallback static plans if no DB plans yet --}}
        <div class="row g-4 justify-content-center">
            @foreach([
                ['Starter','299',$currency,'month',['Up to 5 Users','1 Branch','1,000 Products','Basic Reports','Email Support'],false],
                ['Business','899',$currency,'month',['Up to 20 Users','3 Branches','Unlimited Products','Advanced Reports','Priority Support','API Access'],true],
                ['Enterprise','Custom','','contact us',['Unlimited Users','Unlimited Branches','Unlimited Products','Custom Reports','24/7 Dedicated Support','On-premise Option'],false],
            ] as [$name,$price,$currency,$period,$features,$popular])
            <div class="col-lg-4 col-md-6">
                <div class="lp-plan {{ $popular ? 'featured' : '' }}">
                    @if($popular)<div class="lp-plan-badge">Most Popular</div>@endif
                    <div class="lp-plan-name">{{ $name }}</div>
                    @if(is_numeric($price))
                        <div class="lp-plan-price"><sup>{{ $currency }}</sup>{{ number_format($price) }}</div>
                        <div class="lp-plan-period">per {{ $period }}</div>
                    @else
                        <div class="lp-plan-price" style="font-size:2rem;">{{ $price }}</div>
                        <div class="lp-plan-period">{{ $period }}</div>
                    @endif
                    <ul>
                        @foreach($features as $f)
                        <li><i class="bi bi-check-circle-fill"></i> {{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}"
                       class="{{ $popular ? 'btn-lp-primary' : 'btn btn-outline-secondary' }} w-100 d-block text-center"
                       style="padding:.65rem;border-radius:8px;font-weight:600;text-decoration:none;">
                        {{ $name === 'Enterprise' ? 'Contact Sales' : 'Get Started' }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        {{-- Real plans from DB --}}
        <div class="row g-4 justify-content-center">
            @foreach($plans as $plan)
            <div class="col-lg-4 col-md-6">
                <div class="lp-plan {{ $plan->is_popular ? 'featured' : '' }}">
                    @if($plan->is_popular)<div class="lp-plan-badge">Most Popular</div>@endif
                    <div class="lp-plan-name">{{ $plan->name }}</div>
                    @if($plan->price > 0)
                        <div class="lp-plan-price"><sup>{{ $currency }} </sup>{{ number_format($plan->price, 0) }}</div>
                        <div class="lp-plan-period">per {{ $plan->billing_cycle ?? 'month' }}</div>
                    @else
                        <div class="lp-plan-price" style="font-size:2rem;">Custom</div>
                        <div class="lp-plan-period">contact us</div>
                    @endif
                    <ul>
                        @if($plan->max_users)
                        <li><i class="bi bi-check-circle-fill"></i> Up to {{ $plan->max_users === 9999 ? 'Unlimited' : $plan->max_users }} Users</li>
                        @endif
                        @if($plan->storage_limit_gb)
                        <li><i class="bi bi-check-circle-fill"></i> {{ $plan->storage_limit_gb }}GB Storage</li>
                        @endif
                        @if(is_array($plan->features))
                            @foreach($plan->features as $feature)
                            <li><i class="bi bi-check-circle-fill"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <a href="{{ route('register') }}"
                       class="{{ $plan->is_popular ? 'btn-lp-primary' : 'btn btn-outline-secondary' }} w-100 d-block text-center"
                       style="padding:.65rem;border-radius:8px;font-weight:600;text-decoration:none;">
                        Get Started
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ── CTA ── --}}
<section class="lp-cta">
    <div class="container">
        <h2>Ready to Transform Your Business?</h2>
        <p>Start your free trial today. No credit card required.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('register') }}" class="btn-lp-accent" style="padding:.8rem 2rem;font-size:1.05rem;">
                <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-light" style="border-radius:8px;font-weight:600;padding:.8rem 2rem;font-size:1.05rem;">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </a>
        </div>
    </div>
</section>

{{-- ── Footer ── --}}
<footer class="lp-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="{{ route('landing') }}" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;margin-bottom:1rem;">
                    <img src="{{ asset('upload/horntechlogo/horntech_logo.jpg') }}" alt="Waafibook Logo" style="width:38px;height:38px;object-fit:contain;border-radius:8px;background:white;padding:2px;">
                    <span style="font-size:1.2rem;font-weight:800;color:white;">Waafibook</span>
                </a>
                <p style="color:rgba(255,255,255,.55);font-size:.88rem;line-height:1.65;">
                    Complete cloud-based ERP for growing businesses in Saudi Arabia and beyond.
                </p>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="lp-footer-title">Product</h6>
                <a href="#features">Features</a>
                <a href="#modules">Modules</a>
                <a href="#pricing">Pricing</a>
                <a href="{{ route('register') }}">Get Started</a>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="lp-footer-title">Account</h6>
                <a href="{{ route('login') }}">Sign In</a>
                <a href="{{ route('register') }}">Register</a>
                <a href="{{ route('host.login') }}">Admin Portal</a>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="lp-footer-title">Support</h6>
                <a href="#">Help Center</a>
                <a href="#">Documentation</a>
                <a href="#">API Reference</a>
                <a href="#">System Status</a>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="lp-footer-title">Legal</h6>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
        <div class="lp-footer-bottom">
            &copy; {{ date('Y') }} Waafibook. All rights reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
