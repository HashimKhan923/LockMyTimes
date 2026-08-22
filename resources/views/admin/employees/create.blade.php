@extends('layouts.admin')
@section('title', isset($employee) ? 'Edit Employee' : 'Add Employee')
@section('page-title', isset($employee) ? 'Edit Employee' : 'Add Employee')

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- Back button --}}
    <a href="{{ route('admin.employees.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-700 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Employees
    </a>

    <form action="{{ $employee->exists ? route('admin.employees.update',[$tenant,$employee->id]) : route('admin.employees.store',$tenant) }}" method="POST" enctype="multipart/form-data">
          
        @csrf
        @if($employee->exists) @method('PUT') @endif
        {{-- Personal Info --}}
        <div class="lmt-card mb-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <h3 class="font-black text-gray-900">Personal Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Avatar --}}
                <div class="md:col-span-2 flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl overflow-hidden flex-shrink-0 border-2 border-gray-100"
                         id="avatar-preview-wrapper">
                        @if(isset($employee) && $employee->avatar)
                        <img src="{{ $employee->avatar_url }}" class="w-full h-full object-cover" id="avatar-preview"/>
                        @else
                        <div class="w-full h-full lmt-gradient-bg flex items-center justify-center text-white text-xl font-black" id="avatar-placeholder">
                            {{ isset($employee) ? substr($employee->first_name,0,1) : '?' }}
                        </div>
                        @endif
                    </div>
                    <div>
                        <label class="lmt-label">Profile Photo</label>
                        <input type="file" name="avatar" accept="image/*" class="lmt-input py-2 text-sm"
                               onchange="previewAvatar(this)"/>
                        <p class="lmt-help">JPG, PNG up to 2MB</p>
                    </div>
                </div>

                <div>
                    <label class="lmt-label">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name ?? '') }}"
                           class="lmt-input @error('first_name') lmt-input-error @enderror" required/>
                    @error('first_name')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lmt-label">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name ?? '') }}"
                           class="lmt-input @error('last_name') lmt-input-error @enderror" required/>
                    @error('last_name')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lmt-label">Work Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}"
                           class="lmt-input @error('email') lmt-input-error @enderror" required/>
                    @error('email')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lmt-label">Employee ID</label>
                    <input type="text" name="employee_code"
                           value="{{ old('employee_code', $employee->employee_code ?? $suggestedCode ?? '') }}"
                           class="lmt-input @error('employee_code') lmt-input-error @enderror"/>
                    @error('employee_code')<p class="lmt-err">{{ $message }}</p>@enderror
                    <p class="lmt-help">Auto-generated, but you can edit it. Must be unique.</p>
                </div>

                <div>
                    <label class="lmt-label">Phone</label>
                    <input type="tel" name="phone" value="{{ old('phone', $employee->phone ?? '') }}"
                           class="lmt-input" placeholder="+1 (555) 000-0000"/>
                </div>

                <div>
                    <label class="lmt-label">Date of Birth</label>
                    <input type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d') ?? '') }}"
                           class="lmt-input"/>
                </div>

                <div>
                    <label class="lmt-label">Gender</label>
                    <select name="gender" class="lmt-select">
                        <option value="">Prefer not to say</option>
                        @foreach(['male'=>'Male','female'=>'Female','non_binary'=>'Non-Binary','prefer_not_to_say'=>'Prefer not to say'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('gender', $employee->gender ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Employment Info --}}
        <div class="lmt-card mb-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                </div>
                <h3 class="font-black text-gray-900">Employment Details</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="lmt-label">Hire Date <span class="text-red-500">*</span></label>
                    <input type="date" name="hire_date"
                           value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d') ?? '') }}"
                           class="lmt-input @error('hire_date') lmt-input-error @enderror" required/>
                    @error('hire_date')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="lmt-label">Employment Status <span class="text-red-500">*</span></label>
                    <select name="employment_status" class="lmt-select" required>
                        @foreach(['active'=>'Active','on_leave'=>'On Leave','suspended'=>'Suspended','terminated'=>'Terminated'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('employment_status', $employee->employment_status ?? 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Employment Type <span class="text-red-500">*</span></label>
                    <select name="employment_type" class="lmt-select" required>
                        @foreach(['full_time'=>'Full-Time','part_time'=>'Part-Time','contract'=>'Contract','intern'=>'Intern','temporary'=>'Temporary'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('employment_type', $employee->employment_type ?? 'full_time') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Department</label>
                    <select name="department_id" class="lmt-select">
                        <option value="">— Select Department —</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}"
                                {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Position / Title</label>
                    <select name="position_id" class="lmt-select">
                        <option value="">— Select Position —</option>
                        @foreach($positions as $pos)
                        <option value="{{ $pos->id }}"
                                {{ old('position_id', $employee->position_id ?? '') == $pos->id ? 'selected' : '' }}>
                            {{ $pos->title }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Work Location <span class="text-gray-800 font-normal">(primary)</span></label>
                    <select name="location_id" class="lmt-select">
                        <option value="">— Select Location —</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}"
                                {{ old('location_id', $employee->location_id ?? '') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Employment Mode</label>
                    <select name="employment_mode" class="lmt-select">
                        @foreach(['onsite' => 'Onsite', 'remote' => 'Remote', 'hybrid' => 'Hybrid'] as $val => $label)
                        <option value="{{ $val }}"
                                {{ old('employment_mode', $employee->employment_mode ?? 'onsite') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    <p class="lmt-help">Remote employees skip geofence checks entirely and can clock in from anywhere.</p>
                </div>

                <div>
                    <label class="lmt-label">Timezone</label>
                    @php $currentTz = old('timezone', $employee->user?->timezone ?? ''); @endphp
                    <select name="timezone" class="lmt-select">
                        <option value="">— Company Default ({{ $generalTimezone }}) —</option>
                        @foreach(\DateTimeZone::listIdentifiers() as $tz)
                        <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <p class="lmt-help">
                        Used for this employee's shift times, late/overtime math, and clock-in day
                        boundaries. Leave as company default unless this person works from a different
                        timezone (e.g. a remote employee abroad). Only admins can set this — employees
                        can't change it themselves.
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="lmt-label">Additional Locations <span class="text-gray-800 font-normal">(optional — for employees splitting time across branches)</span></label>
                    <div class="flex flex-wrap gap-3 mt-1">
                        @php $assignedIds = old('location_ids', $employee->exists ? $employee->locations->pluck('id')->all() : []); @endphp
                        @foreach($locations as $loc)
                        <label class="flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-gray-200 cursor-pointer">
                            <input type="checkbox" name="location_ids[]" value="{{ $loc->id }}"
                                   {{ in_array($loc->id, $assignedIds) ? 'checked' : '' }}>
                            {{ $loc->name }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="lmt-label">Direct Manager</label>
                    <select name="manager_id" class="lmt-select">
                        <option value="">— No Manager —</option>
                        @foreach($managers as $mgr)
                        <option value="{{ $mgr->id }}"
                                {{ old('manager_id', $employee->manager_id ?? '') == $mgr->id ? 'selected' : '' }}>
                            {{ $mgr->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="lmt-label">Probation End Date</label>
                    <input type="date" name="probation_end_date"
                           value="{{ old('probation_end_date', $employee->probation_end_date?->format('Y-m-d') ?? '') }}"
                           class="lmt-input"/>
                </div>
            </div>
        </div>

        {{-- Compensation --}}
        <div class="lmt-card mb-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                </div>
                <h3 class="font-black text-gray-900">Compensation</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="lmt-label">Base Salary / Rate</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-800 font-semibold text-sm">$</span>
                        <input type="number" name="base_salary" step="0.01" min="0"
                               value="{{ old('base_salary', $employee->base_salary ?? '') }}"
                               class="lmt-input pl-7"/>
                    </div>
                </div>
                <div>
                    <label class="lmt-label">Pay Frequency</label>
                    <select name="salary_frequency" class="lmt-select">
                        @foreach(['hourly'=>'Hourly','weekly'=>'Weekly','bi_weekly'=>'Bi-Weekly','semi_monthly'=>'Semi-Monthly','monthly'=>'Monthly','annually'=>'Annually'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('salary_frequency', $employee->salary_frequency ?? 'annually') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.employees.index', $tenant) }}" class="lmt-btn-secondary">Cancel</a>
            <button type="submit" class="lmt-btn-primary">
                <i data-lucide="{{ isset($employee) ? 'save' : 'user-plus' }}" class="w-4 h-4"></i>
                {{ isset($employee) ? 'Save Changes' : 'Add Employee' }}
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const wrapper = document.getElementById('avatar-preview-wrapper');
            wrapper.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"/>`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush