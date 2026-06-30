<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lockmytimes — All-in-One HR Platform for Modern Teams')</title>
    <meta name="description" content="@yield('meta_description', 'QR-based attendance, payroll, performance, projects & more — all in one beautifully designed HR platform built for US businesses.')" />

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet" />

    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Public site overrides — use Syne for headings */
        body { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3,h4,h5,h6, .font-display { font-family: 'Syne', sans-serif !important; }
    </style>

    @stack('head')
</head>
<body class="bg-white text-ink antialiased overflow-x-hidden">

    @yield('content')

    {{-- Flash Messages --}}
    @if(session('success'))
    <div id="flash-msg" class="fixed bottom-6 right-6 z-[200] lmt-alert-success animate-slide-up shadow-pop">
        <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div id="flash-msg" class="fixed bottom-6 right-6 z-[200] lmt-alert-error animate-slide-up shadow-pop">
        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            // Auto-dismiss flash messages
            const flash = document.getElementById('flash-msg');
            if (flash) setTimeout(() => flash.remove(), 5000);
        });
    </script>

    @stack('scripts')
</body>
</html>