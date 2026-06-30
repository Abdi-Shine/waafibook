<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaafiBook — You're Offline</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #004161;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 2rem;
        }
        .card {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 420px;
            width: 100%;
        }
        .icon { font-size: 4rem; margin-bottom: 1.5rem; opacity: .8; }
        h1 { font-size: 1.6rem; font-weight: 800; margin-bottom: .5rem; }
        p  { font-size: .95rem; opacity: .7; line-height: 1.6; margin-bottom: 2rem; }
        .brand { font-size: .75rem; opacity: .45; margin-top: 2rem; letter-spacing: .05em; text-transform: uppercase; }
        .btn {
            display: inline-block;
            background: #99CC33;
            color: #002d47;
            font-weight: 800;
            font-size: .9rem;
            padding: .75rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .2s;
        }
        .btn:hover { background: #7aaa1e; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: .8; }
            50% { opacity: .4; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon pulse">📡</div>
        <h1>You're Offline</h1>
        <p>WaafiBook can't reach the server right now. Check your internet connection and try again.</p>
        <p>The POS Terminal may still be available from your recent cache.</p>
        <a href="/sales/pos" class="btn" style="margin-right:.5rem">Open POS Terminal</a>
        <button class="btn" onclick="window.location.reload()" style="background:rgba(255,255,255,.15);color:#fff;margin-top:.5rem;">
            Try Again
        </button>
        <div class="brand">WaafiBook ERP · Offline Mode</div>
    </div>
    <script>
        window.addEventListener('online', () => window.location.reload());
    </script>
</body>
</html>
