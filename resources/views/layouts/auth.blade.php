<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title', 'Sign In') — Lockmytimes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen antialiased" style="font-family:'Nunito Sans',sans-serif;">

<div class="min-h-screen flex">

    {{-- Left panel — brand --}}
    <div class="hidden lg:flex lg:w-1/2 lmt-gradient-bg flex-col justify-between p-12 relative overflow-hidden">

        {{-- Background decoration --}}
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 rounded-full bg-white/5 translate-y-1/2 -translate-x-1/2"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-white/3"></div>

        {{-- Logo --}}
        <div class="relative z-10">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center p-1.5">
                    @if(request()->routeIs('superadmin.*'))
                        <img src="{{ asset('images/white_logo.png') }}" alt="Lockmytimes" class="w-full h-full object-contain"/>
                    @else
                        <img src="{{ asset('images/white_logo.png') }}" alt="Lockmytimes" class="w-full h-full object-contain"/>
                    @endif
                </div>
                <span class="text-white text-xl font-bold" style="font-family:'Nunito',sans-serif">Lockmytimes</span>
            </a>
        </div>

        {{-- Center content --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center">
            @yield('auth-panel')
        </div>

        {{-- Bottom testimonial --}}
        <!-- <div class="relative z-10">
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/20">
                <div class="flex mb-3">
                    @for($i=0;$i<5;$i++)
                    <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                    @endfor
                </div>
                <p class="text-white/90 text-sm leading-relaxed italic mb-4">
                    "Lockmytimes transformed how we manage our 200-person team. The QR attendance alone saves us hours every week."
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center text-white text-xs font-bold">M</div>
                    <div>
                        <p class="text-white text-xs font-semibold">Maria G.</p>
                        <p class="text-white/60 text-xs">HR Director, Acme Corp</p>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    {{-- Right panel — form --}}
    <div class="flex-1 flex flex-col justify-center items-center p-6 lg:p-12 bg-white">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl lmt-gradient-bg flex items-center justify-center p-1.5">
                    @if(request()->routeIs('superadmin.*'))
                        <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-full h-full object-contain"/>
                    @else
                        <i data-lucide="clock" class="w-4 h-4 text-white"></i>
                    @endif
                </div>
                <span class="text-xl font-bold text-ink" style="font-family:'Nunito',sans-serif">Lockmytimes</span>
            </a>
        </div>

        <div class="w-full max-w-md animate-slide-up">
            @yield('form')
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-xs text-gray-800 text-center">
            © {{ date('Y') }} Lockmytimes ·
            <a href="#" class="hover:text-brand-500 transition-colors">Privacy</a> ·
            <a href="#" class="hover:text-brand-500 transition-colors">Terms</a>
        </p>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div class="fixed top-6 right-6 z-50 lmt-alert-success lmt-alert shadow-pop animate-slide-down max-w-sm">
    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="fixed top-6 right-6 z-50 lmt-alert-error lmt-alert shadow-pop animate-slide-down max-w-sm">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    {{ session('error') }}
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
        // Auto dismiss flash
        setTimeout(() => {
            document.querySelectorAll('[class*="lmt-alert"]').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 4000);
    });
</script>

@stack('scripts')
</body>
</html>