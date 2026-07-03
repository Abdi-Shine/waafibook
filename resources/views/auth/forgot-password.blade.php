<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - Waafibook</title>
    
    <!-- Global CSS & JS (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-page-bg">
    <div class="auth-container max-w-[450px] w-full animate-fadeIn">
        <div class="auth-card">
            @php
                $company = \App\Models\Company::first();
            @endphp
            <div class="auth-header-gradient auth-header-border p-5 py-4 text-center text-white">
                <div class="auth-logo flex justify-center mb-6">
                    @if($company && $company->logo)
                        <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl overflow-hidden border-4 border-accent/30 p-2 transform hover:scale-110 transition-transform duration-300">
                            <img src="{{ asset($company->logo) }}" class="w-full h-full object-contain" alt="Logo">
                        </div>
                    @else
                        <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl border-4 border-accent/30 p-2 transform hover:scale-110 transition-transform duration-300">
                            @include('partials.logo_svg', ['width' => 56, 'height' => 56])
                        </div>
                    @endif
                </div>
                <h1 class="auth-title text-2xl font-black tracking-tighter">Forgot Password?</h1>
                <p class="auth-subtitle text-sm opacity-90">We will send you a secure reset link</p>
            </div>
            
            <div class="auth-body p-5 py-4">

                @if (session('temp_password'))
                    {{-- Show temp password on screen — no email sent --}}
                    <div class="text-center mb-3">
                        <div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="bi bi-unlock-fill" style="font-size:24px;color:#16a34a;"></i>
                        </div>
                        <p class="fw-bold mb-1" style="color:#004161;font-size:15px;">Your Temporary Password</p>
                        <p class="text-muted small mb-3">Use this to log in, then change your password from your profile.</p>
                        <div style="background:#f0fdf4;border:2px dashed #86efac;border-radius:12px;padding:16px 20px;margin-bottom:16px;">
                            <div style="font-size:11px;font-weight:700;color:#16a34a;letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">Temporary Password</div>
                            <div id="tmpPwd" style="font-size:26px;font-weight:900;color:#004161;letter-spacing:3px;font-family:monospace;">{{ session('temp_password') }}</div>
                        </div>
                        <button onclick="navigator.clipboard.writeText('{{ session('temp_password') }}').then(()=>this.innerHTML='<i class=\'bi bi-check2\' ></i> Copied!')"
                                class="btn btn-sm btn-outline-success rounded-pill px-4 fw-bold mb-3">
                            <i class="bi bi-clipboard"></i> Copy Password
                        </button>
                        <div class="alert border-0 rounded-3 small text-start" style="background:#fff7ed;color:#9a3412;">
                            <i class="bi bi-exclamation-triangle-fill me-1" style="color:#f97316;"></i>
                            <strong>Important:</strong> This password is shown only once. Please copy it now and log in immediately.
                        </div>
                        <a href="{{ route('login') }}" class="btn-auth d-block text-center text-decoration-none">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                        </a>
                    </div>

                @else

                    <div class="mb-4 text-sm text-center text-muted fw-medium leading-relaxed">
                        Enter your admin email address to get a temporary password.
                    </div>

                    @if ($errors->has('email'))
                        <div class="alert border-0 rounded-4 mb-4 small fw-bold text-center" style="background:#fff0f0;color:#dc2626;" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first('email') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <i class="bi bi-envelope input-icon"></i>
                                <input id="email" class="form-control" type="email" name="email"
                                       value="{{ old('email') }}" required autofocus placeholder="name@company.com" />
                            </div>
                        </div>
                        <button type="submit" class="btn-auth">
                            <i class="bi bi-key me-2"></i>Get Temporary Password
                        </button>
                    </form>

                    <div class="mt-3 text-center small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Only company administrator accounts can use this feature.
                    </div>

                @endif
            </div>
            
            <div class="auth-footer py-1 px-5 text-center">
                <a href="{{ route('login') }}" class="link-auth small fw-bold">
                    <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                </a>
                <p class="text-muted m-0 mt-1" style="font-size: 0.75rem;">
                    &copy; {{ date('Y') }} Waafibook. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
