@extends('layouts.public')

@section('title', 'Lockmytimes — All-in-One HR Platform for Modern US Teams')

@section('content')

{{-- ================================================================
     NAVIGATION
================================================================ --}}
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 40">
    <div :class="scrolled ? 'bg-white/90 backdrop-blur-xl shadow-soft border-b border-gray-100' : 'bg-transparent'" class="transition-all duration-300">
        <div class="lmt-container flex items-center justify-between h-16 px-6">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-8 h-8 object-contain group-hover:scale-110 transition-transform"/>
                <span class="text-xl font-bold text-ink" style="font-family:'Syne',sans-serif">Lockmytimes</span>
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="#features"  class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Features</a>
                <a href="#how"       class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">How It Works</a>
                <a href="#pricing"   class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Pricing</a>
                <a href="#faq"       class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">FAQ</a>
            </div>

            {{-- CTA --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="{{ url('/superadmin') }}" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Sign In</a>
                <a href="#pricing" class="lmt-btn-primary lmt-btn-sm">Start Free Trial</a>
            </div>

            {{-- Hamburger --}}
            <button @click="open=!open" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="menu" class="w-5 h-5" x-show="!open"></i>
                <i data-lucide="x"    class="w-5 h-5" x-show="open" x-cloak></i>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="open" x-cloak class="md:hidden bg-white border-t border-gray-100 px-6 py-4 flex flex-col gap-4">
            <a href="#features" @click="open=false" class="text-sm font-medium text-ink-soft">Features</a>
            <a href="#how"      @click="open=false" class="text-sm font-medium text-ink-soft">How It Works</a>
            <a href="#pricing"  @click="open=false" class="text-sm font-medium text-ink-soft">Pricing</a>
            <a href="#faq"      @click="open=false" class="text-sm font-medium text-ink-soft">FAQ</a>
            <a href="#pricing"  class="lmt-btn-primary w-full text-center">Start Free Trial</a>
        </div>
    </div>
</nav>

{{-- ================================================================
     HERO
================================================================ --}}
<section class="relative min-h-screen flex items-center overflow-hidden lmt-mesh-bg pt-16">

    {{-- Animated background blobs --}}
    <div class="absolute top-20 right-10 w-96 h-96 rounded-full bg-brand-500/10 blur-3xl animate-float"></div>
    <div class="absolute bottom-20 left-10 w-72 h-72 rounded-full bg-accent-500/10 blur-3xl animate-float" style="animation-delay:2s"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-brand-500/5 blur-3xl"></div>

    <div class="lmt-container px-6 py-20 relative z-10">
        <div class="max-w-4xl mx-auto text-center">

            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 bg-brand-50 border border-brand-200 text-brand-700 text-xs font-semibold px-4 py-2 rounded-full mb-8 animate-fade-in">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse-slow"></span>
                Built for the US market · FLSA · FMLA · I-9 Ready
            </div>

            {{-- Headline --}}
            <h1 class="text-5xl md:text-7xl font-black text-ink leading-[1.05] tracking-tight mb-6" data-lmt-anim="fade-up">
                HR that actually
                <span class="lmt-gradient-text block">works for you</span>
            </h1>

            {{-- Sub --}}
            <p class="text-xl md:text-2xl text-ink-soft font-light max-w-2xl mx-auto mb-10 leading-relaxed" data-lmt-anim="fade-up" data-lmt-delay="0.1">
                QR-based attendance, smart payroll, performance reviews, project tracking, loans & more — one platform, every team.
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16" data-lmt-anim="fade-up" data-lmt-delay="0.2">
                <a href="#pricing" class="lmt-btn-primary lmt-btn-lg gap-3 shadow-glow">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    Start 14-Day Free Trial
                </a>
                <a href="#how" class="lmt-btn-secondary lmt-btn-lg gap-3">
                    <i data-lucide="play-circle" class="w-5 h-5"></i>
                    See How It Works
                </a>
            </div>

            {{-- Social proof --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 text-sm text-ink-soft" data-lmt-anim="fade-up" data-lmt-delay="0.3">
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2">
                        @foreach(['A','B','C','D','E'] as $l)
                        <div class="w-7 h-7 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-[10px] font-bold border-2 border-white">{{ $l }}</div>
                        @endforeach
                    </div>
                    <span>500+ companies trust Lockmytimes</span>
                </div>
                <span class="hidden sm:block text-gray-300">·</span>
                <div class="flex items-center gap-1">
                    @for($i=0;$i<5;$i++)
                    <i data-lucide="star" class="w-4 h-4 fill-accent-500 text-accent-500"></i>
                    @endfor
                    <span class="ml-1">4.9/5 rating</span>
                </div>
                <span class="hidden sm:block text-gray-300">·</span>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                    <span>SOC 2 compliant</span>
                </div>
            </div>
        </div>

        {{-- Dashboard Preview --}}
        <div class="mt-20 relative max-w-5xl mx-auto" data-lmt-anim="zoom" data-lmt-delay="0.4">
            <div class="absolute inset-0 lmt-gradient-bg opacity-20 blur-3xl rounded-3xl scale-95"></div>
            <div class="relative bg-white rounded-2xl border border-gray-200 shadow-pop overflow-hidden">

                {{-- Fake browser bar --}}
                <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    <div class="flex-1 mx-4 bg-white border border-gray-200 rounded-full px-3 py-1 text-xs text-ink-soft text-center">
                        app.lockmytimes.com/t/acme-corp/admin
                    </div>
                </div>

                {{-- Fake Dashboard --}}
                <div class="p-6 bg-surface-alt">
                    {{-- Stat row --}}
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        @php
                        $stats = [
                            ['label'=>'Total Employees','value'=>'248','icon'=>'users','color'=>'bg-brand-50 text-brand-600','delta'=>'+12%'],
                            ['label'=>'Present Today',  'value'=>'201','icon'=>'user-check','color'=>'bg-emerald-50 text-emerald-600','delta'=>'81%'],
                            ['label'=>'Pending Leaves', 'value'=>'14', 'icon'=>'calendar-off','color'=>'bg-amber-50 text-amber-600','delta'=>'3 urgent'],
                            ['label'=>'Payroll Due',    'value'=>'$84K','icon'=>'dollar-sign','color'=>'bg-purple-50 text-purple-600','delta'=>'In 3 days'],
                        ];
                        @endphp
                        @foreach($stats as $s)
                        <div class="bg-white rounded-xl p-4 border border-gray-100">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-xs font-medium text-ink-soft">{{ $s['label'] }}</span>
                                <div class="w-8 h-8 rounded-lg {{ $s['color'] }} flex items-center justify-center">
                                    <i data-lucide="{{ $s['icon'] }}" class="w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-ink mb-1" style="font-family:'Syne',sans-serif">{{ $s['value'] }}</div>
                            <div class="text-xs text-emerald-600 font-medium">{{ $s['delta'] }}</div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Two columns --}}
                    <div class="grid grid-cols-3 gap-4">
                        {{-- Attendance chart placeholder --}}
                        <div class="col-span-2 bg-white rounded-xl p-4 border border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-semibold text-ink">Attendance Overview</span>
                                <span class="lmt-badge-brand text-xs">This Week</span>
                            </div>
                            <div class="flex items-end gap-2 h-20">
                                @foreach([75,88,92,78,95,82,70] as $h)
                                <div class="flex-1 rounded-t-md lmt-gradient-bg opacity-80 transition-all hover:opacity-100"
                                     style="height:{{ $h }}%"></div>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-2">
                                @foreach(['M','T','W','T','F','S','S'] as $d)
                                <span class="flex-1 text-center text-[10px] text-ink-soft">{{ $d }}</span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Recent activity --}}
                        <div class="bg-white rounded-xl p-4 border border-gray-100">
                            <span class="text-sm font-semibold text-ink block mb-3">Recent Activity</span>
                            <div class="space-y-2.5">
                                @foreach([
                                    ['Sarah K. clocked in','2m ago','user-check','emerald'],
                                    ['Leave approved','5m ago','calendar-check','brand'],
                                    ['Payslip generated','1h ago','file-text','purple'],
                                    ['New hire added','2h ago','user-plus','amber'],
                                ] as $a)
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-{{ $a[3] }}-50 text-{{ $a[3] }}-600 flex items-center justify-center shrink-0">
                                        <i data-lucide="{{ $a[2] }}" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-ink truncate">{{ $a[0] }}</p>
                                        <p class="text-[10px] text-ink-soft">{{ $a[1] }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
     LOGOS / TRUSTED BY
================================================================ --}}
<section class="py-14 border-y border-gray-100 bg-white overflow-hidden">
    <div class="lmt-container px-6">
        <p class="text-center text-xs font-semibold text-ink-soft uppercase tracking-widest mb-8">Trusted by teams at</p>
        <div class="flex items-center justify-center flex-wrap gap-x-12 gap-y-4 opacity-40">
            @foreach(['Acme Corp','TechStack Inc','BuildRight LLC','Nova Health','FastFreight','UrbanRetail','CloudNine','PrimeCare'] as $co)
            <span class="text-sm font-bold text-ink-soft tracking-wide" style="font-family:'Syne',sans-serif">{{ $co }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     FEATURES
================================================================ --}}
<section id="features" class="lmt-section lmt-mesh-bg">
    <div class="lmt-container px-6">

        <div class="text-center mb-16" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Everything in one place</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">
                Every HR tool your team needs
            </h2>
            <p class="text-lg text-ink-soft max-w-2xl mx-auto">
                Stop juggling five different apps. Lockmytimes brings every HR workflow into one clean, connected platform.
            </p>
        </div>

        {{-- Feature grid --}}
        @php
        $features = [
            [
                'icon'=>'qr-code','color'=>'brand',
                'title'=>'QR Clock-In / Clock-Out',
                'desc'=>'Geo-fenced QR codes ensure employees can only clock in from the right location. Anti-spoofing with optional selfie capture.',
                'badge'=>'Flagship Feature',
            ],
            [
                'icon'=>'dollar-sign','color'=>'emerald',
                'title'=>'Automated Payroll',
                'desc'=>'Calculate wages, overtime (FLSA-compliant), taxes, 401(k), and generate branded payslips — all with one click.',
                'badge'=>null,
            ],
            [
                'icon'=>'kanban','color'=>'purple',
                'title'=>'Project & Task Boards',
                'desc'=>'Kanban boards, Gantt-ready timelines, time tracking, and billable hours — project management baked into HR.',
                'badge'=>'Popular',
            ],
            [
                'icon'=>'trending-up','color'=>'amber',
                'title'=>'Performance Reviews',
                'desc'=>'360° reviews, OKRs, 1-on-1 meeting tracker, peer kudos, and PIPs — build a high-performance culture.',
                'badge'=>null,
            ],
            [
                'icon'=>'briefcase','color'=>'rose',
                'title'=>'Recruitment / ATS',
                'desc'=>'Post jobs, manage candidates through a visual pipeline, schedule interviews, and onboard hires automatically.',
                'badge'=>null,
            ],
            [
                'icon'=>'file-signature','color'=>'teal',
                'title'=>'Document Management',
                'desc'=>'Central repository for policies, contracts, and forms with built-in e-signature and expiry tracking.',
                'badge'=>null,
            ],
            [
                'icon'=>'piggy-bank','color'=>'orange',
                'title'=>'Loans & Advances',
                'desc'=>'Employees can apply for salary advances or loans. Auto-generate EMI schedules and deduct via payroll.',
                'badge'=>null,
            ],
            [
                'icon'=>'package','color'=>'cyan',
                'title'=>'Asset Management',
                'desc'=>'Track every laptop, phone, and access card. QR-tag assets and auto-reclaim them on offboarding.',
                'badge'=>null,
            ],
            [
                'icon'=>'graduation-cap','color'=>'indigo',
                'title'=>'Training & LMS',
                'desc'=>'Assign mandatory trainings, track completions, and auto-generate certificates with your company branding.',
                'badge'=>null,
            ],
        ];
        $colorMap = [
            'brand'=>['bg'=>'bg-brand-50','text'=>'text-brand-600','ring'=>'ring-brand-200'],
            'emerald'=>['bg'=>'bg-emerald-50','text'=>'text-emerald-600','ring'=>'ring-emerald-200'],
            'purple'=>['bg'=>'bg-purple-50','text'=>'text-purple-600','ring'=>'ring-purple-200'],
            'amber'=>['bg'=>'bg-amber-50','text'=>'text-amber-600','ring'=>'ring-amber-200'],
            'rose'=>['bg'=>'bg-rose-50','text'=>'text-rose-600','ring'=>'ring-rose-200'],
            'teal'=>['bg'=>'bg-teal-50','text'=>'text-teal-600','ring'=>'ring-teal-200'],
            'orange'=>['bg'=>'bg-orange-50','text'=>'text-orange-600','ring'=>'ring-orange-200'],
            'cyan'=>['bg'=>'bg-cyan-50','text'=>'text-cyan-600','ring'=>'ring-cyan-200'],
            'indigo'=>['bg'=>'bg-indigo-50','text'=>'text-indigo-600','ring'=>'ring-indigo-200'],
        ];
        @endphp

        <div class="grid md:grid-cols-3 gap-6">
            @foreach($features as $i => $f)
            @php $c = $colorMap[$f['color']]; @endphp
            <div class="lmt-card-hover group" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.05 }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center ring-1 {{ $c['ring'] }} group-hover:scale-110 transition-transform">
                        <i data-lucide="{{ $f['icon'] }}" class="w-5 h-5"></i>
                    </div>
                    @if($f['badge'])
                    <span class="lmt-badge-brand text-xs">{{ $f['badge'] }}</span>
                    @endif
                </div>
                <h3 class="text-base font-bold text-ink mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-ink-soft leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     HOW IT WORKS
================================================================ --}}
<section id="how" class="lmt-section bg-white">
    <div class="lmt-container px-6">

        <div class="text-center mb-16" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Simple setup</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">
                Up and running in minutes
            </h2>
            <p class="text-lg text-ink-soft max-w-xl mx-auto">
                No IT team needed. No config nightmare. Three steps and your whole company is on board.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 relative">
            {{-- Connecting line --}}
            <div class="hidden md:block absolute top-10 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-brand-200 via-brand-500 to-brand-200"></div>

            @php
            $steps = [
                [
                    'num' => '01',
                    'icon' => 'credit-card',
                    'title' => 'Pick a plan & subscribe',
                    'desc' => 'Choose from Starter, Professional, or Enterprise. Your isolated workspace is auto-provisioned in seconds after payment.',
                ],
                [
                    'num' => '02',
                    'icon' => 'building-2',
                    'title' => 'Set up your company',
                    'desc' => 'Add your departments, locations, and employees. Invite your HR team as admins and managers via email.',
                ],
                [
                    'num' => '03',
                    'icon' => 'smartphone',
                    'title' => 'Go live — web & mobile',
                    'desc' => 'Employees scan QR codes to clock in, apply for leave, and view payslips right from their phones.',
                ],
            ];
            @endphp

            @foreach($steps as $i => $step)
            <div class="text-center relative" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.15 }}">
                <div class="relative inline-flex mb-6">
                    <div class="w-20 h-20 rounded-2xl lmt-gradient-bg flex items-center justify-center shadow-pop">
                        <i data-lucide="{{ $step['icon'] }}" class="w-8 h-8 text-white"></i>
                    </div>
                    <div class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-accent-500 flex items-center justify-center text-xs font-black text-ink" style="font-family:'Syne',sans-serif">
                        {{ $step['num'] }}
                    </div>
                </div>
                <h3 class="text-xl font-bold text-ink mb-3">{{ $step['title'] }}</h3>
                <p class="text-ink-soft leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     QR FEATURE SPOTLIGHT
================================================================ --}}
<section class="py-20 lmt-gradient-bg overflow-hidden relative">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full bg-white blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 rounded-full bg-white blur-3xl"></div>
    </div>
    <div class="lmt-container px-6 relative z-10">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div data-lmt-anim="fade-right">
                <div class="inline-flex items-center gap-2 bg-white/20 border border-white/30 text-white text-xs font-semibold px-4 py-2 rounded-full mb-6">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    Geo-Fenced Attendance
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-6 leading-tight">
                    Clock in with a scan. From exactly where they should be.
                </h2>
                <p class="text-white/80 text-lg mb-8 leading-relaxed">
                    Each office location gets a unique QR code. When an employee scans it, we verify they're physically within your defined radius — not in their car, not at home.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        ['icon'=>'shield-check','label'=>'Anti-spoofing protection'],
                        ['icon'=>'map-pin','label'=>'GPS geofencing'],
                        ['icon'=>'camera','label'=>'Optional selfie capture'],
                        ['icon'=>'rotate-ccw','label'=>'Daily token rotation'],
                    ] as $pt)
                    <div class="flex items-center gap-3 text-white/90">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $pt['icon'] }}" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="text-sm font-medium">{{ $pt['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- QR Illustration --}}
            <div class="flex justify-center" data-lmt-anim="fade-left">
                <div class="relative">
                    {{-- Phone mockup --}}
                    <div class="w-64 bg-white rounded-3xl shadow-pop p-4 border-4 border-white relative z-10">
                        <div class="bg-gray-100 rounded-2xl p-6 flex flex-col items-center gap-4">
                            {{-- QR code simulation --}}
                            <div class="w-36 h-36 bg-white rounded-xl p-3 shadow-soft">
                                <div class="w-full h-full grid grid-cols-7 gap-0.5">
                                    @for($r=0;$r<7;$r++)
                                        @for($cl=0;$cl<7;$cl++)
                                            @php
                                            $corner = ($r<3&&$cl<3)||($r<3&&$cl>3)||($r>3&&$cl<3);
                                            $filled = $corner || rand(0,1);
                                            @endphp
                                            <div class="rounded-[1px] {{ $filled ? 'bg-ink' : 'bg-white' }}"></div>
                                        @endfor
                                    @endfor
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-bold text-ink">Lockmytimes HQ</p>
                                <p class="text-[10px] text-ink-soft">Main Entrance · 100m radius</p>
                            </div>
                            <div class="w-full bg-brand-500 text-white text-center text-xs font-bold py-2.5 rounded-xl">
                                Scan to Clock In
                            </div>
                        </div>
                    </div>

                    {{-- Floating badges --}}
                    <div class="absolute -top-4 -right-8 bg-white rounded-xl px-3 py-2 shadow-pop text-xs font-semibold text-emerald-600 flex items-center gap-1.5 animate-float">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Clocked In
                    </div>
                    <div class="absolute -bottom-4 -left-8 bg-white rounded-xl px-3 py-2 shadow-pop text-xs font-semibold text-brand-600 flex items-center gap-1.5 animate-float" style="animation-delay:1s">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> 42m away ✓
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
     PRICING
================================================================ --}}
<section id="pricing" class="lmt-section lmt-mesh-bg">
    <div class="lmt-container px-6">

        <div class="text-center mb-4" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Simple pricing</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">
                Choose your plan
            </h2>
            <p class="text-lg text-ink-soft max-w-xl mx-auto mb-8">
                All plans include a 14-day free trial. No credit card required to start.
            </p>
        </div>

        {{-- Toggle + Cards share a single Alpine scope so the toggle actually controls the cards --}}
        <div x-data="{ yearly: false }">

        {{-- Toggle --}}
        <div class="flex items-center justify-center gap-4 mb-12">
            <span class="text-sm font-medium" :class="!yearly ? 'text-ink' : 'text-ink-soft'">Monthly</span>
            <button @click="yearly=!yearly"
                class="relative w-14 h-7 rounded-full transition-colors"
                :class="yearly ? 'bg-brand-500' : 'bg-gray-200'">
                <span class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full shadow transition-transform"
                      :class="yearly ? 'translate-x-7' : 'translate-x-0'"></span>
            </button>
            <span class="text-sm font-medium" :class="yearly ? 'text-ink' : 'text-ink-soft'">
                Yearly
                <span class="lmt-badge-green ml-1">Save 17%</span>
            </span>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @foreach($plans as $i => $plan)
            <div class="relative flex flex-col {{ $plan->is_featured ? 'ring-2 ring-brand-500 scale-[1.02] shadow-glow' : 'ring-1 ring-gray-200' }} bg-white rounded-2xl p-8 transition-all hover:shadow-pop"
                 data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.1 }}">

                @if($plan->badge)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="lmt-badge-brand shadow-soft text-xs font-bold px-3 py-1">{{ $plan->badge }}</span>
                </div>
                @endif

                {{-- Plan name --}}
                <div class="mb-6">
                    <div class="w-10 h-10 rounded-xl {{ $plan->is_featured ? 'lmt-gradient-bg' : 'bg-gray-100' }} flex items-center justify-center mb-3">
                        <i data-lucide="{{ $plan->slug === 'starter' ? 'sprout' : ($plan->slug === 'professional' ? 'rocket' : 'crown') }}"
                           class="w-5 h-5 {{ $plan->is_featured ? 'text-white' : 'text-ink-soft' }}"></i>
                    </div>
                    <h3 class="text-xl font-black text-ink">{{ $plan->name }}</h3>
                    <p class="text-sm text-ink-soft mt-1">{{ $plan->description }}</p>
                </div>

                {{-- Price --}}
                <div class="mb-6">
                    <div x-show="!yearly">
                        <span class="text-5xl font-black text-ink" style="font-family:'Syne',sans-serif">${{ number_format($plan->monthly_price, 0) }}</span>
                        <span class="text-ink-soft text-sm">/month</span>
                    </div>
                    <div x-show="yearly" x-cloak>
                        <span class="text-5xl font-black text-ink" style="font-family:'Syne',sans-serif">${{ number_format($plan->yearly_price / 12, 0) }}</span>
                        <span class="text-ink-soft text-sm">/month</span>
                        <p class="text-xs text-emerald-600 font-semibold mt-1">Billed ${{ number_format($plan->yearly_price, 0) }}/year</p>
                    </div>
                </div>

                {{-- CTA --}}
                <button onclick="openCheckout('{{ $plan->slug }}')"
                   x-show="!yearly"
                   class="{{ $plan->is_featured ? 'lmt-btn-primary' : 'lmt-btn-secondary' }} w-full text-center mb-8">
                    Start Free Trial — Monthly
                </button>
                <button onclick="openCheckout('{{ $plan->slug }}', 'yearly')"
                   x-show="yearly" x-cloak
                   class="{{ $plan->is_featured ? 'lmt-btn-primary' : 'lmt-btn-secondary' }} w-full text-center mb-8">
                    Start Free Trial — Yearly
                </button>

                {{-- Features --}}
                <div class="space-y-3">
                    @php $featureList = $plan->features ?? []; @endphp
                    @foreach($featureList as $feat)
                    <div class="flex items-start gap-2.5 text-sm">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0"></i>
                        <span class="text-ink-soft">{{ $feat }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Limits --}}
                <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-2 gap-3">
                    <div class="text-center">
                        <div class="text-lg font-black text-ink" style="font-family:'Syne',sans-serif">
                            {{ $plan->max_employees >= 9999 ? '∞' : $plan->max_employees }}
                        </div>
                        <div class="text-xs text-ink-soft">employees</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-ink" style="font-family:'Syne',sans-serif">{{ $plan->max_storage_gb }}GB</div>
                        <div class="text-xs text-ink-soft">storage</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Enterprise callout --}}
        <div class="mt-12 max-w-2xl mx-auto text-center bg-white rounded-2xl p-8 border border-gray-200 shadow-soft" data-lmt-anim="fade-up">
            <i data-lucide="building-2" class="w-8 h-8 text-brand-500 mx-auto mb-3"></i>
            <h3 class="text-xl font-bold text-ink mb-2">Need a custom plan?</h3>
            <p class="text-ink-soft mb-4">More than 1,000 employees? Need custom integrations, SSO, or a dedicated SLA? Let's talk.</p>
            <a href="mailto:sales@lockmytimes.com" class="lmt-btn-secondary inline-flex">
                <i data-lucide="mail" class="w-4 h-4"></i>
                Contact Sales
            </a>
        </div>

        </div>{{-- end x-data yearly scope --}}
    </div>
</section>

{{-- ================================================================
     TESTIMONIALS
================================================================ --}}
<section class="lmt-section bg-white">
    <div class="lmt-container px-6">
        <div class="text-center mb-14" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Customer stories</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">Loved by HR teams</h2>
        </div>

        @php
        $testimonials = [
            ['name'=>'Maria G.','role'=>'HR Director, Acme Corp','quote'=>'The QR attendance feature alone saved us 3 hours a day. No more buddy punching, no more manual corrections. Game changer.','rating'=>5,'avatar'=>'M'],
            ['name'=>'James T.','role'=>'Operations Manager, TechStack Inc','quote'=>'We switched from three different tools to Lockmytimes. Payroll that used to take two days now takes 20 minutes.','rating'=>5,'avatar'=>'J'],
            ['name'=>'Priya S.','role'=>'Founder, Nova Health','quote'=>'The project and task module was unexpected but perfect. Our clinical teams now track billable hours right inside their HR system.','rating'=>5,'avatar'=>'P'],
        ];
        @endphp

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($testimonials as $i => $t)
            <div class="lmt-card flex flex-col" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.1 }}">
                <div class="flex mb-4">
                    @for($s=0;$s<$t['rating'];$s++)
                    <i data-lucide="star" class="w-4 h-4 fill-accent-500 text-accent-500"></i>
                    @endfor
                </div>
                <p class="text-ink-soft italic flex-1 mb-6 leading-relaxed">"{{ $t['quote'] }}"</p>
                <div class="flex items-center gap-3">
                    <div class="lmt-avatar-md font-bold">{{ $t['avatar'] }}</div>
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ $t['name'] }}</p>
                        <p class="text-xs text-ink-soft">{{ $t['role'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     FAQ
================================================================ --}}
<section id="faq" class="lmt-section lmt-mesh-bg">
    <div class="lmt-container px-6 max-w-3xl">
        <div class="text-center mb-14" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Got questions?</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">Frequently asked</h2>
        </div>

        @php
        $faqs = [
            ['q'=>'Is my company data isolated from other companies?','a'=>'Absolutely. Each company gets a completely separate database — not just different rows, but a different MySQL database entirely. Your data never shares infrastructure with another tenant.'],
            ['q'=>'Does the QR attendance work for remote or hybrid teams?','a'=>'Yes. You can set up multiple locations, each with their own QR code and geofence radius. Remote employees can use the web-based clock-in, and managers get a clear visual of who clocked in from where.'],
            ['q'=>'Is Lockmytimes FLSA compliant?','a'=>'Yes. Overtime is calculated automatically according to federal FLSA rules (1.5× after 40 hours/week). State-level overtime rules and tax rates are also supported.'],
            ['q'=>'Can employees use a mobile app?','a'=>'Yes. Lockmytimes provides a full REST API compatible with iOS and Android apps. Employees can clock in via QR scan, apply for leave, view payslips, and track their tasks from their phone.'],
            ['q'=>'What happens after my 14-day trial?','a'=>'You\'ll be asked to enter payment details to continue. If you don\'t, your account is paused (your data is never deleted for 30 days, giving you time to export everything).'],
            ['q'=>'Can I import our existing employee data?','a'=>'Yes. The admin panel includes a CSV import wizard for employees, departments, and historical attendance data.'],
        ];
        @endphp

        <div class="space-y-4" x-data="{ open: null }">
            @foreach($faqs as $i => $faq)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-soft overflow-hidden" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.05 }}">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="w-full flex items-center justify-between px-6 py-4 text-left gap-4">
                    <span class="text-sm font-semibold text-ink">{{ $faq['q'] }}</span>
                    <div class="shrink-0 w-6 h-6 rounded-full bg-brand-50 text-brand-500 flex items-center justify-center transition-transform"
                         :class="open === {{ $i }} ? 'rotate-45' : ''">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    </div>
                </button>
                <div x-show="open === {{ $i }}" x-collapse class="px-6 pb-5">
                    <p class="text-sm text-ink-soft leading-relaxed">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     CTA STRIP
================================================================ --}}
<section class="py-24 lmt-gradient-bg relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-white blur-3xl"></div>
    </div>
    <div class="lmt-container px-6 relative z-10 text-center">
        <h2 class="text-4xl md:text-6xl font-black text-white mb-6 leading-tight" data-lmt-anim="fade-up">
            Ready to transform<br>your HR?
        </h2>
        <p class="text-white/80 text-xl mb-10 max-w-xl mx-auto" data-lmt-anim="fade-up" data-lmt-delay="0.1">
            Join 500+ US companies already running their HR on Lockmytimes. Start free, no credit card needed.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4" data-lmt-anim="fade-up" data-lmt-delay="0.2">
            <a href="#pricing" class="bg-white text-brand-600 font-bold px-8 py-4 rounded-xl hover:shadow-pop transition-all active:scale-95 text-sm inline-flex items-center gap-2">
                <i data-lucide="zap" class="w-5 h-5"></i>
                Start 14-Day Free Trial
            </a>
            <a href="mailto:sales@lockmytimes.com" class="border border-white/40 text-white font-semibold px-8 py-4 rounded-xl hover:bg-white/10 transition-all text-sm inline-flex items-center gap-2">
                <i data-lucide="mail" class="w-5 h-5"></i>
                Talk to Sales
            </a>
        </div>
    </div>
</section>

{{-- ================================================================
     FOOTER
================================================================ --}}
<footer class="bg-ink text-white/60 py-16">
    <div class="lmt-container px-6">
        <div class="grid md:grid-cols-5 gap-10 mb-12">
            {{-- Brand --}}
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-8 h-8 object-contain"/>
                    <span class="text-white text-xl font-bold" style="font-family:'Syne',sans-serif">Lockmytimes</span>
                </div>
                <p class="text-sm leading-relaxed mb-4">
                    All-in-one HR platform built for modern US businesses. QR attendance, payroll, performance, and more.
                </p>
                <div class="flex gap-3">
                    @foreach(['twitter','linkedin','github'] as $social)
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-brand-500 flex items-center justify-center transition-colors">
                        <i data-lucide="{{ $social }}" class="w-4 h-4 text-white"></i>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Links --}}
            @php
            $footerLinks = [
                'Product' => ['Features','Pricing','Changelog','Roadmap'],
                'Company' => ['About','Blog','Careers','Press'],
                'Legal'   => ['Privacy Policy','Terms of Service','Cookie Policy','Security'],
            ];
            @endphp
            @foreach($footerLinks as $group => $links)
            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-4">{{ $group }}</h4>
                <ul class="space-y-2.5">
                    @foreach($links as $link)
                    <li><a href="#" class="text-sm hover:text-white transition-colors">{{ $link }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
            <p>© {{ date('Y') }} Lockmytimes. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    SOC 2 Compliant
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-brand-400"></i>
                    256-bit Encryption
                </span>
                <span class="flex items-center gap-1.5">
                    <i data-lucide="server" class="w-3.5 h-3.5 text-amber-400"></i>
                    99.9% Uptime SLA
                </span>
            </div>
        </div>
    </div>
</footer>

{{-- ================================================================
     CHECKOUT MODAL
================================================================ --}}
<div id="checkout-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center p-4"
     style="background:rgba(17,24,39,0.6); backdrop-filter:blur(6px);">

    <div class="bg-white rounded-2xl shadow-pop w-full max-w-md p-8 animate-slide-up relative">

        {{-- Close --}}
        <button onclick="closeCheckout()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>

        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-xl lmt-gradient-bg flex items-center justify-center mx-auto mb-3">
                <i data-lucide="zap" class="w-6 h-6 text-white"></i>
            </div>
            <h3 class="text-xl font-black text-ink">Start your free trial</h3>
            <p class="text-sm text-ink-soft mt-1">14 days free · No charges until trial ends</p>
        </div>

        <form id="checkout-form" action="{{ route('checkout') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="plan_slug" id="modal-plan-slug" value="">
            <input type="hidden" name="billing_cycle" id="modal-billing-cycle" value="monthly">

            <div>
                <label class="lmt-label">Company Name <span class="text-red-500">*</span></label>
                <input type="text" name="company_name" required placeholder="Acme Corporation"
                       class="lmt-input" />
            </div>
            <div>
                <label class="lmt-label">Your Name <span class="text-red-500">*</span></label>
                <input type="text" name="contact_name" required placeholder="John Smith"
                       class="lmt-input" />
            </div>
            <div>
                <label class="lmt-label">Work Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="john@acmecorp.com"
                       class="lmt-input" />
            </div>

            <div id="modal-plan-display" class="bg-brand-50 rounded-xl px-4 py-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-brand-700" id="modal-plan-name">Plan</span>
                <span class="text-xs text-brand-600" id="modal-plan-cycle">Monthly</span>
            </div>

            <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                Continue to Payment
            </button>
        </form>

        <p class="text-center text-xs text-ink-soft mt-4">
            🔒 Secured by Stripe · Cancel anytime
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script>
const planNames = @json($plans->pluck('name', 'slug'));

function openCheckout(slug, cycle = 'monthly') {
    document.getElementById('modal-plan-slug').value    = slug;
    document.getElementById('modal-billing-cycle').value = cycle;
    document.getElementById('modal-plan-name').textContent  = planNames[slug] || slug;
    document.getElementById('modal-plan-cycle').textContent = cycle.charAt(0).toUpperCase() + cycle.slice(1);

    const modal = document.getElementById('checkout-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    if (window.lucide) lucide.createIcons();
}

function closeCheckout() {
    const modal = document.getElementById('checkout-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('checkout-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCheckout();
});

// Close on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeCheckout();
});

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 500);
});
</script>
@endpush