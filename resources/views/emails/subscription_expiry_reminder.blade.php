<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isExpired ? 'Your WaafiBook ' . ($isTrial ? 'trial' : 'subscription') . ' has expired' : 'Subscription reminder - WaafiBook' }}</title>
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
        .badge-wrap { padding: 20px 40px 0; text-align: center; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; }
        .badge.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .badge.danger { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        /* Body */
        .body { padding: 32px 40px; }
        .body p { font-size: 15px; line-height: 1.7; margin-bottom: 16px; color: #444; }
        .body p.greeting { font-size: 17px; font-weight: 600; color: #004161; margin-bottom: 20px; }
        .body strong { color: #004161; }

        /* CTA Button */
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; padding: 14px 36px; background: #004161; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px; }

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

        {{-- Status badge --}}
        <div class="badge-wrap">
            @if($isExpired)
                <span class="badge danger">&#9888;&nbsp; {{ $isTrial ? 'Trial' : 'Subscription' }} Expired</span>
            @else
                <span class="badge warning">&#9200;&nbsp; {{ $daysLeft }} {{ Str::plural('Day', $daysLeft) }} Left</span>
            @endif
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">Hi {{ $userName }},</p>

            @if($isExpired)
                <p>Your <strong>{{ $companyName }}</strong> WaafiBook {{ $isTrial ? 'free trial' : 'subscription' }} expired on <strong>{{ $expiryDate->format('F j, Y') }}</strong>.</p>
                <p>All your data remains safe and untouched. To regain access and continue using WaafiBook, please get in touch with us to subscribe.</p>
            @else
                <p>Your <strong>{{ $companyName }}</strong> WaafiBook {{ $isTrial ? 'free trial' : 'subscription' }} ends on <strong>{{ $expiryDate->format('F j, Y') }}</strong>.</p>
                <p>All your data will remain safe. To continue using WaafiBook after {{ $isTrial ? 'your trial' : 'this period' }}, please get in touch with us to subscribe.</p>
            @endif

            <div class="btn-wrap">
                <a href="mailto:support@waafibook.com?subject=Subscribe%20to%20WaafiBook%20-%20{{ urlencode($companyName) }}" class="btn">Contact us to subscribe</a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>Need help? Email us at <a href="mailto:support@waafibook.com">support@waafibook.com</a></p>
            <p>Or call us at <a href="tel:+252615164216">+252 615164216</a> / <a href="tel:+252615539846">+252 61 5539846</a></p>
            <p>Prefer WhatsApp? Reach us at <a href="https://wa.me/252615164216">+252 61 5164216</a></p>
            <p class="company">WaafiBook &mdash; Accounting SaaS for modern teams</p>
        </div>

    </div>
</div>
</body>
</html>
