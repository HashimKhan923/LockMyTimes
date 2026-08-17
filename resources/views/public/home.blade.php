@extends('layouts.public')

@section('title', 'Lockmytimes — All-in-One HR Platform for Modern US Teams')

@section('content')

{{-- ================================================================
     NAVIGATION
================================================================ --}}
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 40">
    <div :class="scrolled ? 'bg-white backdrop-blur-lg shadow-soft border-b border-gray-100' : 'bg-white'" class="transition-all duration-300">
        <div class="lmt-container flex items-center justify-between h-16 px-6">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <img src="{{ asset('images/logo.png') }}" alt="Lockmytimes" class="w-10 h-10 object-contain group-hover:scale-110 transition-transform"/>
                <span class="text-2xl font-bold text-brand-500" style="font-family:'Syne',sans-serif">Lockmytimes</span>
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Features</a>
                <a href="#how" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">How It Works</a>
                <a href="#pricing" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">FAQ</a>
                <a href="#contact" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Contact</a>
            </div>

            {{-- CTA --}}
            <div class="hidden md:flex items-center gap-3">
                <!-- <a href="{{ url('/superadmin') }}" class="text-sm font-medium text-ink-soft hover:text-brand-500 transition-colors">Sign In</a> -->
                <a href="#pricing" class="lmt-btn-primary lmt-btn-md">Start Free Trial</a>
            </div>

            {{-- Hamburger --}}
            <button @click="open=!open" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="menu" class="w-5 h-5" x-show="!open"></i>
                <i data-lucide="x" class="w-5 h-5" x-show="open" x-cloak></i>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="open" x-cloak class="md:hidden bg-white border-t border-gray-100 px-6 py-4 flex flex-col gap-4">
            <a href="#features" @click="open=false" class="text-sm font-medium text-ink-soft">Features</a>
            <a href="#how" @click="open=false" class="text-sm font-medium text-ink-soft">How It Works</a>
            <a href="#pricing" @click="open=false" class="text-sm font-medium text-ink-soft">Pricing</a>
            <a href="#faq" @click="open=false" class="text-sm font-medium text-ink-soft">FAQ</a>
            <a href="#contact" @click="open=false" class="text-sm font-medium text-ink-soft">Contact</a>
            <a href="#pricing" class="lmt-btn-primary w-full text-center">Start Free Trial</a>
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
            <!-- <div class="inline-flex items-center gap-2 bg-brand-50 border border-brand-200 text-brand-700 text-xs font-semibold px-4 py-2 rounded-full mb-8 animate-fade-in">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse-slow"></span>
                Built for the US market · FLSA · FMLA · I-9 Ready
            </div> -->

            {{-- Headline --}}
            <h1 class="text-5xl md:text-6xl font-black text-ink tracking-tight mb-6" data-lmt-anim="fade-up">
                Streamline your Workforce with
                <span class="lmt-gradient-text"> Real-Time Insights</span>
            </h1>

            {{-- Sub --}}
            <p class="text-xl md:text-2xl text-ink-soft font-light max-w-2xl mx-auto mb-10" data-lmt-anim="fade-up" data-lmt-delay="0.1">
                QR-based attendance, smart payroll, performance reviews, project tracking, loans & more — one platform, every team.
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16" data-lmt-anim="fade-up" data-lmt-delay="0.2">
                <a href="#pricing" class="lmt-btn-primary lmt-btn-lg gap-3 shadow-glow">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    Get Started
                </a>
                <a href="#how" class="lmt-btn-secondary lmt-btn-lg gap-3">
                    <!-- <i data-lucide="info" class="w-5 h-5"></i> -->
                   How It Works
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
        <div class="mt-12 sm:mt-16 lg:mt-20 relative max-w-5xl mx-auto px-4 sm:px-0" data-lmt-anim="zoom" data-lmt-delay="0.4">
            <div class="absolute inset-0 lmt-gradient-bg opacity-20 blur-3xl rounded-3xl scale-95"></div>
            <div class="relative bg-white rounded-2xl border border-gray-200 shadow-pop overflow-hidden">

                {{-- Fake browser bar --}}
                <div class="flex items-center gap-2 px-3 sm:px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <div class="w-3 h-3 rounded-full bg-red-400 shrink-0"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400 shrink-0"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400 shrink-0"></div>
                    <div class="flex-1 min-w-0 mx-2 sm:mx-4 bg-white border border-gray-200 rounded-full px-3 py-1 text-[10px] sm:text-xs text-ink-soft text-center truncate">
                        lockymtimes.com/dashboard
                    </div>
                </div>

                {{-- Fake Dashboard --}}
                <div class="p-3 sm:p-6 bg-surface-alt">
                    {{-- Stat row --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                        @php
                        $stats = [
                            ['label'=>'Total Employees','value'=>'248','icon'=>'users','color'=>'bg-brand-50 text-brand-600','delta'=>'+12%'],
                            ['label'=>'Present Today',   'value'=>'201','icon'=>'user-check','color'=>'bg-emerald-50 text-emerald-600','delta'=>'81%'],
                            ['label'=>'Pending Leaves',  'value'=>'14', 'icon'=>'calendar-off','color'=>'bg-amber-50 text-amber-600','delta'=>'3 urgent'],
                            ['label'=>'Payroll Due',     'value'=>'$84K','icon'=>'dollar-sign','color'=>'bg-purple-50 text-purple-600','delta'=>'In 3 days'],
                        ];
                        @endphp
                        @foreach($stats as $s)
                        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100">
                            <div class="flex items-start justify-between mb-2 sm:mb-3">
                                <span class="text-[11px] sm:text-xs font-medium text-ink-soft">{{ $s['label'] }}</span>
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg {{ $s['color'] }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $s['icon'] }}" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                </div>
                            </div>
                            <div class="text-lg sm:text-2xl font-bold text-ink mb-1" style="font-family:'Syne',sans-serif">{{ $s['value'] }}</div>
                            <div class="text-[11px] sm:text-xs text-emerald-600 font-medium">{{ $s['delta'] }}</div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Two columns --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                        {{-- Attendance chart placeholder --}}
                        <div class="md:col-span-2 bg-white rounded-xl p-3 sm:p-4 border border-gray-100">
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
                        <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100">
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
              <div class="lmt-badge-brand bg-white mb-4">Everything in one place</div>
           
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">
                Revolutionize Workforce Management with <br/>
                  <span class="lmt-gradient-text"> Smart Solutions </span>
            </h2>
            <p class="text-lg text-ink-soft max-w-2xl mx-auto">
 Efficient, modern, and hassle-free tools to manage your workforce
            seamlessly. </p>
        </div>

        {{-- Feature grid --}}
        @php
        $features = [
            [
                'icon'=>'qr-code','color'=>'brand',
                'title'=>'QR Based Attendance',
                'desc'=>'Geo-fenced QR codes ensure employees can only clock in from the right location.',
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
                Simplify HR for your entire company <br />
                  <span class="lmt-gradient-text"> in 3 easy steps</span>
                
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
                    <div class="absolute -top-2 -right-2 w-7 h-7 rounded-full lmt-badge-brand flex items-center justify-center text-xs font-semibold" style="font-family:'Syne',sans-serif">
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
                <div class="inline-flex items-center gap-2 bg-white text-brand-500 text-sm font-semibold px-4 py-2 rounded-full mb-6">
                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                    Geo-Fenced Attendance
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-white mb-6 leading-tight">
                   Clock in only from the right location.
                </h2>
                <p class="text-white/80 text-lg mb-8 leading-relaxed">
                    Each office location gets a unique QR code. When an employee scans it, we verify they're physically within your defined radius — not in their car, not at home.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        ['icon'=>'shield-check','label'=>'Anti-spoofing protection'],
                        ['icon'=>'map-pin','label'=>'GPS geofencing'],
                        ['icon'=>'radar','label'=>'Direct check-in within radius'],
                        ['icon'=>'rotate-ccw','label'=>'Daily token rotation'],
                    ] as $pt)
                    <div class="flex items-center gap-3 text-white/90">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $pt['icon'] }}" class="w-4 h-4 text-brand-500"></i>
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
                    <div class="w-64 bg-white rounded-3xl shadow-pop p-4 border-4 border-white relative">
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
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> 42m away
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
            <div class="lmt-badge-brand mb-4 bg-white">Simple pricing</div>
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
            <div class="relative flex flex-col {{ $plan->is_featured ? 'scale-[1.02] shadow-glow' : 'ring-1 ring-gray-200 bg-white' }} rounded-2xl p-8 transition-all hover:shadow-pop"
                 @if($plan->is_featured) style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));" @endif
                 data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.1 }}">

                @if($plan->badge)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="{{ $plan->is_featured ? 'bg-white text-brand-600' : 'lmt-badge-brand' }} shadow-soft text-xs font-bold px-3 py-1 rounded-full">{{ $plan->badge }}</span>
                </div>
                @endif

                {{-- Plan name --}}
                <div class="mb-6">
                    <div class="w-10 h-10 rounded-xl {{ $plan->is_featured ? 'bg-white/15' : 'bg-gray-100' }} flex items-center justify-center mb-3">
                        <i data-lucide="{{ $plan->slug === 'starter' ? 'sprout' : ($plan->slug === 'professional' ? 'rocket' : 'crown') }}"
                           class="w-5 h-5 {{ $plan->is_featured ? 'text-white' : 'text-ink-soft' }}"></i>
                    </div>
                    <h3 class="text-xl font-black {{ $plan->is_featured ? 'text-white' : 'text-ink' }}">{{ $plan->name }}</h3>
                    <p class="text-sm mt-1 {{ $plan->is_featured ? 'text-white/80' : 'text-ink-soft' }}">{{ $plan->description }}</p>
                </div>

                {{-- Price --}}
                <div class="mb-6">
                    <div x-show="!yearly">
                        <span class="text-5xl font-black {{ $plan->is_featured ? 'text-white' : 'text-ink' }}" style="font-family:'Syne',sans-serif">${{ number_format($plan->monthly_price, 0) }}</span>
                        <span class="text-sm {{ $plan->is_featured ? 'text-white/70' : 'text-ink-soft' }}">/month</span>
                    </div>
                    <div x-show="yearly" x-cloak>
                        <span class="text-5xl font-black {{ $plan->is_featured ? 'text-white' : 'text-ink' }}" style="font-family:'Syne',sans-serif">${{ number_format($plan->yearly_price / 12, 0) }}</span>
                        <span class="text-sm {{ $plan->is_featured ? 'text-white/70' : 'text-ink-soft' }}">/month</span>
                        <p class="text-xs font-semibold mt-1 {{ $plan->is_featured ? 'text-white/90' : 'text-emerald-600' }}">Billed ${{ number_format($plan->yearly_price, 0) }}/year</p>
                    </div>
                </div>

                {{-- CTA --}}
                <button onclick="openCheckout('{{ $plan->slug }}')"
                   x-show="!yearly"
                   class="{{ $plan->is_featured ? 'lmt-btn-invert' : 'lmt-btn-secondary' }} w-full text-center mb-8">
                    Start Free Trial — Monthly
                </button>
                <button onclick="openCheckout('{{ $plan->slug }}', 'yearly')"
                   x-show="yearly" x-cloak
                   class="{{ $plan->is_featured ? 'lmt-btn-invert' : 'lmt-btn-secondary' }} w-full text-center mb-8">
                    Start Free Trial — Yearly
                </button>

                {{-- Features --}}
                <div class="space-y-3">
                    {{-- Storage bullets (e.g. "10 GB document storage") filtered out per client
                         request (2026-08) — the plan's stored features list is left untouched,
                         this just skips rendering any storage-related line here. --}}
                    @php $featureList = collect($plan->features ?? [])->reject(fn ($f) => stripos($f, 'storage') !== false); @endphp
                    @foreach($featureList as $feat)
                    <div class="flex items-start gap-2.5 text-sm">
                        <i data-lucide="check" class="w-4 h-4 mt-0.5 shrink-0 {{ $plan->is_featured ? 'text-white' : 'text-emerald-500' }}"></i>
                        <span class="{{ $plan->is_featured ? 'text-white/90' : 'text-ink-soft' }}">{{ $feat }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Limits --}}
                <div class="mt-6 pt-6 border-t {{ $plan->is_featured ? 'border-white/20' : 'border-gray-100' }}">
                    <div class="text-center">
                        <div class="text-lg font-black {{ $plan->is_featured ? 'text-white' : 'text-ink' }}" style="font-family:'Syne',sans-serif">
                            {{ $plan->max_employees >= 9999 ? '∞' : $plan->max_employees }}
                        </div>
                        <div class="text-xs {{ $plan->is_featured ? 'text-white/70' : 'text-ink-soft' }}">employees</div>
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
            <a href="#contact" class="lmt-btn-secondary inline-flex">
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
            <div class="lmt-badge-brand mb-4">Testimonials</div>
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
    <div class="lmt-container px-6 max-w-5xl">
        <div class="text-center mb-14" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Got questions?</div>
            <h2 class="text-4xl md:text-5xl font-black text-ink mb-4">Frequently asked</h2>
            <p class="text-lg text-ink-soft max-w-xl mx-auto">Everything you need to know about attendance tracking, security, billing, and getting started with Lockmytimes.</p>
        </div>

        @php
        $faqs = [
            ['q'=>'What is a time & attendance system?','a'=>'A time & attendance system is a digital solution that tracks employee work hours, monitors attendance, and manages leave records — replacing manual timesheets and punch cards with accurate, automated data.'],
            ['q'=>'How does Lockmytimes track attendance?','a'=>'Employees clock in and out through a QR scan or a secure web-based portal. Every entry is timestamped and geo-verified instantly, then rolled up into attendance, payroll, and reporting automatically — no manual reconciliation needed.'],
            ['q'=>'What clock-in methods are available?','a'=>'Employees can clock in via QR-based scan through the web app, or a direct web-based login — whichever fits how your team actually works.'],
            ['q'=>'How does QR-based attendance work?','a'=>'Each office location gets a unique QR code. Employees scan it to mark attendance, and the system verifies their timestamp and location instantly — no dedicated hardware or biometric terminals required.'],
            ['q'=>'What happens if an employee forgets to scan the QR code?','a'=>'Their attendance simply won\'t be recorded automatically. An admin or manager can review the day and apply a manual override with an audit note, so nothing falls through the cracks.'],
            ['q'=>'Can QR codes be reused, or do they rotate?','a'=>'QR codes can be reused for repeat scans — only the first scan of the day counts toward attendance. For extra security, admins can also enable daily token rotation so each code expires and refreshes automatically.'],
            ['q'=>'Is my organization\'s data isolated from other organizations?','a'=>'Absolutely. Each organization gets a completely separate database — not just different rows, but a different MySQL database entirely. Your data never shares infrastructure with another organization.'],
            ['q'=>'Does the system support multiple office locations?','a'=>'Yes. Set up as many locations as you need, each with its own QR code and geofence radius, and monitor attendance across every branch in real time from a single dashboard.'],
            ['q'=>'Is Lockmytimes compatible with remote or hybrid teams?','a'=>'Yes. Remote and hybrid employees can clock in through the web-based portal from anywhere, while on-site staff use geofenced QR scans — giving managers one unified view regardless of where people work.'],
            ['q'=>'What types of businesses can use Lockmytimes?','a'=>'Lockmytimes scales from small teams to multi-branch operations — offices, factories, schools, hospitals, and retail chains all run their workforce management on the same platform.'],
            ['q'=>'How secure is the platform?','a'=>'Every organization is protected with encrypted data storage, role-based access controls, and granular permissions down to the action level, so employees, managers, and admins only ever see what they should.'],
            ['q'=>'Does it support leave and overtime management?','a'=>'Yes. Employees submit leave requests directly in the app, managers approve or reject with one click, and overtime is tracked and calculated automatically alongside regular hours.'],
            ['q'=>'Is Lockmytimes FLSA compliant?','a'=>'Yes. Overtime is calculated automatically according to federal FLSA rules (1.5× after 40 hours/week), with support for state-level overtime rules and tax rates as well.'],
            ['q'=>'Can employees use a mobile-friendly app?','a'=>'Yes. Lockmytimes is fully mobile-responsive with a REST API to match, so employees can clock in via QR scan, apply for leave, view payslips, and track their tasks right from their phone.'],
            ['q'=>'Can I generate reports from the system?','a'=>'Yes. Pull detailed reports on attendance, late arrivals, early departures, overtime, and leave balances, and export them in the format your payroll or finance team needs.'],
            ['q'=>'Can I import our existing employee data?','a'=>'Yes. The admin panel includes a CSV import wizard for employees, departments, and historical attendance data, so you\'re not starting from a blank slate.'],
            ['q'=>'Do you offer a free trial, and what happens after?','a'=>'Every plan starts with a 14-day free trial, no credit card required upfront. Afterwards you\'ll be asked to enter payment details to continue — if you don\'t, your account is simply paused, with your data kept safe for 30 days so you have time to export it.'],
            ['q'=>'What are the pricing plans for Lockmytimes?','a'=>'Pricing scales with your number of employees and the features you need. Check the plans above, or contact us for a custom quote tailored to your business.'],
            ['q'=>'How can I get started with Lockmytimes?','a'=>'Start your 14-day free trial directly from this page, or contact us to schedule a demo — our team will help you get set up and train your staff.'],
            ['q'=>'Is customer support available?','a'=>'Yes. Our support team is available around the clock via email to help with any questions or technical issues that come up.'],
        ];
        @endphp

        <div class="grid md:grid-cols-2 gap-4 items-start" x-data="{ open: null }">
            @foreach($faqs as $i => $faq)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-soft overflow-hidden" data-lmt-anim="fade-up" data-lmt-delay="{{ min($i * 0.03, 0.4) }}">
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
<section id="contact" class="py-24 lmt-gradient-bg relative overflow-hidden">
     <div class="text-center mb-14" data-lmt-anim="fade-up">
            <div class="lmt-badge-brand mb-4">Contact</div>
            <h2 class="text-4xl md:text-5xl font-black text-white mb-4">Connect with us</h2>
                <p class="text-lg text-white/80 max-w-4xl mx-auto">
                Lockmytimes is built for you. Your feedback helps us improve, so we'd love to hear your ideas, suggestions, or comments. Share your thoughts using the form below.
                </p>  
        </div>
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-white blur-3xl"></div>
    </div>
    <div class="lmt-container px-6 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            {{-- Left: Heading --}}
            <div class="text-center lg:text-left md:block hidden" data-lmt-anim="fade-right">
                <h2 class="text-5xl font-black text-white mb-6">
                  We would love to hear <br /> from you
                </h2>
                <p class="text-md text-white/80 max-w-4xl mx-auto">
                  Have a question, suggestion, or need assistance? Our team is here to help. Fill out the form and we'll get back to you as soon as possible.
                </p>
            </div>

            {{-- Right: Contact Form --}}
            <div class="bg-white rounded-2xl shadow-pop p-6 sm:p-8" data-lmt-anim="fade-left">
               
                <form class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="contact-first-name" class="lmt-label">First name</label>
                            <input type="text" id="contact-first-name" name="first_name" class="lmt-input" placeholder="Jane">
                        </div>
                        <div>
                            <label for="contact-last-name" class="lmt-label">Last name</label>
                            <input type="text" id="contact-last-name" name="last_name" class="lmt-input" placeholder="Doe">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="contact-email" class="lmt-label">Email</label>
                            <input type="email" id="contact-email" name="email" class="lmt-input" placeholder="jane@company.com">
                        </div>
                        <div>
                            <label for="contact-phone" class="lmt-label">Phone</label>
                            <input type="tel" id="contact-phone" name="phone" class="lmt-input" placeholder="(555) 123-4567">
                        </div>
                    </div>
                    <div>
                        <label for="contact-message" class="lmt-label">Message</label>
                        <textarea id="contact-message" name="message" class="lmt-textarea" placeholder="Tell us a bit about your team and what you're looking for..."></textarea>
                    </div>
                    <div class="flex justify-center pt-2">
                        <button type="submit" class="lmt-btn-primary lmt-btn-lg gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@include('partials.public-footer')

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
                <label class="lmt-label">Organization Name <span class="text-red-500">*</span></label>
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
             Secured by Stripe · Cancel anytime
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script>
const planNames = @json($plans->pluck('name', 'slug'));

function openCheckout(slug, cycle = 'monthly') {
    document.getElementById('modal-plan-slug').value = slug;
    document.getElementById('modal-billing-cycle').value = cycle;
    document.getElementById('modal-plan-name').textContent = planNames[slug] || slug;
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