<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin Login — Waafibook</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #004161;
            --primary-dark: #002d47;
            --accent: #99CC33;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(153,204,51,.12) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Left panel */
        .brand-panel {
            width: 420px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3rem 3rem 4rem;
            position: relative;
            z-index: 1;
        }
        .brand-logo {
            width: 56px;
            height: 56px;
            background: var(--accent);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--primary-dark);
            margin-bottom: 2.5rem;
            box-shadow: 0 8px 24px rgba(153,204,51,.35);
        }
        .brand-panel h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.25rem;
            font-weight: 800;
            color: white;
            line-height: 1.15;
            margin-bottom: 1rem;
        }
        .brand-panel p {
            color: rgba(255,255,255,.75);
            font-size: .95rem;
            line-height: 1.65;
            margin-bottom: 2.5rem;
        }
        .brand-features li {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: rgba(255,255,255,.8);
            font-size: .875rem;
            font-weight: 500;
            margin-bottom: .85rem;
        }
        .brand-features li i {
            width: 28px;
            height: 28px;
            background: rgba(153,204,51,.2);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            flex-shrink: 0;
            font-size: .8rem;
        }

        /* Right panel – login card */
        .login-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 24px 60px rgba(0,0,0,.25);
        }
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(0,65,97,.07);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: .4rem .9rem;
            border-radius: 20px;
            border: 1px solid rgba(0,65,97,.12);
            margin-bottom: 1.5rem;
        }
        .login-card h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: .4rem;
        }
        .login-card .subtitle {
            color: #6b7280;
            font-size: .875rem;
            margin-bottom: 2rem;
        }
        .form-label {
            font-weight: 600;
            font-size: .8rem;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: .45rem;
        }
        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .9rem;
            transition: all .2s;
            background: #f9fafb;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,65,97,.1);
            background: white;
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: .9rem;
            pointer-events: none;
        }
        .input-icon-wrap .form-control {
            padding-left: 2.5rem;
        }
        .toggle-pw {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: .25rem;
            font-size: .9rem;
        }
        .toggle-pw:hover { color: var(--primary); }
        .btn-login {
            width: 100%;
            padding: .85rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
            transition: all .2s;
            cursor: pointer;
        }
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0,65,97,.3);
        }
        .btn-login:active { transform: translateY(0); }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #f0f0f0;
        }
        .divider span {
            background: white;
            padding: 0 .75rem;
            position: relative;
            color: #9ca3af;
            font-size: .8rem;
        }
        .company-login-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            color: #4b5563;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
        }
        .company-login-link:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(0,65,97,.03);
        }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.25rem;
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .brand-panel { width: 100%; padding: 2.5rem 1.5rem 1.5rem; }
            .brand-panel h1 { font-size: 1.75rem; }
            .brand-features { display: none; }
            .login-panel { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    {{-- Left brand panel --}}
    <div class="brand-panel">
        <div class="brand-logo">
            <i class="bi bi-shop"></i>
        </div>
        <h1>Waafibook<br>Admin Portal</h1>
        <p>Manage your entire SaaS platform from one place. Monitor companies, subscriptions, billing, and system settings.</p>
        <ul class="brand-features list-unstyled">
            <li><i class="bi bi-building"></i> Manage all tenant companies</li>
            <li><i class="bi bi-credit-card"></i> Subscription & billing control</li>
            <li><i class="bi bi-people"></i> Platform-wide user management</li>
            <li><i class="bi bi-graph-up"></i> Revenue & analytics reports</li>
            <li><i class="bi bi-shield-lock"></i> Security & system settings</li>
        </ul>
    </div>

    {{-- Right login panel --}}
    <div class="login-panel">
        <div class="login-card">
            <div class="admin-badge">
                <i class="bi bi-shield-fill-check"></i> Super Admin Access
            </div>

            <h2>Welcome back</h2>
            <p class="subtitle">Sign in to the administration portal</p>

            {{-- Error messages --}}
            @if ($errors->any())
            <div class="error-msg">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ $errors->first() }}
            </div>
            @endif

            @if (session('status'))
            <div class="error-msg" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">
                <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('host.login.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email') }}"
                               placeholder="admin@waafibook.com"
                               autocomplete="email" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password" id="pwField" class="form-control"
                               placeholder="••••••••"
                               autocomplete="current-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw()">
                            <i class="bi bi-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <label class="d-flex align-items-center gap-2" style="cursor:pointer;font-size:.875rem;color:#6b7280;">
                        <input type="checkbox" name="remember" class="form-check-input m-0">
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size:.8rem;color:var(--primary);font-weight:600;text-decoration:none;">
                        Forgot password?
                    </a>
                    @endif
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-shield-lock me-2"></i>Sign In to Admin Portal
                </button>
            </form>

            <div class="divider"><span>or</span></div>

            <a href="{{ route('login') }}" class="company-login-link">
                <i class="bi bi-building"></i>
                Company Login Portal
            </a>
        </div>
    </div>

    <script>
        function togglePw() {
            const f = document.getElementById('pwField');
            const i = document.getElementById('pwIcon');
            if (f.type === 'password') {
                f.type = 'text';
                i.className = 'bi bi-eye-slash';
            } else {
                f.type = 'password';
                i.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
