@extends('super_admin.layouts.master')
@section('page_title', 'Platform Settings')
@section('content')

<div class="mb-4">
    <h4 style="font-weight:800;color:#111827;margin:0;">System Settings</h4>
    <p style="color:#6b7280;font-size:.875rem;margin:.2rem 0 0;">Platform-wide configuration, defaults, and maintenance controls.</p>
</div>

</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('host.settings.update') }}">
    @csrf

    <div class="row g-4">

        {{-- ── LEFT COLUMN ─────────────────────────────────────── --}}
        <div class="col-md-8 d-flex flex-column gap-4">

            {{-- Platform --}}
            <div class="sa-card p-4">
                <h6 class="fw-black mb-4 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                    <i class="bi bi-globe2 me-2"></i>Platform
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Platform Name</label>
                        <input type="text" name="platform_name" class="form-control"
                               value="{{ old('platform_name', $settings['platform_name'] ?? 'Horntech LTD') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Support Email</label>
                        <input type="email" name="support_email" class="form-control"
                               value="{{ old('support_email', $settings['support_email'] ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Default Trial Period (days)</label>
                        <input type="number" name="trial_days" class="form-control" min="1" max="365"
                               value="{{ old('trial_days', $settings['trial_days'] ?? '14') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Default Currency</label>
                        <select name="default_currency" class="form-select">
                            @foreach(['USD' => 'USD — US Dollar', 'SOS' => 'SOS — Somali Shilling', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — British Pound', 'SAR' => 'SAR — Saudi Riyal', 'AED' => 'AED — UAE Dirham'] as $code => $label)
                                <option value="{{ $code }}"
                                    {{ old('default_currency', $settings['default_currency'] ?? 'USD') === $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- EVC Mobile Money --}}
            <div class="sa-card p-4">
                <h6 class="fw-black mb-1 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                    <i class="bi bi-phone-fill me-2"></i>EVC Plus — Mobile Money
                </h6>
                <p class="text-muted small mb-4">
                    Clients will see these details on the checkout page when paying via EVC Plus.
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">EVC Merchant Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="text" name="evc_merchant_number" class="form-control"
                                   placeholder="e.g. 615000000"
                                   value="{{ old('evc_merchant_number', $settings['evc_merchant_number'] ?? '') }}">
                        </div>
                        <div class="form-text">The number clients send money to via EVC Plus.</div>
                    </div>
                </div>
            </div>

            {{-- Bank Transfer --}}
            <div class="sa-card p-4">
                <h6 class="fw-black mb-1 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                    <i class="bi bi-bank2 me-2"></i>Bank Transfer
                </h6>
                <p class="text-muted small mb-4">
                    Clients will see these details on the checkout page when paying via bank transfer.
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control"
                               placeholder="e.g. Premier Bank Somalia"
                               value="{{ old('bank_name', $settings['bank_name'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Account Name</label>
                        <input type="text" name="bank_account_name" class="form-control"
                               placeholder="e.g. Horntech LTD"
                               value="{{ old('bank_account_name', $settings['bank_account_name'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Account Number</label>
                        <input type="text" name="bank_account_number" class="form-control"
                               placeholder="e.g. 1234567890"
                               value="{{ old('bank_account_number', $settings['bank_account_number'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">SWIFT / BIC Code <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="bank_swift_code" class="form-control"
                               placeholder="e.g. PRBKSOMAXXXX"
                               value="{{ old('bank_swift_code', $settings['bank_swift_code'] ?? '') }}">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" class="btn text-white fw-bold px-5"
                        style="background:var(--primary);border-radius:10px;padding-top:.75rem;padding-bottom:.75rem;">
                    <i class="bi bi-check-lg me-1"></i> Save Settings
                </button>
            </div>

        </div>

        {{-- ── RIGHT COLUMN ────────────────────────────────────── --}}
        <div class="col-md-4 d-flex flex-column gap-4">

            {{-- Preview --}}
            <div class="sa-card p-4">
                <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                    <i class="bi bi-eye me-2"></i>Checkout Preview
                </h6>

                {{-- EVC Preview --}}
                <div class="rounded-3 p-3 mb-3" style="background:rgba(0,65,97,.06);border:1px solid rgba(0,65,97,.18);">
                    <p class="fw-black text-uppercase mb-1" style="font-size:.65rem;letter-spacing:.08em;color:var(--primary);">
                        EVC Plus — What clients see
                    </p>
                    <p class="small mb-1 fw-semibold" style="color:var(--primary);">Merchant Number:</p>
                    <p class="fw-black mb-0" style="color:var(--primary-dark);font-size:.85rem;">
                        {{ $settings['evc_merchant_number'] ?: '— Not configured —' }}
                    </p>
                </div>

                {{-- Bank Preview --}}
                <div class="rounded-3 p-3" style="background:rgba(153,204,51,.12);border:1px solid rgba(153,204,51,.35);">
                    <p class="fw-black text-uppercase mb-2" style="font-size:.65rem;letter-spacing:.08em;color:#5a7a1a;">
                        Bank Transfer — What clients see
                    </p>
                    @foreach([
                        'Bank' => $settings['bank_name'] ?? '',
                        'Account Name' => $settings['bank_account_name'] ?? '',
                        'Account No.' => $settings['bank_account_number'] ?? '',
                        'SWIFT' => $settings['bank_swift_code'] ?? '',
                    ] as $label => $val)
                        @if($val)
                        <div class="d-flex justify-content-between small mb-1">
                            <span style="color:#5a7a1a;">{{ $label }}</span>
                            <span class="fw-bold" style="color:var(--primary-dark);">{{ $val }}</span>
                        </div>
                        @endif
                    @endforeach
                    @if(!($settings['bank_name'] ?? '') && !($settings['bank_account_number'] ?? ''))
                        <p class="small mb-0 fw-semibold" style="color:#5a7a1a;">— Not configured —</p>
                    @endif
                </div>
            </div>

            {{-- Maintenance Mode --}}
            <div class="sa-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-black mb-0 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                        <i class="bi bi-cone-striped me-2"></i>Maintenance Mode
                    </h6>
                    <span class="sa-badge {{ $maintenanceMode ? 'sa-badge-red' : 'sa-badge-green' }}">{{ $maintenanceMode ? 'ON' : 'OFF' }}</span>
                </div>
                <p class="text-muted small mb-3">When enabled, every tenant is shown a maintenance page until you disable this. Super Admin always retains access.</p>
                <form method="POST" action="{{ route('host.settings.maintenance') }}"
                      onsubmit="return confirm('{{ $maintenanceMode ? 'Disable maintenance mode? Tenants will regain access immediately.' : 'Enable maintenance mode? Every tenant will be locked out until you disable this.' }}');">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $maintenanceMode ? 'btn-outline-primary' : 'btn-outline-danger' }} w-100">
                        {{ $maintenanceMode ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}
                    </button>
                </form>
            </div>

            {{-- System Info --}}
            <div class="sa-card p-4">
                <h6 class="fw-black mb-3 text-uppercase" style="color:var(--primary);font-size:.75rem;letter-spacing:.08em;">
                    <i class="bi bi-info-circle me-2"></i>System Info
                </h6>
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2"><i class="bi bi-box me-2"></i>Laravel {{ app()->version() }}</li>
                    <li class="mb-2"><i class="bi bi-code-slash me-2"></i>PHP {{ PHP_VERSION }}</li>
                    <li class="mb-2"><i class="bi bi-server me-2"></i>{{ php_uname('s') }}</li>
                    <li class="mb-0"><i class="bi bi-calendar me-2"></i>{{ now()->format('d M Y, H:i') }}</li>
                </ul>
            </div>

        </div>
    </div>
</form>

@endsection
