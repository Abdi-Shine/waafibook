<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Waafibook</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-page-bg">
@php
    $company = \App\Models\Company::first();
    $activeTab = ($errors->has('company_name') || $errors->has('industry') || session('register_attempted')) ? 'register' : 'login';
@endphp

<div class="auth-container max-w-[450px] w-full animate-fadeIn">
    <div class="auth-card">

        <!-- Header -->
        <div class="auth-header-gradient auth-header-border p-5 py-4 text-center text-white">
            <div class="auth-logo flex justify-center mb-4">
                @if($company && $company->logo)
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl overflow-hidden border-4 border-accent/30 p-1.5">
                        <img src="{{ asset($company->logo) }}" class="w-full h-full object-contain" alt="Logo">
                    </div>
                @else
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl border-4 border-accent/30 p-1.5">
                        @include('partials.logo_svg', ['width' => 44, 'height' => 44])
                    </div>
                @endif
            </div>
            <h1 class="text-2xl font-black tracking-tighter mb-0">Waafi Book</h1>
            <p class="text-white/60 text-sm mt-1 mb-3">Accounting SaaS for modern teams</p>
        </div>

        <!-- Body -->
        <div class="auth-body p-5 py-4">

            @if (session('status'))
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm font-bold rounded-xl px-4 py-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('status') }}
                </div>
            @endif

            <!-- ── Sign In Form ── -->
            <div id="form-login" style="{{ $activeTab === 'register' ? 'display:none' : '' }}">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Email Address</label>
                        <div class="relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <input id="email" class="form-control @error('email') border-red-400 @enderror" type="email" name="email"
                                   value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@company.com">
                        </div>
                        @error('email')
                            <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1.5">
                            <label for="password" class="block text-[11px] font-black text-primary uppercase tracking-wider">Password</label>
                            @if (Route::has('password.request'))
                                <a class="link-auth text-[0.75rem]" href="{{ route('password.request') }}">Forgot Password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <i class="bi bi-lock input-icon"></i>
                            <input id="password" class="form-control @error('password') border-red-400 @enderror" type="password" name="password"
                                   required autocomplete="current-password" placeholder="••••••••">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon')">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 mb-4">
                        <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 cursor-pointer accent-primary" name="remember">
                        <label for="remember_me" class="text-sm font-semibold text-primary opacity-60 cursor-pointer">Remember me on this device</label>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="bi bi-box-arrow-in-right mr-2"></i>Sign In
                    </button>
                </form>

                <p class="text-center text-primary opacity-50 text-sm mt-4 mb-0">
                    &copy; {{ date('Y') }} Waafibook. All rights reserved. <a href="#" class="link-auth" onclick="switchTab('register'); return false;">Sign up</a>
                </p>
            </div>

            <!-- ── Create Account Form ── -->
            <div id="form-register" style="{{ $activeTab === 'login' ? 'display:none' : '' }}">
                <form method="POST" action="{{ route('register') }}" id="registerForm">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Company Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="bi bi-building input-icon"></i>
                            <input type="text" name="company_name"
                                   class="form-control @error('company_name') border-red-400 @enderror"
                                   placeholder="Enter your company name"
                                   value="{{ old('company_name') }}" required>
                        </div>
                        @error('company_name')
                            <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Country</label>
                            <div class="relative">
                                <i class="bi bi-globe input-icon"></i>
                                <select name="country" class="form-control">
                                    @foreach(['Djibouti','Ethiopia','Kenya','Somalia','Tanzania','Uganda', 'Rwanda', 'Burundi', 'South Sudan', 'Sudan', 'Eritrea', 'Other'] as $c)
                                        <option value="{{ $c }}" {{ old('country', 'Somalia') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Company Telephone</label>
                            <div class="relative">
                                <i class="bi bi-telephone input-icon"></i>
                                <input type="text" name="company_phone"
                                       class="form-control"
                                       placeholder="+252 61 00000000"
                                       value="{{ old('company_phone') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" name="name"
                                   class="form-control @error('name') border-red-400 @enderror"
                                   placeholder="Enter your full name"
                                   value="{{ old('name') }}" required autocomplete="name">
                        </div>
                        @error('name')
                            <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" name="email"
                                   class="form-control @error('email') border-red-400 @enderror"
                                   placeholder="admin@company.com"
                                   value="{{ old('email') }}" required autocomplete="username">
                        </div>
                        @error('email')
                            <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" name="password" id="reg-password"
                                       class="form-control @error('password') border-red-400 @enderror"
                                       placeholder="Create password" required autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('reg-password', 'regToggleIcon')">
                                    <i class="bi bi-eye" id="regToggleIcon"></i>
                                </button>
                            </div>
                            <p class="text-primary opacity-40 mt-1.5 text-[0.7rem]">Min. 8 characters with uppercase, lowercase and numbers</p>
                            @error('password')
                                <p class="text-red-500 font-bold mt-1.5 uppercase text-[0.7rem]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-primary uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input type="password" name="password_confirmation" id="reg-password-confirm"
                                       class="form-control" placeholder="Confirm password" required autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('reg-password-confirm', 'regConfirmToggleIcon')">
                                    <i class="bi bi-eye" id="regConfirmToggleIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2 mb-4">
                        <input id="terms" type="checkbox" class="w-4 h-4 mt-0.5 rounded border-gray-300 cursor-pointer accent-primary" required>
                        <label for="terms" class="text-sm font-semibold text-primary opacity-60 cursor-pointer">
                            I agree to the <a href="{{ route('terms') }}" target="_blank" class="link-auth">Terms of Service</a> and <a href="{{ route('privacy') }}" target="_blank" class="link-auth">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-auth">
                        <i class="bi bi-rocket-takeoff mr-2"></i>Create Account
                    </button>
                    <p class="text-center text-primary opacity-40 text-[0.75rem] mt-2 mb-0">14-day free trial &bull; No credit card required</p>
                </form>

                <p class="text-center text-primary opacity-50 text-sm mt-4 mb-0">
                    Already have an account? <a href="#" class="link-auth" onclick="switchTab('login'); return false;">Sign in</a>
                </p>
            </div>

        </div>

    </div>
</div>

<script>
    function switchTab(tab) {
        const isLogin = tab === 'login';
        document.getElementById('form-login').style.display    = isLogin ? '' : 'none';
        document.getElementById('form-register').style.display = isLogin ? 'none' : '';
    }

    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function (e) {
        const p = document.getElementById('reg-password').value;
        const c = document.getElementById('reg-password-confirm').value;
        if (p !== c) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Passwords do not match',
                text: 'Please make sure both password fields are identical.',
                confirmButtonColor: '#004161',
            });
        }
    });
</script>
</body>
</html>
