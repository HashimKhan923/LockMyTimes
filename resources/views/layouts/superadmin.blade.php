<!DOCTYPE html>
<html lang="en" class="scroll-smooth h-full"
      x-data="{
          sidebarOpen: localStorage.getItem('sa-sidebar') !== 'false',
          darkMode: localStorage.getItem('sa-dark') === 'true',
          notifOpen: false,
      }"
      :class="{ 'dark': darkMode }"
      x-init="
          $watch('sidebarOpen', v => localStorage.setItem('sa-sidebar', v));
          $watch('darkMode', v => localStorage.setItem('sa-dark', v));
      ">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title','Dashboard') — Lockmytimes Control Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body { font-family: 'Nunito Sans', sans-serif; }
        h1,h2,h3,h4,h5,h6 { font-family: 'Nunito', sans-serif; }

        /* Sidebar transitions */
        .sa-sidebar           { width: 260px; transition: width 0.25s ease; }
        .sa-sidebar.collapsed { width: 72px; }
        .sa-sidebar .nav-label          { transition: opacity 0.15s ease, width 0.15s ease; }
        .sa-sidebar.collapsed .nav-label { opacity: 0; width: 0; overflow: hidden; white-space: nowrap; }
        .sa-sidebar.collapsed .logo-text { opacity: 0; width: 0; overflow: hidden; }
        .sa-sidebar.collapsed .nav-section-label { display: none; }

        /* Nav links */
        .sa-nav-link { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:12px; color:#6B7280; font-weight:600; font-size:14px; text-decoration:none; transition:all 0.15s; white-space:nowrap; position:relative; }
        .sa-nav-link:hover  { background:#EEF0FE; color:#4A5BE8; }
        .sa-nav-link.active { background:#6C7DF7; color:#fff; box-shadow:0 4px 14px rgba(108,125,247,0.4); }
        .sa-nav-link .icon  { width:20px; height:20px; flex-shrink:0; }

        /* Tooltip for collapsed sidebar */
        .sa-nav-link .tooltip {
            position:absolute; left:calc(100% + 12px); top:50%; transform:translateY(-50%);
            background:#1F2937; color:#fff; font-size:12px; padding:4px 10px; border-radius:8px;
            white-space:nowrap; opacity:0; pointer-events:none; transition:opacity 0.15s; z-index:99;
        }
        .sa-sidebar.collapsed .sa-nav-link:hover .tooltip { opacity:1; }

        /* Main scroll */
        .sa-main { flex:1; min-width:0; overflow-y:auto; display:flex; flex-direction:column; }

        /* Notification dropdown */
        .notif-dropdown { animation: slideDown 0.15s ease; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    </style>
    @stack('head')
</head>
<body class="h-full" style="background:#F4F6FB;">

<div class="flex h-screen overflow-hidden">

    {{-- ═══════════════════════════ SIDEBAR ════════════════════════════ --}}
    <aside class="sa-sidebar flex-shrink-0 bg-white border-r border-gray-100 flex flex-col h-screen overflow-y-auto"
           :class="{ 'collapsed': !sidebarOpen }"
           style="box-shadow:2px 0 20px rgba(108,125,247,0.06);">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden"
                 style="background:#EEF0FE;box-shadow:0 4px 12px rgba(108,125,247,0.4);">
                <img src="{{ asset('images/logo.png') }}" class="w-full h-full object-contain p-1" alt="Lockmytimes"/>
            </div>
            <div class="logo-text overflow-hidden">
                <p class="font-black text-gray-900 text-sm leading-none" style="font-family:'Nunito',sans-serif;">Lockmytimes</p>
                <p class="text-xs font-semibold mt-0.5" style="color:#6C7DF7;letter-spacing:.05em;">CONTROL CENTER</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">

            {{-- Main --}}
            <p class="nav-section-label text-xs font-bold text-gray-400 uppercase tracking-widest px-3 py-2">Main</p>
            @php
            $nav = [
                ['route'=>'superadmin.dashboard',       'icon'=>'layout-dashboard', 'label'=>'Dashboard'],
                ['route'=>'superadmin.tenants.index',   'icon'=>'building-2',       'label'=>'Tenants'],
                ['route'=>'superadmin.analytics.index', 'icon'=>'bar-chart-2',      'label'=>'Analytics'],
            ];
            @endphp
            @foreach($nav as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}"
               class="sa-nav-link {{ $active ? 'active' : '' }}"
               :style="!sidebarOpen ? 'justify-content:center;padding:10px;' : ''">
                <i data-lucide="{{ $item['icon'] }}" class="icon"></i>
                <span class="nav-label">{{ $item['label'] }}</span>
                <span class="tooltip">{{ $item['label'] }}</span>
            </a>
            @endforeach

            {{-- Billing --}}
            <p class="nav-section-label text-xs font-bold text-gray-400 uppercase tracking-widest px-3 pt-4 pb-2">Billing</p>
            @php
            $billing = [
                ['route'=>'superadmin.plans.index',    'icon'=>'layers',      'label'=>'Plans'],
                ['route'=>'superadmin.payments.index', 'icon'=>'credit-card', 'label'=>'Payments'],
            ];
            @endphp
            @foreach($billing as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}"
               class="sa-nav-link {{ $active ? 'active' : '' }}"
               :style="!sidebarOpen ? 'justify-content:center;padding:10px;' : ''">
                <i data-lucide="{{ $item['icon'] }}" class="icon"></i>
                <span class="nav-label">{{ $item['label'] }}</span>
                <span class="tooltip">{{ $item['label'] }}</span>
            </a>
            @endforeach

            {{-- Support --}}
            <p class="nav-section-label text-xs font-bold text-gray-400 uppercase tracking-widest px-3 pt-4 pb-2">Support & Security</p>
            @php
            $support = [
                ['route'=>'superadmin.tickets.index', 'icon'=>'life-buoy',    'label'=>'Support Tickets', 'badge'=>\App\Models\Main\SupportTicket::where('status','open')->count()],
                ['route'=>'superadmin.audit.index',   'icon'=>'shield-check', 'label'=>'Audit Logs',      'badge'=>null],
            ];
            @endphp
            @foreach($support as $item)
            @php $active = request()->routeIs($item['route'].'*'); @endphp
            <a href="{{ route($item['route']) }}"
               class="sa-nav-link {{ $active ? 'active' : '' }}"
               :style="!sidebarOpen ? 'justify-content:center;padding:10px;' : ''">
                <i data-lucide="{{ $item['icon'] }}" class="icon"></i>
                <span class="nav-label flex-1">{{ $item['label'] }}</span>
                @if($item['badge'])
                <span class="nav-label {{ $active ? 'bg-white/30 text-white' : 'bg-red-100 text-red-600' }} text-xs font-bold px-1.5 py-0.5 rounded-full leading-none">{{ $item['badge'] }}</span>
                @endif
                <span class="tooltip">{{ $item['label'] }}</span>
            </a>
            @endforeach

            {{-- Config --}}
            <p class="nav-section-label text-xs font-bold text-gray-400 uppercase tracking-widest px-3 pt-4 pb-2">Configuration</p>
            @php $active = request()->routeIs('superadmin.settings*'); @endphp
            <a href="{{ route('superadmin.settings') }}"
               class="sa-nav-link {{ $active ? 'active' : '' }}"
               :style="!sidebarOpen ? 'justify-content:center;padding:10px;' : ''">
                <i data-lucide="settings" class="icon"></i>
                <span class="nav-label">Settings</span>
                <span class="tooltip">Settings</span>
            </a>
        </nav>

        {{-- User footer --}}
        <div class="flex-shrink-0 border-t border-gray-100 p-3">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-gray-50 transition-colors">
                <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-xs text-white"
                     style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                    {{ substr(Auth::guard('superadmin')->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="nav-label flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::guard('superadmin')->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-400 capitalize">{{ Auth::guard('superadmin')->user()->role ?? 'owner' }}</p>
                </div>
                <form action="{{ route('superadmin.logout') }}" method="POST" class="nav-label">
                    @csrf
                    <button type="submit"
                            class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50"
                            title="Sign out">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════════════════ MAIN CONTENT ═══════════════════════════ --}}
    <div class="sa-main">

        {{-- Topbar --}}
        <header class="flex-shrink-0 bg-white/80 backdrop-blur-xl border-b border-gray-100 px-6 py-3 flex items-center justify-between sticky top-0 z-30"
                style="box-shadow:0 2px 12px rgba(0,0,0,0.04);">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen"
                        class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors flex-shrink-0">
                    <i data-lucide="panel-left" class="w-5 h-5 text-gray-500"></i>
                </button>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-400 font-medium">Control Center</span>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300"></i>
                    <span class="font-bold text-gray-900">@yield('page-title','Dashboard')</span>
                </div>
            </div>

            <div class="flex items-center gap-2">

                {{-- Dark mode --}}
                <button @click="darkMode=!darkMode"
                        class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors"
                        title="Toggle dark mode">
                    <i :data-lucide="darkMode ? 'sun' : 'moon'" class="w-4 h-4 text-gray-500"></i>
                </button>

                {{-- Notification bell with dropdown --}}
                @php
                $notifItems = [];
                $expiringTrials = \App\Models\Main\Tenant::where('status','trial')
                    ->where('trial_ends_at','<=',now()->addDays(3))
                    ->where('trial_ends_at','>',now())
                    ->orderBy('trial_ends_at')
                    ->limit(5)->get(['id','company_name','trial_ends_at','slug']);
                foreach($expiringTrials as $t) {
                    $notifItems[] = ['type'=>'warning','icon'=>'clock','msg'=>$t->company_name.' trial expires '.$t->trial_ends_at->diffForHumans(),'url'=>route('superadmin.tenants.show',$t)];
                }
                $failedPayments = \App\Models\Main\Payment::where('status','failed')->where('created_at','>=',now()->subDays(7))->count();
                if($failedPayments) {
                    $notifItems[] = ['type'=>'danger','icon'=>'credit-card','msg'=>$failedPayments.' failed payment(s) in last 7 days','url'=>route('superadmin.payments.index',['status'=>'failed'])];
                }
                $openTickets = \App\Models\Main\SupportTicket::where('status','open')->count();
                if($openTickets) {
                    $notifItems[] = ['type'=>'info','icon'=>'life-buoy','msg'=>$openTickets.' open support ticket(s)','url'=>route('superadmin.tickets.index',['status'=>'open'])];
                }
                $totalNotifs = count($notifItems);
                @endphp

                <div class="relative" x-data="{ open: false }" @click.outside="open=false">
                    <button @click="open=!open"
                            class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors relative"
                            title="Notifications">
                        <i data-lucide="bell" class="w-4 h-4 text-gray-500"></i>
                        @if($totalNotifs > 0)
                        <span class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center leading-none">{{ $totalNotifs }}</span>
                        @endif
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl border border-gray-100 overflow-hidden z-50"
                         style="box-shadow:0 8px 32px rgba(0,0,0,0.12);">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <p class="font-bold text-ink text-sm">Notifications</p>
                            @if($totalNotifs > 0)
                            <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $totalNotifs }} new</span>
                            @endif
                        </div>

                        @if(empty($notifItems))
                        <div class="px-4 py-8 text-center">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500"></i>
                            </div>
                            <p class="text-sm font-semibold text-ink">All clear!</p>
                            <p class="text-xs text-ink-soft mt-1">No alerts at this time.</p>
                        </div>
                        @else
                        <div class="max-h-72 overflow-y-auto divide-y divide-gray-50">
                            @foreach($notifItems as $n)
                            @php
                            $nColors = ['warning'=>['bg'=>'bg-amber-50','icon'=>'text-amber-500'],'danger'=>['bg'=>'bg-red-50','icon'=>'text-red-500'],'info'=>['bg'=>'bg-brand-50','icon'=>'text-brand-500']];
                            $nc = $nColors[$n['type']] ?? $nColors['info'];
                            @endphp
                            <a href="{{ $n['url'] }}"
                               class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors group"
                               @click="open=false">
                                <div class="w-8 h-8 rounded-lg {{ $nc['bg'] }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <i data-lucide="{{ $n['icon'] }}" class="w-4 h-4 {{ $nc['icon'] }}"></i>
                                </div>
                                <p class="text-sm text-ink leading-snug flex-1">{{ $n['msg'] }}</p>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-300 group-hover:text-brand-500 transition-colors flex-shrink-0 mt-1"></i>
                            </a>
                            @endforeach
                        </div>
                        @endif

                        <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50/50">
                            <a href="{{ route('superadmin.dashboard') }}" class="text-xs text-brand-500 hover:text-brand-700 font-semibold"
                               @click="open=false">View all alerts on dashboard →</a>
                        </div>
                    </div>
                </div>

                {{-- Date badge --}}
                <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    {{ now()->format('M j, Y') }}
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mx-6 mt-4">
            <div class="lmt-alert lmt-alert-success animate-slide-down flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-500"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="mx-6 mt-4">
            <div class="lmt-alert lmt-alert-error animate-slide-down flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-red-500"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        @endif
        @if($errors->any())
        <div class="mx-6 mt-4">
            <div class="lmt-alert lmt-alert-error animate-slide-down">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-red-500"></i>
                <div>
                    <p class="font-semibold text-sm">Please fix the following errors:</p>
                    <ul class="mt-1 list-disc list-inside text-sm space-y-0.5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
        setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 200);
    });
</script>
@stack('scripts')
</body>
</html>
