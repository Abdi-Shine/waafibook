<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivatedMail;
use App\Mail\RegistrationOtpMail;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;

class RegisteredUserController extends Controller
{
    private const OTP_SESSION_KEY = 'registration_pending';
    private const OTP_TTL_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request: validate the form, then stage
     * the registration and email a one-time code instead of creating the
     * account immediately. This guarantees the email address is real and
     * reachable by the person signing up, not just typed text.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:200'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name'  => ['required', 'string', 'max:255', 'unique:companies,name'],
            'company_email' => ['nullable', 'email', 'max:255'],
        ]);

        $pending = [
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => $validated['password'],
            'company_name'   => $validated['company_name'],
            'company_email'  => $validated['company_email'] ?? null,
            'industry'       => $request->industry,
            'cr_number'      => $request->cr_number,
            'company_phone'  => $request->company_phone,
            'address'        => $request->address,
            'city'           => $request->city,
            'country'        => $request->country,
            'postal_code'    => $request->postal_code,
        ];

        $this->sendOtp($request, $pending);

        return redirect()->route('register.verify')
            ->with('status', 'We sent a 4-digit code to ' . $pending['email'] . '. Enter it below to finish creating your account.');
    }

    /**
     * Display the "enter the code we emailed you" screen.
     */
    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::OTP_SESSION_KEY);

        if (!$pending) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp', ['email' => $pending['email']]);
    }

    /**
     * Verify the submitted code and, if correct, actually create the
     * company + admin account using the staged registration data.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string'],
        ]);

        $pending = $request->session()->get(self::OTP_SESSION_KEY);

        if (!$pending) {
            return redirect()->route('register')
                ->withErrors(['otp' => 'Your registration session expired. Please start again.']);
        }

        if (now()->greaterThan($pending['otp_expires_at'])) {
            $request->session()->forget(self::OTP_SESSION_KEY);

            return redirect()->route('register')
                ->withErrors(['otp' => 'That code has expired. Please start registration again.']);
        }

        if ($pending['otp_attempts'] >= self::OTP_MAX_ATTEMPTS) {
            $request->session()->forget(self::OTP_SESSION_KEY);

            return redirect()->route('register')
                ->withErrors(['otp' => 'Too many incorrect attempts. Please start registration again.']);
        }

        if (!hash_equals($pending['otp_code'], trim((string) $request->otp))) {
            $pending['otp_attempts']++;
            $request->session()->put(self::OTP_SESSION_KEY, $pending);

            return back()->withErrors(['otp' => 'That code is incorrect. Please try again.']);
        }

        $request->session()->forget(self::OTP_SESSION_KEY);

        $this->completeRegistration($pending);

        return redirect()->route('login')
            ->with('status', 'Account created! Please check your email for your login credentials.');
    }

    /**
     * Regenerate and resend the OTP for the in-progress registration.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::OTP_SESSION_KEY);

        if (!$pending) {
            return redirect()->route('register');
        }

        $this->sendOtp($request, $pending);

        return back()->with('status', 'We sent a new code to ' . $pending['email'] . '.');
    }

    /**
     * Generate a fresh OTP for the given pending registration, store it in
     * the session, and email it to the address being verified.
     */
    private function sendOtp(Request $request, array $pending): void
    {
        $pending['otp_code']       = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $pending['otp_expires_at'] = now()->addMinutes(self::OTP_TTL_MINUTES);
        $pending['otp_attempts']   = 0;

        $request->session()->put(self::OTP_SESSION_KEY, $pending);

        Mail::to($pending['email'])->send(new RegistrationOtpMail(
            $pending['otp_code'],
            $pending['name'],
            self::OTP_TTL_MINUTES
        ));
    }

    /**
     * Create the company (tenant), admin user, and all default records for
     * a newly verified registration. This is the original account-creation
     * logic, now run only after the email has been proven reachable.
     */
    private function completeRegistration(array $data): void
    {
        // Ensure a free trial plan exists
        $trialPlan = SubscriptionPlan::firstOrCreate(
            ['name' => 'Free Trial'],
            [
                'description'   => '7-day free trial',
                'price'         => 0,
                'billing_cycle' => 'monthly',
                'max_users'     => 5,
                'status'        => 'active',
            ]
        );

        // Create a new company (tenant) for this registrant
        $company = \App\Models\Company::create([
            'name'                => $data['company_name'],
            'email'               => $data['company_email'] ?: $data['email'],
            'industry'            => $data['industry'],
            'registration_number' => $data['cr_number'],
            'phone'               => $data['company_phone'],
            'address'             => $data['address'],
            'city'                => $data['city'],
            'country'             => $data['country'] ?: 'Saudi Arabia',
            'postal_code'         => $data['postal_code'],
        ]);

        // Use withoutGlobalScopes because the user is not authenticated yet,
        // so the BelongsToTenant creating hook cannot auto-assign company_id.
        // We also set email_verified_at immediately: the OTP step above
        // already proved the admin controls this inbox.
        $user = User::withoutGlobalScopes()->create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'role'              => 'admin',
            'company_id'        => $company->id,
            'email_verified_at' => now(),
        ]);

        // Send customized activation email with credentials
        try {
            Mail::to($user->email)->send(new AccountActivatedMail(
                $company->name,
                $user->email,
                $data['password'],
                $user->name
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Signup email failed for ' . $user->email . ': ' . $e->getMessage());
        }

        // Seed default roles for this company so the admin can assign them to staff
        $allActions = ['view', 'create', 'edit', 'delete'];
        $modules = [
            'Dashboard', 'Sales & POS', 'Customers', 'Suppliers',
            'Products', 'Purchases', 'Accounting', 'HR',
            'Reports', 'Settings',
        ];
        $fullPermissions = array_fill_keys($modules, $allActions);
        $viewOnlyPermissions = array_fill_keys($modules, ['view']);

        $defaultRoles = [
            ['name' => 'Manager',   'description' => 'Full access to all modules',       'permissions' => $fullPermissions],
            ['name' => 'Cashier',   'description' => 'Sales and POS access',              'permissions' => array_fill_keys(['Dashboard', 'Sales & POS', 'Customers', 'Products'], $allActions)],
            ['name' => 'Accountant','description' => 'Accounting and reports access',     'permissions' => array_fill_keys(['Dashboard', 'Accounting', 'Reports'], $allActions)],
            ['name' => 'Staff',     'description' => 'View-only access to all modules',   'permissions' => $viewOnlyPermissions],
        ];

        foreach ($defaultRoles as $roleData) {
            \App\Models\Role::withoutGlobalScopes()->create(array_merge($roleData, [
                'company_id' => $company->id,
            ]));
        }

        // Seed default feature flags for this company
        $defaultFeatures = [
            ['feature_key' => 'pos',              'category' => 'sales',     'title' => 'POS System',              'is_enabled' => true],
            ['feature_key' => 'invoice',          'category' => 'sales',     'title' => 'Invoice Generation',      'is_enabled' => true],
            ['feature_key' => 'returns',          'category' => 'sales',     'title' => 'Sales Returns',           'is_enabled' => true],
            ['feature_key' => 'multibranch',      'category' => 'inventory', 'title' => 'Multi-Branch Support',    'is_enabled' => false],
            ['feature_key' => 'transfers',        'category' => 'inventory', 'title' => 'Stock Transfers',         'is_enabled' => false],
            ['feature_key' => 'adjustments',      'category' => 'inventory', 'title' => 'Stock Adjustments',       'is_enabled' => true],
            ['feature_key' => 'alerts',           'category' => 'inventory', 'title' => 'Low Stock Alerts',        'is_enabled' => true],
            ['feature_key' => 'po',               'category' => 'purchase',  'title' => 'Purchase Orders',         'is_enabled' => true],
            ['feature_key' => 'vendors',          'category' => 'purchase',  'title' => 'Vendor Management',       'is_enabled' => true],
            ['feature_key' => 'vendorpay',        'category' => 'purchase',  'title' => 'Vendor Payments',         'is_enabled' => true],
            ['feature_key' => 'employees',        'category' => 'hr',        'title' => 'Employee Profiles',       'is_enabled' => true],
            ['feature_key' => 'payroll',          'category' => 'hr',        'title' => 'Payroll Processing',      'is_enabled' => true],
            ['feature_key' => 'expenses',         'category' => 'finance',   'title' => 'Expense Tracking',        'is_enabled' => true],
            ['feature_key' => 'salesreports',     'category' => 'reports',   'title' => 'Sales Analytics',         'is_enabled' => true],
            ['feature_key' => 'inventoryreports', 'category' => 'reports',   'title' => 'Stock Reports',           'is_enabled' => true],
            ['feature_key' => 'financialreports', 'category' => 'reports',   'title' => 'Financial Statements',    'is_enabled' => true],
        ];

        foreach ($defaultFeatures as $feature) {
            \App\Models\FeatureSetting::withoutGlobalScopes()->create(array_merge($feature, [
                'company_id' => $company->id,
            ]));
        }

        // Create a 7-day trial subscription for the new company
        Subscription::create([
            'company_id'           => $company->id,
            'subscription_plan_id' => $trialPlan->id,
            'start_date'           => now()->toDateString(),
            'expiry_date'          => now()->addDays(7)->toDateString(),
            'status'               => 'trial',
            'auto_renew'           => false,
        ]);

        // Seed a default HQ branch so the company can operate immediately
        $hqBranch = \App\Models\Branch::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name'       => $company->name . ' - HQ',
            'code'       => 'BR-HQ',
            'level'      => 'Headquarters',
            'is_active'  => true,
        ]);

        // Seed full chart of accounts using the same service as the seeder
        app(\App\Services\ChartOfAccountsService::class)->seedForCompany($company->id, $hqBranch->id);

        // Seed the standard set of measurement units so the product form's
        // Base Unit dropdown isn't empty from the start
        foreach (['Piece', 'Box', 'Pack', 'Carton', 'Dozen', 'kg', 'g', 'Meter', 'Liter'] as $unitName) {
            \App\Models\Unit::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name'       => $unitName,
                'status'     => 'active',
            ]);
        }

        // Create an Employee record for the registering admin so they appear in Employee Management
        \App\Models\Employee::withoutGlobalScopes()->create([
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'employee_id' => 'EMP-' . date('Y') . '-001',
            'full_name'   => $user->name,
            'email'       => $user->email,
            'company'     => $company->name,
            'designation' => 'Administrator',
            'department'  => 'Management',
            'branch'      => $hqBranch->name,
            'status'      => 'active',
        ]);
    }
}
