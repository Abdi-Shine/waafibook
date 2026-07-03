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
                <div class="mb-4 text-sm text-center text-muted fw-medium leading-relaxed">
                    Forgot your password? Enter your admin email and we will send you a reset link.
                </div>

                @if (session('status'))
                    <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold text-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
                    </div>
                @endif

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
                        <i class="bi bi-send me-2"></i>Send Reset Link
                    </button>
                </form>

                <div class="mt-3 text-center small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    A reset link will be sent to your registered email address.
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
