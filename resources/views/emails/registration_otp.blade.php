<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email - WaafiBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f0f4f8; color: #333; }
        .wrapper { max-width: 620px; margin: 30px auto; padding: 20px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        .header { background: #004161; padding: 32px 40px; text-align: center; }
        .header .logo-text { color: #ffffff; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .header .logo-accent { color: #99CC33; }
        .header .tagline { color: rgba(255,255,255,0.65); font-size: 13px; margin-top: 4px; }

        .body { padding: 32px 40px; text-align: center; }
        .body p { font-size: 15px; line-height: 1.7; margin-bottom: 16px; color: #444; }
        .body p.greeting { font-size: 17px; font-weight: 600; color: #004161; margin-bottom: 20px; text-align: left; }
        .body p.intro { text-align: left; }

        .code-box { display: inline-block; background: #f8fafc; border: 2px dashed #99CC33; border-radius: 10px; padding: 18px 36px; margin: 12px 0 24px; }
        .code { font-size: 34px; font-weight: 800; letter-spacing: 10px; color: #004161; font-family: monospace; }

        .tip { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-bottom: 8px; text-align: left; }
        .tip strong { color: #78350f; }

        .footer { background: #f8fafc; border-top: 1px solid #e8ecef; padding: 24px 40px; text-align: center; }
        .footer p { font-size: 13px; color: #888; line-height: 1.6; margin-bottom: 6px; }
        .footer .company { font-weight: 700; color: #004161; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        <div class="header">
            <div class="logo-text">Waafi<span class="logo-accent">Book</span></div>
            <div class="tagline">Accounting SaaS for modern teams</div>
        </div>

        <div class="body">
            <p class="greeting">Hi {{ $userName }},</p>
            <p class="intro">Use the code below to verify this email address and finish creating your WaafiBook account.</p>

            <div class="code-box">
                <span class="code">{{ $otpCode }}</span>
            </div>

            <div class="tip">
                <strong>This code expires in {{ $ttlMinutes }} minutes.</strong> If you didn't try to create a WaafiBook account, you can safely ignore this email.
            </div>
        </div>

        <div class="footer">
            <p>Need help? Email us at <a href="mailto:support@waafibook.com">support@waafibook.com</a></p>
            <p class="company">WaafiBook &mdash; Accounting SaaS for modern teams</p>
        </div>

    </div>
</div>
</body>
</html>
