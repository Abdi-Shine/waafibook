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
                <h1 class="auth-title text-2xl font-black tracking-tighter">Reset Password</h1>
                <p class="auth-subtitle text-sm opacity-90">We will send you a secure reset link</p>
            </div>
            
            <div class="auth-body p-5 py-4">
                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;background:rgba(0,65,97,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="bi bi-shield-lock" style="font-size:28px;color:#004161;"></i>
                    </div>
                    <p class="fw-semibold mb-1" style="color:#004161;font-size:15px;">Password Reset Disabled</p>
                    <p class="text-muted small leading-relaxed">
                        Self-service password reset is not available.<br>
                        Please contact your <strong style="color:#004161;">system administrator</strong> to reset your password.
                    </p>
                </div>
                <div class="alert border-0 rounded-4 small text-center" style="background:rgba(153,204,51,0.12);color:#004161;" role="alert">
                    <i class="bi bi-info-circle-fill me-2" style="color:#99CC33;"></i>
                    Your admin can reset your password from the user management panel.
                </div>
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
