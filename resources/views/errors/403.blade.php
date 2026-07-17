<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - WaafiBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            max-width: 420px;
            width: 100%;
            padding: 40px 36px;
            text-align: center;
        }
        .logo {
            font-size: 22px;
            font-weight: 800;
            color: #004161;
            letter-spacing: -0.5px;
            margin-bottom: 28px;
        }
        .logo span { color: #99CC33; }
        .icon-box {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fef2f2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
        }
        .icon-box svg { width: 30px; height: 30px; }
        h1 {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }
        p.message {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #004161;
            color: #fff;
            border-radius: 10px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .9; }
        .code {
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
            font-weight: 600;
            letter-spacing: .05em;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Waafi<span>Book</span></div>

        <div class="icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <h1>Access Restricted</h1>
        <p class="message">
            You don't have permission to view this page. If you believe this is a mistake,
            please contact your company administrator to request access.
        </p>

        <a href="{{ url('/dashboard') }}" class="btn">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3.5 2 8.5V14a1 1 0 0 0 1 1h3.5v-4A1.5 1.5 0 0 1 8 9.5h0A1.5 1.5 0 0 1 9.5 11v4H13a1 1 0 0 0 1-1V8.5L8 3.5Z"/></svg>
            Back to Dashboard
        </a>

        <p class="code">ERROR 403 · UNAUTHORIZED</p>
    </div>
</body>
</html>
