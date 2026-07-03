@extends('layouts.public')
@section('title', 'Welcome to Lockmytimes!')

@section('content')
<div class="min-h-screen lmt-mesh-bg flex items-center justify-center px-6 py-20">
  <div class="max-w-lg w-full text-center">

    {{-- Animated checkmark --}}
    <div class="w-24 h-24 rounded-full lmt-gradient-bg flex items-center justify-center mx-auto mb-8 shadow-glow animate-float">
      <i data-lucide="check" class="w-12 h-12 text-white"></i>
    </div>

    <h1 class="text-4xl font-black text-ink mb-4">You're all set! 🎉</h1>

    @if($tenant)
      <p class="text-lg text-ink-soft mb-8">
        Your <strong class="text-ink">{{ $tenant->company_name }}</strong> workspace is ready.
        We've sent your login details to <strong class="text-ink">{{ $email }}</strong>.
      </p>

      <div class="lmt-card mb-8 text-left">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
          </div>
          <div>
            <p class="font-semibold text-ink text-sm">Your Admin Portal</p>
            <p class="text-xs text-ink-soft">{{ $tenant->adminUrl() }}</p>
          </div>
        </div>
        <a href="{{ $tenant->adminUrl().'/login' }}" class="lmt-btn-primary w-full text-center">
          <i data-lucide="log-in" class="w-4 h-4"></i>
          Go to My Workspace
        </a>
      </div>

      <div class="lmt-alert-success lmt-alert">
        <i data-lucide="mail" class="w-5 h-5 shrink-0"></i>
        <span class="text-sm">Check your email at <strong>{{ $email }}</strong> for your login credentials.</span>
      </div>

    @else
      {{-- Tenant not provisioned yet (job still running) --}}
      <p class="text-lg text-ink-soft mb-8">
        We're setting up your workspace right now. This takes about 30 seconds.
        We'll email you at <strong class="text-ink">{{ $email }}</strong> the moment it's ready.
      </p>

      <div class="lmt-card mb-8">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
          </div>
          <div class="text-left">
            <p class="font-semibold text-ink text-sm">Provisioning your workspace…</p>
            <p class="text-xs text-ink-soft">Usually takes less than a minute</p>
          </div>
        </div>
      </div>

      <p class="text-sm text-ink-soft" id="refresh-msg">This page will refresh automatically.</p>

      <script>
        // Refresh up to 12 times (1 minute max), then stop
        var count = parseInt(sessionStorage.getItem('lmt_refresh') || '0');
        if (count < 12) {
          sessionStorage.setItem('lmt_refresh', count + 1);
          setTimeout(function(){ location.reload(); }, 5000);
        } else {
          document.getElementById('refresh-msg').textContent = 'Setup is taking longer than expected. Please check your email or contact support.';
        }
      </script>
    @endif

    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="{{ route('home') }}" class="lmt-btn-secondary lmt-btn-sm">
        <i data-lucide="home" class="w-4 h-4"></i>
        Back to Home
      </a>
      <a href="mailto:support@lockmytimes.com" class="lmt-btn-ghost lmt-btn-sm">
        <i data-lucide="life-buoy" class="w-4 h-4"></i>
        Contact Support
      </a>
    </div>

  </div>
</div>
@endsection