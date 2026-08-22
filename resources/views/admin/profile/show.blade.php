@extends('layouts.admin')
@section('title','My Profile')
@section('page-title','My Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Avatar + name header --}}
    <div class="lmt-card flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-black flex-shrink-0"
             style="background:linear-gradient(135deg,var(--brand-500),var(--brand-600));">
            {{ substr($user->name, 0, 1) }}
        </div>
        <div>
            <p class="text-xl font-black text-gray-900 dark:text-slate-100">{{ $user->name }}</p>
            <p class="text-sm text-gray-800">{{ $user->email }}</p>
            <p class="text-xs text-gray-800 mt-0.5">
                @foreach($user->getRoleNames() as $role)
                    <span class="lmt-badge-brand mr-1">{{ str_replace('Tenant Admin', 'Organization Admin', $role) }}</span>
                @endforeach
            </p>
        </div>
    </div>

    {{-- Edit profile --}}
    <div class="lmt-card">
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:var(--brand-50);color:var(--brand-600)">
                <i data-lucide="user" class="w-4 h-4"></i>
            </div>
            <h3 class="font-black text-gray-900 dark:text-slate-100">Account Information</h3>
        </div>

        <form action="{{ route('admin.profile.update', $tenant) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="lmt-input" required/>
                    @error('name')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lmt-label">Email</label>
                    <input type="email" value="{{ $user->email }}" class="lmt-input opacity-60 cursor-not-allowed" disabled/>
                    <p class="lmt-help">Email cannot be changed here.</p>
                </div>
                <div>
                    <label class="lmt-label">Phone</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="lmt-input" placeholder="+1 (555) 000-0000"/>
                </div>
                <div>
                    <label class="lmt-label">Locale</label>
                    <select name="locale" class="lmt-input">
                        @foreach(['en'=>'English','es'=>'Spanish','fr'=>'French','de'=>'German','ar'=>'Arabic'] as $code=>$label)
                        <option value="{{ $code }}" {{ old('locale', $user->locale ?? 'en') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Change password --}}
    <div class="lmt-card">
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100 dark:border-slate-700">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="lock" class="w-4 h-4"></i>
            </div>
            <h3 class="font-black text-gray-900 dark:text-slate-100">Change Password</h3>
        </div>

        <form action="{{ route('admin.profile.password', $tenant) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')

            <div>
                <label class="lmt-label">Current Password</label>
                <input type="password" name="current_password" class="lmt-input" required/>
                @error('current_password')<p class="lmt-err">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">New Password</label>
                    <input type="password" name="password" class="lmt-input" required/>
                    @error('password')<p class="lmt-err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="lmt-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="lmt-input" required/>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="lmt-btn-primary">
                    <i data-lucide="key" class="w-4 h-4"></i>
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush
