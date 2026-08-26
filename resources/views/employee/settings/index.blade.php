@extends('layouts.employee')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
@php
    use App\Http\Controllers\Employee\SettingsController;
    $locales = SettingsController::locales();

    $tabs = [
        'preferences'   => ['label' => __('employee.settings_preferences'),   'icon' => 'settings-2'],
        'notifications' => ['label' => __('employee.settings_notifications'), 'icon' => 'bell'],
        'privacy'       => ['label' => __('employee.settings_privacy'),       'icon' => 'shield'],
        'security'      => ['label' => __('employee.settings_security'),      'icon' => 'lock'],
    ];

    $curDateFmt = $user->date_format ?? 'DMY';
    $curTimeFmt = $user->time_format ?? '12';
    $curTheme   = $user->theme      ?? 'light';
@endphp

<div x-data="{
        tab: (window.location.hash || '').replace('#','') || 'preferences',
        switchTab(t) { this.tab = t; history.replaceState(null,'','#'+t); },
        init() {
            window.addEventListener('hashchange', () => {
                this.tab = (window.location.hash || '').replace('#','') || 'preferences';
            });
        }
     }" class="max-w-4xl mx-auto">

    {{-- ══ Header ══════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl p-5 lg:p-7 mb-6 relative overflow-hidden"
         style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600) 55%,#7C3AED 100%);"
         data-lmt-anim="fade-up">
        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-12 left-1/3 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="relative z-10 flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                <i data-lucide="settings" class="w-7 h-7 text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white leading-tight">{{ __('employee.settings_title') }}</h1>
                <p class="text-white/75 text-sm mt-0.5">{{ __('employee.settings_subtitle') }}</p>
            </div>
        </div>
    </div>

    {{-- ══ Flash ════════════════════════════════════════════════════════ --}}
    @if(session('success'))
    <div class="lmt-alert lmt-alert-success mb-5" data-lmt-anim="fade-up">
        <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="lmt-alert mb-5 bg-red-50 border-red-200 text-red-700" data-lmt-anim="fade-up">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- ══ Tab Bar ══════════════════════════════════════════════════════ --}}
    <div class="flex gap-1 mb-6 bg-white border border-gray-200 rounded-xl p-1 w-fit overflow-x-auto shadow-sm" data-lmt-anim="fade-up">
        @foreach($tabs as $key => $meta)
        <button @click="switchTab('{{ $key }}')"
                :class="tab==='{{ $key }}' ? 'bg-brand-500 text-white shadow-sm' : 'text-gray-800 hover:text-gray-800'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap flex-shrink-0">
            <i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4"></i>
            {{ $meta['label'] }}
        </button>
        @endforeach
    </div>


    {{-- ══════════════════════════════════════════════════════════════════
         TAB 1 — PREFERENCES
    ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='preferences'" x-cloak data-lmt-anim="fade-up">
        <form action="{{ route('employee.settings.update', $tenantSlug) }}" method="POST">
            @csrf @method('PUT')

            <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100 shadow-sm">

                {{-- Section header --}}
                <div class="px-6 py-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="globe" class="w-4 h-4 text-brand-500"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ __('employee.settings_regional') }}</h2>
                        <p class="text-xs text-gray-800">{{ __('employee.settings_regional_desc') }}</p>
                    </div>
                </div>

                {{-- Language --}}
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ __('employee.settings_language') }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ __('employee.settings_language_desc') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <select name="locale" class="lmt-input @error('locale') border-red-400 @enderror">
                            @foreach($locales as $code => $name)
                                <option value="{{ $code }}" @selected(($user->locale ?? 'en') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('locale')<p class="lmt-err">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Timezone — admin-managed, not employee-editable (set from Admin > Employees) --}}
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ __('employee.settings_timezone') }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ __('employee.settings_timezone_desc') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        @php $effectiveTz = $emp->attendanceTimezone(); @endphp
                        <div class="lmt-input bg-gray-50 text-gray-800 flex items-center justify-between">
                            <span>{{ str_replace('_', ' ', $effectiveTz) }}</span>
                            <span class="text-xs text-gray-800">Set by your admin</span>
                        </div>
                        <p class="lmt-help mt-1.5">
                            {{ __('employee.settings_your_time') }}:
                            <strong class="text-gray-800">{{ now()->setTimezone($effectiveTz)->format('D, d M Y — H:i T') }}</strong>
                        </p>
                    </div>
                </div>

                {{-- Date Format --}}
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-start"
                     x-data="{ val: '{{ $curDateFmt }}' }">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ __('employee.settings_date_format') }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ __('employee.settings_date_format_desc') }}</p>
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-3">
                        @foreach(['DMY' => 'DD / MM / YYYY', 'MDY' => 'MM / DD / YYYY', 'YMD' => 'YYYY-MM-DD'] as $v => $lbl)
                        <label :class="val === '{{ $v }}' ? 'lmt-pill-radio selected' : 'lmt-pill-radio'">
                            <input type="radio" name="date_format" value="{{ $v }}" x-model="val" class="sr-only">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Time Format --}}
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-start"
                     x-data="{ val: '{{ $curTimeFmt }}' }">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ __('employee.settings_time_format') }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ __('employee.settings_time_format_desc') }}</p>
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-3">
                        @foreach(['12' => '12-hour  (2:30 PM)', '24' => '24-hour  (14:30)'] as $v => $lbl)
                        <label :class="val === '{{ $v }}' ? 'lmt-pill-radio selected' : 'lmt-pill-radio'">
                            <input type="radio" name="time_format" value="{{ $v }}" x-model="val" class="sr-only">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Theme --}}
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-start"
                     x-data="{ val: '{{ $curTheme }}' }">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ __('employee.settings_theme') }}</p>
                        <p class="text-xs text-gray-800 mt-0.5">{{ __('employee.settings_theme_desc') }}</p>
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-3">
                        @foreach(['light' => ['sun', __('employee.settings_theme_light')], 'dark' => ['moon', __('employee.settings_theme_dark')], 'system' => ['monitor', __('employee.settings_theme_system')]] as $v => [$icon,$lbl])
                        <label :class="val === '{{ $v }}' ? 'lmt-pill-radio selected' : 'lmt-pill-radio'">
                            <input type="radio" name="theme" value="{{ $v }}" x-model="val" class="sr-only">
                            <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 flex justify-end bg-gray-50 rounded-b-2xl">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Preferences
                    </button>
                </div>
            </div>
        </form>
    </div>


    {{-- ══════════════════════════════════════════════════════════════════
         TAB 2 — NOTIFICATIONS
    ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='notifications'" x-cloak data-lmt-anim="fade-up">
        <form action="{{ route('employee.settings.notifications', $tenantSlug) }}" method="POST">
            @csrf @method('PUT')

            <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100 shadow-sm">

                <div class="px-6 py-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="bell" class="w-4 h-4 text-brand-500"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Notification Preferences</h2>
                        <p class="text-xs text-gray-800">Control which events notify you and through which channel</p>
                    </div>
                </div>

                {{-- Column headers --}}
                <div class="px-6 py-2.5 flex items-center justify-between bg-gray-50">
                    <span class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Event</span>
                    <div class="flex items-center gap-6 pr-1">
                        <span class="text-xs font-semibold text-gray-800 uppercase tracking-wide w-14 text-center">In-app</span>
                        <span class="text-xs font-semibold text-gray-800 uppercase tracking-wide w-14 text-center">Email</span>
                    </div>
                </div>

                @php
                $notifRows = [
                    ['key'=>'leave_approvals',   'icon'=>'calendar-check', 'label'=>'Leave approvals',        'desc'=>'When your leave request is approved or rejected'],
                    ['key'=>'payslip_available', 'icon'=>'receipt',        'label'=>'Payslip available',      'desc'=>'When a new payslip is published for you'],
                    ['key'=>'announcements',     'icon'=>'megaphone',      'label'=>'New announcements',      'desc'=>'When the company posts a new announcement'],
                    ['key'=>'expense_updates',   'icon'=>'wallet',         'label'=>'Expense status updates', 'desc'=>'When your expense claim is approved or rejected'],
                    ['key'=>'loan_updates',      'icon'=>'piggy-bank',     'label'=>'Loan & advance updates', 'desc'=>'When your loan or advance changes status'],
                    ['key'=>'task_assignments',  'icon'=>'kanban-square',  'label'=>'Task assignments',       'desc'=>'When a task is assigned or updated for you'],
                ];
                @endphp

                @foreach($notifRows as $row)
                <div class="px-6 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ $row['icon'] }}" class="w-4 h-4 text-brand-500"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $row['label'] }}</p>
                            <p class="text-xs text-gray-800 truncate">{{ $row['desc'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 flex-shrink-0 pr-1">
                        @foreach(['in_app','email'] as $ch)
                        <div class="w-14 flex justify-center">
                            <label class="lmt-toggle">
                                <input type="hidden"   name="notif_{{ $row['key'] }}_{{ $ch }}" value="0">
                                <input type="checkbox" name="notif_{{ $row['key'] }}_{{ $ch }}" value="1"
                                       @if($user->getNotifPref($row['key'], $ch)) checked @endif>
                                <span class="lmt-toggle-track"></span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <div class="px-6 py-4 flex items-center justify-between bg-gray-50 rounded-b-2xl">
                    <p class="text-xs text-gray-800 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        Changes apply to all future notifications immediately.
                    </p>
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save
                    </button>
                </div>
            </div>
        </form>
    </div>


    {{-- ══════════════════════════════════════════════════════════════════
         TAB 3 — PRIVACY
    ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='privacy'" x-cloak data-lmt-anim="fade-up">
        <form action="{{ route('employee.settings.privacy', $tenantSlug) }}" method="POST">
            @csrf @method('PUT')

            <div class="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100 shadow-sm">

                <div class="px-6 py-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="shield" class="w-4 h-4 text-brand-500"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Privacy Settings</h2>
                        <p class="text-xs text-gray-800">Control what colleagues see about you in the company directory</p>
                    </div>
                </div>

                @php
                $privacyRows = [
                    ['key'=>'show_in_directory', 'icon'=>'users',     'label'=>'Appear in Company Directory', 'desc'=>'Allow other employees to find you in the org directory'],
                    ['key'=>'show_department',   'icon'=>'briefcase', 'label'=>'Show Department',             'desc'=>'Display your department name on your directory profile'],
                    ['key'=>'show_position',     'icon'=>'tag',       'label'=>'Show Job Title',              'desc'=>'Display your position on your directory profile'],
                    ['key'=>'show_phone',        'icon'=>'phone',     'label'=>'Show Phone Number',           'desc'=>'Make your work phone visible to colleagues'],
                    ['key'=>'show_email',        'icon'=>'mail',      'label'=>'Show Email Address',          'desc'=>'Make your work email visible to colleagues'],
                ];
                @endphp

                @foreach($privacyRows as $row)
                <div class="px-6 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ $row['icon'] }}" class="w-4 h-4 text-brand-500"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $row['label'] }}</p>
                            <p class="text-xs text-gray-800">{{ $row['desc'] }}</p>
                        </div>
                    </div>
                    <label class="lmt-toggle flex-shrink-0">
                        <input type="hidden"   name="{{ $row['key'] }}" value="0">
                        <input type="checkbox" name="{{ $row['key'] }}" value="1"
                               @if($emp->getPrivacySetting($row['key'])) checked @endif>
                        <span class="lmt-toggle-track"></span>
                    </label>
                </div>
                @endforeach

                <div class="px-6 py-3 bg-amber-50 border-t border-amber-100">
                    <p class="text-xs text-amber-700 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        HR managers and administrators can always view your full profile regardless of these settings.
                    </p>
                </div>

                <div class="px-6 py-4 flex justify-end bg-gray-50 rounded-b-2xl">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Privacy Settings
                    </button>
                </div>
            </div>
        </form>
    </div>


    {{-- ══════════════════════════════════════════════════════════════════
         TAB 4 — SECURITY
    ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab==='security'" x-cloak data-lmt-anim="fade-up" class="space-y-5">

        {{-- Last Login --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm divide-y divide-gray-100">
            <div class="px-6 py-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="activity" class="w-4 h-4 text-brand-500"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Recent Login Activity</h2>
                    <p class="text-xs text-gray-800">Your most recent account access details</p>
                </div>
            </div>

            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-green-50 border border-green-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="log-in" class="w-4 h-4 text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-800 font-medium uppercase tracking-wide">Last Login</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->setTimezone($user->timezone ?? 'UTC')->format('D, d M Y  H:i T') }}
                            @else
                                No record available
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="globe-2" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-800 font-medium uppercase tracking-wide">IP Address</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $user->last_login_ip ?? 'Unknown' }}</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 bg-gray-50 rounded-b-2xl">
                <p class="text-xs text-gray-800 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    If you don't recognise this activity, change your password immediately and contact HR.
                </p>
            </div>
        </div>

        {{-- Sign out other sessions --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm divide-y divide-gray-100"
             x-data="{ open: false }">
            <div class="px-6 py-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="log-out" class="w-4 h-4 text-orange-500"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Active Sessions</h2>
                    <p class="text-xs text-gray-800">Remotely end all other browser or device sessions</p>
                </div>
            </div>

            <div class="px-6 py-5">
                <p class="text-sm text-gray-800 mb-4">
                    If you've signed in on another device or browser, you can sign out all those sessions here. You will remain signed in on this device.
                </p>
                <button type="button" @click="open = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100 text-sm font-medium transition-colors">
                    <i data-lucide="shield-x" class="w-4 h-4"></i>
                    Sign Out Other Sessions
                </button>
            </div>

            {{-- Confirm modal --}}
            <div x-show="open" x-cloak
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                 @click.self="open = false">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-x" class="w-5 h-5 text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Sign Out Other Sessions</h3>
                            <p class="text-xs text-gray-800">Enter your password to confirm</p>
                        </div>
                    </div>
                    <form action="{{ route('employee.settings.signout-other', $tenantSlug) }}" method="POST">
                        @csrf
                        <div class="mb-5">
                            <label class="lmt-label">Current Password</label>
                            <input type="password" name="password" required autocomplete="current-password"
                                   class="lmt-input" placeholder="Enter your password">
                            @error('password')<p class="lmt-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="open = false" class="lmt-btn-secondary">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold transition-colors">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                                Sign Out Sessions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Change password shortcut --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-6 py-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="key-round" class="w-4 h-4 text-brand-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Password</p>
                        <p class="text-xs text-gray-800">Change your login password</p>
                    </div>
                </div>
                <a href="{{ route('employee.profile.index', $tenantSlug) }}#security"
                   class="lmt-btn-secondary text-sm">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    Change Password
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
