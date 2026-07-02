<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created - WaafiBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f4f8; color: #333; }
        .wrapper { max-width: 620px; margin: 30px auto; padding: 20px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: #004161; padding: 32px 40px; text-align: center; }
        .header .logo-text { color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .header .logo-accent { color: #99CC33; }
        .header .tagline { color: rgba(255,255,255,0.65); font-size: 13px; margin-top: 4px; }

        /* Badge */
        .badge-wrap { background: #f8fffe; padding: 20px 40px 0; text-align: center; }
        .badge { display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; padding: 6px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; }

        /* Body */
        .body { padding: 32px 40px; }
        .body p { font-size: 15px; line-height: 1.7; margin-bottom: 16px; color: #444; }
        .body p.greeting { font-size: 17px; font-weight: 600; color: #004161; margin-bottom: 20px; }

        /* Credentials box */
        .creds { background: #f8fafc; border-left: 4px solid #99CC33; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
        .creds-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .creds-row:last-child { margin-bottom: 0; }
        .creds-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; min-width: 80px; padding-top: 3px; }
        .creds-value { font-size: 15px; font-weight: 600; color: #004161; word-break: break-all; }
        .creds-value a { color: #004161; text-decoration: none; }

        /* Password box */
        .pwd-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 8px 14px; display: inline-block; font-size: 16px; font-weight: 700; font-family: monospace; color: #c2410c; letter-spacing: 1px; }

        /* CTA Button */
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; padding: 14px 36px; background: #004161; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px; }

        /* Security tip */
        .tip { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-bottom: 24px; }
        .tip strong { color: #78350f; }

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
            <div class="logo-text">Waafi<span class="logo-accent">Book</span></div>
            <div class="tagline">Accounting SaaS for modern teams</div>
        </div>

        {{-- Verified badge --}}
        <div class="badge-wrap">
            <span class="badge">&#10003;&nbsp; Account Successfully Created</span>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">Welcome to WaafiBook, {{ $userName }}!</p>

            <p>Your <strong>{{ $companyName }}</strong> account has been created and is ready to use. Below are your login credentials — please keep them safe.</p>

            {{-- Credentials --}}
            <div class="creds">
                <div class="creds-row">
                    <span class="creds-label">Login URL</span>
                    <span class="creds-value">
                        <a href="{{ config('app.url') }}/login">{{ config('app.url') }}/login</a>
                    </span>
                </div>
                <div class="creds-row">
                    <span class="creds-label">Email</span>
                    <span class="creds-value">{{ $username }}</span>
                </div>
                <div class="creds-row">
                    <span class="creds-label">Password</span>
                    <span class="creds-value">
                        <span class="pwd-box">{{ $password }}</span>
                    </span>
                </div>
            </div>

            {{-- Security tip --}}
            <div class="tip">
                <strong>Security tip:</strong> We recommend changing your password after your first login. Go to <em>Settings → Profile</em> to update it.
            </div>

            <p>Your account includes a <strong>7-day free trial</strong> with full access to all features. Start managing your business right away.</p>

            <div class="btn-wrap">
                <a href="{{ config('app.url') }}/login" class="btn">Login to WaafiBook &rarr;</a>
            </div>

            <p>If you have any questions or need help getting started, our support team is here for you.</p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Need help? Email us at <a href="mailto:support@waafibook.com">support@waafibook.com</a></p>
            <p class="company">WaafiBook &mdash; Accounting SaaS for modern teams</p>
            <p style="margin-top:8px; font-size:11px; color:#bbb;">
                You received this email because an account was created using this email address.<br>
                If you did not sign up, please ignore this email or contact support.
            </p>
        </div>

    </div>
</div>
</body>
</html>
