@extends('layouts.admin')
@section('title', 'Billing & Subscription')
@section('page-title', 'Billing & Subscription')

@section('content')

@if(request('upgraded'))
<div class="mb-6 rounded-2xl p-5 flex items-center gap-4" style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5); border:1px solid #6EE7B7;">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#10B981;">
        <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
    </div>
    <div>
        <p class="font-bold text-emerald-800">Subscription activated!</p>
        <p class="text-sm text-emerald-700">Your plan has been updated and all features are now available.</p>
    </div>
</div>
@endif

<div class="grid gap-6 lg:grid-cols-3">

{{-- ─── LEFT: Current Plan ─── --}}
<div class="lg:col-span-2 space-y-6">

    {{-- Current Subscription Card --}}
    <div class="lmt-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-bold text-ink">Current Subscription</h3>
            @if($subscription)
            <span class="px-3 py-1 rounded-full text-xs font-bold
                @if($subscription->status === 'active') bg-emerald-100 text-emerald-700
                @elseif($subscription->status === 'trialing') bg-blue-100 text-blue-700
                @elseif($subscription->status === 'past_due') bg-red-100 text-red-700
                @elseif($subscription->status === 'cancelled') bg-gray-100 text-gray-700
                @else bg-amber-100 text-amber-700 @endif">
                {{ ucfirst($subscription->status) }}
            </span>
            @endif
        </div>

        @if($subscription && $subscription->plan)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-ink-soft font-medium mb-1">Plan</div>
                <div class="font-bold text-ink">{{ $subscription->plan->name }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-ink-soft font-medium mb-1">Billing Cycle</div>
                <div class="font-bold text-ink capitalize">{{ $subscription->billing_cycle }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-ink-soft font-medium mb-1">Amount</div>
                <div class="font-bold text-ink">${{ number_format($subscription->amount, 2) }}/{{ $subscription->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs text-ink-soft font-medium mb-1">
                    @if($subscription->status === 'trialing') Trial Ends
                    @else Renews
                    @endif
                </div>
                <div class="font-bold text-ink">
                    {{ $subscription->current_period_ends_at?->format('M j, Y') ?? '—' }}
                </div>
            </div>
        </div>

        @if($subscription->cancel_at_period_end)
        <div class="rounded-xl p-4 mb-4" style="background:#FEF3C7; border:1px solid #FCD34D;">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-amber-800 text-sm">Subscription cancels on {{ $subscription->current_period_ends_at?->format('M j, Y') }}</p>
                    <p class="text-xs text-amber-700 mt-0.5">Your access will continue until then. You can resume anytime.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.billing.resume', $tenant) }}" class="mt-3">
                @csrf
                <button type="submit" class="lmt-btn-primary text-sm py-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Resume Subscription
                </button>
            </form>
        </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.billing.portal', $tenant) }}"
               class="lmt-btn-secondary text-sm">
                <i data-lucide="credit-card" class="w-4 h-4"></i>
                Manage Payment Method
            </a>
            @if(!$subscription->cancel_at_period_end && $subscription->isActive())
            <form method="POST" action="{{ route('admin.billing.cancel', $tenant) }}"
                  onsubmit="return confirm('Cancel your subscription at period end?')">
                @csrf
                <button type="submit" class="lmt-btn-secondary text-sm text-red-600 border-red-200 hover:bg-red-50">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                    Cancel Plan
                </button>
            </form>
            @endif
        </div>

        @else
        <div class="text-center py-8">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="credit-card" class="w-7 h-7 text-brand-500"></i>
            </div>
            <p class="text-ink font-semibold mb-1">No active subscription</p>
            <p class="text-ink-soft text-sm">Choose a plan below to get started.</p>
        </div>
        @endif
    </div>

    {{-- Plans Grid --}}
    <div>
        <h3 class="text-base font-bold text-ink mb-4">Available Plans</h3>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($plans as $plan)
            @php $isCurrent = $subscription?->plan_id === $plan->id; @endphp
            <div class="lmt-card flex flex-col {{ $plan->is_featured ? 'ring-2 ring-brand-400' : '' }} {{ $isCurrent ? 'ring-2 ring-emerald-400' : '' }}">

                @if($isCurrent)
                <div class="text-center mb-3">
                    <span class="inline-block bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full">CURRENT PLAN</span>
                </div>
                @elseif($plan->is_featured)
                <div class="text-center mb-3">
                    <span class="inline-block text-xs font-bold px-3 py-1 rounded-full" style="background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff;">MOST POPULAR</span>
                </div>
                @endif

                <div class="mb-4">
                    <h4 class="font-bold text-ink text-base">{{ $plan->name }}</h4>
                    @if($plan->description)
                    <p class="text-xs text-ink-soft mt-0.5">{{ $plan->description }}</p>
                    @endif
                </div>

                <div class="mb-4 flex items-end gap-1">
                    <span class="text-2xl font-black text-ink">${{ number_format($plan->monthly_price, 0) }}</span>
                    <span class="text-ink-soft text-sm mb-1">/mo</span>
                </div>

                <ul class="space-y-2 mb-5 flex-1">
                    @if($plan->max_employees)
                    <li class="flex items-center gap-2 text-sm text-ink-soft">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-brand-400 flex-shrink-0"></i>
                        Up to {{ $plan->max_employees }} employees
                    </li>
                    @endif
                    @foreach(array_filter([
                        $plan->has_payroll      ? 'Payroll' : null,
                        $plan->has_recruitment  ? 'Recruitment' : null,
                        $plan->has_performance  ? 'Performance Reviews' : null,
                        $plan->has_training     ? 'Training' : null,
                        $plan->has_assets       ? 'Asset Management' : null,
                    ]) as $feature)
                    <li class="flex items-center gap-2 text-sm text-ink-soft">
                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrent)
                <div class="text-center text-sm text-emerald-600 font-semibold py-2">
                    <i data-lucide="check-circle" class="w-4 h-4 inline-block mr-1"></i>
                    Active
                </div>
                @else
                <form method="POST" action="{{ route('admin.billing.checkout', $tenant) }}" x-data="{ cycle: 'monthly' }">
                    @csrf
                    <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                    <input type="hidden" name="billing_cycle" x-bind:value="cycle">

                    <div class="flex rounded-xl overflow-hidden border border-gray-200 mb-3">
                        <button type="button"
                            x-on:click="cycle='monthly'"
                            x-bind:class="cycle==='monthly' ? 'bg-brand-500 text-white' : 'bg-white text-ink-soft hover:bg-gray-50'"
                            class="flex-1 text-xs font-semibold py-2 transition-colors">
                            Monthly
                        </button>
                        <button type="button"
                            x-on:click="cycle='yearly'"
                            x-bind:class="cycle==='yearly' ? 'bg-brand-500 text-white' : 'bg-white text-ink-soft hover:bg-gray-50'"
                            class="flex-1 text-xs font-semibold py-2 transition-colors">
                            Yearly
                            @if($plan->yearly_savings_percent > 0)
                            <span class="ml-1 text-xs opacity-80">-{{ $plan->yearly_savings_percent }}%</span>
                            @endif
                        </button>
                    </div>

                    <button type="submit" class="w-full lmt-btn-primary text-sm py-2.5">
                        @if($subscription && $subscription->plan && $plan->monthly_price > $subscription->plan->monthly_price)
                            Upgrade to {{ $plan->name }}
                        @elseif($subscription && $subscription->plan)
                            Switch to {{ $plan->name }}
                        @else
                            Subscribe
                        @endif
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ─── RIGHT: Usage + Invoices ─── --}}
<div class="space-y-6">

    {{-- Usage --}}
    <div class="lmt-card">
        <h3 class="text-sm font-bold text-ink mb-4">Usage</h3>
        <div class="space-y-4">
            @php
                $maxEmp  = $currentTenant->getPlanLimit('max_employees');
                $usedEmp = $currentTenant->employees_count ?? 0;
                $empPct  = $maxEmp ? min(100, round($usedEmp / $maxEmp * 100)) : 0;
            @endphp

            <div>
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-ink-soft font-medium">Employees</span>
                    <span class="text-ink font-semibold">{{ $usedEmp }} / {{ $maxEmp ?? '∞' }}</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-2 rounded-full transition-all
                        @if($empPct > 90) bg-red-500 @elseif($empPct > 70) bg-amber-500 @else bg-brand-500 @endif"
                        style="width: {{ $empPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    <div class="lmt-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-ink">Payment History</h3>
            @if($currentTenant->stripe_customer_id)
            <a href="{{ route('admin.billing.portal', $tenant) }}" class="text-xs text-brand-500 hover:underline">All invoices </a>
            @endif
        </div>

        @if(count($invoices))
        <div class="space-y-2">
            @foreach($invoices as $invoice)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <div class="text-sm font-medium text-ink">
                        {{ \Carbon\Carbon::createFromTimestamp($invoice->created)->format('M j, Y') }}
                    </div>
                    <div class="text-xs text-ink-soft">
                        {{ strtoupper($invoice->currency) }} {{ number_format($invoice->amount_paid / 100, 2) }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                        @if($invoice->status === 'paid') bg-emerald-100 text-emerald-700
                        @elseif($invoice->status === 'open') bg-amber-100 text-amber-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($invoice->status) }}
                    </span>
                    @if($invoice->hosted_invoice_url)
                    <a href="{{ $invoice->hosted_invoice_url }}" target="_blank"
                       class="text-brand-500 hover:text-brand-700" title="Download">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-ink-soft text-center py-4">No payment history yet.</p>
        @endif
    </div>

</div>
</div>

@endsection
