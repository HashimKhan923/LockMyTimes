{{-- =====================================================================
   Lockmytimes — Master Layout (app.blade.php)
   All global CSS/JS/assets load here. Pages just extend this layout.
====================================================================== --}}
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', 'Lockmytimes — All-in-One HR Platform')</title>

    <meta name="description" content="@yield('meta_description', 'Lockmytimes is a futuristic, all-in-one HR platform for modern US businesses. Attendance, Payroll, Performance, Assets and more.')" />

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    {{-- Tailwind CSS + custom design system --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Lucide icons (CDN) --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>

    @stack('head')
</head>

<body class="min-h-screen antialiased">

    {{-- ================== Flash Messages ================== --}}
    @if (session('success'))
        <div class="fixed top-6 right-6 z-[100] lmt-alert-success animate-slide-down">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="fixed top-6 right-6 z-[100] lmt-alert-error animate-slide-down">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- ================== Page Content ================== --}}
    @yield('content')

    {{-- ================== Footer Scripts ================== --}}
    <script>
        // Initialize Lucide icons whenever DOM is ready or updated
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>

    @stack('scripts')
</body>
</html>