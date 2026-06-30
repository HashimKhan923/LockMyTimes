@extends('layouts.superadmin')
@section('title','Analytics')
@section('page-title','Analytics')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Analytics & Reports</h2>
        <p class="text-sm text-ink-soft mt-0.5">Platform-wide business metrics and revenue intelligence</p>
    </div>
    <div class="flex items-center gap-2 text-xs text-ink-soft bg-white border border-gray-200 px-3 py-2 rounded-xl">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
        Live data · {{ now()->format('M j, Y H:i') }}
    </div>
</div>

{{-- Top KPI row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach([
        ['label'=>'ARR',              'value'=>'$'.number_format($summary['arr'],0),              'sub'=>'Annual recurring revenue',  'icon'=>'trending-up',    'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'ARPU',             'value'=>'$'.number_format($summary['arpu'],2),             'sub'=>'Per paying tenant / mo',    'icon'=>'user-check',     'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Conversion Rate',  'value'=>$summary['conversionRate'].'%',                   'sub'=>'Trial → paid',              'icon'=>'percent',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Churn Rate',       'value'=>$summary['churnRate'].'%',                        'sub'=>$summary['churnedThisMonth'].' lost this month','icon'=>'user-minus','bg'=>'bg-red-50','text'=>'text-red-500'],
    ] as $kpi)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $kpi['label'] }}</p>
            <p class="lmt-stat-value">{{ $kpi['value'] }}</p>
            <p class="text-xs text-ink-soft mt-0.5">{{ $kpi['sub'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}">
            <i data-lucide="{{ $kpi['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- Secondary KPIs --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    @foreach([
        ['label'=>'Paying Tenants',     'value'=>number_format($summary['payingTenants']),     'icon'=>'building-2'],
        ['label'=>'New This Month',     'value'=>number_format($summary['newThisMonth']),       'icon'=>'plus-circle'],
        ['label'=>'Payment Success',    'value'=>$summary['paymentSuccessRate'].'%',            'icon'=>'check-circle'],
    ] as $s)
    <div class="bg-white border border-gray-100 rounded-2xl p-4 flex items-center gap-4" style="box-shadow:0 1px 8px rgba(0,0,0,0.04);">
        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5 text-ink-soft"></i>
        </div>
        <div>
            <p class="text-xs text-ink-soft">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-ink" style="font-family:'Nunito',sans-serif">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Revenue trend + Tenant growth --}}
<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <div class="lmt-card">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Monthly Revenue</h3>
                <p class="text-xs text-ink-soft mt-0.5">Cash collected · last 12 months</p>
            </div>
        </div>
        <canvas id="mrrChart" height="150"></canvas>
    </div>

    <div class="lmt-card">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Tenant Growth</h3>
                <p class="text-xs text-ink-soft mt-0.5">Cumulative tenants over 12 months</p>
            </div>
        </div>
        <canvas id="tenantChart" height="150"></canvas>
    </div>
</div>

{{-- Revenue by plan + Top tenants --}}
<div class="grid lg:grid-cols-2 gap-6 mb-8">

    {{-- Revenue by plan --}}
    <div class="lmt-card">
        <h3 class="font-bold text-ink mb-5" style="font-family:'Nunito',sans-serif">Revenue by Plan</h3>
        @if($revenueByPlan->isEmpty())
        <p class="text-sm text-ink-soft text-center py-8">No plans configured yet.</p>
        @else
        <div class="space-y-4">
            @php $maxMrr = $revenueByPlan->max('mrr') ?: 1; @endphp
            @foreach($revenueByPlan as $plan)
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-ink">{{ $plan->name }}</span>
                        <span class="lmt-badge-gray text-xs">{{ $plan->active_count }} subscribers</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-ink">${{ number_format($plan->mrr, 0) }}<span class="text-ink-soft font-normal">/mo</span></span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full lmt-gradient-bg transition-all duration-700"
                         style="width: {{ $maxMrr > 0 ? round(($plan->mrr / $maxMrr) * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Plan doughnut --}}
        <div class="mt-6 flex justify-center">
            <canvas id="planChart" width="200" height="200" style="max-width:200px;max-height:200px;"></canvas>
        </div>
        @endif
    </div>

    {{-- Top tenants by LTV --}}
    <div class="lmt-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Top Tenants by Revenue</h3>
            <span class="text-xs text-ink-soft">Lifetime value</span>
        </div>
        @forelse($topTenants as $i => $tenant)
        <div class="flex items-center gap-3 py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
            <span class="text-xs font-bold text-ink-soft w-5 text-right flex-shrink-0">{{ $i + 1 }}</span>
            <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">
                {{ substr($tenant->company_name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('superadmin.tenants.show', $tenant) }}"
                   class="text-sm font-semibold text-ink hover:text-brand-600 transition-colors truncate block">
                    {{ $tenant->company_name }}
                </a>
                <p class="text-xs text-ink-soft">{{ $tenant->activeSubscription?->plan?->name ?? '—' }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold text-emerald-600">${{ number_format($tenant->lifetime_revenue ?? 0, 0) }}</p>
                <p class="text-xs text-ink-soft">lifetime</p>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-ink-soft text-sm">
            No revenue data yet.
        </div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();

    const brandColor = '#6C7DF7';
    const emeraldColor = '#10B981';

    // MRR Chart
    const mrrCtx = document.getElementById('mrrChart');
    if (mrrCtx && window.Chart) {
        new Chart(mrrCtx, {
            type: 'bar',
            data: {
                labels: @json($mrrTrend['labels']),
                datasets: [{
                    label: 'Revenue ($)',
                    data: @json($mrrTrend['data']),
                    backgroundColor: ctx => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                        g.addColorStop(0, 'rgba(108,125,247,0.85)');
                        g.addColorStop(1, 'rgba(108,125,247,0.15)');
                        return g;
                    },
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { callback: v => '$' + v.toLocaleString(), font: { family: 'Nunito Sans', size: 11 } }
                    },
                    x: { grid: { display: false }, ticks: { font: { family: 'Nunito Sans', size: 11 } } }
                }
            }
        });
    }

    // Tenant growth Chart
    const tenantCtx = document.getElementById('tenantChart');
    if (tenantCtx && window.Chart) {
        new Chart(tenantCtx, {
            type: 'line',
            data: {
                labels: @json($tenantGrowth['labels']),
                datasets: [{
                    label: 'Total Tenants',
                    data: @json($tenantGrowth['data']),
                    borderColor: emeraldColor,
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: emeraldColor,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'Nunito Sans', size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Nunito Sans', size: 11 } } }
                }
            }
        });
    }

    // Plan doughnut
    const planCtx = document.getElementById('planChart');
    if (planCtx && window.Chart) {
        const planNames = @json($revenueByPlan->pluck('name'));
        const planMrr   = @json($revenueByPlan->pluck('mrr'));
        const colors    = ['#6C7DF7','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4'];
        new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: planNames,
                datasets: [{
                    data: planMrr,
                    backgroundColor: colors.slice(0, planNames.length),
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Nunito Sans', size: 11 }, padding: 12 } },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: $${ctx.raw.toFixed(0)}/mo` } }
                }
            }
        });
    }
});
</script>
@endpush
