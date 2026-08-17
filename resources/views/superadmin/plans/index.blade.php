@extends('layouts.superadmin')
@section('title','Subscription Plans')
@section('page-title','Subscription Plans')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Subscription Plans</h2>
        <p class="text-sm text-ink-soft mt-0.5">{{ $plans->count() }} plan{{ $plans->count() !== 1 ? 's' : '' }} configured</p>
    </div>
    <a href="{{ route('superadmin.plans.create') }}" class="lmt-btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Plan
    </a>
</div>

{{-- Plans grid --}}
@if($plans->isEmpty())
<div class="lmt-card text-center py-16">
    <div class="w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="layers" class="w-8 h-8 text-brand-500"></i>
    </div>
    <h3 class="font-bold text-ink text-lg mb-1">No Plans Yet</h3>
    <p class="text-ink-soft text-sm mb-6">Create your first subscription plan to start onboarding organizations.</p>
    <a href="{{ route('superadmin.plans.create') }}" class="lmt-btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Create First Plan
    </a>
</div>
@else
<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    @foreach($plans as $plan)
    <div class="lmt-card relative flex flex-col {{ $plan->is_featured ? 'ring-2 ring-brand-400' : '' }} {{ !$plan->is_active ? 'opacity-60' : '' }}">

        {{-- Featured badge --}}
        @if($plan->is_featured)
        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-brand-500 text-white text-xs font-bold px-4 py-1 rounded-full shadow-pop">FEATURED</span>
        </div>
        @endif

        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-black text-ink text-lg" style="font-family:'Nunito',sans-serif">{{ $plan->name }}</h3>
                    @if($plan->badge)
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ $plan->badge }}</span>
                    @endif
                </div>
                @if($plan->description)
                <p class="text-xs text-ink-soft">{{ $plan->description }}</p>
                @endif
            </div>
            <span class="{{ $plan->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs flex-shrink-0">
                {{ $plan->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        {{-- Pricing --}}
        <div class="flex items-end gap-4 mb-5 pb-5 border-b border-gray-100">
            <div>
                <p class="text-2xl font-black text-ink">${{ number_format($plan->monthly_price, 0) }}<span class="text-sm font-medium text-ink-soft">/mo</span></p>
                <p class="text-xs text-ink-soft mt-0.5">${{ number_format($plan->yearly_price, 0) }}/yr
                    @if($plan->yearly_savings_percent > 0)
                    <span class="text-emerald-600 font-semibold">(save {{ $plan->yearly_savings_percent }}%)</span>
                    @endif
                </p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-2xl font-black text-brand-500">{{ $plan->subscriptions_count }}</p>
                <p class="text-xs text-ink-soft">subscribers</p>
            </div>
        </div>

        {{-- Limits --}}
        <div class="grid grid-cols-2 gap-3 mb-5">
            @foreach([
                ['label' => 'Employees', 'value' => $plan->max_employees ?? '∞', 'icon' => 'users'],
                ['label' => 'Admins', 'value' => $plan->max_admins ?? '∞', 'icon' => 'shield'],
            ] as $limit)
            <div class="text-center bg-gray-50 rounded-xl p-3">
                <i data-lucide="{{ $limit['icon'] }}" class="w-4 h-4 text-ink-soft mx-auto mb-1"></i>
                <p class="text-sm font-bold text-ink">{{ $limit['value'] }}</p>
                <p class="text-xs text-ink-soft">{{ $limit['label'] }}</p>
            </div>
            @endforeach
        </div>

        {{-- Feature toggles --}}
        <div class="space-y-1.5 mb-5 flex-1">
            @php
            $featureMap = [
                'has_attendance'       => 'Attendance & Shifts',
                'has_payroll'          => 'Payroll Processing',
                'has_performance'      => 'Performance Reviews',
                'has_recruitment'      => 'Recruitment ATS',
                // 'has_documents' hidden per client request (2026-08)
                'has_expenses'         => 'Expense Tracking',
                'has_assets'           => 'Asset Management',
                'has_training'         => 'Training Module',
                'has_api_access'       => 'API Access',
                'has_white_label'      => 'White Label',
                'has_priority_support' => 'Priority Support',
            ];
            @endphp
            @foreach($featureMap as $field => $label)
            <div class="flex items-center gap-2 text-sm {{ $plan->$field ? 'text-ink' : 'text-gray-300' }}">
                <i data-lucide="{{ $plan->$field ? 'check-circle' : 'x-circle' }}"
                   class="w-4 h-4 flex-shrink-0 {{ $plan->$field ? 'text-emerald-500' : 'text-gray-200' }}"></i>
                {{ $label }}
            </div>
            @endforeach
        </div>

        {{-- Custom features list — storage-related bullets filtered from display per client
             request (2026-08); the plan's stored features list itself is untouched. --}}
        @php $extraFeatures = collect($plan->features ?? [])->reject(fn ($f) => stripos($f, 'storage') !== false); @endphp
        @if($extraFeatures->isNotEmpty())
        <div class="mb-4 pt-3 border-t border-gray-100">
            <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider mb-2">Additional Features</p>
            <div class="space-y-1">
                @foreach($extraFeatures as $feat)
                <div class="flex items-center gap-2 text-xs text-ink-soft">
                    <i data-lucide="star" class="w-3 h-3 text-amber-400 flex-shrink-0"></i>
                    {{ $feat }}
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Stripe Sync Status --}}
        <div class="rounded-xl p-3 mb-4 {{ $plan->stripe_product_id ? 'bg-emerald-50 border border-emerald-100' : 'bg-amber-50 border border-amber-100' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $plan->stripe_product_id ? 'bg-emerald-500' : 'bg-amber-400' }}"></div>
                    <span class="text-xs font-semibold {{ $plan->stripe_product_id ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $plan->stripe_product_id ? 'Synced with Stripe' : 'Not synced to Stripe' }}
                    </span>
                </div>
                <form action="{{ route('superadmin.plans.sync-stripe', $plan) }}" method="POST">
                    @csrf
                    <button type="submit" title="Sync to Stripe"
                            class="text-xs font-semibold px-2 py-1 rounded-lg border
                                   {{ $plan->stripe_product_id ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-100' : 'border-amber-300 text-amber-700 hover:bg-amber-100' }}
                                   transition-colors">
                        <i data-lucide="refresh-cw" class="w-3 h-3 inline-block mr-0.5"></i>
                        Sync
                    </button>
                </form>
            </div>
            @if($plan->stripe_product_id)
            <div class="mt-2 space-y-0.5">
                <p class="text-xs text-emerald-600 font-mono truncate" title="{{ $plan->stripe_product_id }}">
                    prod: {{ $plan->stripe_product_id }}
                </p>
                @if($plan->stripe_monthly_price_id)
                <p class="text-xs text-emerald-600 font-mono truncate" title="{{ $plan->stripe_monthly_price_id }}">
                    mo: {{ $plan->stripe_monthly_price_id }}
                </p>
                @endif
                @if($plan->stripe_yearly_price_id)
                <p class="text-xs text-emerald-600 font-mono truncate" title="{{ $plan->stripe_yearly_price_id }}">
                    yr: {{ $plan->stripe_yearly_price_id }}
                </p>
                @endif
            </div>
            @endif
        </div>

        {{-- Footer actions --}}
        <div class="flex items-center gap-2 pt-4 border-t border-gray-100 mt-auto">
            <a href="{{ route('superadmin.plans.edit', $plan) }}"
               class="lmt-btn-secondary lmt-btn-sm flex-1 justify-center">
                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                Edit
            </a>
            <form action="{{ route('superadmin.plans.toggle', $plan) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit"
                        class="lmt-btn-sm {{ $plan->is_active ? 'lmt-btn-ghost text-amber-600 border-amber-200 hover:bg-amber-50' : 'lmt-btn-ghost text-emerald-600 border-emerald-200 hover:bg-emerald-50' }}"
                        title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                    <i data-lucide="{{ $plan->is_active ? 'pause' : 'play' }}" class="w-3.5 h-3.5"></i>
                </button>
            </form>
            @if($plan->subscriptions_count === 0)
            <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST"
                  onsubmit="return confirm('Delete plan \'{{ addslashes($plan->name) }}\'? This cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="lmt-btn-sm lmt-btn-ghost text-red-500 border-red-200 hover:bg-red-50">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
            @endif
        </div>

        {{-- Trial badge --}}
        @if($plan->trial_days > 0)
        <div class="mt-3 text-center">
            <span class="text-xs text-ink-soft">
                <i data-lucide="clock" class="w-3 h-3 inline-block mr-0.5"></i>
                {{ $plan->trial_days }}-day free trial
            </span>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
