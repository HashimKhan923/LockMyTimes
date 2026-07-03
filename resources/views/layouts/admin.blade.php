<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth"
      x-data="{
          sidebarOpen: localStorage.getItem('adm-sidebar') !== 'false',
          mobileOpen: false,
          darkMode: localStorage.getItem('adm-dark') === 'true',
          notifOpen: false,
          profileOpen: false,
      }"
      :class="{ 'dark': darkMode }"
      x-init="
          $watch('sidebarOpen', v => localStorage.setItem('adm-sidebar', v));
          $watch('darkMode',    v => localStorage.setItem('adm-dark', v));
      ">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title','Dashboard') — {{ $currentTenant->company_name ?? 'Admin' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])

    @php
        /* Resolve tenant brand colors from settings (falls back to main tenant record, then defaults) */
        $brandPrimary   = \App\Models\Tenant\Setting::get('theme.primary_color')
                       ?? $currentTenant->primary_color
                       ?? '#6C7DF7';
        $brandSecondary = \App\Models\Tenant\Setting::get('theme.secondary_color')
                       ?? $currentTenant->secondary_color
                       ?? '#4A5BE8';

        /* Derive tints: lighten primary by mixing with white at different levels */
        $hex = ltrim($brandPrimary, '#');
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
        $brand50  = sprintf('#%02x%02x%02x', 230+round(($r-230)*.15), 230+round(($g-230)*.15), 245+round(($b-245)*.15));
        $brand100 = sprintf('#%02x%02x%02x', 215+round(($r-215)*.2),  215+round(($g-215)*.2),  240+round(($b-240)*.2));
        $brand200 = sprintf('#%02x%02x%02x', 180+round(($r-180)*.3),  185+round(($g-185)*.3),  250+round(($b-250)*.3));
        $brand700 = sprintf('#%02x%02x%02x', max(0,round($r*.55)), max(0,round($g*.55)), max(0,round($b*.55)));
    @endphp
    <style>
        :root {
            --brand-500: {{ $brandPrimary }};
            --brand-600: {{ $brandSecondary }};
            --brand-700: {{ $brand700 }};
            --brand-50:  {{ $brand50 }};
            --brand-100: {{ $brand100 }};
            --brand-200: {{ $brand200 }};
            --brand-shadow-05: rgba({{ $r }},{{ $g }},{{ $b }},.05);
            --brand-shadow-08: rgba({{ $r }},{{ $g }},{{ $b }},.08);
            --brand-shadow-10: rgba({{ $r }},{{ $g }},{{ $b }},.10);
            --brand-shadow-12: rgba({{ $r }},{{ $g }},{{ $b }},.12);
            --brand-shadow-15: rgba({{ $r }},{{ $g }},{{ $b }},.15);
            --brand-shadow-30: rgba({{ $r }},{{ $g }},{{ $b }},.30);
            --brand-shadow-35: rgba({{ $r }},{{ $g }},{{ $b }},.35);
        }
    </style>
    <style>
        body { font-family:'Nunito Sans',sans-serif; background:#F4F6FB; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family:'Nunito',sans-serif !important; }

        /* ===== Sidebar ===== */
        .adm-sidebar { width:268px; transition:width .25s cubic-bezier(.4,0,.2,1); flex-shrink:0; }
        .adm-sidebar.collapsed { width:72px; }

        .nav-label  { transition:opacity .15s, width .15s; white-space:nowrap; overflow:hidden; }
        .adm-sidebar.collapsed .nav-label  { opacity:0; width:0; }
        .adm-sidebar.collapsed .brand-text { opacity:0; width:0; overflow:hidden; }

        .adm-nav-link {
            position:relative; display:flex; align-items:center; gap:12px;
            padding:10px 12px; border-radius:12px;
            color:#6B7280; font-weight:600; font-size:13.5px;
            text-decoration:none; transition:all .15s;
        }
        .adm-nav-link:hover  { background:var(--brand-50); color:var(--brand-600); }
        .adm-nav-link.active { background:var(--brand-500); color:#fff;
            box-shadow:0 4px 14px var(--brand-shadow-35,rgba(108,125,247,.35)); }
        .adm-nav-link .nav-icon { width:18px; height:18px; flex-shrink:0; }

        /* Collapsed tooltip */
        .nav-tooltip {
            position:absolute; left:calc(100% + 12px); top:50%; transform:translateY(-50%);
            background:#1F2937; color:#fff; font-size:11px; padding:4px 10px;
            border-radius:8px; white-space:nowrap; opacity:0; pointer-events:none;
            transition:opacity .15s; z-index:99;
        }
        .adm-sidebar.collapsed .adm-nav-link:hover .nav-tooltip { opacity:1; }
        .adm-sidebar.collapsed .adm-nav-link { justify-content:center; padding:10px; }

        /* Section headers */
        .nav-section-label {
            font-size:10px; font-weight:700; letter-spacing:.08em;
            text-transform:uppercase; color:#9CA3AF;
            padding:4px 12px; margin-top:8px; margin-bottom:2px;
            white-space:nowrap; overflow:hidden; transition:opacity .15s;
        }
        .adm-sidebar.collapsed .nav-section-label { opacity:0; }

        /* Badge on nav items */
        .nav-badge {
            margin-left:auto; font-size:10px; font-weight:700; padding:2px 6px;
            border-radius:20px; background:rgba(239,68,68,.12); color:#ef4444;
            flex-shrink:0;
        }
        .adm-nav-link.active .nav-badge { background:rgba(255,255,255,.25); color:#fff; }
        .adm-sidebar.collapsed .nav-badge { display:none; }

        /* Main area */
        .adm-main { flex:1; min-width:0; display:flex; flex-direction:column; overflow:hidden; }
        .adm-content { flex:1; overflow-y:auto; padding:24px; }

        /* Mobile overlay */
        .mobile-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:39;
            backdrop-filter:blur(2px); }

        /* ===== Dark mode overrides ===== */
        .dark body { background:#0F172A; color:#E2E8F0; }
        .dark .adm-sidebar { background:#1E293B !important; border-right-color:#334155 !important; }
        .dark .adm-sidebar .brand-text { color:#F1F5F9; }
        .dark .adm-nav-link { color:#94A3B8; }
        .dark .adm-nav-link:hover { background:#334155; color:#818CF8; }
        .dark .adm-nav-link.active { background:#6C7DF7; color:#fff; box-shadow:0 4px 14px rgba(108,125,247,.35); }
        .dark .nav-section-label { color:#475569; }
        .dark .nav-tooltip { background:#0F172A; }
        .dark .adm-main { background:#0F172A; }
        .dark .adm-content { background:#0F172A; }
        /* Topbar dark */
        .dark .adm-main > header { background:rgba(15,23,42,.9) !important; border-bottom-color:#334155 !important; }
        /* Sidebar top logo area */
        .dark .adm-sidebar > div:first-child { border-bottom-color:#334155 !important; }
        /* Sidebar bottom user area */
        .dark .adm-sidebar > div:last-child { border-top-color:#334155 !important; }
        /* Quick search bar */
        .dark .adm-main > header .bg-gray-50 { background:#0F172A !important; border-color:#334155 !important; }
        .dark .adm-main > header kbd { background:#1E293B !important; border-color:#475569 !important; color:#94A3B8 !important; }
    </style>
    @stack('head')
</head>
<body class="h-full overflow-hidden">

{{-- Mobile overlay --}}
<div class="mobile-overlay lg:hidden" x-show="mobileOpen" @click="mobileOpen=false"
     x-transition:enter="transition-opacity duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
</div>

<div class="flex h-screen overflow-hidden">

    {{-- =====================================================
         SIDEBAR
    ===================================================== --}}
    <aside class="adm-sidebar bg-white border-r border-gray-100 flex flex-col h-screen overflow-hidden z-40
                  fixed lg:relative lg:translate-x-0
                  transition-transform duration-300"
           :class="{
               'collapsed': !sidebarOpen,
               '-translate-x-full': !mobileOpen,
               'translate-x-0': mobileOpen
           }"
           style="box-shadow:2px 0 24px rgba(108,125,247,.06);">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-white text-sm overflow-hidden"
                 style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);box-shadow:0 4px 12px rgba(108,125,247,.4);">
                @if(isset($currentTenant) && $currentTenant->logo)
                    <img src="{{ $currentTenant->logo_url }}" class="w-full h-full object-cover" alt="Logo"/>
                @else
                    <img src="{{ asset('images/logo.png') }}" class="w-full h-full object-contain p-1" alt="Lockmytimes"/>
                @endif
            </div>
            <div class="brand-text overflow-hidden flex-1 min-w-0">
                <p class="font-black text-gray-900 text-sm leading-none truncate">
                    {{ $currentTenant->company_name ?? 'Admin Portal' }}
                </p>
                <p class="text-xs font-semibold mt-0.5" style="color:#6C7DF7;">HR Admin</p>
            </div>
        </div>

        {{-- Scrollable nav --}}
        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">

            @php
            $tenantSlug = $currentTenant->slug ?? request()->route('tenant');
            $navGroups = [
                'Main' => [
                    ['route'=>'admin.dashboard',         'icon'=>'layout-dashboard', 'label'=>'Dashboard'],
                ],
                'People' => [
                    ['route'=>'admin.employees.index',   'icon'=>'users',            'label'=>'Employees'],
                    ['route'=>'admin.departments.index', 'icon'=>'git-branch',       'label'=>'Departments'],
                    ['route'=>'admin.positions.index',   'icon'=>'briefcase',        'label'=>'Positions'],
                    ['route'=>'admin.locations.index',   'icon'=>'map-pin',          'label'=>'Locations'],
                ],
                'Time & Attendance' => [
                    ['route'=>'admin.attendance.index',  'icon'=>'clock',            'label'=>'Attendance'],
                    ['route'=>'admin.qrcodes.index',     'icon'=>'qr-code',          'label'=>'QR Codes'],
                    ['route'=>'admin.shifts.index',      'icon'=>'calendar-days',    'label'=>'Shifts'],
                    ['route'=>'admin.leaves.index',      'icon'=>'calendar-off',     'label'=>'Leaves'],
                ],
                'Payroll & Finance' => [
                    ['route'=>'admin.payroll.index',     'icon'=>'dollar-sign',      'label'=>'Payroll'],
                    ['route'=>'admin.expenses.index',    'icon'=>'receipt',          'label'=>'Expenses'],
                    ['route'=>'admin.loans.index',       'icon'=>'piggy-bank',       'label'=>'Loans & Advances'],
                ],
                'Performance' => [
                    ['route'=>'admin.performance.index', 'icon'=>'trending-up', 'label'=>'Reviews'],
                    ['route'=>'admin.performance.index', 'icon'=>'target',      'label'=>'Goals & OKRs'],
                ],
                'Projects' => [
                    ['route'=>'admin.projects.index', 'icon'=>'kanban',       'label'=>'Projects & Tasks'],
                ],
                'Organisation' => [
                    ['route'=>'admin.assets.index',      'icon'=>'package',          'label'=>'Assets'],
                    ['route'=>'admin.training.index',    'icon'=>'graduation-cap',   'label'=>'Training'],
                    ['route'=>'admin.documents.index',   'icon'=>'folder-open',      'label'=>'Documents'],
                    ['route'=>'admin.recruitment.index', 'icon'=>'user-plus',        'label'=>'Recruitment'],
                ],
                'Communication' => [
                    ['route'=>'admin.announcements.index','icon'=>'megaphone',       'label'=>'Announcements'],
                    ['route'=>'admin.reports.index',     'icon'=>'bar-chart-2',      'label'=>'Reports'],
                ],
                'Account' => [
                    ['route'=>'admin.billing.index',     'icon'=>'credit-card',      'label'=>'Billing & Plan'],
                ],
                'System' => [
                    ['route'=>'admin.roles.index',       'icon'=>'shield',           'label'=>'Roles'],
                    ['route'=>'admin.settings.index',    'icon'=>'settings',         'label'=>'Settings'],
                ],
            ];
            @endphp

            @foreach($navGroups as $section => $items)
                <div class="nav-section-label">{{ $section }}</div>
                @foreach($items as $item)
                @php
                    $routeExists = \Route::has($item['route']);
                    $active = $routeExists && request()->routeIs($item['route'].'*');
                @endphp
                @if($routeExists)
                <a href="{{ route($item['route'], $tenantSlug) }}"
                   class="adm-nav-link {{ $active ? 'active' : '' }}">
                    <i data-lucide="{{ $item['icon'] }}" class="nav-icon"></i>
                    <span class="nav-label flex-1">{{ $item['label'] }}</span>
                    <span class="nav-tooltip">{{ $item['label'] }}</span>
                </a>
                @else
                <div class="adm-nav-link opacity-50 cursor-not-allowed select-none">
                    <i data-lucide="{{ $item['icon'] }}" class="nav-icon"></i>
                    <span class="nav-label flex-1">{{ $item['label'] }}</span>
                </div>
                @endif
                @endforeach
            @endforeach
        </nav>

        {{-- User profile --}}
        <div class="flex-shrink-0 border-t border-gray-100 p-3">
            <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors">
                <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-xs text-white"
                     style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="nav-label flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <form action="{{ route('admin.logout', $tenantSlug) }}" method="POST" class="nav-label">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- =====================================================
         MAIN CONTENT
    ===================================================== --}}
    <div class="adm-main">

        {{-- ===== TOPBAR ===== --}}
        <header class="flex-shrink-0 sticky top-0 z-30 bg-white/85 backdrop-blur-xl border-b border-gray-100 px-6 py-3 flex items-center justify-between"
                style="box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div class="flex items-center gap-3">

                {{-- Sidebar toggle (desktop) --}}
                <button @click="sidebarOpen=!sidebarOpen"
                        class="hidden lg:flex w-9 h-9 rounded-xl hover:bg-gray-100 items-center justify-center transition-colors">
                    <i data-lucide="panel-left" class="w-5 h-5 text-gray-500"></i>
                </button>

                {{-- Hamburger (mobile) --}}
                <button @click="mobileOpen=!mobileOpen"
                        class="lg:hidden w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <i data-lucide="menu" class="w-5 h-5 text-gray-500"></i>
                </button>

                {{-- Breadcrumb --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="hidden sm:block text-gray-400 font-medium">{{ $currentTenant->company_name ?? '' }}</span>
                    <i data-lucide="chevron-right" class="hidden sm:block w-4 h-4 text-gray-300"></i>
                    <span class="font-bold text-gray-900">@yield('page-title','Dashboard')</span>
                </div>
            </div>

            <div class="flex items-center gap-2">

                {{-- Quick Search --}}
                <div x-data="quickSearch('{{ route('admin.search', $tenantSlug) }}')" @keydown.window="onKey($event)">
                    {{-- Trigger button --}}
                    <button @click="open=true; $nextTick(()=>$refs.input.focus())"
                            class="hidden md:flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-sm text-gray-400 cursor-pointer hover:border-brand-300 transition-colors">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span class="text-xs">Quick search…</span>
                        <kbd class="text-[10px] bg-white border border-gray-200 px-1.5 py-0.5 rounded font-mono ml-2">⌘K</kbd>
                    </button>

                    {{-- Mobile trigger --}}
                    <button @click="open=true; $nextTick(()=>$refs.input.focus())"
                            class="md:hidden w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                        <i data-lucide="search" class="w-4 h-4 text-gray-500"></i>
                    </button>

                    {{-- Modal overlay --}}
                    <div x-show="open" x-cloak
                         @keydown.escape.window="close()"
                         class="fixed inset-0 z-[100] flex items-start justify-center pt-20 px-4"
                         style="background:rgba(15,23,42,.6); backdrop-filter:blur(4px);"
                         @click.self="close()">

                        <div class="w-full max-w-xl bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                            {{-- Input row --}}
                            <div class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-100 dark:border-slate-700">
                                <i data-lucide="search" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                                <input x-ref="input" type="text" x-model="query"
                                       @input.debounce.250ms="doSearch()"
                                       placeholder="Search employees, assets, documents, jobs…"
                                       class="flex-1 bg-transparent outline-none text-sm text-gray-900 dark:text-slate-100 placeholder-gray-400"/>
                                <button @click="close()"
                                        class="text-[10px] font-mono bg-gray-100 dark:bg-slate-700 text-gray-400 dark:text-slate-400 px-1.5 py-0.5 rounded border border-gray-200 dark:border-slate-600">ESC</button>
                            </div>

                            {{-- Results --}}
                            <div class="max-h-96 overflow-y-auto">
                                {{-- Loading --}}
                                <div x-show="loading" class="flex items-center justify-center py-10 gap-3 text-sm text-gray-400">
                                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                    </svg>
                                    Searching…
                                </div>

                                {{-- Empty --}}
                                <div x-show="!loading && query.length >= 2 && results.length === 0"
                                     class="flex flex-col items-center py-10 text-sm text-gray-400 gap-2">
                                    <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                    No results for "<span x-text="query" class="font-semibold"></span>"
                                </div>

                                {{-- Hint --}}
                                <div x-show="!loading && query.length < 2"
                                     class="px-4 py-6 text-xs text-gray-400 dark:text-slate-500 text-center">
                                    Type at least 2 characters to search
                                </div>

                                {{-- Result list --}}
                                <template x-for="(item, i) in results" :key="i">
                                    <a :href="item.url"
                                       @click="close()"
                                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors border-b border-gray-50 dark:border-slate-700/50 last:border-0">
                                        <div :class="item.color" class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0">
                                            <i :data-lucide="item.icon" class="w-4 h-4"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 truncate" x-text="item.title"></p>
                                            <p class="text-xs text-gray-400 dark:text-slate-400 truncate" x-text="item.subtitle"></p>
                                        </div>
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 flex-shrink-0" x-text="item.type"></span>
                                    </a>
                                </template>
                            </div>

                            {{-- Footer --}}
                            <div class="px-4 py-2 border-t border-gray-100 dark:border-slate-700 flex items-center gap-4 text-[11px] text-gray-400">
                                <span><kbd class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">↑↓</kbd> navigate</span>
                                <span><kbd class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">↵</kbd> open</span>
                                <span><kbd class="font-mono bg-gray-100 dark:bg-slate-700 px-1 rounded">ESC</kbd> close</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dark mode --}}
                <button @click="darkMode=!darkMode"
                        class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <i :data-lucide="darkMode ? 'sun' : 'moon'" class="w-4 h-4 text-gray-500"></i>
                </button>

                {{-- Notifications --}}
                <div class="relative" x-data="notifBell('{{ route('admin.notifications.feed', $tenantSlug) }}', '{{ route('admin.notifications.read-all', $tenantSlug) }}')"
                     @click.outside="open=false"
                     x-init="loadFeed(); setInterval(()=>loadFeed(), 60000)">

                    {{-- Bell button --}}
                    <button @click="open=!open; if(open) loadFeed()"
                            class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors relative">
                        <i data-lucide="bell" class="w-4 h-4 text-gray-500"></i>
                        <span x-show="unread > 0" x-cloak
                              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full text-white text-[10px] font-bold flex items-center justify-center px-1"
                              style="background:var(--brand-500);" x-text="unread > 9 ? '9+' : unread"></span>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-pop border border-gray-100 dark:border-slate-700 overflow-hidden z-50">

                        {{-- Header --}}
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                            <span class="font-bold text-sm text-gray-900 dark:text-slate-100">Notifications</span>
                            <div class="flex items-center gap-2">
                                <span x-show="unread > 0" x-cloak class="lmt-badge-brand text-xs" x-text="unread + ' new'"></span>
                                <button x-show="unread > 0" x-cloak @click="markAllRead()"
                                        class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Mark all read</button>
                            </div>
                        </div>

                        {{-- List --}}
                        <div class="max-h-80 overflow-y-auto">

                            {{-- Loading --}}
                            <div x-show="loading" class="flex items-center justify-center py-8 text-sm text-gray-400 gap-2">
                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                Loading…
                            </div>

                            {{-- Empty --}}
                            <div x-show="!loading && items.length === 0"
                                 class="flex flex-col items-center py-10 gap-2 text-sm text-gray-400">
                                <i data-lucide="bell-off" class="w-8 h-8 text-gray-300"></i>
                                No notifications
                            </div>

                            {{-- Items --}}
                            <template x-for="item in items" :key="item.id">
                                <div @click="clickItem(item)"
                                     :class="item.unread ? 'bg-blue-50/40 dark:bg-slate-700/40' : ''"
                                     class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-colors border-b border-gray-50 dark:border-slate-700/50 last:border-0">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                                         :style="'background:' + item.color + '20; color:' + item.color">
                                        <i :data-lucide="item.icon" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 leading-snug" x-text="item.title"></p>
                                        <p class="text-xs text-gray-400 mt-0.5" x-text="item.time"></p>
                                    </div>
                                    <span x-show="item.unread" class="w-2 h-2 rounded-full flex-shrink-0 mt-2" style="background:var(--brand-500);"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Footer --}}
                        <div class="px-4 py-2.5 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between">
                            <a href="{{ route('admin.notifications.index', $tenantSlug) }}"
                               class="text-xs font-semibold hover:underline" style="color:var(--brand-500)">
                                View all notifications →
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" @click.outside="open=false"
                            class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                        </div>
                        <span class="hidden md:block text-sm font-semibold text-gray-700">
                            {{ Auth::user()->name ?? 'Admin' }}
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400"></i>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-pop border border-gray-100 overflow-hidden z-50 py-1">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-bold text-gray-900">{{ Auth::user()->name ?? '' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                        </div>
                        <a href="{{ route('admin.profile', $tenantSlug) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-sm text-gray-700">
                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            My Profile
                        </a>
                        <a href="{{ route('admin.settings.index', $tenantSlug) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-sm text-gray-700">
                            <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                            Settings
                        </a>
                        <div class="border-t border-gray-100 mt-1 pt-1">
                            <form action="{{ route('admin.logout', $tenantSlug) }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 transition-colors text-sm text-red-600 w-full text-left">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="px-6 pt-4">
            <div class="lmt-alert lmt-alert-success animate-slide-down">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="px-6 pt-4">
            <div class="lmt-alert lmt-alert-error animate-slide-down">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                {{ session('error') }}
            </div>
        </div>
        @endif

        {{-- Page Content --}}
        <div class="adm-content">
            @yield('content')
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 300);
});

function notifBell(feedUrl, markAllUrl) {
    return {
        open: false,
        loading: false,
        items: [],
        unread: 0,

        async loadFeed() {
            this.loading = true;
            try {
                const res = await window.fetch(feedUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.items   = data.items   ?? [];
                this.unread  = data.unread_count ?? 0;
            } catch(e) {}
            finally {
                this.loading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },

        async markAllRead() {
            await window.fetch(markAllUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });
            this.items  = this.items.map(i => ({ ...i, unread: false }));
            this.unread = 0;
        },

        async clickItem(item) {
            // Mark as read via API then navigate
            if (item.unread) {
                const readUrl = feedUrl.replace('/feed', '/' + item.id + '/read');
                await window.fetch(readUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                item.unread = false;
                this.unread = Math.max(0, this.unread - 1);
            }
            if (item.action_url) {
                window.location.href = item.action_url;
            }
        },
    };
}

function quickSearch(searchUrl) {
    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        _timer: null,

        onKey(e) {
            if ((e.key === 'k' && (e.metaKey || e.ctrlKey))) {
                e.preventDefault();
                this.open = true;
                this.$nextTick(() => this.$refs.input && this.$refs.input.focus());
            }
        },

        close() {
            this.open = false;
            this.query = '';
            this.results = [];
        },

        async doSearch() {
            if (this.query.length < 2) { this.results = []; return; }
            this.loading = true;
            try {
                const url = searchUrl + '?q=' + encodeURIComponent(this.query);
                const res = await window.fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                if (!res.ok) {
                    console.error('Search failed:', res.status, await res.text());
                    this.results = [];
                    return;
                }
                this.results = await res.json();
            } catch (err) {
                console.error('Search error:', err);
                this.results = [];
            } finally {
                this.loading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },
    };
}
</script>
@stack('scripts')
</body>
</html>