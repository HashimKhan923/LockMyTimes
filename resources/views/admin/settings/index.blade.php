@extends('layouts.admin')
@section('title','Settings')
@section('page-title','Settings')

@section('content')

<div class="flex gap-6 items-start">

    {{-- =====================================================
         LEFT SIDEBAR NAV
    ===================================================== --}}
    <div class="w-56 flex-shrink-0">
        <div class="lmt-card p-0 overflow-hidden">
            @foreach([
                'general'      => ['building-2', 'General'],
                'branding'     => ['palette',    'Branding'],
                'attendance'   => ['clock',      'Attendance'],
                'payroll'      => ['dollar-sign','Payroll'],
                'tax'          => ['percent',    'Tax Settings'],
                'leaves'       => ['calendar-off','Leave Policy'],
                'notifications'=> ['bell',       'Notifications'],
                'email'        => ['mail',       'Email Templates'],
                'integrations' => ['plug',       'Integrations'],
            ] as $key => [$icon, $label])
            <a href="{{ route('admin.settings.index', $tenant) }}?tab={{ $key }}"
               class="flex items-center gap-3 px-4 py-3 text-sm font-semibold border-b border-gray-50 last:border-none transition-colors
                      {{ $tab === $key ? 'bg-brand-50 text-brand-700 border-l-[3px] border-l-brand-500' : 'text-gray-600 hover:bg-gray-50' }}">
                <i data-lucide="{{ $icon }}" class="w-4 h-4 flex-shrink-0"></i>
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- =====================================================
         RIGHT CONTENT
    ===================================================== --}}
    <div class="flex-1 min-w-0">

        {{-- ===== GENERAL ===== --}}
        @if($tab === 'general')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">General Settings</h2>
                    <p class="text-xs text-gray-800">Organization information, regional, and date/time preferences</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update', [$tenant, 'general']) }}" method="POST" class="space-y-5">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 gap-5">
                    <div class="col-span-2">
                        <label class="lmt-label">Organization Name</label>
                        <input type="text" name="name" class="lmt-input" value="{{ $general['name'] ?? '' }}"/>
                    </div>
                    <div>
                        <label class="lmt-label">Currency</label>
                        <select name="currency" class="lmt-select">
                            @foreach(['USD'=>'USD - US Dollar','EUR'=>'EUR - Euro','GBP'=>'GBP - British Pound','CAD'=>'CAD - Canadian Dollar','AUD'=>'AUD - Australian Dollar','PKR'=>'PKR - Pakistani Rupee','INR'=>'INR - Indian Rupee'] as $v=>$l)
                            <option value="{{ $v }}" {{ ($general['currency'] ?? 'USD') === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Timezone</label>
                        <select name="timezone" class="lmt-select">
                            @foreach(['America/New_York'=>'Eastern Time (US)','America/Chicago'=>'Central Time (US)','America/Denver'=>'Mountain Time (US)','America/Los_Angeles'=>'Pacific Time (US)','Europe/London'=>'London','Asia/Karachi'=>'Karachi','Asia/Dubai'=>'Dubai','Asia/Kolkata'=>'India'] as $v=>$l)
                            <option value="{{ $v }}" {{ ($general['timezone'] ?? '') === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Date Format</label>
                        <select name="date_format" class="lmt-select">
                            @foreach(['M d, Y'=>'Jun 17, 2026','d/m/Y'=>'17/06/2026','m/d/Y'=>'06/17/2026','Y-m-d'=>'2026-06-17'] as $v=>$l)
                            <option value="{{ $v }}" {{ ($general['date_format'] ?? '') === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Time Format</label>
                        <select name="time_format" class="lmt-select">
                            <option value="h:i A" {{ ($general['time_format'] ?? '') === 'h:i A' ? 'selected':'' }}>12-hour (2:30 PM)</option>
                            <option value="H:i" {{ ($general['time_format'] ?? '') === 'H:i' ? 'selected':'' }}>24-hour (14:30)</option>
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Week Starts On</label>
                        <select name="week_starts_on" class="lmt-select">
                            @foreach(['monday'=>'Monday','sunday'=>'Sunday','saturday'=>'Saturday'] as $v=>$l)
                            <option value="{{ $v }}" {{ ($general['week_starts_on'] ?? '') === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Fiscal Year Start (MM-DD)</label>
                        <input type="text" name="fiscal_year_start" class="lmt-input" placeholder="01-01"
                               value="{{ $general['fiscal_year_start'] ?? '' }}"/>
                    </div>
                    <div>
                        <label class="lmt-label">Employee ID Prefix</label>
                        <input type="text" name="employee_code_prefix" class="lmt-input" placeholder="EMP-"
                               value="{{ $general['employee_code_prefix'] ?? 'EMP-' }}"/>
                        <p class="lmt-help mt-1">e.g. "EMP-", "WORKER-", "SK-"</p>
                    </div>
                    <div>
                        <label class="lmt-label">Employee ID Digits</label>
                        <select name="employee_code_padding" class="lmt-select">
                            @foreach([3,4,5,6] as $digits)
                            <option value="{{ $digits }}" {{ (int) ($general['employee_code_padding'] ?? 4) === $digits ? 'selected':'' }}>
                                {{ $digits }} digits (e.g. {{ ($general['employee_code_prefix'] ?? 'EMP-').str_pad('1', $digits, '0', STR_PAD_LEFT) }})
                            </option>
                            @endforeach
                        </select>
                        <p class="lmt-help mt-1">Applies to new employees only — existing IDs are not renumbered.</p>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- ===== BRANDING ===== --}}
        @elseif($tab === 'branding')
        <div class="space-y-6">
            <div class="lmt-card">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-gray-900">Logo & Favicon</h2>
                        <p class="text-xs text-gray-800">Upload your company branding assets</p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.branding.update', $tenant) }}" method="POST"
                      enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="lmt-label">Company Logo</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-brand-300 transition-colors">
                                @if($currentTenant->logo)
                                <img src="{{ asset('storage/'.$currentTenant->logo) }}" class="h-16 mx-auto mb-3 object-contain"/>
                                @else
                                <i data-lucide="image" class="w-10 h-10 text-gray-800 mx-auto mb-3"></i>
                                @endif
                                <input type="file" name="logo" accept="image/*" class="text-sm w-full"/>
                                <p class="lmt-help mt-2">Recommended: 200×60px PNG with transparent background</p>
                            </div>
                        </div>
                        <div>
                            <label class="lmt-label">Favicon</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-6 text-center hover:border-brand-300 transition-colors">
                                @if($currentTenant->favicon)
                                <img src="{{ asset('storage/'.$currentTenant->favicon) }}" class="h-10 w-10 mx-auto mb-3 object-contain"/>
                                @else
                                <i data-lucide="image" class="w-10 h-10 text-gray-800 mx-auto mb-3"></i>
                                @endif
                                <input type="file" name="favicon" accept="image/*" class="text-sm w-full"/>
                                <p class="lmt-help mt-2">Recommended: 32×32px square PNG</p>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        Upload Branding
                    </button>
                </form>
            </div>

            <div class="lmt-card">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="palette" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-gray-900">Theme Colors</h2>
                        <p class="text-xs text-gray-800">Customize your portal's color scheme</p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.update', [$tenant, 'theme']) }}" method="POST" class="space-y-5">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="lmt-label">Primary Color</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="primary_color" id="primary-color-input"
                                       value="{{ $theme['primary_color'] ?? '#6C7DF7' }}"
                                       class="w-14 h-14 rounded-xl cursor-pointer border border-gray-200"/>
                                <input type="text" id="primary-color-text" value="{{ $theme['primary_color'] ?? '#6C7DF7' }}"
                                       class="lmt-input flex-1" readonly/>
                            </div>
                        </div>
                        <div>
                            <label class="lmt-label">Secondary Color</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="secondary_color" id="secondary-color-input"
                                       value="{{ $theme['secondary_color'] ?? '#FFB547' }}"
                                       class="w-14 h-14 rounded-xl cursor-pointer border border-gray-200"/>
                                <input type="text" id="secondary-color-text" value="{{ $theme['secondary_color'] ?? '#FFB547' }}"
                                       class="lmt-input flex-1" readonly/>
                            </div>
                        </div>
                    </div>
                    {{-- Live preview --}}
                    <div class="p-4 rounded-2xl border border-gray-100 flex items-center gap-3">
                        <div id="color-preview-badge" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black"
                             style="background:linear-gradient(135deg,{{ $theme['primary_color'] ?? '#6C7DF7' }},{{ $theme['secondary_color'] ?? '#FFB547' }})">
                            A
                        </div>
                        <p class="text-sm text-gray-800">Live preview of your brand gradient</p>
                    </div>
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Theme
                    </button>
                </form>
            </div>
        </div>

        {{-- ===== ATTENDANCE ===== --}}
        @elseif($tab === 'attendance')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">Attendance Settings</h2>
                    <p class="text-xs text-gray-800">Clock-in methods, lateness, and overtime rules</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update', [$tenant, 'attendance']) }}" method="POST" class="space-y-5">
                @csrf @method('PATCH')

                <p class="text-xs font-bold text-gray-800 uppercase tracking-wider">Clock-In Methods</p>
                <div class="grid grid-cols-3 gap-3">
                    @foreach([
                        'allow_qr'     => ['QR Code Scan', 'qr-code'],
                        'allow_web'    => ['Web Portal',   'monitor'],
                        'allow_mobile' => ['Mobile App',   'smartphone'],
                    ] as $field => [$label, $icon])
                    <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors
                                  {{ ($attendance[$field] ?? false) ? 'border-brand-400 bg-brand-50' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="checkbox" name="{{ $field }}" value="1" class="w-4 h-4 rounded"
                               {{ ($attendance[$field] ?? false) ? 'checked' : '' }}/>
                        <i data-lucide="{{ $icon }}" class="w-4 h-4 text-gray-800"></i>
                        <span class="text-sm font-semibold text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>

                <p class="text-xs font-bold text-gray-800 uppercase tracking-wider pt-2">Verification & Rules</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center justify-between p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-sm font-medium text-gray-700">Strict Geofencing</span>
                        <input type="checkbox" name="geofence_strict" value="1" class="w-4 h-4 rounded"
                               {{ ($attendance['geofence_strict'] ?? false) ? 'checked' : '' }}/>
                    </label>
                    <label class="col-span-2 flex items-start justify-between gap-4 p-3 rounded-xl border border-indigo-200 bg-indigo-50/50 hover:bg-indigo-50 transition-colors cursor-pointer">
                        <div>
                            <span class="text-sm font-semibold text-gray-800">Enforce Shift Window</span>
                            <p class="text-xs text-gray-800 mt-0.5">Employees can only clock in during their assigned shift hours. Clock-in outside the shift window will be blocked.</p>
                        </div>
                        <input type="checkbox" name="shift_window_strict" value="1" class="w-4 h-4 rounded mt-0.5 shrink-0"
                               {{ ($attendance['shift_window_strict'] ?? true) ? 'checked' : '' }}/>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-5 pt-2">
                    <div>
                        <label class="lmt-label">Late Grace Period (minutes)</label>
                        <input type="number" name="late_grace_minutes" min="0" max="120" class="lmt-input"
                               value="{{ $attendance['late_grace_minutes'] ?? 10 }}"/>
                        <p class="lmt-help">Employees clocking in within this window are not marked late.</p>
                    </div>
                    <div>
                        <label class="lmt-label">Early Clock-In Buffer (minutes)</label>
                        <input type="number" name="early_clockin_minutes" min="0" max="120" class="lmt-input"
                               value="{{ $attendance['early_clockin_minutes'] ?? 30 }}"/>
                        <p class="lmt-help">How many minutes before shift start clock-in is allowed.</p>
                    </div>
                    <div>
                        <label class="lmt-label">Early Check-Out Grace (minutes)</label>
                        <input type="number" name="early_out_grace_minutes" min="0" max="120" class="lmt-input"
                               value="{{ $attendance['early_out_grace_minutes'] ?? 10 }}"/>
                        <p class="lmt-help">Employees clocking out within this window before shift end are not marked as early out.</p>
                    </div>
                    <div>
                        <label class="lmt-label">Daily Overtime Threshold (hours)</label>
                        <input type="number" name="overtime_threshold" min="0" max="80" class="lmt-input"
                               value="{{ $attendance['overtime_threshold'] ?? 8 }}"/>
                        <p class="lmt-help">Hours worked beyond this per day count as overtime.</p>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Attendance Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- ===== PAYROLL ===== --}}
        @elseif($tab === 'payroll')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">Payroll Configuration</h2>
                    <p class="text-xs text-gray-800">Pay schedule, overtime, FICA, and mileage rates</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update', [$tenant, 'payroll']) }}" method="POST" class="space-y-5">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="lmt-label">Pay Schedule</label>
                        <select name="pay_schedule" class="lmt-select">
                            @foreach(['weekly'=>'Weekly','bi_weekly'=>'Bi-Weekly','semi_monthly'=>'Semi-Monthly','monthly'=>'Monthly'] as $v=>$l)
                            <option value="{{ $v }}" {{ ($payroll['pay_schedule'] ?? '') === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lmt-label">Overtime Multiplier</label>
                        <div class="relative">
                            <input type="text" name="overtime_rate" class="lmt-input" value="{{ $payroll['overtime_rate'] ?? '1.5' }}"/>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-800">×</span>
                        </div>
                    </div>

                    <div class="col-span-2 pt-2">
                        <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">FICA Rates (US)</p>
                    </div>
                    <div>
                        <label class="lmt-label">Social Security Rate</label>
                        <div class="relative">
                            <input type="text" name="fica_ss_rate" class="lmt-input" value="{{ $payroll['fica_ss_rate'] ?? '6.2' }}"/>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-800">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="lmt-label">Medicare Rate</label>
                        <div class="relative">
                            <input type="text" name="fica_medicare_rate" class="lmt-input" value="{{ $payroll['fica_medicare_rate'] ?? '1.45' }}"/>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-800">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="lmt-label">Social Security Wage Base ($)</label>
                        <input type="text" name="ss_wage_base" class="lmt-input" value="{{ $payroll['ss_wage_base'] ?? '168600' }}"/>
                        <p class="lmt-help">Annual income cap above which SS tax no longer applies.</p>
                    </div>
                    <div>
                        <label class="lmt-label">Mileage Reimbursement Rate ($/mile)</label>
                        <input type="text" name="mileage_rate" class="lmt-input" value="{{ $payroll['mileage_rate'] ?? '0.67' }}"/>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Payroll Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- ===== TAX SETTINGS ===== --}}
        @elseif($tab === 'tax')
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                        <i data-lucide="percent" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-gray-900">Tax Settings</h2>
                        <p class="text-xs text-gray-800">Federal, state, and local tax configurations by year</p>
                    </div>
                </div>
                <button onclick="openModal('add-tax-modal')" class="lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Tax Rule
                </button>
            </div>

            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Type</th>
                        <th>State</th>
                        <th>Rate / Brackets</th>
                        <th>Wage Base</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxSettings as $tax)
                    <tr>
                        <td class="font-bold text-gray-900 text-sm">{{ $tax->year }}</td>
                        <td class="text-sm text-gray-700">{{ $tax->tax_type_label }}</td>
                        <td class="text-sm text-gray-800">{{ $tax->state ?? '—' }}</td>
                        <td class="text-sm text-gray-700">
                            @if($tax->flat_rate)
                                {{ $tax->flat_rate }}%
                            @elseif($tax->brackets)
                                <span class="lmt-badge-brand text-xs">{{ count($tax->brackets) }} brackets</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-sm text-gray-600">{{ $tax->wage_base ? '$'.number_format($tax->wage_base,0) : '—' }}</td>
                        <td>
                            <span class="{{ $tax->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                                {{ $tax->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('admin.settings.tax.destroy', [$tenant, $tax->id]) }}" method="POST"
                                  onsubmit="return confirm('Delete this tax setting?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-12 text-gray-800">No tax settings configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== LEAVE POLICY ===== --}}
        @elseif($tab === 'leaves')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="calendar-off" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">Leave Policy</h2>
                    <p class="text-xs text-gray-800">Approval rules and balance handling</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update', [$tenant, 'leaves']) }}" method="POST" class="space-y-5">
                @csrf @method('PATCH')
                <div>
                    <label class="lmt-label">Auto-Approve Requests Under (days)</label>
                    <input type="number" name="auto_approve_under_days" min="0" max="30" class="lmt-input"
                           value="{{ $leaves['auto_approve_under_days'] ?? 0 }}"/>
                    <p class="lmt-help">Set to 0 to disable auto-approval — all requests require manual approval.</p>
                </div>
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Allow Negative Leave Balance</p>
                        <p class="text-xs text-gray-800 mt-0.5">Employees can request leave even with insufficient balance</p>
                    </div>
                    <input type="checkbox" name="allow_negative_balance" value="1" class="w-4 h-4 rounded"
                           {{ ($leaves['allow_negative_balance'] ?? false) ? 'checked' : '' }}/>
                </label>
                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Leave Policy
                    </button>
                </div>
            </form>
        </div>

        {{-- ===== NOTIFICATIONS ===== --}}
        @elseif($tab === 'notifications')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">Notification Channels</h2>
                    <p class="text-xs text-gray-800">Choose how the system communicates with employees</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update', [$tenant, 'notifications']) }}" method="POST" class="space-y-3">
                @csrf @method('PATCH')
                @foreach([
                    'email_enabled' => ['Email Notifications', 'Send updates and alerts via email', 'mail'],
                    'sms_enabled'   => ['SMS Notifications',   'Send time-sensitive alerts via text message', 'message-square'],
                    'push_enabled'  => ['Push Notifications',  'Send real-time alerts to the mobile app', 'smartphone'],
                ] as $field => [$title, $desc, $icon])
                <label class="flex items-center justify-between p-4 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ $icon }}" class="w-4 h-4 text-gray-800"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">{{ $title }}</p>
                            <p class="text-xs text-gray-800">{{ $desc }}</p>
                        </div>
                    </div>
                    <input type="checkbox" name="{{ $field }}" value="1" class="w-5 h-5 rounded"
                           {{ ($notifications[$field] ?? false) ? 'checked' : '' }}/>
                </label>
                @endforeach
                <div class="pt-3">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Notification Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- ===== EMAIL TEMPLATES ===== --}}
        @elseif($tab === 'email')
        <div class="space-y-4">
            @forelse($emailTemplates as $template)
            <div class="lmt-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $template->name }}</p>
                            <p class="text-xs text-gray-800">{{ $template->category ?? 'General' }} · <code class="text-[10px]">{{ $template->slug }}</code></p>
                        </div>
                    </div>
                    <span class="{{ $template->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                        {{ $template->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                <form action="{{ route('admin.settings.email.update', [$tenant, $template->id]) }}" method="POST" class="space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="lmt-label text-xs">Subject Line</label>
                        <input type="text" name="subject" class="lmt-input" value="{{ $template->subject }}"/>
                    </div>
                    <div>
                        <label class="lmt-label text-xs">Body</label>
                        <textarea name="body" rows="5" class="lmt-textarea font-mono text-xs">{{ $template->body }}</textarea>
                        @if(!empty($template->variables))
                        <p class="lmt-help">
                            Available variables:
                            @foreach($template->variables as $var)
                            <code class="bg-gray-100 px-1.5 py-0.5 rounded text-[10px] mr-1">{{ '{{' . $var . '}' . '}' }}</code>
                            @endforeach
                        </p>
                        @endif
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="w-4 h-4 rounded"
                                   {{ $template->is_active ? 'checked' : '' }}/>
                            <span class="text-xs font-medium text-gray-600">Template Active</span>
                        </label>
                        <button type="submit" class="lmt-btn-secondary lmt-btn-sm">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            Save Template
                        </button>
                    </div>
                </form>
            </div>
            @empty
            <div class="lmt-card text-center py-16">
                <i data-lucide="mail" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
                <p class="font-black text-gray-800">No email templates found</p>
            </div>
            @endforelse
        </div>

        {{-- ===== INTEGRATIONS ===== --}}
        @elseif($tab === 'integrations')
        <div class="lmt-card">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="plug" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-black text-gray-900">Payroll Relief (AccountantsWorld)</h2>
                    <p class="text-xs text-gray-800">Optional — pull finished payslips from your Payroll Relief account into LockMyTimes</p>
                </div>
                @if($payrollIntegration && $payrollIntegration->status === 'connected')
                    <span class="lmt-badge-green text-xs ml-auto">Connected</span>
                @elseif($payrollIntegration && $payrollIntegration->status === 'error')
                    <span class="lmt-badge-red text-xs ml-auto">Connection error</span>
                @else
                    <span class="lmt-badge-gray text-xs ml-auto">Not connected</span>
                @endif
            </div>

            <p class="text-sm text-gray-800 mb-6">
                This is entirely optional. If you don't connect anything here, your team keeps using LockMyTimes's
                own built-in payroll system exactly as before — nothing changes. Connecting only adds the ability
                to automatically pull payslips already finalized in Payroll Relief into LockMyTimes so employees
                can see them in the app.
            </p>

            @if($payrollIntegration && $payrollIntegration->status === 'error' && $payrollIntegration->last_error)
            <div class="rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3 mb-6">
                {{ $payrollIntegration->last_error }}
            </div>
            @endif

            @if($payrollIntegration && $payrollIntegration->isConnected())
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-xs text-gray-800 font-semibold uppercase tracking-wider mb-1">Last synced</p>
                        <p class="font-bold text-gray-900 text-sm">
                            {{ $payrollIntegration->last_synced_at?->format('M j, Y g:i A') ?? 'Never yet' }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-xs text-gray-800 font-semibold uppercase tracking-wider mb-1">Imported so far</p>
                        <p class="font-bold text-gray-900 text-sm">
                            {{ $payrollIntegration->employees_synced }} employee(s) · {{ $payrollIntegration->payslips_synced }} payslip(s)
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mb-8">
                    <form action="{{ route('admin.settings.integrations.payrollRelief.sync', $tenant) }}" method="POST">
                        @csrf
                        <button type="submit" class="lmt-btn-primary">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            Sync Now
                        </button>
                    </form>
                    <form action="{{ route('admin.settings.integrations.payrollRelief.disconnect', $tenant) }}" method="POST"
                          onsubmit="return confirm('Disconnect Payroll Relief? You can reconnect any time with your API key.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="lmt-btn-secondary">
                            <i data-lucide="unlink" class="w-4 h-4"></i>
                            Disconnect
                        </button>
                    </form>
                </div>
            @endif

            <form action="{{ route('admin.settings.integrations.payrollRelief.connect', $tenant) }}" method="POST" class="space-y-5 border-t border-gray-100 pt-6">
                @csrf
                <div>
                    <label class="lmt-label">Payroll Relief API Key</label>
                    <input type="password" name="api_key" class="lmt-input" autocomplete="off"
                           placeholder="{{ $payrollIntegration?->isConnected() ? 'Enter a new key to replace the saved one' : 'Paste your API key' }}"/>
                    <p class="lmt-help">
                        Get this from AccountantsWorld after they enable the Payroll Relief Open API for your account
                        (contact 877-840-6122 if you haven't been onboarded yet).
                    </p>
                </div>
                <div>
                    <label class="lmt-label">API Base URL <span class="text-gray-800 font-normal">(optional — only if AccountantsWorld gave you a different one)</span></label>
                    <input type="url" name="base_url" class="lmt-input" placeholder="https://api.accountantsworld.com/payrollrelief/v1"
                           value="{{ $payrollIntegration?->base_url }}"/>
                </div>
                <div class="pt-2">
                    <button type="submit" class="lmt-btn-primary">
                        <i data-lucide="plug" class="w-4 h-4"></i>
                        {{ $payrollIntegration?->isConnected() ? 'Update & Reconnect' : 'Connect' }}
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- ============================================================
     ADD TAX SETTING MODAL
============================================================ --}}
<div id="add-tax-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Add Tax Setting</h3>
            <button onclick="closeModal('add-tax-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.settings.tax.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Year <span class="text-red-500">*</span></label>
                    <input type="number" name="year" required min="2020" max="2100" class="lmt-input" value="{{ $currentYear }}"/>
                </div>
                <div>
                    <label class="lmt-label">Tax Type <span class="text-red-500">*</span></label>
                    <select name="tax_type" required class="lmt-select">
                        @foreach(['federal_income'=>'Federal Income','state_income'=>'State Income','fica_ss'=>'FICA Social Security','fica_medicare'=>'FICA Medicare','futa'=>'FUTA','suta'=>'SUTA','sdi'=>'SDI','local'=>'Local Tax'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">State (if applicable)</label>
                    <input type="text" name="state" class="lmt-input" placeholder="e.g. TX, CA"/>
                </div>
                <div>
                    <label class="lmt-label">Flat Rate (%)</label>
                    <input type="number" name="flat_rate" step="0.0001" min="0" max="100" class="lmt-input" placeholder="6.2000"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Wage Base ($)</label>
                    <input type="number" name="wage_base" step="0.01" min="0" class="lmt-input" placeholder="168600.00"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Brackets (JSON, optional — for progressive tax)</label>
                    <textarea name="brackets" rows="3" class="lmt-textarea font-mono text-xs"
                              placeholder='[{"min":0,"max":10000,"rate":10},{"min":10001,"max":40000,"rate":12}]'></textarea>
                    <p class="lmt-help">Leave blank if using a flat rate instead.</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Add Tax Setting</button>
                <button type="button" onclick="closeModal('add-tax-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
document.getElementById('add-tax-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('add-tax-modal'); });

// Live color sync for theme tab
['primary','secondary'].forEach(name => {
    const colorInput = document.getElementById(`${name}-color-input`);
    const textInput  = document.getElementById(`${name}-color-text`);
    colorInput?.addEventListener('input', () => {
        textInput.value = colorInput.value;
        const badge = document.getElementById('color-preview-badge');
        if (badge) {
            const primary   = document.getElementById('primary-color-input').value;
            const secondary = document.getElementById('secondary-color-input').value;
            badge.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
        }
    });
});
</script>
@endpush