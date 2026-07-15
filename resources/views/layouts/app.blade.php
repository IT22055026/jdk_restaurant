<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Restaurant BYOB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f8fb;
        }

        /* Sidebar */
        .sidebar {
            background: #fff;
            border-right: 1px solid #eef1f6;
        }
        .active-nav {
            font-weight: 600;
        }
        .sidebar-brand-card {
            background: #f8fafc;
            border: 1px solid #eef1f6;
        }
        .main-content { margin-left: 256px; }
        @media (max-width: 1024px) {
            .main-content { margin-left: 0; }
            .sidebar.mobile-open {
                display: flex !important;
                z-index: 60;
                box-shadow: 8px 0 24px rgba(15,23,42,0.15);
            }
        }
    </style>
    @include('layouts.dark-mode')
    @yield('styles')
</head>
<body>
    <!-- Navbar -->
    @include('layouts.navbar')

    <div class="flex" style="padding-top:67px;">
        <!-- Sidebar -->
        <x-sidebar :modules="$modules ?? []" />

        <!-- Main Content -->
        <div class="flex-1 main-content px-6 py-8 w-full lg:w-auto">
            @if(session('low_stock_warning'))
                <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg px-5 py-3 flex items-center gap-3">
                    <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0"></i>
                    <span class="text-sm font-medium">{{ session('low_stock_warning') }}</span>
                    <a href="{{ route('inventory.index') }}" class="ml-auto text-amber-700 hover:text-amber-900 text-sm font-semibold underline whitespace-nowrap">View Inventory</a>
                </div>
            @endif
            {{-- Breadcrumb slot: pages can define a `breadcrumb` section to render here. --}}
            @hasSection('breadcrumb')
                <div class="mb-4">
                    @yield('breadcrumb')
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @yield('scripts')
    <script>
    document.addEventListener('click', function (e) {
        var sb = document.querySelector('.sidebar.mobile-open');
        if (sb && !e.target.closest('.sidebar') && !e.target.closest('.navbar-hamburger')) {
            sb.classList.remove('mobile-open');
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        var CTRL_KEYS  = [8, 9, 13, 27, 46, 35, 36, 37, 38, 39, 40]; // backspace,tab,enter,esc,del,home,end,arrows
        var DIGITS     = function (k) { return (k >= 48 && k <= 57) || (k >= 96 && k <= 105); };
        var SHORTCUT   = function (e) { return (e.ctrlKey || e.metaKey) && [65, 67, 86, 88, 90].includes(e.keyCode); };

        // Amounts / quantities: digits + decimal + minus allowed
        document.querySelectorAll('input[type="number"]').forEach(function (input) {
            input.addEventListener('keydown', function (e) {
                if (CTRL_KEYS.includes(e.keyCode) || SHORTCUT(e) || DIGITS(e.keyCode)) return;
                if (e.keyCode === 190 || e.keyCode === 110) return; // decimal point
                if (e.keyCode === 189 || e.keyCode === 109) return; // minus
                e.preventDefault();
            });
        });

        // Phone fields (type="tel") and any field marked data-numeric: digits only
        document.querySelectorAll('input[type="tel"], input[data-numeric]').forEach(function (input) {
            input.addEventListener('keydown', function (e) {
                if (CTRL_KEYS.includes(e.keyCode) || SHORTCUT(e) || DIGITS(e.keyCode)) return;
                e.preventDefault();
            });
        });
    });
    </script>
</body>
</html>
