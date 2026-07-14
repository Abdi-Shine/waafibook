<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Your Email - Waafibook</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #004161;
            --accent:  #99CC33;
        }

        body {
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f7e6 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .otp-container {
            width: 100%;
            max-width: 460px;
        }

        .otp-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 65, 97, 0.15);
            overflow: hidden;
        }

        .otp-header {
            background: linear-gradient(135deg, var(--primary) 0%, #005a87 100%);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .otp-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
        }

        .logo-icon {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .otp-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: .4rem;
        }

        .otp-subtitle {
            font-size: .88rem;
            opacity: .85;
            margin: 0;
        }

        .otp-body {
            padding: 2rem;
        }

        .otp-input {
            width: 100%;
            border: 1.5px solid #d0dde6;
            border-radius: 10px;
            padding: .75rem;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 18px;
            text-align: center;
            font-family: monospace;
            color: var(--primary);
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 65, 97, .12);
            outline: none;
        }

        .btn-submit {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, var(--primary) 0%, #005a87 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .3px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 65, 97, .35);
        }

        .btn-resend {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 700;
            font-size: .875rem;
            text-decoration: none;
        }

        .btn-resend:hover { color: var(--accent); }
        .btn-resend:disabled { color: #9aa8b4; cursor: not-allowed; }

        .back-link {
            text-align: center;
            margin-top: 1.4rem;
            font-size: .875rem;
            color: #6c7a8a;
        }

        .back-link a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .back-link a:hover { color: var(--accent); }
    </style>
</head>
<body>
<div class="container">
    <div class="otp-container">
        <div class="otp-card">

            <div class="otp-header">
                <div class="logo-icon">
                    <img src="/icons/icon-192.png" alt="Waafibook Logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                </div>
                <h1 class="otp-title">Verify Your Email</h1>
                <p class="otp-subtitle">Enter the 4-digit code we sent to<br><strong>{{ $email }}</strong></p>
            </div>

            <div class="otp-body">

                @if (session('status'))
                    <div class="alert alert-success mb-4">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('register.verify.store') }}">
                    @csrf
                    <div class="mb-4">
                        <input type="text" name="otp" inputmode="numeric" autocomplete="one-time-code"
                               maxlength="4" pattern="\d{4}"
                               class="otp-input @error('otp') is-invalid @enderror"
                               placeholder="----" required autofocus>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-circle me-2"></i>Verify &amp; Create Account
                    </button>
                </form>

                <form method="POST" action="{{ route('register.verify.resend') }}" class="text-center mt-3">
                    @csrf
                    <span class="text-muted" style="font-size:.875rem;">Didn't get the code?</span>
                    <button type="submit" class="btn-resend">Resend code</button>
                </form>

                <div class="back-link">
                    Wrong email? <a href="{{ route('register') }}">Start over</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
