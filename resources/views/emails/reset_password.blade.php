<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - WaafiBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f4f8; color: #333; }
        .wrapper { max-width: 620px; margin: 30px auto; padding: 20px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: #004161; padding: 28px 40px 24px; text-align: center; }
        .header .logo-img { width: 64px; height: 64px; border-radius: 14px; object-fit: contain; background: #fff; padding: 4px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto; }
        .header .logo-text { color: #ffffff; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .header .logo-accent { color: #99CC33; }
        .header .tagline { color: rgba(255,255,255,0.65); font-size: 13px; margin-top: 4px; }

        /* Badge */
        .badge-wrap { background: #f8fffe; padding: 20px 40px 0; text-align: center; }
        .badge { display: inline-flex; align-items: center; gap: 6px; background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 6px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; }

        /* Body */
        .body { padding: 32px 40px; }
        .body p { font-size: 15px; line-height: 1.7; margin-bottom: 16px; color: #444; }
        .body p.greeting { font-size: 17px; font-weight: 600; color: #004161; margin-bottom: 20px; }

        /* Info box */
        .info-box { background: #f8fafc; border-left: 4px solid #99CC33; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .info-box p { margin: 0; font-size: 14px; color: #555; }
        .info-box .email-val { font-weight: 700; color: #004161; }

        /* CTA Button */
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; padding: 14px 40px; background: #004161; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px; }

        /* Expiry notice */
        .expiry { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px 18px; font-size: 13px; color: #92400e; margin-bottom: 20px; }

        /* Fallback URL */
        .fallback { background: #f1f5f9; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; }
        .fallback p { font-size: 13px; color: #64748b; margin-bottom: 6px; }
        .fallback a { font-size: 12px; color: #004161; word-break: break-all; }

        /* Footer */
        .footer { background: #f8fafc; border-top: 1px solid #e8ecef; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 13px; color: #888; line-height: 1.6; margin-bottom: 6px; }
        .footer a { color: #004161; text-decoration: none; }
        .footer .company { font-weight: 700; color: #004161; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <img src="{{ config('app.url') }}/icons/icon-192.png" alt="WaafiBook" class="logo-img">
            <div class="logo-text">Waafi<span class="logo-accent">Book</span></div>
            <div class="tagline">Accounting SaaS for modern teams</div>
        </div>

        {{-- Badge --}}
        <div class="badge-wrap">
            <span class="badge">&#128274;&nbsp; Password Reset Request</span>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">Hello, {{ $userName }}!</p>

            <p>We received a request to reset the password for your WaafiBook account. Click the button below to set a new password.</p>

            <div class="info-box">
                <p>Account email: <span class="email-val">{{ $email }}</span></p>
            </div>

            <div class="btn-wrap">
                <a href="{{ $url }}" class="btn">Reset My Password &rarr;</a>
            </div>

            <div class="expiry">
                &#9201;&nbsp; This link will expire in <strong>60 minutes</strong>. If it expires, you can request a new one from the login page.
            </div>

            <p>If you did not request a password reset, no action is needed — your account remains secure and this link will expire automatically.</p>

            <div class="fallback">
                <p>If the button above doesn&rsquo;t work, copy and paste this URL into your browser:</p>
                <a href="{{ $url }}">{{ $url }}</a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Need help? Email us at <a href="mailto:support@waafibook.com">support@waafibook.com</a></p>
            <p class="company">WaafiBook &mdash; Accounting SaaS for modern teams</p>
            <p style="margin-top:8px; font-size:11px; color:#bbb;">
                You received this email because a password reset was requested for this account.<br>
                If you did not make this request, please ignore this email.
            </p>
        </div>

    </div>
</div>
</body>
</html>
