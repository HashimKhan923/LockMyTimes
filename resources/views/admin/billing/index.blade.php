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
                @elseif($subscription->status === 'cancelled') bg-gray-100 text-gray-800
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
    <div @if($subscription) x-data="billingPlanChange('{{ route('admin.billing.preview-change', $tenant) }}')" @endif>
        <h3 class="text-base font-bold text-ink mb-4">Available Plans</h3>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($plans as $plan)
            @php $isCurrent = $subscription?->plan_id === $plan->id; @endphp
            <div class="lmt-card flex flex-col {{ $plan->is_featured ? 'ring-2 ring-brand-400' : '' }} {{ $isCurrent ? 'ring-2 ring-emerald-400' : '' }}"
                 x-data="{ cycle: 'monthly' }">

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

                <div class="mb-4">
                    <div x-show="cycle==='monthly'" class="flex items-end gap-1">
                        <span class="text-2xl font-black text-ink">${{ number_format($plan->monthly_price, 0) }}</span>
                        <span class="text-ink-soft text-sm mb-1">/mo</span>
                    </div>
                    <div x-show="cycle==='yearly'" x-cloak class="flex items-end gap-1">
                        <span class="text-2xl font-black text-ink">${{ number_format($plan->yearly_price / 12, 0) }}</span>
                        <span class="text-ink-soft text-sm mb-1">/mo</span>
                    </div>
                    <p x-show="cycle==='yearly'" x-cloak class="text-xs text-emerald-600 font-semibold mt-0.5">
                        Billed ${{ number_format($plan->yearly_price, 0) }}/year
                    </p>
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
                <form method="POST" action="{{ route('admin.billing.checkout', $tenant) }}">
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

                    @if($subscription)
                    <button type="button"
                            @click="openConfirm('{{ $plan->slug }}', @js($plan->name), cycle, $el.closest('form'))"
                            class="w-full lmt-btn-primary text-sm py-2.5">
                        @if($subscription->plan && $plan->monthly_price > $subscription->plan->monthly_price)
                            Upgrade to {{ $plan->name }}
                        @else
                            Switch to {{ $plan->name }}
                        @endif
                    </button>
                    @else
                    <button type="submit" class="w-full lmt-btn-primary text-sm py-2.5">
                        Subscribe
                    </button>
                    @endif
                </form>
                @endif
            </div>
            @endforeach
        </div>

        @if($subscription)
        {{-- Plan Change Confirmation Modal --}}
        <div class="lmt-modal-backdrop" x-show="open" x-cloak @click.self="open=false">
            <div class="lmt-modal">
                <h3 class="font-black text-ink mb-4">Confirm plan change</h3>

                <div x-show="loading" class="flex items-center justify-center py-8 text-sm text-ink-soft gap-2">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Calculating price change…
                </div>

                <div x-show="!loading && error" class="text-center py-6">
                    <p class="text-sm text-red-600 font-semibold">Could not calculate the price change.</p>
                    <p class="text-xs text-ink-soft mt-1">Please try again in a moment.</p>
                </div>

                <div x-show="!loading && !error" class="space-y-4">
                    <p class="text-sm text-ink-soft">
                        You're switching to <span class="font-bold text-ink" x-text="planName"></span>.
                    </p>

                    <template x-if="isTrialing">
                        <div class="rounded-xl p-3 text-sm bg-blue-50 text-blue-700">
                            You're still in your free trial, so nothing is charged today. This will be your price once billing starts.
                        </div>
                    </template>

                    <div class="rounded-xl p-4 bg-gray-50 text-center">
                        <p class="text-xs text-ink-soft mb-1" x-text="isCredit ? 'You\'ll be credited' : (isTrialing ? 'Price after trial' : 'You\'ll be charged today')"></p>
                        <p class="text-2xl font-black" :class="isCredit ? 'text-emerald-600' : 'text-ink'">
                            <span x-text="isCredit ? '-' : ''"></span>{{-- credit sign --}}
                            <span x-text="currency"></span>&nbsp;<span x-text="amountDue?.toFixed(2)"></span>
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="confirmSubmit()" class="lmt-btn-primary flex-1">Confirm switch</button>
                        <button type="button" @click="open=false" class="lmt-btn-secondary flex-1">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
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
                        @else bg-gray-100 text-gray-800 @endif">
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

@push('scripts')
<script>
function billingPlanChange(previewUrl) {
    return {
        open: false,
        loading: false,
        error: false,
        planName: '',
        amountDue: null,
        currency: 'USD',
        isTrialing: false,
        isCredit: false,
        formEl: null,

        async openConfirm(planSlug, planName, cycle, formEl) {
            this.formEl = formEl;
            this.planName = planName;
            this.open = true;
            this.loading = true;
            this.error = false;
            try {
                const res = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ plan_slug: planSlug, billing_cycle: cycle }),
                });
                const data = await res.json();
                if (!res.ok || !data.has_existing_subscription) {
                    // Nothing to preview against — submit directly rather than blocking on a
                    // confirmation the backend can't compute.
                    this.open = false;
                    formEl.submit();
                    return;
                }
                this.amountDue = Math.abs(data.amount_due);
                this.isCredit = data.amount_due < 0;
                this.currency = data.currency;
                this.isTrialing = data.is_trialing;
            } catch (e) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },

        confirmSubmit() {
            if (this.formEl) this.formEl.submit();
        },
    };
}
</script>
@endpush
