<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin Login — Waafibook</title>

    <!-- Global CSS & JS (Vite) — Inter font & Bootstrap Icons loaded via app.css + CDN below -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="auth-page-bg">
    <div class="auth-container max-w-[450px] w-full animate-fadeIn">
        <div class="auth-card">
            <div class="auth-header-gradient auth-header-border p-6 py-5 text-center text-white">
                <div class="auth-logo flex justify-center mb-6">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl overflow-hidden border-4 border-accent/30 p-2 transform hover:scale-110 transition-transform duration-300">
                        <img src="/icons/icon-192.png" class="w-full h-full object-contain" alt="Waafibook Logo">
                    </div>
                </div>
                <h1 class="auth-title text-2xl font-black tracking-tighter">Waafibook</h1>
                <p class="text-[0.7rem] text-white/70 font-bold uppercase tracking-widest mt-1.5 opacity-90 text-accent">Super Admin Portal</p>
            </div>

            <div class="auth-body p-6 py-5">
                {{-- Error messages --}}
                @if ($errors->any())
                <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl px-4 py-3 mb-5" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ $errors->first() }}
                </div>
                @endif

                {{-- Status tracking --}}
                @if (session('status'))
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-xs font-bold rounded-xl px-4 py-3 mb-5" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('status') }}
                </div>
                @endif

                <form method="POST" action="{{ route('host.login.store') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="mb-5">
                        <label for="email" class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Email Address</label>
                        <div class="relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <input id="email" class="form-control @error('email') border-red-400 @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@waafibook.com" />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-5">
                        <div class="flex justify-between items-center mb-1.5">
                            <label for="password" class="block text-[11px] font-black text-primary uppercase tracking-wider">Password</label>
                            @if (Route::has('password.request'))
                                <a class="link-auth text-[0.75rem]" href="{{ route('password.request') }}">Forgot Password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <i class="bi bi-lock input-icon"></i>
                            <input id="password" class="form-control @error('password') border-red-400 @enderror" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon')">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center gap-2 mb-6">
                        <input id="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary cursor-pointer accent-primary" name="remember">
                        <label for="remember" class="text-[0.85rem] font-semibold text-primary opacity-60 cursor-pointer">Remember me securely</label>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="bi bi-shield-lock-fill mr-2"></i>Sign In to Admin Portal
                    </button>
                </form>
            </div>

            <div class="auth-footer py-4 px-6 text-center border-t border-gray-50 bg-gray-50/50">
                <a href="{{ route('landing') }}" class="text-[0.75rem] font-bold text-primary/50 hover:text-primary transition-colors inline-block">
                    <i class="bi bi-house-door me-1"></i> Return to Landing Page
                </a>
            </div>
        </div>
    </div>

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
