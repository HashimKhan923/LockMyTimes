<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full scroll-smooth"
      x-data="{
          sidebarOpen: localStorage.getItem('emp-sidebar') !== 'false',
          mobileOpen: false,
          darkMode: (function() {
              @if(session('theme_saved'))
              localStorage.removeItem('emp-dark-override');
              @endif
              var override = localStorage.getItem('emp-dark-override');
              if (override !== null) return override === 'true';
              var t = '{{ $userTheme ?? 'light' }}';
              if (t === 'dark')   return true;
              if (t === 'light')  return false;
              return window.matchMedia('(prefers-color-scheme: dark)').matches;
          })(),
      }"
      :class="{ 'dark': darkMode }"
      x-init="
          $watch('sidebarOpen', v => localStorage.setItem('emp-sidebar', v));
          @if(($userTheme ?? 'light') === 'system')
          if (localStorage.getItem('emp-dark-override') === null) {
              window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => { darkMode = e.matches; });
          }
          @endif
      ">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="theme-color" content="#6C7DF7"/>
    <title>@yield('title','Home') — {{ $currentTenant->company_name ?? 'Employee Portal' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css','resources/js/app.js'])

    @php
        /* Resolve tenant brand color from settings (mirrors admin layout) */
        $brandPrimary   = \App\Models\Tenant\Setting::get('theme.primary_color')
                       ?? $currentTenant->primary_color
                       ?? '#6C7DF7';

        /* Derive tints/shades from the primary color only — --brand-600 must stay in the
           brand family (used for hover states, gradients, shadows), not the tenant's
           separate accent/secondary color setting. */
        $hex = ltrim($brandPrimary, '#');
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
        $brand50  = sprintf('#%02x%02x%02x', 230+round(($r-230)*.15), 230+round(($g-230)*.15), 245+round(($b-245)*.15));
        $brand100 = sprintf('#%02x%02x%02x', 215+round(($r-215)*.2),  215+round(($g-215)*.2),  240+round(($b-240)*.2));
        $brand200 = sprintf('#%02x%02x%02x', 180+round(($r-180)*.3),  185+round(($g-185)*.3),  250+round(($b-250)*.3));
        $brand600 = sprintf('#%02x%02x%02x', max(0,round($r*.78)), max(0,round($g*.78)), max(0,round($b*.78)));
        $brand700 = sprintf('#%02x%02x%02x', max(0,round($r*.55)), max(0,round($g*.55)), max(0,round($b*.55)));

        $tenantSlug = $currentTenant->slug ?? request()->route('tenant');
        $u   = Auth::user();
        $emp = $u?->employee;
    @endphp
    <style>
        :root {
            --brand-500: {{ $brandPrimary }};
            --brand-600: {{ $brand600 }};
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
        body { font-family:'DM Sans', system-ui, sans-serif; background:#F4F6FB; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family:'Plus Jakarta Sans', system-ui, sans-serif !important; }

        /* ===== Sidebar ===== */
        .emp-sidebar { width:260px; transition:width .25s cubic-bezier(.4,0,.2,1); flex-shrink:0; }
        .emp-sidebar.collapsed { width:72px; }

        .nav-label  { transition:opacity .15s, width .15s; white-space:nowrap; overflow:hidden; }
        .emp-sidebar.collapsed .nav-label  { opacity:0; width:0; }
        .emp-sidebar.collapsed .brand-text { opacity:0; width:0; overflow:hidden; }

        .emp-nav-link {
            position:relative; display:flex; align-items:center; gap:12px;
            padding:10px 12px; border-radius:12px;
            color:#6B7280; font-weight:600; font-size:13.5px;
            text-decoration:none; transition:all .15s;
        }
        .emp-nav-link:hover  { background:var(--brand-50); color:var(--brand-600); }
        .emp-nav-link.active { background:var(--brand-500); color:#fff;
            box-shadow:0 4px 14px var(--brand-shadow-35,rgba(108,125,247,.35)); }
        .emp-nav-link .nav-icon { width:18px; height:18px; flex-shrink:0; }
        .emp-nav-link.muted { opacity:.5; cursor:not-allowed; }

        /* Collapsed tooltip */
        .nav-tooltip {
            position:absolute; left:calc(100% + 12px); top:50%; transform:translateY(-50%);
            background:#1F2937; color:#fff; font-size:11px; padding:4px 10px;
            border-radius:8px; white-space:nowrap; opacity:0; pointer-events:none;
            transition:opacity .15s; z-index:99;
        }
        .emp-sidebar.collapsed .emp-nav-link:hover .nav-tooltip { opacity:1; }
        .emp-sidebar.collapsed .emp-nav-link { justify-content:center; padding:10px; }

        /* Section headers */
        .nav-section-label {
            font-size:10px; font-weight:700; letter-spacing:.08em;
            text-transform:uppercase; color:#9CA3AF;
            padding:4px 12px; margin-top:10px; margin-bottom:2px;
            white-space:nowrap; overflow:hidden; transition:opacity .15s;
        }
        .emp-sidebar.collapsed .nav-section-label { opacity:0; }

        /* Badge on nav items */
        .nav-badge {
            margin-left:auto; font-size:10px; font-weight:700; padding:2px 6px;
            border-radius:20px; background:rgba(239,68,68,.12); color:#ef4444;
            flex-shrink:0;
        }
        .emp-nav-link.active .nav-badge { background:rgba(255,255,255,.25); color:#fff; }
        .emp-sidebar.collapsed .nav-badge { display:none; }

        /* Main area */
        .emp-main { flex:1; min-width:0; display:flex; flex-direction:column; overflow:hidden; }
        .emp-content { flex:1; overflow-y:auto; padding:24px; padding-bottom:96px; }

        @media (min-width:1024px) {
            .emp-content { padding-bottom:24px; }
        }

        /* Mobile overlay */
        .mobile-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:39;
            backdrop-filter:blur(2px); }

        /* ===== Mobile bottom-nav (only on small screens) ===== */
        .emp-botnav {
            display:none;
            position:fixed; bottom:0; left:0; right:0;
            background:#fff; border-top:1px solid #F3F4F6;
            padding:8px 6px calc(8px + env(safe-area-inset-bottom));
            z-index:35;
            box-shadow:0 -4px 16px rgba(15,23,42,.04);
            grid-template-columns:repeat(5, 1fr); gap:2px;
        }
        @media (max-width:1023.98px) { .emp-botnav { display:grid; } }
        .emp-botnav a {
            display:flex; flex-direction:column; align-items:center; gap:3px;
            padding:6px 4px; border-radius:10px;
            color:#9CA3AF; font-size:10px; font-weight:600; text-decoration:none;
            transition:all .15s;
        }
        .emp-botnav a i { width:18px; height:18px; }
        .emp-botnav a.active { color:var(--brand-500); }
        .emp-botnav a.active .botnav-icon { background:var(--brand-50); }
        .botnav-icon {
            width:42px; height:28px; display:flex; align-items:center; justify-content:center;
            border-radius:10px; transition:all .15s;
        }
        .emp-botnav a.cta .botnav-icon {
            background:linear-gradient(135deg,var(--brand-500),var(--brand-600));
            color:#fff; width:48px; height:36px; margin-top:-12px;
            box-shadow:0 6px 16px var(--brand-shadow-35);
        }

        /* ===== Dark mode overrides ===== */
        .dark body { background:#0F172A; color:#E2E8F0; }
        .dark .emp-sidebar { background:#1E293B !important; border-right-color:#334155 !important; }
        .dark .emp-sidebar .brand-text { color:#F1F5F9; }
        .dark .emp-nav-link { color:#94A3B8; }
        .dark .emp-nav-link:hover { background:#334155; color:#818CF8; }
        .dark .emp-nav-link.active { background:var(--brand-500); color:#fff; }
        .dark .nav-section-label { color:#475569; }
        .dark .nav-tooltip { background:#0F172A; }
        .dark .emp-main { background:#0F172A; }
        .dark .emp-content { background:#0F172A; }
        .dark .emp-main > header { background:rgba(15,23,42,.9) !important; border-bottom-color:#334155 !important; }
        .dark .emp-sidebar > div:first-child { border-bottom-color:#334155 !important; }
        .dark .emp-sidebar > div:last-child  { border-top-color:#334155 !important; }
        .dark .emp-botnav { background:#1E293B; border-top-color:#334155; }
        .dark .emp-botnav a { color:#64748B; }
        .dark .emp-botnav a.active { color:#A5B4FC; }
        .dark .emp-botnav a.active .botnav-icon { background:#334155; }

        [x-cloak] { display:none !important; }
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
    <aside class="emp-sidebar bg-white border-r border-gray-100 flex flex-col h-screen overflow-hidden z-40
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
                 style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));box-shadow:0 4px 12px var(--brand-shadow-35);">
                @if(isset($currentTenant) && $currentTenant->logo)
                    <img src="{{ $currentTenant->logo_url }}" class="w-full h-full object-cover" alt="Logo"/>
                @else
                    {{ substr($currentTenant->company_name ?? 'L', 0, 1) }}
                @endif
            </div>
            <div class="brand-text overflow-hidden flex-1 min-w-0">
                <p class="font-black text-gray-900 text-sm leading-none truncate">
                    {{ $currentTenant->company_name ?? 'Employee Portal' }}
                </p>
                <p class="text-xs font-semibold mt-0.5" style="color:var(--brand-500);">{{ __('employee.my_workspace') }}</p>
            </div>
        </div>

        {{-- Scrollable nav --}}
        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            @php
                // 'permission' => null means no restriction (always visible)
                $navGroups = [
                    __('employee.nav_section_main') => [
                        ['route'=>'employee.dashboard',          'icon'=>'home',           'label'=>__('employee.nav_home'),          'permission'=>null],
                    ],
                    __('employee.nav_section_time_leave') => [
                        ['route'=>'employee.attendance.index',   'icon'=>'clock',          'label'=>__('employee.nav_attendance'),    'permission'=>'attendance.view'],
                        ['route'=>'employee.attendance-corrections.index', 'icon'=>'edit-3', 'label'=>__('employee.nav_corrections'), 'permission'=>'attendance.view'],
                        ['route'=>'employee.leaves.index',       'icon'=>'calendar-off',   'label'=>__('employee.nav_leaves'),        'permission'=>'leaves.view'],
                    ],
                    __('employee.nav_section_money') => [
                        ['route'=>'employee.payslips.index',     'icon'=>'receipt',        'label'=>__('employee.nav_payslips'),      'permission'=>'payslips.view'],
                        ['route'=>'employee.expenses.index',     'icon'=>'wallet',         'label'=>__('employee.nav_expenses'),      'permission'=>'expenses.view'],
                        ['route'=>'employee.loans.index',        'icon'=>'piggy-bank',     'label'=>__('employee.nav_loans'),         'permission'=>'loans.view'],
                    ],
                    __('employee.nav_section_work') => [
                        ['route'=>'employee.tasks.index',        'icon'=>'kanban-square',  'label'=>__('employee.nav_tasks'),         'permission'=>'tasks.view'],
                        ['route'=>'employee.projects.index',     'icon'=>'folder-kanban',  'label'=>__('employee.nav_projects'),      'permission'=>'projects.view'],
                        ['route'=>'employee.performance.index',  'icon'=>'target',         'label'=>__('employee.nav_performance'),   'permission'=>'performance.view'],
                        ['route'=>'employee.training.index',     'icon'=>'graduation-cap', 'label'=>__('employee.nav_training'),      'permission'=>'training.view'],
                    ],
                    __('employee.nav_section_resources') => [
                        ['route'=>'employee.assets.index',       'icon'=>'package',        'label'=>__('employee.nav_assets'),        'permission'=>'assets.view'],
                        ['route'=>'employee.directory.index',    'icon'=>'users',          'label'=>__('employee.nav_directory'),     'permission'=>'employees.view'],
                    ],
                    __('employee.nav_section_communication') => [
                        ['route'=>'employee.announcements.index','icon'=>'megaphone',      'label'=>__('employee.nav_announcements'), 'permission'=>'announcements.view'],
                    ],
                    __('employee.nav_section_team') => [
                        ['route'=>'employee.team.index',         'icon'=>'users-round',    'label'=>__('employee.nav_team'),          'permission'=>null, 'manager_only'=>true],
                    ],
                    __('employee.nav_section_account') => [
                        ['route'=>'employee.profile.index',      'icon'=>'user',           'label'=>__('employee.nav_profile'),       'permission'=>null],
                        ['route'=>'employee.settings.index',     'icon'=>'settings',       'label'=>__('employee.nav_settings'),      'permission'=>null],
                    ],
                ];

                $isManager = $emp && \App\Models\Tenant\Employee::where('manager_id', $emp->id)->exists();
                $authUser  = Auth::user();
            @endphp

            @foreach($navGroups as $section => $items)
                @php
                    $visibleItems = collect($items)->filter(function($i) use ($isManager, $authUser) {
                        // Hide manager-only items for non-managers
                        if (!empty($i['manager_only']) && !$isManager) return false;
                        // Hide items the user doesn't have permission for
                        if (!empty($i['permission']) && !$authUser->can($i['permission'])) return false;
                        return true;
                    });
                @endphp
                @if($visibleItems->isNotEmpty())
                    <div class="nav-section-label">{{ $section }}</div>
                    @foreach($visibleItems as $item)
                        @php
                            $routeExists = \Route::has($item['route']);
                            $active = $routeExists && request()->routeIs($item['route'].'*');
                        @endphp
                        @if($routeExists)
                            <a href="{{ route($item['route'], $tenantSlug) }}"
                               class="emp-nav-link {{ $active ? 'active' : '' }}">
                                <i data-lucide="{{ $item['icon'] }}" class="nav-icon"></i>
                                <span class="nav-label flex-1">{{ $item['label'] }}</span>
                                <span class="nav-tooltip">{{ $item['label'] }}</span>
                            </a>
                        @else
                            <div class="emp-nav-link muted select-none">
                                <i data-lucide="{{ $item['icon'] }}" class="nav-icon"></i>
                                <span class="nav-label flex-1">{{ $item['label'] }}</span>
                                <span class="nav-tooltip">{{ __('employee.coming_soon') }}</span>
                            </div>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </nav>

        {{-- User profile (bottom of sidebar) --}}
        <div class="flex-shrink-0 border-t border-gray-100 p-3">
            <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors">
                <div class="w-9 h-9 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center font-bold text-xs text-white"
                     style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));">
                    @if($u?->avatar)
                        <img src="{{ $u->avatar_url }}" class="w-full h-full object-cover" alt=""/>
                    @else
                        {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                    @endif
                </div>
                <div class="nav-label flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">{{ $u->name ?? 'Employee' }}</p>
                    <p class="text-xs text-gray-800 truncate">
                        @if($emp?->position)
                            {{ $emp->position->name ?? '' }}
                        @else
                            {{ $u->email ?? '' }}
                        @endif
                    </p>
                </div>
                <form action="{{ route('employee.logout', $tenantSlug) }}" method="POST" class="nav-label">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="text-gray-800 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- =====================================================
         MAIN CONTENT
    ===================================================== --}}
    <div class="emp-main">

        {{-- ===== TOPBAR ===== --}}
        <header class="flex-shrink-0 sticky top-0 z-30 bg-white/85 backdrop-blur-xl border-b border-gray-100 px-4 lg:px-6 py-3 flex items-center justify-between"
                style="box-shadow:0 2px 12px rgba(0,0,0,.04);">
            <div class="flex items-center gap-3">

                {{-- Sidebar toggle (desktop) --}}
                <button @click="sidebarOpen=!sidebarOpen"
                        class="hidden lg:flex w-9 h-9 rounded-xl hover:bg-gray-100 items-center justify-center transition-colors">
                    <i data-lucide="panel-left" class="w-5 h-5 text-gray-800"></i>
                </button>

                {{-- Hamburger (mobile) --}}
                <button @click="mobileOpen=!mobileOpen"
                        class="lg:hidden w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <i data-lucide="menu" class="w-5 h-5 text-gray-800"></i>
                </button>

                {{-- Breadcrumb --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="hidden sm:block text-gray-800 font-medium">{{ $currentTenant->company_name ?? '' }}</span>
                    <i data-lucide="chevron-right" class="hidden sm:block w-4 h-4 text-gray-800"></i>
                    <span class="font-bold text-gray-900">@yield('page-title','Home')</span>
                </div>
            </div>

            <div class="flex items-center gap-1.5 lg:gap-2">

                {{-- Quick clock-in shortcut (only when not on attendance page) --}}
                @if(!request()->routeIs('employee.attendance.*'))
                    @if(\Route::has('employee.attendance.index'))
                        <a href="{{ route('employee.attendance.index', $tenantSlug) }}"
                           class="hidden md:inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-white transition-all hover:scale-105"
                           style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));box-shadow:0 4px 14px var(--brand-shadow-35);">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span>{{ __('employee.clock_in_out') }}</span>
                        </a>
                    @endif
                @endif

                {{-- Dark mode --}}
                <button @click="darkMode=!darkMode; localStorage.setItem('emp-dark-override', darkMode)"
                        class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <i :data-lucide="darkMode ? 'sun' : 'moon'" class="w-4 h-4 text-gray-800"></i>
                </button>

                {{-- Notifications --}}
                @php $notifFeedExists = \Route::has('employee.notifications.feed'); @endphp
                <div class="relative" x-data="{
                        open: false, items: [], unread: 0, loading: false,
                        async load() {
                            @if($notifFeedExists)
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('employee.notifications.feed', $tenantSlug) }}', {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                });
                                const data = await res.json();
                                this.items = data.items ?? [];
                                this.unread = data.unread_count ?? 0;
                            } catch(e) {} finally {
                                this.loading = false;
                                this.$nextTick(() => window.lucide && lucide.createIcons());
                            }
                            @endif
                        },
                        async markAllRead() {
                            @if(\Route::has('employee.notifications.read-all'))
                            try {
                                await fetch('{{ route('employee.notifications.read-all', $tenantSlug) }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    }
                                });
                                this.items = this.items.map(i => ({ ...i, unread: false }));
                                this.unread = 0;
                            } catch(e) {}
                            @endif
                        },
                        async clickItem(item) {
                            @if(\Route::has('employee.notifications.read'))
                            if (item.unread) {
                                try {
                                    await fetch('{{ route('employee.notifications.feed', $tenantSlug) }}'.replace('/feed', '/' + item.id + '/read'), {
                                        method: 'PATCH',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        }
                                    });
                                    item.unread = false;
                                    this.unread = Math.max(0, this.unread - 1);
                                } catch(e) {}
                            }
                            @endif
                            if (item.action_url) { window.location.href = item.action_url; }
                        }
                    }"
                    x-init="load(); setInterval(()=>load(), 60000)"
                    @click.outside="open=false">

                    <button @click="open=!open; if(open) load()"
                            class="w-9 h-9 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors relative">
                        <i data-lucide="bell" class="w-4 h-4 text-gray-800"></i>
                        <span x-show="unread > 0" x-cloak
                              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full text-white text-[10px] font-bold flex items-center justify-center px-1"
                              style="background:var(--brand-500);" x-text="unread > 9 ? '9+' : unread"></span>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         class="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-800 rounded-2xl shadow-pop border border-gray-100 dark:border-slate-700 overflow-hidden z-50">

                        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
                            <span class="font-bold text-sm text-gray-900 dark:text-slate-100">Notifications</span>
                            <div class="flex items-center gap-2">
                                <span x-show="unread > 0" x-cloak class="lmt-badge-brand text-xs" x-text="unread + ' new'"></span>
                                <button x-show="unread > 0" x-cloak @click="markAllRead()"
                                        class="text-xs text-gray-800 hover:text-gray-800 transition-colors">Mark all read</button>
                            </div>
                        </div>

                        <div class="max-h-80 overflow-y-auto">
                            <div x-show="loading" class="flex items-center justify-center py-8 text-sm text-gray-800 gap-2">
                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                Loading…
                            </div>

                            <div x-show="!loading && items.length === 0"
                                 class="flex flex-col items-center py-10 gap-2 text-sm text-gray-800">
                                <i data-lucide="bell-off" class="w-8 h-8 text-gray-800"></i>
                                You're all caught up
                            </div>

                            <template x-for="item in items" :key="item.id">
                                <div @click="clickItem(item)"
                                     :class="item.unread ? 'bg-blue-50/40 dark:bg-slate-700/40' : ''"
                                     class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-colors border-b border-gray-50 dark:border-slate-700/50 last:border-0">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                                         :style="'background:' + (item.color || '#6C7DF7') + '20; color:' + (item.color || '#6C7DF7')">
                                        <i :data-lucide="item.icon || 'bell'" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 leading-snug" x-text="item.title"></p>
                                        <p class="text-xs text-gray-800 mt-0.5" x-text="item.time"></p>
                                    </div>
                                    <span x-show="item.unread" class="w-2 h-2 rounded-full flex-shrink-0 mt-2" style="background:var(--brand-500);"></span>
                                </div>
                            </template>
                        </div>

                        @if(\Route::has('employee.notifications.index'))
                        <div class="px-4 py-2.5 border-t border-gray-100 dark:border-slate-700">
                            <a href="{{ route('employee.notifications.index', $tenantSlug) }}"
                               class="text-xs font-semibold hover:underline" style="color:var(--brand-500)">
                                View all notifications
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open=!open" @click.outside="open=false"
                            class="flex items-center gap-2 pl-1 pr-2 lg:pr-3 py-1 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-7 h-7 rounded-full overflow-hidden flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));">
                            @if($u?->avatar)
                                <img src="{{ $u->avatar_url }}" class="w-full h-full object-cover" alt=""/>
                            @else
                                {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                        <span class="hidden md:block text-sm font-semibold text-gray-800 dark:text-slate-200 max-w-[120px] truncate">
                            {{ explode(' ', $u->name ?? 'You')[0] }}
                        </span>
                        <i data-lucide="chevron-down" class="hidden md:block w-3.5 h-3.5 text-gray-800"></i>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-pop border border-gray-100 dark:border-slate-700 overflow-hidden z-50 py-1">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                            <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">{{ $u->name ?? '' }}</p>
                            <p class="text-xs text-gray-800 truncate">{{ $u->email ?? '' }}</p>
                            @if($emp?->employee_code)
                                <span class="lmt-badge-brand text-[10px] mt-1.5">{{ $emp->employee_code }}</span>
                            @endif
                        </div>
                        @if(\Route::has('employee.profile.index'))
                        <a href="{{ route('employee.profile.index', $tenantSlug) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors text-sm text-gray-800 dark:text-slate-200">
                            <i data-lucide="user" class="w-4 h-4 text-gray-800"></i> My Profile
                        </a>
                        @endif
                        @if(\Route::has('employee.settings.index'))
                        <a href="{{ route('employee.settings.index', $tenantSlug) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors text-sm text-gray-800 dark:text-slate-200">
                            <i data-lucide="settings" class="w-4 h-4 text-gray-800"></i> Settings
                        </a>
                        @endif
                        <a href="{{ route('employee.password.change', $tenantSlug) }}"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors text-sm text-gray-800 dark:text-slate-200">
                            <i data-lucide="key" class="w-4 h-4 text-gray-800"></i> Change Password
                        </a>
                        <div class="border-t border-gray-100 dark:border-slate-700 mt-1 pt-1">
                            <form action="{{ route('employee.logout', $tenantSlug) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors text-sm text-red-600 w-full text-left">
                                    <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="px-4 lg:px-6 pt-4">
            <div class="lmt-alert lmt-alert-success animate-slide-down">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        </div>
        @endif
        @if(session('error'))
        <div class="px-4 lg:px-6 pt-4">
            <div class="lmt-alert lmt-alert-error animate-slide-down">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                {{ session('error') }}
            </div>
        </div>
        @endif
        @if(session('warning'))
        <div class="px-4 lg:px-6 pt-4">
            <div class="lmt-alert lmt-alert-warning animate-slide-down">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
                {{ session('warning') }}
            </div>
        </div>
        @endif
        @if(session('info'))
        <div class="px-4 lg:px-6 pt-4">
            <div class="lmt-alert lmt-alert-info animate-slide-down">
                <i data-lucide="info" class="w-5 h-5 shrink-0"></i>
                {{ session('info') }}
            </div>
        </div>
        @endif

        {{-- Page Content --}}
        <div class="emp-content">
            @yield('content')
        </div>
    </div>
</div>

{{-- ===== MOBILE BOTTOM NAV ===== --}}
@php
    $botRoute = fn($n) => \Route::has($n) ? route($n, $tenantSlug) : '#';
    $botActive = fn($n) => \Route::has($n) && request()->routeIs($n.'*');
@endphp
<nav class="emp-botnav">
    <a href="{{ $botRoute('employee.dashboard') }}" class="{{ $botActive('employee.dashboard') ? 'active' : '' }}">
        <div class="botnav-icon"><i data-lucide="home"></i></div>
        <span>Home</span>
    </a>
    <a href="{{ $botRoute('employee.attendance.index') }}" class="{{ $botActive('employee.attendance.index') ? 'active' : '' }}">
        <div class="botnav-icon"><i data-lucide="clock"></i></div>
        <span>Attendance</span>
    </a>
    <a href="{{ $botRoute('employee.attendance.index') }}" class="cta">
        <div class="botnav-icon"><i data-lucide="fingerprint"></i></div>
        <span style="margin-top:2px;font-weight:700;">Clock</span>
    </a>
    <a href="{{ $botRoute('employee.leaves.index') }}" class="{{ $botActive('employee.leaves.index') ? 'active' : '' }}">
        <div class="botnav-icon"><i data-lucide="calendar-off"></i></div>
        <span>Leaves</span>
    </a>
    <a href="{{ $botRoute('employee.profile.index') }}" class="{{ $botActive('employee.profile.index') ? 'active' : '' }}">
        <div class="botnav-icon"><i data-lucide="user"></i></div>
        <span>Profile</span>
    </a>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 300);
});
</script>
@stack('scripts')
</body>
</html>