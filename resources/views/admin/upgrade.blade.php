@extends('layouts.admin')
@section('title', 'Upgrade Your Plan')
@section('page-title', 'Upgrade Your Plan')

@section('content')

{{-- Locked module banner --}}
@if($lockedModule)
<div class="mb-6 rounded-2xl p-5 flex items-start gap-4"
     style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);">
    <div class="text-amber-600 mt-0.5">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <div>
        <p class="font-bold text-amber-800 text-base">
            The <span class="capitalize">{{ str_replace('_', ' ', $lockedModule) }}</span> module is locked
        </p>
        <p class="text-amber-700 text-sm mt-0.5">
            This feature is not available on your current plan. Upgrade to unlock it.
        </p>
    </div>
</div>
@endif

{{-- Header --}}
<div class="text-center mb-10">
    <h2 class="text-3xl font-black text-gray-900">Choose the right plan for your team</h2>
    <p class="text-gray-500 mt-2 text-base">Upgrade at any time. Changes take effect immediately.</p>
    @if($currentPlan)
        <p class="text-sm text-indigo-600 font-medium mt-1">
            Current plan: <span class="capitalize font-bold">{{ $currentPlan }}</span>
        </p>
    @endif
</div>

{{-- Plan cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
    @foreach($plans as $plan)
    @php
        $isCurrent = $currentPlan === $plan->slug;
        $modules = [
            'attendance'  => $plan->has_attendance,
            'payroll'     => $plan->has_payroll,
            'performance' => $plan->has_performance,
            'assets'      => $plan->has_assets,
            'training'    => $plan->has_training,
            'recruitment' => $plan->has_recruitment,
            // 'documents' hidden per client request (2026-08)
            'expenses'    => $plan->has_expenses,
        ];
    @endphp
    <div class="rounded-2xl border-2 p-6 flex flex-col relative
        {{ $plan->is_featured ? 'border-indigo-500 shadow-xl shadow-indigo-100' : 'border-gray-200 shadow-sm' }}
        {{ $isCurrent ? 'bg-indigo-50' : 'bg-white' }}">

        @if($plan->is_featured)
        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
            <span class="bg-indigo-600 text-white text-xs font-bold px-4 py-1 rounded-full">Most Popular</span>
        </div>
        @endif

        @if($isCurrent)
        <div class="absolute top-4 right-4">
            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">Current Plan</span>
        </div>
        @endif

        <div class="mb-4">
            <h3 class="text-xl font-black text-gray-900">{{ $plan->name }}</h3>
            <p class="text-gray-500 text-sm mt-1">{{ $plan->description }}</p>
        </div>

        <div class="mb-6">
            <span class="text-4xl font-black text-gray-900">${{ number_format($plan->monthly_price, 0) }}</span>
            <span class="text-gray-500 text-sm">/month</span>
        </div>

        {{-- Limits --}}
        <div class="flex gap-6 mb-6 p-4 rounded-xl bg-gray-50">
            <div class="text-center">
                <p class="text-2xl font-black text-gray-900">
                    {{ $plan->max_employees >= 9999 ? '∞' : $plan->max_employees }}
                </p>
                <p class="text-xs text-gray-500">employees</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-black text-gray-900">{{ $plan->max_admins }}</p>
                <p class="text-xs text-gray-500">admins</p>
            </div>
        </div>

        {{-- Module list --}}
        <ul class="space-y-2 mb-8 flex-1">
            @foreach($modules as $name => $included)
            <li class="flex items-center gap-2 text-sm {{ $included ? 'text-gray-700' : 'text-gray-400' }}">
                @if($included)
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                @endif
                <span class="capitalize">{{ str_replace('_', ' ', $name) }}</span>
            </li>
            @endforeach
        </ul>

        {{-- CTA --}}
        @if($isCurrent)
            <button disabled
                class="w-full py-3 rounded-xl font-bold text-sm bg-gray-200 text-gray-500 cursor-not-allowed">
                Current Plan
            </button>
        @else
            <form method="POST" action="{{ route('checkout') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                <input type="hidden" name="billing_cycle" value="monthly">
                <button type="submit"
                    class="w-full py-3 rounded-xl font-bold text-sm transition
                    {{ $plan->is_featured
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                        : 'bg-gray-900 hover:bg-gray-800 text-white' }}">
                    Upgrade to {{ $plan->name }}
                </button>
            </form>
        @endif
    </div>
    @endforeach
</div>

{{-- Usage summary --}}
@if($currentTenant)
<div class="mt-10 max-w-2xl mx-auto p-6 rounded-2xl bg-gray-50 border border-gray-200">
    <h4 class="font-bold text-gray-800 mb-4">Your Current Usage</h4>
    <div class="grid grid-cols-2 gap-4 text-center">
        <div>
            <p class="text-2xl font-black text-gray-900">{{ $currentTenant->employees_count }}</p>
            <p class="text-xs text-gray-500">
                of {{ $currentTenant->getPlanLimit('max_employees', '∞') }} employees
            </p>
            @php $maxEmp = $currentTenant->getPlanLimit('max_employees'); @endphp
            @if($maxEmp)
            <div class="mt-1.5 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $currentTenant->employees_count / $maxEmp > 0.8 ? 'bg-red-500' : 'bg-indigo-500' }}"
                     style="width: {{ min(100, round($currentTenant->employees_count / $maxEmp * 100)) }}%"></div>
            </div>
            @endif
        </div>
        <div>
            <p class="text-2xl font-black text-gray-900">{{ $currentTenant->admins_count }}</p>
            <p class="text-xs text-gray-500">
                of {{ $currentTenant->getPlanLimit('max_admins', '∞') }} admins
            </p>
        </div>
    </div>
</div>
@endif

@endsection
