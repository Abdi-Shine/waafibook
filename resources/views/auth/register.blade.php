<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account - Waafibook</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css">
    <style>
        .iti { width: 100%; }
        .iti__search-input { font-size: .88rem; }
        .iti__flag-container { z-index: 10; }
    </style>

    <style>
        :root {
            --primary: #004161;
            --accent:  #99CC33;
        }

        body {
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f7e6 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .registration-container {
            width: 100%;
            max-width: 760px;
        }

        .registration-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 65, 97, 0.15);
            overflow: hidden;
        }

        /* ── Header ── */
        .registration-header {
            background: linear-gradient(135deg, var(--primary) 0%, #005a87 100%);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            color: #fff;
            position: relative;
        }

        .registration-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
        }

        .logo-icon {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .registration-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: .4rem;
        }

        .registration-subtitle {
            font-size: .9rem;
            opacity: .8;
            margin: 0;
        }

        /* ── Body ── */
        .registration-body {
            padding: 1.2rem 2rem 1rem;
        }

        /* ── Section titles ── */
        .section-title {
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .section-title i {
            color: var(--accent);
            font-size: 1rem;
        }

        /* ── Form controls ── */
        .form-label {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--primary);
            margin-bottom: .4rem;
        }

        .required { color: #e74c3c; }

        .form-control,
        .form-select {
            border: 1.5px solid #d0dde6;
            border-radius: 10px;
            padding: .55rem .85rem;
            font-size: .9rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 65, 97, .12);
            outline: none;
        }

        .input-group-text {
            background: #f0f6fa;
            border: 1.5px solid #d0dde6;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: var(--primary);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .input-group .btn-outline-secondary {
            border: 1.5px solid #d0dde6;
            border-left: none;
            border-radius: 0 10px 10px 0;
            color: var(--primary);
            background: #f0f6fa;
        }

        .input-group .btn-outline-secondary:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ── Password strength ── */
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: .4rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width .3s, background .3s;
        }

        .password-strength-bar.weak   { width: 33%; background: #e74c3c; }
        .password-strength-bar.medium { width: 66%; background: #f39c12; }
        .password-strength-bar.strong { width: 100%; background: var(--accent); }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, var(--primary) 0%, #005a87 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .3px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent);
            border-radius: 0 0 12px 12px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 65, 97, .35);
        }

        /* ── Checkbox / terms ── */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .text-primary { color: var(--primary) !important; }
        a.text-primary:hover { color: var(--accent) !important; }

        /* ── Benefits ── */
        .benefits-section {
            background: linear-gradient(135deg, #f0f6fa 0%, #f4f9ec 100%);
            border: 1.5px solid #d0dde6;
            border-radius: 14px;
            padding: 1.4rem;
            margin-top: 1.8rem;
        }

        .benefits-section h6 {
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            margin-bottom: .9rem;
        }

        .benefit-item:last-child { margin-bottom: 0; }

        .benefit-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
        }

        .benefit-text h6 {
            font-size: .85rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: .15rem;
            text-transform: none;
            letter-spacing: 0;
        }

        .benefit-text p {
            font-size: .8rem;
            color: #6c7a8a;
            margin: 0;
        }

        /* ── Footer link ── */
        .back-link {
            text-align: center;
            margin-top: 1.4rem;
            font-size: .875rem;
            color: #6c7a8a;
        }

        .back-link a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .back-link a:hover { color: var(--accent); }

        hr { border-color: #e2ecf3; }
    </style>
</head>
<body>
<div class="container">
    <div class="registration-container">
        <div class="registration-card">

            <div class="registration-header">
                <div class="logo-icon">
                    <img src="/icons/icon-192.png" alt="Waafibook Logo" style="width:100%;height:100%;object-fit:contain;border-radius:8px;">
                </div>
                <h1 class="registration-title">Create Your Account</h1>
                <p class="registration-subtitle">Join thousands of businesses using Waafibook</p>
            </div>

            <div class="registration-body">

                @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('register') }}" id="registrationForm">
                    @csrf

                    {{-- ── Company Information ── --}}
                    <p class="section-title"><i class="bi bi-building"></i> Company Information</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Company Name <span class="required">*</span></label>
                            <input type="text" name="company_name"
                                   class="form-control @error('company_name') is-invalid @enderror"
                                   placeholder="Enter company name"
                                   value="{{ old('company_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <select name="industry" class="form-select">
                                <option value="">Select industry</option>
                                @foreach(['Retail & Trading','Manufacturing','Wholesale Distribution','Food & Beverage','Electronics & Technology','Healthcare','Construction','Professional Services','Other'] as $ind)
                                    <option value="{{ $ind }}" {{ old('industry') === $ind ? 'selected' : '' }}>{{ $ind }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Size</label>
                            <select name="company_size" class="form-select">
                                <option value="">Select size</option>
                                @foreach(['1-10 employees','11-50 employees','51-200 employees','201-500 employees','500+ employees'] as $size)
                                    <option value="{{ $size }}" {{ old('company_size') === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <select name="country" id="countrySelect" class="form-select">
                                <option value="">Select country</option>
                                @php
                                $countries = [
                                    'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AD'=>'Andorra','AO'=>'Angola',
                                    'AG'=>'Antigua and Barbuda','AR'=>'Argentina','AM'=>'Armenia','AU'=>'Australia',
                                    'AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh',
                                    'BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin',
                                    'BT'=>'Bhutan','BO'=>'Bolivia','BA'=>'Bosnia and Herzegovina','BW'=>'Botswana',
                                    'BR'=>'Brazil','BN'=>'Brunei','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi',
                                    'CV'=>'Cabo Verde','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CF'=>'Central African Republic',
                                    'TD'=>'Chad','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','KM'=>'Comoros',
                                    'CD'=>'Congo (DRC)','CG'=>'Congo','CR'=>'Costa Rica','HR'=>'Croatia','CU'=>'Cuba',
                                    'CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica',
                                    'DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador',
                                    'GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','SZ'=>'Eswatini',
                                    'ET'=>'Ethiopia','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France','GA'=>'Gabon',
                                    'GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece',
                                    'GD'=>'Grenada','GT'=>'Guatemala','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana',
                                    'HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India',
                                    'ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland','IL'=>'Israel',
                                    'IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan',
                                    'KE'=>'Kenya','KI'=>'Kiribati','KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Laos',
                                    'LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya',
                                    'LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MG'=>'Madagascar',
                                    'MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta',
                                    'MH'=>'Marshall Islands','MR'=>'Mauritania','MU'=>'Mauritius','MX'=>'Mexico',
                                    'FM'=>'Micronesia','MD'=>'Moldova','MC'=>'Monaco','MN'=>'Mongolia','ME'=>'Montenegro',
                                    'MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru',
                                    'NP'=>'Nepal','NL'=>'Netherlands','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger',
                                    'NG'=>'Nigeria','NO'=>'Norway','OM'=>'Oman','PK'=>'Pakistan','PW'=>'Palau',
                                    'PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru',
                                    'PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania',
                                    'RU'=>'Russia','RW'=>'Rwanda','KN'=>'Saint Kitts and Nevis','LC'=>'Saint Lucia',
                                    'VC'=>'Saint Vincent and the Grenadines','WS'=>'Samoa','SM'=>'San Marino',
                                    'ST'=>'Sao Tome and Principe','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia',
                                    'SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia',
                                    'SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa',
                                    'SS'=>'South Sudan','ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname',
                                    'SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan',
                                    'TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TO'=>'Tonga',
                                    'TT'=>'Trinidad and Tobago','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan',
                                    'TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates',
                                    'GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan',
                                    'VU'=>'Vanuatu','VE'=>'Venezuela','VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe',
                                ];
                                @endphp
                                @foreach($countries as $code => $name)
                                    <option value="{{ $name }}" data-code="{{ strtolower($code) }}" {{ old('country') === $name ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Telephone</label>
                            <input type="tel" id="company_phone" name="company_phone"
                                   class="form-control" placeholder="61 234 5678"
                                   value="{{ old('company_phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Commercial Registration</label>
                            <input type="text" name="cr_number" class="form-control"
                                   placeholder="CR Number" value="{{ old('cr_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   placeholder="City" value="{{ old('city') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                                <input type="email" name="company_email" class="form-control @error('company_email') is-invalid @enderror"
                                       placeholder="info@company.com"
                                       value="{{ old('company_email') }}">
                            </div>
                            @error('company_email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>



                    {{-- ── Admin User Details ── --}}
                    <p class="section-title mt-2 mb-2"><i class="bi bi-person-badge"></i> Admin User Details</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Enter your full name"
                                       value="{{ old('name') }}" required autocomplete="name">
                            </div>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="admin@company.com"
                                       value="{{ old('email') }}" required autocomplete="username">
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Create password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1 text-nowrap" style="font-size: 0.72rem;">Min. 8 characters with uppercase, lowercase and numbers</small>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="password_confirmation" id="confirmPassword"
                                       class="form-control"
                                       placeholder="Confirm password" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>



                    {{-- ── Terms ── --}}
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-primary">Terms of Service</a> and <a href="{{ route('privacy') }}" target="_blank" class="text-primary">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-rocket-takeoff me-2"></i>Create Account
                    </button>
                    <div class="text-center text-muted mt-2">
                        <small>14-day free trial &bull; No credit card required</small>
                    </div>

                </form>



                <div class="back-link">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js"></script>
<script>
    // Initialise intl-tel-input
    const phoneInput = document.getElementById('company_phone');
    const iti = window.intlTelInput(phoneInput, {
        initialCountry: 'so',
        separateDialCode: true,
        preferredCountries: ['so','ke','et','ug','tz','ae','sa','gb','us'],
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js',
    });

    // Sync phone country with country dropdown
    document.getElementById('countrySelect').addEventListener('change', function () {
        const code = this.options[this.selectedIndex].dataset.code;
        if (code) iti.setCountry(code);
    });

    // On submit, write the full international number into the input
    document.getElementById('registrationForm').addEventListener('submit', function (e) {
        const p = document.getElementById('password').value;
        const c = document.getElementById('confirmPassword').value;
        if (p !== c) { e.preventDefault(); alert('Passwords do not match!'); return; }
        if (iti.isValidNumber()) {
            phoneInput.value = iti.getNumber();
        }
    });
</script>
<script>
    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }



</script>
</body>
</html>
