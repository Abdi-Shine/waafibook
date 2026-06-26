<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set New Password - {{ \App\Models\Company::first()?->name ?? 'Waafibook' }}</title>
    
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
                <h1 class="auth-title text-2xl font-black tracking-tighter">New Password</h1>
                <p class="auth-subtitle text-sm opacity-90">Create a secure new password for your account</p>
            </div>
            
            <div class="auth-body p-5 py-4">
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="bi bi-envelope input-icon"></i>
                            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
                        </div>
                        @error('email')
                            <div class="text-danger small mt-2 fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <i class="bi bi-lock input-icon"></i>
                            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIconNew')">
                                <i class="bi bi-eye" id="toggleIconNew"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-2 fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <i class="bi bi-shield-check input-icon"></i>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                        </div>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="bi bi-check2-circle me-2"></i>Reset Password
                    </button>
                </form>
            </div>
            
            <div class="auth-footer py-1 px-5 text-center">
                <p class="text-muted m-0" style="font-size: 0.75rem;">
                    &copy; {{ date('Y') }} {{ $company->name ?? 'Waafibook' }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>
</html>
