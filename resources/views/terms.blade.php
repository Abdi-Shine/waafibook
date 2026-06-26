<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Waafibook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; }
        .container { max-width: 800px; padding: 3rem 1rem; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 2.5rem; }
        h1 { color: #004161; font-weight: 700; margin-bottom: 2rem; }
        h2 { color: #004161; font-size: 1.25rem; font-weight: 600; margin-top: 2rem; margin-bottom: 1rem; }
        p { margin-bottom: 1rem; }
        .back-link { margin-bottom: 2rem; display: inline-block; color: #99CC33; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <a href="{{ url()->previous() }}" class="back-link">&larr; Back</a>
        <div class="card">
            <h1>Terms of Service</h1>
            <p>Last updated: {{ date('F d, Y') }}</p>

            <h2>1. Agreement to Terms</h2>
            <p>By accessing or using Waafibook, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>

            <h2>2. Description of Service</h2>
            <p>Waafibook provides cloud-based enterprise resource planning software including but not limited to accounting, inventory management, sales, and HR modules.</p>

            <h2>3. User Accounts</h2>
            <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

            <h2>4. Acceptable Use</h2>
            <p>You agree not to use the service for any illegal purposes or in any way that could damage, disable, or impair the service.</p>

            <h2>5. Data Ownership</h2>
            <p>You retain all rights and ownership of the data you input into the system. Waafibook claims no intellectual property rights over your data.</p>

            <h2>6. Limitation of Liability</h2>
            <p>Waafibook shall not be liable for any indirect, incidental, special, or consequential damages resulting from the use or inability to use the service.</p>

            <h2>7. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Continued use of the service after such changes constitutes acceptance of the new terms.</p>

            <div class="mt-5 pt-4 border-top text-center text-muted">
                &copy; {{ date('Y') }} Waafibook. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
