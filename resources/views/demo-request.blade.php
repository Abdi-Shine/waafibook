<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Demo - Waafibook SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #004161; --secondary-color: #99CC33; --primary-dark: #002d47; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .demo-container { min-height: 100vh; display: flex; align-items: center; padding: 2rem 0; }
        .demo-card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,.1); overflow: hidden; }
        .demo-sidebar { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; padding: 3rem 2rem; min-height: 100%; }
        .logo-icon { width: 60px; height: 60px; background: white; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem; }
        .demo-sidebar h2 { font-family: 'Poppins', sans-serif; font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; }
        .demo-sidebar p { color: rgba(255,255,255,.9); line-height: 1.6; margin-bottom: 2rem; }
        .demo-features { list-style: none; }
        .demo-features li { display: flex; align-items: start; gap: 1rem; margin-bottom: 1.5rem; }
        .feature-icon { width: 40px; height: 40px; background: rgba(153,204,51,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--secondary-color); flex-shrink: 0; }
        .feature-text h6 { margin-bottom: .25rem; font-weight: 600; }
        .feature-text p { margin: 0; font-size: .875rem; color: rgba(255,255,255,.8); }
        .demo-form-section { padding: 3rem 2rem; }
        .form-title { font-family: 'Poppins', sans-serif; font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: .5rem; }
        .form-subtitle { color: #6c757d; margin-bottom: 2rem; }
        .form-label { font-weight: 600; color: var(--primary-color); margin-bottom: .5rem; font-size: .9rem; }
        .form-label .required { color: #dc3545; }
        .form-control, .form-select { border: 2px solid #e9ecef; border-radius: 10px; padding: .75rem 1rem; transition: all .3s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0,65,97,.1); }
        textarea.form-control { min-height: 120px; }
        .btn-primary { background: var(--primary-color); border: none; padding: .875rem 2rem; border-radius: 10px; font-weight: 600; transition: all .3s; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,65,97,.3); }
        .btn-success { background: var(--secondary-color); border: none; color: var(--primary-dark); padding: .875rem 2rem; border-radius: 10px; font-weight: 600; transition: all .3s; }
        .btn-success:hover { background: #88bb22; color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(153,204,51,.4); }
        .time-slot { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .75rem; margin-top: 1rem; }
        .time-slot-btn { padding: .75rem; border: 2px solid #e9ecef; border-radius: 8px; background: white; text-align: center; font-weight: 600; cursor: pointer; transition: all .3s; }
        .time-slot-btn:hover { border-color: var(--primary-color); background: rgba(0,65,97,.05); }
        .time-slot-btn.selected { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .info-box { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196f3; padding: 1rem; border-radius: 8px; margin-top: 2rem; }
        .info-box h6 { color: #1976d2; font-weight: 700; margin-bottom: .5rem; display: flex; align-items: center; gap: .5rem; }
        .info-box p { color: #0d47a1; font-size: .875rem; margin: 0; }
        .back-link { text-align: center; margin-top: 2rem; }
        .back-link a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .demo-sidebar { padding: 2rem 1.5rem; }
            .demo-form-section { padding: 2rem 1.5rem; }
            .time-slot { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="demo-container">
    <div class="container">
        <div class="demo-card">
            <div class="row g-0">
                <!-- Sidebar -->
                <div class="col-lg-5">
                    <div class="demo-sidebar">
                        <div class="logo-section mb-4">
                            <div class="logo-icon"><i class="bi bi-shop"></i></div>
                            <h2>Request a Demo</h2>
                            <p>See Waafibook in action with a personalized demonstration tailored to your business needs.</p>
                        </div>
                        <ul class="demo-features">
                            <li>
                                <div class="feature-icon"><i class="bi bi-clock"></i></div>
                                <div class="feature-text"><h6>30-Minute Session</h6><p>Quick, focused demo covering key features</p></div>
                            </li>
                            <li>
                                <div class="feature-icon"><i class="bi bi-person-video2"></i></div>
                                <div class="feature-text"><h6>Live with Expert</h6><p>Interactive session with our product specialist</p></div>
                            </li>
                            <li>
                                <div class="feature-icon"><i class="bi bi-card-checklist"></i></div>
                                <div class="feature-text"><h6>Customized Walkthrough</h6><p>Demo tailored to your industry and needs</p></div>
                            </li>
                            <li>
                                <div class="feature-icon"><i class="bi bi-question-circle"></i></div>
                                <div class="feature-text"><h6>Q&A Session</h6><p>Get all your questions answered</p></div>
                            </li>
                            <li>
                                <div class="feature-icon"><i class="bi bi-gift"></i></div>
                                <div class="feature-text"><h6>Free Trial Access</h6><p>7-day trial immediately after demo</p></div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Form -->
                <div class="col-lg-7">
                    <div class="demo-form-section">
                        <h1 class="form-title">Schedule Your Demo</h1>
                        <p class="form-subtitle">Fill in the details below and we'll get back to you shortly</p>

                        @if(session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-check-circle-fill"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="demoForm" method="POST" action="{{ route('demo.request.store') }}">
                            @csrf

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Company Name <span class="required">*</span></label>
                                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                           placeholder="Enter your company name" value="{{ $prefill['company_name'] }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Your Name <span class="required">*</span></label>
                                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                           placeholder="Enter your name" value="{{ $prefill['full_name'] }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           placeholder="you@gmail.com" value="{{ $prefill['email'] }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Phone <span class="required">*</span></label>
                                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           placeholder="+966 50 123 4567" value="{{ $prefill['phone'] }}" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-calendar-check me-2"></i>Schedule Demo
                            </button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
