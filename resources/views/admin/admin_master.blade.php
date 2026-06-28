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
    <title>@yield('page_title', 'Dashboard') - {{ $company->name ?? 'Waafibook' }}</title>
    
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
                title:         'swal-title-branded',
                htmlContainer: 'swal-text-branded',
                footer:        'swal-footer-branded',
            },
        });

        /* Override global Swal so all calls use branding by default — including
           the legacy Swal.fire(title, text, icon) shorthand still used in many
           views, which a plain object-only patch would silently skip. */
        const _origFire = Swal.fire.bind(Swal);
        Swal.fire = function (...args) {
            let opts = null;
            if (args.length === 1 && typeof args[0] === 'object') {
                opts = args[0];
            } else if (typeof args[0] === 'string') {
                opts = { title: args[0], text: args[1], icon: args[2] };
            }

            if (opts) {
                if (!opts.confirmButtonColor) opts.confirmButtonColor = '#004161';
                if (!opts.cancelButtonColor)  opts.cancelButtonColor  = '#6b7280';
                if (!opts.denyButtonColor)    opts.denyButtonColor    = '#e11d48';
                if (!opts.customClass) {
                    opts.customClass = {
                        confirmButton: 'swal-btn-confirm', denyButton: 'swal-btn-deny',
                        cancelButton: 'swal-btn-cancel', popup: 'swal-popup-branded',
                        title: 'swal-title-branded', htmlContainer: 'swal-text-branded',
                        footer: 'swal-footer-branded',
                    };
                }
                return _origFire(opts);
            }
            return _origFire(...args);
        };

        /* ── ERP action alerts ───────────────────────────────────────────────────
           One branded template per business event (sale, purchase, customer,
           supplier, payment, warning/error). Every category uses the same
           navy/lime/rose brand palette — only the icon and footer copy change —
           so the whole app speaks with one visual voice instead of the
           per-category rainbow colors a generic SweetAlert demo would use.
         ─────────────────────────────────────────────────────────────────────── */
        const ERP_FOOTERS = {
            sale:     'Inventory levels have been updated automatically.',
            purchase: 'Stock and supplier balance have been updated.',
            customer: 'Customer account balance has been updated.',
            supplier: 'Supplier account balance has been updated.',
            payment:  'Account balances have been updated.',
            expense:  'Expense accounts have been updated.',
            delete:   'Deleted records are not recoverable.',
        };

        function erpSuccess(category, title, text) {
            Swal.fire({ icon: 'success', title, text, footer: ERP_FOOTERS[category] });
        }
        function erpDeleteConfirm(category, title, text, onConfirm) {
            Swal.fire({
                icon: 'warning', title, text,
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                footer: ERP_FOOTERS.delete,
            }).then(r => { if (r.isConfirmed && onConfirm) onConfirm(); });
        }

        window.ErpAlerts = {
            saleSaved:        () => erpSuccess('sale', 'Sale Recorded Successfully', 'The sale transaction has been saved to the system.'),
            saleUpdated:      () => erpSuccess('sale', 'Sale Updated', 'The sale record has been revised and saved.'),
            saleDeleted:      (onConfirm) => erpDeleteConfirm('sale', 'Delete This Sale?', 'This will permanently remove the sale record and reverse its effect on inventory.', onConfirm),

            purchaseSaved:    () => erpSuccess('purchase', 'Purchase Order Recorded', 'The purchase has been logged and stock levels updated accordingly.'),
            purchaseUpdated:  () => erpSuccess('purchase', 'Purchase Order Updated', 'Changes to the purchase record have been saved successfully.'),
            purchaseDeleted:  (onConfirm) => erpDeleteConfirm('purchase', 'Delete This Purchase?', 'Removing this purchase will reverse the stock additions associated with it.', onConfirm),

            customerSaved:    () => erpSuccess('customer', 'Customer Added', 'The new customer profile has been created and is ready for use.'),
            customerUpdated:  () => erpSuccess('customer', 'Customer Profile Updated', "The customer's details have been revised and saved."),
            customerDeleted:  (onConfirm) => erpDeleteConfirm('customer', 'Delete This Customer?', 'Sales history linked to this customer will remain, but the profile will be removed.', onConfirm),

            supplierSaved:    () => erpSuccess('supplier', 'Supplier Added', 'The supplier has been registered and is available for purchase orders.'),
            supplierUpdated:  () => erpSuccess('supplier', 'Supplier Profile Updated', "The supplier's information has been revised and saved."),
            supplierDeleted:  (onConfirm) => erpDeleteConfirm('supplier', 'Delete This Supplier?', 'Purchase history for this supplier will be retained, but the supplier profile will be removed.', onConfirm),

            paymentReceived:  () => erpSuccess('payment', 'Payment Received', 'The customer payment has been recorded and the account balance updated.'),
            paymentSent:      () => erpSuccess('payment', 'Payment Sent to Supplier', 'The outgoing payment has been recorded and the supplier balance adjusted.'),
            paymentDeleted:   (onConfirm) => erpDeleteConfirm('payment', 'Delete This Payment?', 'Removing this payment will reverse the balance adjustment on the associated account.', onConfirm),

            lowStock:         (text) => Swal.fire({ icon: 'warning', title: 'Low Stock Alert', text: text || 'One or more items have fallen below their minimum stock threshold.', footer: 'You can set reorder levels in Inventory Settings.' }),
            validationError:  (text) => Swal.fire({ icon: 'error', title: 'Required Fields Missing', text: text || 'Please review the form and correct the highlighted fields before submitting.', confirmButtonColor: '#e11d48' }),
            sessionExpired:   () => Swal.fire({ icon: 'info', title: 'Session Expired', text: 'Your session has timed out due to inactivity. Please log in again to continue.', allowOutsideClick: false, allowEscapeKey: false, confirmButtonText: 'Log In Again' }),
        };

        /* Best-effort category guess from a flashed message's wording, so every
           existing controller's session('success'/'error') flash — without any
           controller changes — still gets a relevant footer hint instead of a
           blank one. */
        function erpFooterFor(message) {
            const m = (message || '').toLowerCase();
            if (m.includes('delet')) return ERP_FOOTERS.delete;
            if (m.includes('invoice') || m.includes('sale')) return ERP_FOOTERS.sale;
            if (m.includes('purchase')) return ERP_FOOTERS.purchase;
            if (m.includes('customer')) return ERP_FOOTERS.customer;
            if (m.includes('supplier')) return ERP_FOOTERS.supplier;
            if (m.includes('payment') || m.includes('voucher') || m.includes('receipt')) return ERP_FOOTERS.payment;
            if (m.includes('expense')) return ERP_FOOTERS.expense;
            return undefined;
        }
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
                footer: erpFooterFor("{{ session('success') }}"),
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul class="text-left text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                confirmButtonColor: '#e11d48',
                footer: 'All fields marked with * are mandatory.',
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
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                footer: options.footer || ERP_FOOTERS.delete,
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
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#6b7280',
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
                                if (data.has_transactions && options.onBlocked) {
                                    return { blocked: true };
                                }
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
                    if (!result2.isConfirmed) return;

                    if (result2.value && result2.value.blocked) {
                        options.onBlocked();
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: label + ' has been deleted.',
                        confirmButtonColor: '#004161',
                        footer: options.footer || ERP_FOOTERS.delete,
                        customClass: popupClass
                    }).then(() => {
                        if (options.onSuccess) {
                            options.onSuccess();
                        } else {
                            window.location.reload();
                        }
                    });
                });
            });
        };

        // Customer/supplier delete flow: if the party already has transactions
        // against it, deleting would cascade-delete that financial history (see
        // the destroy() guard server-side), so offer deactivating it instead.
        // This rides the normal password-confirmed delete attempt itself rather
        // than a separate pre-check request, so there's only one URL to get
        // right (deleteRecordWithPassword's own, already battle-tested).
        window.showDeactivatePartyDialog = async function (deactivateUrl, name) {
            const popupClass = { popup: 'rounded-[1.5rem]' };
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const result = await Swal.fire({
                title: 'Delete Party',
                html: `
                    <div class="text-left">
                        <div style="background:#fdecea;color:#b71c1c;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.85rem;">
                            This party cannot be deleted as it is already used in transactions. Please delete all transactions before deleting the party.
                        </div>
                        <p style="font-size:0.85rem;margin-bottom:0.5rem;"><strong>To avoid deletion of all transactions</strong>, you can try turning off the active status for this party instead. If you deactivate this party:</p>
                        <ul style="font-size:0.85rem;padding-left:1.25rem;margin:0;text-align:left;">
                            <li>Move this party to the bottom of your list</li>
                            <li>All existing transactions will be retained, with no changes to your reports.</li>
                            <li>Remove this party from the billing list dropdown, but you will still be able to create bills for them if needed.</li>
                        </ul>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Deactivate',
                cancelButtonText: 'No, Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#9ca3af',
                customClass: popupClass
            });

            if (!result.isConfirmed) return;

            try {
                const res = await fetch(deactivateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                });
                if (!res.ok) throw new Error('Failed');
                await Swal.fire({
                    icon: 'success',
                    title: 'Deactivated',
                    text: name + ' has been deactivated.',
                    confirmButtonColor: '#004161',
                    customClass: popupClass
                });
                window.location.reload();
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Something went wrong',
                    text: 'Could not deactivate this party. Please try again.',
                    customClass: popupClass
                });
            }
        };
    </script>
</body>
</html>
