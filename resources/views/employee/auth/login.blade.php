@extends('layouts.auth')

@section('title', 'Employee Login — '.$tenantModel->company_name)

@section('auth-panel')
<div class="text-white">
    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6 overflow-hidden">
        @if($tenantModel->logo)
            <img src="{{ $tenantModel->logo_url }}" alt="{{ $tenantModel->company_name }}" class="w-full h-full object-cover"/>
        @else
            <span class="text-2xl font-black text-white">{{ substr($tenantModel->company_name,0,1) }}</span>
        @endif
    </div>

    <h2 class="text-3xl font-black mb-3 leading-tight" style="font-family:'Nunito',sans-serif">
        {{ $tenantModel->company_name }}
    </h2>
    <p class="text-white/75 mb-8 text-sm leading-relaxed">
        Employee Self-Service Portal
    </p>

    <div class="space-y-4">
        @foreach([
            ['icon'=>'qr-code','label'=>'Clock in & out via QR'],
            ['icon'=>'calendar','label'=>'Apply for leave'],
            ['icon'=>'file-text','label'=>'View your payslips'],
            ['icon'=>'kanban','label'=>'Track your tasks'],
        ] as $item)
        <div class="flex items-center gap-3 text-white/80">
            <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-white"></i>
            </div>
            <span class="text-sm font-medium">{{ $item['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endsection

@section('form')
<div class="mb-8">
    <div class="inline-flex items-center gap-2 lmt-badge-green mb-4">
        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
        Employee Portal
    </div>
    <h1 class="text-2xl font-black text-ink mb-2" style="font-family:'Nunito',sans-serif">
        Good to see you!
    </h1>
    <p class="text-ink-soft text-sm">
        Sign in to <strong>{{ $tenantModel->company_name }}</strong>'s employee portal
    </p>
</div>

@if(session('error'))
<div class="lmt-alert lmt-alert-error mb-6">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
    <span class="text-sm">{{ session('error') }}</span>
</div>
@endif

<form action="{{ route('employee.login.post', $tenantModel->slug) }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label class="lmt-label">Work Email</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="mail" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="lmt-input pl-10" placeholder="you@company.com" autofocus required/>
        </div>
    </div>

    <div>
        <label class="lmt-label">Password</label>
        <div class="relative" x-data="{ show: false }">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="lock" class="w-4 h-4 text-gray-800"></i>
            </div>
            <input :type="show ? 'text' : 'password'" name="password"
                   class="lmt-input pl-10 pr-10" placeholder="••••••••" required/>
            <button type="button" @click="show=!show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-800 hover:text-gray-600">
                <i :data-lucide="show ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300"/>
        <span class="text-sm text-gray-600">Keep me signed in</span>
    </label>

    <button type="submit" class="lmt-btn-primary w-full lmt-btn-lg" style="background:linear-gradient(135deg,#10B981,#059669);">
        <i data-lucide="log-in" class="w-5 h-5"></i>
        Sign In
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-800">
        Are you an HR manager or admin?
        <a href="{{ route('admin.login', $tenantModel->slug) }}"
           class="font-semibold text-brand-600 hover:text-brand-700 transition-colors">
            Use the Admin Portal
        </a>
    </p>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 100);
});
</script>
@endpush