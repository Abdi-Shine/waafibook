<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isExpired ? 'Your WaafiBook ' . ($isTrial ? 'trial' : 'subscription') . ' has expired' : 'Subscription reminder - WaafiBook' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0d1117; color: #c9d1d9; }
        .wrapper { max-width: 480px; margin: 0 auto; padding: 40px 32px; }

        .logo-text { color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 32px; }
        .logo-accent { color: #99CC33; }

        h1 { color: #ffffff; font-size: 26px; font-weight: 800; line-height: 1.3; margin-bottom: 28px; }

        p { font-size: 15px; line-height: 1.7; margin-bottom: 20px; color: #c9d1d9; }
        p strong { color: #ffffff; }

        .btn-wrap { margin: 32px 0; }
        .btn { display: inline-block; padding: 14px 28px; background: #99CC33; color: #0d1117 !important; text-decoration: none; border-radius: 10px; font-size: 15px; font-weight: 800; }

        .divider { border: none; border-top: 1px solid #2a2f38; margin: 32px 0 24px; }

        .footer p { font-size: 13px; color: #8b949e; margin-bottom: 0; }
        .footer a { color: #99CC33; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="logo-text">Waafi<span class="logo-accent">Book</span></div>

    @if($isExpired)
        <h1>Your {{ $isTrial ? 'trial' : 'subscription' }} has expired</h1>
    @else
        <h1>{{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} left in your {{ $isTrial ? 'trial' : 'subscription' }}</h1>
    @endif

    <p>Hi {{ $userName }},</p>

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

    <hr class="divider">

    <div class="footer">
        <p>Prefer WhatsApp? Reach us at <a href="https://wa.me/252615164216">+252 61 5164216</a>.</p>
    </div>

</div>
</body>
</html>
