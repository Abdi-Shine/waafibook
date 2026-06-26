<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Waafibook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #004161; --accent: #99CC33; }
        body { font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #004161 0%, #002b40 100%); margin: 0; }
        .verification-card { max-width: 500px; width: 100%; padding: 3rem; background: white; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); text-align: center; }
        .icon { width: 80px; height: 80px; background: rgba(153, 204, 51, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem; }
        h1 { color: var(--primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 1rem; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 2rem; }
        .btn-resend { background: var(--primary); border: none; padding: 0.75rem 1.5rem; border-radius: 0.75rem; color: white; font-weight: 600; transition: all 0.3s; margin-bottom: 1rem; width: 100%; }
        .btn-resend:hover { background: #002b40; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .logout-link { color: #94a3b8; text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .logout-link:hover { color: var(--primary); }
        .alert-success { background-color: #f0fdf4; color: #166534; border: 1px solid #dcfce7; border-radius: 1rem; padding: 1rem; margin-bottom: 2rem; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="icon">
            <i class="bi bi-envelope-check-fill"></i>
        </div>
        
        <h1>Verify your email</h1>
        
        @if (session('status') == 'verification-link-sent')
            <div class="alert-success">
                A new verification link has been sent to the email address you provided.
            </div>
        @else
            <p>Thanks for joining Waafibook! Before you start, please verify your email address by clicking on the link we just sent to you. check your inbox and your spam folder.</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-resend">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-link btn btn-link">
                Log Out
            </button>
        </form>
    </div>
</body>
</html>
