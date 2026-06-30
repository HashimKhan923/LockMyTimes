@extends('layouts.superadmin')
@section('title','Payments')
@section('page-title','Payments')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Payment History</h2>
        <p class="text-sm text-ink-soft mt-0.5">{{ number_format($payments->total()) }} total transactions</p>
    </div>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
    $kpis = [
        ['label'=>'Total Revenue',    'value'=>'$'.number_format($stats['total_revenue'],2),      'icon'=>'trending-up',   'bg'=>'bg-emerald-50', 'text'=>'text-emerald-600'],
        ['label'=>'This Month',       'value'=>'$'.number_format($stats['this_month'],2),          'icon'=>'calendar',      'bg'=>'bg-brand-50',   'text'=>'text-brand-600'],
        ['label'=>'Total Refunded',   'value'=>'$'.number_format($stats['total_refunded'],2),      'icon'=>'rotate-ccw',    'bg'=>'bg-amber-50',   'text'=>'text-amber-600'],
        ['label'=>'Failed Payments',  'value'=>number_format($stats['failed_count']),               'icon'=>'alert-triangle','bg'=>'bg-red-50',     'text'=>'text-red-500'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $kpi['label'] }}</p>
            <p class="lmt-stat-value">{{ $kpi['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}">
            <i data-lucide="{{ $kpi['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form action="{{ route('superadmin.payments.index') }}" method="GET"
              class="flex flex-wrap items-end gap-3">
            <div class="relative flex-1 min-w-48">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm" placeholder="Company, email, Stripe ID…">
            </div>
            <div>
                <select name="status" class="lmt-select text-sm py-2">
                    <option value="">All Statuses</option>
                    <option value="succeeded" {{ request('status')==='succeeded'?'selected':'' }}>Succeeded</option>
                    <option value="failed"    {{ request('status')==='failed'   ?'selected':'' }}>Failed</option>
                    <option value="refunded"  {{ request('status')==='refunded' ?'selected':'' }}>Refunded</option>
                    <option value="pending"   {{ request('status')==='pending'  ?'selected':'' }}>Pending</option>
                </select>
            </div>
            <div>
                <select name="tenant_id" class="lmt-select text-sm py-2">
                    <option value="">All Tenants</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id?'selected':'' }}>{{ $t->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="lmt-input text-sm py-2" title="From date">
            </div>
            <div>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="lmt-input text-sm py-2" title="To date">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
                @if(request()->hasAny(['search','status','tenant_id','from','to']))
                <a href="{{ route('superadmin.payments.index') }}" class="lmt-btn-ghost lmt-btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Stripe ID</th>
                    <th>Date</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                @php
                $statusMap = [
                    'succeeded' => 'lmt-badge-green',
                    'failed'    => 'lmt-badge-red',
                    'refunded'  => 'lmt-badge-amber',
                    'pending'   => 'lmt-badge-gray',
                ];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">
                                {{ substr($payment->tenant?->company_name ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-ink text-sm">{{ $payment->tenant?->company_name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-soft">{{ $payment->tenant?->contact_email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-sm text-ink">
                            {{ $payment->subscription?->plan?->name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <div>
                            <p class="font-bold text-ink text-sm">${{ number_format($payment->amount, 2) }}</p>
                            @if($payment->amount_refunded > 0)
                            <p class="text-xs text-amber-600">-${{ number_format($payment->amount_refunded, 2) }} refunded</p>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="{{ $statusMap[$payment->status] ?? 'lmt-badge-gray' }} text-xs">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                    <td>
                        @if($payment->card_brand && $payment->card_last4)
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="credit-card" class="w-3.5 h-3.5 text-ink-soft"></i>
                            <span class="text-sm capitalize text-ink-soft">{{ $payment->card_brand }} ••••{{ $payment->card_last4 }}</span>
                        </div>
                        @else
                        <span class="text-ink-soft text-sm">—</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->stripe_payment_intent_id)
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded-lg font-mono text-ink-soft">
                            {{ \Illuminate\Support\Str::limit($payment->stripe_payment_intent_id, 20) }}
                        </code>
                        @else
                        <span class="text-ink-soft">—</span>
                        @endif
                    </td>
                    <td class="text-sm text-ink-soft whitespace-nowrap">
                        {{ $payment->paid_at ? $payment->paid_at->format('M j, Y H:i') : '—' }}
                    </td>
                    <td>
                        @if($payment->receipt_url)
                        <a href="{{ $payment->receipt_url }}" target="_blank"
                           class="w-8 h-8 flex items-center justify-center rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white transition-colors"
                           title="View Receipt">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="credit-card" class="w-7 h-7 text-gray-300"></i>
                        </div>
                        <p class="font-semibold text-ink-soft">No payments found</p>
                        <p class="text-xs text-gray-400 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($payments->hasPages())
    <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-sm text-ink-soft">
            Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}
        </p>
        {{ $payments->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
