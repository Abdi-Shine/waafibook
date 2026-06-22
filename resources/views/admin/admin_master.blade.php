@php
    $company = auth()->check() ? \App\Models\Company::find(auth()->user()->company_id) : \App\Models\Company::first();
    $globalBackups = \App\Models\Backup::latest()->take(5)->get();

    // Low stock alerts: products whose total stock is below their min_stock_level
    $lowStockAlerts = collect();
    if (auth()->check()) {
        $lowStockAlerts = \App\Models\Product::withoutGlobalScopes()
            ->where('company_id', auth()->user()->company_id)
            ->whereNotNull('low_stock_threshold')
            ->where('low_stock_threshold', '>', 0)
            ->get()
            ->filter(function ($product) {
                $stock = \App\Models\ProductStock::withoutGlobalScopes()
                    ->where('product_id', $product->id)
                    ->sum('quantity');
                $product->current_stock = $stock;
                return $stock < $product->low_stock_threshold;
            })
            ->take(10);
    }

    \Illuminate\Support\Facades\View::share('company', $company);
    \Illuminate\Support\Facades\View::share('globalBackups', $globalBackups);
    \Illuminate\Support\Facades\View::share('lowStockAlerts', $lowStockAlerts);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Dashboard') - {{ $company->name ?? 'Horntech LTD' }}</title>
    
    <!-- Global CSS & JS (Vite) — Inter font is loaded via app.css -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        /* ── Global SweetAlert2 branding ───────────────────────────────────────
           Applied automatically to EVERY Swal.fire() in the project.
           Primary  = #004161  (navy)
           Accent   = #99CC33  (lime-green)  — confirm / positive actions
           Danger   = #e11d48  (rose)        — delete / destructive actions
         ─────────────────────────────────────────────────────────────────────── */
        const SwalBranded = Swal.mixin({
            confirmButtonColor: '#004161',   /* navy  – default confirm      */
            denyButtonColor:    '#e11d48',   /* rose  – deny/destructive     */
            cancelButtonColor:  '#6b7280',   /* gray  – cancel               */
            customClass: {
                confirmButton: 'swal-btn-confirm',
                denyButton:    'swal-btn-deny',
                cancelButton:  'swal-btn-cancel',
                popup:         'swal-popup-branded',
            },
        });

        /* Override global Swal so all calls use branding by default */
        const _origFire = Swal.fire.bind(Swal);
        Swal.fire = function (...args) {
            if (args.length === 1 && typeof args[0] === 'object') {
                const opts = args[0];
                if (!opts.confirmButtonColor) opts.confirmButtonColor = '#004161';
                if (!opts.cancelButtonColor)  opts.cancelButtonColor  = '#6b7280';
                if (!opts.denyButtonColor)    opts.denyButtonColor    = '#e11d48';
            }
            return _origFire(...args);
        };
    </script>
    @stack('css')
</head>

<body class="bg-background text-text-primary font-inter">

@auth
    @include('admin.body.sidebar')
    <!-- Mobile/Tablet sidebar backdrop -->
    <div id="sidebarBackdrop"
         class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden transition-opacity duration-300"
         onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden');"></div>
    @include('admin.body.header')
@endauth

<div class="main-content @auth lg:ml-[260px] @endauth min-h-screen transition-all duration-300">
    @yield('admin')
</div>

    @stack('scripts')
    <script>
        // Session Alerts Handler
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "{{ session('success') }}",
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul class="text-left text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: "{!! addslashes(session('error')) !!}",
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: "{!! addslashes(session('warning')) !!}",
            });
        @endif

        // Mobile / Tablet Layout Toggle
        const sidebar  = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
            });
        }

        // Shared admin-only + password-confirmed delete flow for every "delete record" action in the app.
        window.deleteRecordWithPassword = function (url, label, options = {}) {
            const popupClass = {
                popup: 'rounded-[1.5rem]',
                confirmButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest',
                cancelButton: 'rounded-[0.5rem] px-6 py-2 text-xs font-bold uppercase tracking-widest'
            };

            Swal.fire({
                title: options.title || ('Delete ' + label + '?'),
                text: options.text || ('Are you sure you want to delete ' + label + '? This action cannot be undone.'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#004161',
                cancelButtonColor: '#99CC33',
                confirmButtonText: 'Yes, delete it!',
                customClass: popupClass
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Confirm Your Password',
                    text: 'Enter your password to authorize this deletion.',
                    input: 'password',
                    inputPlaceholder: 'Password',
                    inputAttributes: { autocapitalize: 'off', autocomplete: 'current-password' },
                    showCancelButton: true,
                    confirmButtonColor: '#004161',
                    cancelButtonColor: '#99CC33',
                    confirmButtonText: 'Confirm & Delete',
                    customClass: popupClass,
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password is required.');
                            return false;
                        }
                        return fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ password })
                        }).then(async (response) => {
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                throw new Error(data.message || 'Something went wrong.');
                            }
                            return data;
                        }).catch((error) => {
                            Swal.showValidationMessage(error.message);
                            return false;
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result2) => {
                    if (result2.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: label + ' has been deleted.',
                            confirmButtonColor: '#004161',
                            customClass: popupClass
                        }).then(() => {
                            if (options.onSuccess) {
                                options.onSuccess();
                            } else {
                                window.location.reload();
                            }
                        });
                    }
                });
            });
        };
    </script>
</body>
</html>
