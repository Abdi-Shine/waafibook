@php
    $company = auth()->check() ? \App\Models\Company::find(auth()->user()->company_id) : \App\Models\Company::first();
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
        const SwalBranded = Swal.mixin({
            confirmButtonColor: '#004161',
            denyButtonColor:    '#e11d48',
            cancelButtonColor:  '#6b7280',
            customClass: {
                confirmButton: 'swal-btn-confirm',
                denyButton:    'swal-btn-deny',
                cancelButton:  'swal-btn-cancel',
                popup:         'swal-popup-branded',
            },
        });

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

<div class="min-h-screen">
    @yield('admin')
</div>

@stack('scripts')
<script>
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Success!', text: "{{ session('success') }}" });
    @endif

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: '<ul class="text-left text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
        });
    @endif

    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Error!', text: "{!! addslashes(session('error')) !!}" });
    @endif

    @if(session('warning'))
        Swal.fire({ icon: 'warning', title: 'Warning', text: "{!! addslashes(session('warning')) !!}" });
    @endif
</script>
</body>
</html>
