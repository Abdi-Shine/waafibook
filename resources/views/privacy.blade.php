<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Waafibook</title>
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
            <h1>Privacy Policy</h1>
            <p>Last updated: {{ date('F d, Y') }}</p>

            <h2>1. Information We Collect</h2>
            <p>We collect information you provide directly to us when you create an account, such as your name, email address, company details, and payment information.</p>

            <h2>2. How We Use Your Information</h2>
            <p>We use the collected information to provide, maintain, and improve our services, communicate with you, and process transactions.</p>

            <h2>3. Data Protection</h2>
            <p>We implement appropriate technical and organizational measures to protect your data against unauthorized access, loss, or disclosure.</p>

            <h2>4. Information Sharing</h2>
            <p>We do not share your personal information with third parties except as necessary to provide our services, comply with the law, or protect our rights.</p>

            <h2>5. Cookies</h2>
            <p>We use cookies and similar technologies to enhance your experience and collect information about how you use our service.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to access, update, or delete your personal information. Please contact us if you need assistance with these requests.</p>

            <h2>7. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us at privacy@waafibook.com</p>

            <div class="mt-5 pt-4 border-top text-center text-muted">
                &copy; {{ date('Y') }} Waafibook. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
