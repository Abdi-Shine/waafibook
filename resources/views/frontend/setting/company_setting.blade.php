@extends('admin.admin_master')
@section('page_title', 'Company Settings')

@section('admin')
<div class="px-4 py-8 md:px-8 md:py-10 bg-background min-h-screen font-inter">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div>
            <h1 class="text-[20px] font-bold text-primary-dark">Company Settings</h1>
            <p class="text-[13px] text-gray-400 font-medium mt-0.5">Manage company information and system preferences</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="location.reload()"
                class="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 font-semibold rounded-[0.5rem] hover:bg-gray-50 transition-all text-sm shadow-sm">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset</span>
            </button>
            <button type="button" onclick="document.getElementById('globalSettingsForm').submit()"
                class="btn-premium-primary group normal-case">
                <i class="bi bi-check2-circle"></i>
                <span>Save All Changes</span>
            </button>
        </div>
    </div>

    <form id="globalSettingsForm" action="{{ route('company.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Tab Bar -->
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm mb-5 overflow-hidden">
            <div class="flex overflow-x-auto custom-scrollbar border-b border-gray-100">
                <button type="button" onclick="switchTab('company', this)"
                    class="tab-btn active flex items-center gap-2 px-5 py-4 text-[12px] font-bold whitespace-nowrap border-b-2 border-primary text-primary transition-all">
                    <i class="bi bi-building"></i> Company Identity
                </button>
                <button type="button" onclick="switchTab('financial', this)"
                    class="tab-btn flex items-center gap-2 px-5 py-4 text-[12px] font-bold whitespace-nowrap border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all">
                    <i class="bi bi-currency-dollar"></i> Financial Settings
                </button>
                <button type="button" onclick="switchTab('operational', this)"
                    class="tab-btn flex items-center gap-2 px-5 py-4 text-[12px] font-bold whitespace-nowrap border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all">
                    <i class="bi bi-boxes"></i> Operational Logic
                </button>
                <button type="button" onclick="switchTab('system', this)"
                    class="tab-btn flex items-center gap-2 px-5 py-4 text-[12px] font-bold whitespace-nowrap border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-all">
                    <i class="bi bi-shield-lock"></i> System Core
                </button>
            </div>
        </div>

        <!-- ── TAB 1: COMPANY IDENTITY ── -->
        <div id="company-content" class="tab-content space-y-5">

            <!-- Logo -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-image text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Company Branding</h2>
                </div>
                <div class="p-6 flex flex-col sm:flex-row items-center gap-6">
                    <div class="relative group flex-shrink-0">
                        <div class="w-32 h-32 bg-background border-2 border-dashed border-gray-200 rounded-[0.8rem] flex items-center justify-center overflow-hidden hover:border-primary/40 transition-colors cursor-pointer"
                             onclick="document.getElementById('logoInput').click()">
                            <img src="{{ !empty($company->logo) ? asset($company->logo) : asset('upload/no_image.jpg') }}"
                                 id="logoPreview" class="w-full h-full object-contain p-3">
                        </div>
                        <button type="button" onclick="document.getElementById('logoInput').click()"
                            class="absolute -top-2 -right-2 w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="bi bi-pencil text-xs"></i>
                        </button>
                        <input type="file" name="logo" id="logoInput" class="hidden" onchange="previewLogo(this)" accept="image/*">
                    </div>
                    <div class="flex-1">
                        <h3 class="text-[13px] font-black text-primary-dark mb-1">Company Logo</h3>
                        <p class="text-[12px] text-gray-400 mb-4">Used on invoices, reports, and the system header.</p>
                        <div class="flex flex-wrap gap-3 mb-4">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-500">
                                <i class="bi bi-check-circle-fill text-accent text-xs"></i> Square recommended
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-500">
                                <i class="bi bi-check-circle-fill text-accent text-xs"></i> PNG or SVG preferred
                            </span>
                        </div>
                        <button type="button" onclick="document.getElementById('logoInput').click()"
                            class="flex items-center gap-2 px-4 py-2 bg-accent/10 text-primary border border-accent/20 font-bold rounded-[0.5rem] text-[11px] uppercase tracking-wider hover:bg-accent/20 transition-all">
                            <i class="bi bi-upload text-xs"></i> Change Logo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Legal Identity -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-info-circle text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Legal Identity</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Business Name <span class="text-primary">*</span></label>
                        <input type="text" name="name" value="{{ $company->name ?? '' }}" required placeholder="Official company name"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Registration Number</label>
                        <input type="text" name="registration_number" value="{{ $company->registration_number ?? '' }}" placeholder="e.g. CR-12345"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Primary Email <span class="text-primary">*</span></label>
                        <input type="email" name="email" value="{{ $company->email ?? '' }}" required placeholder="company@example.com"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Phone <span class="text-primary">*</span></label>
                        <input type="tel" name="phone" value="{{ $company->phone ?? '' }}" required placeholder="+252 6X XXX XXXX"
                            inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Website</label>
                        <input type="url" name="website" value="{{ $company->website ?? '' }}" placeholder="https://example.com"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Industry Sector</label>
                        <div class="relative">
                            <select name="industry" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                <option value="Trading"       {{ ($company->industry ?? '') == 'Trading'       ? 'selected' : '' }}>Trading & Retail</option>
                                <option value="Manufacturing" {{ ($company->industry ?? '') == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                <option value="Technology"    {{ ($company->industry ?? '') == 'Technology'    ? 'selected' : '' }}>Technology & Software</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Physical Address -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-geo-alt text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Physical Address</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Street Address</label>
                        <input type="text" name="address" value="{{ $company->address ?? '' }}" placeholder="Enter street address"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">City</label>
                            <input type="text" name="city" value="{{ $company->city ?? '' }}" placeholder="City"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ $company->postal_code ?? '' }}" placeholder="00000"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Country</label>
                            <div class="relative">
                                <select name="country" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                    <option value="Somalia"      {{ ($company->country ?? '') == 'Somalia'      ? 'selected' : '' }}>Somalia</option>
                                    <option value="Saudi Arabia" {{ ($company->country ?? '') == 'Saudi Arabia' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="Kenya"        {{ ($company->country ?? '') == 'Kenya'        ? 'selected' : '' }}>Kenya</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB 2: FINANCIAL SETTINGS ── -->
        <div id="financial-content" class="tab-content space-y-5" style="display:none">
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-currency-dollar text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Currency & Fiscal Year</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Base Currency</label>
                        <div class="relative">
                            <select name="base_currency" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                <option value="USD" {{ ($company->base_currency ?? '') == 'USD' ? 'selected' : '' }}>USD ($) — US Dollar</option>
                                <option value="SOS" {{ ($company->base_currency ?? '') == 'SOS' ? 'selected' : '' }}>SOS (Sh) — Somali Shilling</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Fiscal Year Start</label>
                        <input type="text" name="fiscal_year_start" value="{{ $company->fiscal_year_start ?? '01-01' }}" placeholder="MM-DD"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-center">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Fiscal Year End</label>
                        <input type="text" name="fiscal_year_end" value="{{ $company->fiscal_year_end ?? '12-31' }}" placeholder="MM-DD"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all text-center">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB 3: OPERATIONAL LOGIC ── -->
        <div id="operational-content" class="tab-content space-y-5" style="display:none">

            <!-- Inventory -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-boxes text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Inventory Behavior</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center justify-between p-4 bg-background/60 border border-gray-100 rounded-[0.6rem]">
                            <div>
                                <p class="text-[12px] font-black text-primary-dark">Allow Negative Stock Sales</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Sell items even when stock is zero</p>
                            </div>
                            <label class="premium-switch flex-shrink-0">
                                <input type="checkbox" name="allow_negative_inventory" value="1" {{ ($company->allow_negative_inventory ?? false) ? 'checked' : '' }}>
                                <span class="premium-slider"></span>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-background/60 border border-gray-100 rounded-[0.6rem]">
                            <div>
                                <p class="text-[12px] font-black text-primary-dark">Track Expiry Dates</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Enable expiry input for perishables</p>
                            </div>
                            <label class="premium-switch flex-shrink-0">
                                <input type="checkbox" name="track_expiry" value="1" {{ ($company->track_expiry ?? true) ? 'checked' : '' }}>
                                <span class="premium-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Costing Method <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="costing_method" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                    <option value="FIFO" {{ ($company->costing_method ?? '') == 'FIFO' ? 'selected' : '' }}>FIFO — First In, First Out</option>
                                    <option value="LIFO" {{ ($company->costing_method ?? '') == 'LIFO' ? 'selected' : '' }}>LIFO — Last In, First Out</option>
                                    <option value="AVCO" {{ ($company->costing_method ?? '') == 'AVCO' ? 'selected' : '' }}>AVCO — Average Cost</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Default Unit of Measure <span class="text-primary">*</span></label>
                            <div class="relative">
                                <select name="default_uom_id" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                    <option value="1"  {{ ($company->default_uom_id ?? '') == '1'  ? 'selected' : '' }}>Pieces (Pcs)</option>
                                    <option value="2"  {{ ($company->default_uom_id ?? '') == '2'  ? 'selected' : '' }}>Kilograms (Kg)</option>
                                    <option value="3"  {{ ($company->default_uom_id ?? '') == '3'  ? 'selected' : '' }}>Grams (g)</option>
                                    <option value="4"  {{ ($company->default_uom_id ?? '') == '4'  ? 'selected' : '' }}>Liters (L)</option>
                                    <option value="5"  {{ ($company->default_uom_id ?? '') == '5'  ? 'selected' : '' }}>Milliliters (ml)</option>
                                    <option value="6"  {{ ($company->default_uom_id ?? '') == '6'  ? 'selected' : '' }}>Box</option>
                                    <option value="7"  {{ ($company->default_uom_id ?? '') == '7'  ? 'selected' : '' }}>Carton</option>
                                    <option value="8"  {{ ($company->default_uom_id ?? '') == '8'  ? 'selected' : '' }}>Dozen</option>
                                    <option value="9"  {{ ($company->default_uom_id ?? '') == '9'  ? 'selected' : '' }}>Pack</option>
                                    <option value="10" {{ ($company->default_uom_id ?? '') == '10' ? 'selected' : '' }}>Pair</option>
                                    <option value="11" {{ ($company->default_uom_id ?? '') == '11' ? 'selected' : '' }}>Roll</option>
                                    <option value="12" {{ ($company->default_uom_id ?? '') == '12' ? 'selected' : '' }}>Ton</option>
                                    <option value="13" {{ ($company->default_uom_id ?? '') == '13' ? 'selected' : '' }}>Meter (m)</option>
                                    <option value="14" {{ ($company->default_uom_id ?? '') == '14' ? 'selected' : '' }}>Centimeter (cm)</option>
                                </select>
                                <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barcode -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-upc-scan text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Barcode System</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Barcode Type</label>
                        <div class="relative">
                            <select name="barcode_type" class="w-full pl-4 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none">
                                <option value="CODE128">Code-128 (Standard)</option>
                                <option value="EAN13">EAN-13 (Retail)</option>
                                <option value="UPC">UPC (Universal)</option>
                                <option value="QR">QR Code</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-background/60 border border-gray-100 rounded-[0.6rem]">
                        <div>
                            <p class="text-[12px] font-black text-primary-dark">Enable Barcode System</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Activate barcode scanning & printing</p>
                        </div>
                        <label class="premium-switch flex-shrink-0">
                            <input type="checkbox" name="enable_barcode" value="1">
                            <span class="premium-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- POS & Sales -->
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-phone text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">POS & Sales Interface</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Max Discount (%)</label>
                        <input type="number" name="max_discount_percent" value="{{ $company->max_discount_percent ?? '0' }}" min="0" max="100"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-[13px] font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                    </div>
                    <div class="flex items-center justify-between p-4 bg-background/60 border border-gray-100 rounded-[0.6rem]">
                        <div>
                            <p class="text-[12px] font-black text-primary-dark">Enable Global Rounding</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Round invoice totals automatically</p>
                        </div>
                        <label class="premium-switch flex-shrink-0">
                            <input type="checkbox" name="round_invoice" value="1" {{ ($company->round_invoice ?? false) ? 'checked' : '' }}>
                            <span class="premium-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB 4: SYSTEM CORE ── -->
        <div id="system-content" class="tab-content space-y-5" style="display:none">
            <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-background/50 flex items-center gap-2">
                    <i class="bi bi-shield-lock text-primary-dark text-sm"></i>
                    <h2 class="text-xs font-bold text-primary-dark uppercase tracking-wider">Security & Infrastructure</h2>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex flex-col items-center text-center p-4 bg-background/60 border border-gray-100 rounded-[0.6rem] gap-3">
                        <p class="text-[11px] font-black text-primary-dark uppercase tracking-wider">2FA Enforcement</p>
                        <label class="premium-switch">
                            <input type="checkbox" name="force_2fa" value="1" {{ ($company->force_2fa ?? false) ? 'checked' : '' }}>
                            <span class="premium-slider"></span>
                        </label>
                        <p class="text-[10px] text-gray-400">Require two-factor auth</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-4 bg-background/60 border border-gray-100 rounded-[0.6rem] gap-3">
                        <p class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Log Overrides</p>
                        <label class="premium-switch">
                            <input type="checkbox" name="log_overrides" value="1" checked>
                            <span class="premium-slider"></span>
                        </label>
                        <p class="text-[10px] text-gray-400">Track manual data changes</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-4 bg-background/60 border border-gray-100 rounded-[0.6rem] gap-3">
                        <p class="text-[11px] font-black text-primary-dark uppercase tracking-wider">Maintenance Mode</p>
                        <label class="premium-switch">
                            <input type="checkbox" name="maintenance_mode" value="1">
                            <span class="premium-slider"></span>
                        </label>
                        <p class="text-[10px] text-gray-400">Take system offline</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-4 bg-background/60 border border-gray-100 rounded-[0.6rem] gap-3">
                        <p class="text-[11px] font-black text-primary-dark uppercase tracking-wider">API Access</p>
                        <label class="premium-switch">
                            <input type="checkbox" name="enable_api" value="1">
                            <span class="premium-slider"></span>
                        </label>
                        <p class="text-[10px] text-gray-400">Allow external API calls</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Footer -->
        <div class="bg-white rounded-[1rem] border border-gray-100 shadow-sm p-5 mt-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <button type="button" onclick="location.reload()"
                class="flex items-center gap-2 px-5 py-2.5 bg-accent/10 text-primary border border-accent/30 font-bold rounded-[0.5rem] hover:bg-accent/20 transition-all text-sm">
                <i class="bi bi-x-circle"></i>
                <span>Discard Changes</span>
            </button>
            <button type="submit" class="btn-premium-primary group normal-case">
                <i class="bi bi-check2-circle"></i>
                <span>Save All Changes</span>
            </button>
        </div>

    </form>
</div>

<style>
    .tab-btn.active {
        color: #004161;
        border-bottom-color: #004161;
    }
    .premium-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
        cursor: pointer;
    }
    .premium-switch input { opacity: 0; width: 0; height: 0; }
    .premium-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #e2e8f0;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 50px;
        border: 1px solid #cbd5e1;
    }
    .premium-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    input:checked + .premium-slider { background-color: #99CC33; border-color: #88bb22; }
    input:checked + .premium-slider:before { transform: translateX(24px); }
</style>

<script>
    function switchTab(tabName, el) {
        // Hide all panes
        var panes = document.querySelectorAll('.tab-content');
        for (var i = 0; i < panes.length; i++) {
            panes[i].style.display = 'none';
        }
        // Show selected pane
        document.getElementById(tabName + '-content').style.display = 'block';

        // Reset all tab buttons
        var btns = document.querySelectorAll('.tab-btn');
        for (var j = 0; j < btns.length; j++) {
            btns[j].classList.remove('active');
            btns[j].style.color = '';
            btns[j].style.borderBottomColor = '';
        }
        // Activate clicked button
        el.classList.add('active');
    }

    function previewLogo(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('logoPreview').src = e.target.result; };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

@endsection
