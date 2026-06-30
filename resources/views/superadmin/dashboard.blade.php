@extends('layouts.superadmin')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('content')

{{-- Welcome banner --}}
<div class="lmt-gradient-bg rounded-2xl p-6 mb-8 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2"></div>
    <div class="absolute bottom-0 left-1/3 w-32 h-32 rounded-full bg-white/5 translate-y-1/2"></div>
    <div class="relative z-10 flex items-center justify-between">
        <div>
            <p class="text-white/70 text-sm font-medium mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},</p>
            <h1 class="text-white text-2xl font-black" style="font-family:'Nunito',sans-serif">
                {{ Auth::guard('superadmin')->user()->name }} 👋
            </h1>
            <p class="text-white/70 text-sm mt-1">Here's what's happening on your platform today.</p>
        </div>
        <div class="hidden md:flex items-center gap-3">
            <a href="{{ route('superadmin.analytics.index') }}"
               class="bg-white/20 hover:bg-white/30 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 border border-white/20">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                Analytics
            </a>
            <a href="{{ route('superadmin.tenants.create') }}"
               class="bg-white/20 hover:bg-white/30 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 border border-white/20">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Tenant
            </a>
        </div>
    </div>
</div>

{{-- Action Alerts --}}
@if(count($alerts) > 0)
<div class="mb-8 space-y-2.5">
    <p class="text-xs font-bold text-ink-soft uppercase tracking-widest mb-3 flex items-center gap-2">
        <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i>
        Action Required ({{ count($alerts) }})
    </p>
    @foreach($alerts as $alert)
    @php
    $colors = [
        'warning' => ['bg'=>'bg-amber-50','border'=>'border-amber-200','icon'=>'text-amber-500','btn'=>'bg-amber-100 text-amber-700 hover:bg-amber-200'],
        'danger'  => ['bg'=>'bg-red-50',  'border'=>'border-red-200',  'icon'=>'text-red-500',  'btn'=>'bg-red-100 text-red-700 hover:bg-red-200'],
        'info'    => ['bg'=>'bg-brand-50','border'=>'border-brand-200','icon'=>'text-brand-500','btn'=>'bg-brand-100 text-brand-700 hover:bg-brand-200'],
    ];
    $c = $colors[$alert['type']] ?? $colors['info'];
    @endphp
    <div class="{{ $c['bg'] }} {{ $c['border'] }} border rounded-xl px-4 py-3 flex items-center gap-3">
        <i data-lucide="{{ $alert['icon'] }}" class="w-4 h-4 {{ $c['icon'] }} flex-shrink-0"></i>
        <p class="text-sm text-ink flex-1">{!! $alert['message'] !!}</p>
        <a href="{{ $alert['action'] }}"
           class="{{ $c['btn'] }} text-xs font-bold px-3 py-1.5 rounded-lg transition-colors flex-shrink-0">
            {{ $alert['label'] }}
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- KPI Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
    $revDelta = $stats['revenue_last_month'] > 0
        ? round((($stats['revenue_this_month'] - $stats['revenue_last_month']) / $stats['revenue_last_month']) * 100, 1)
        : null;
    $tenantDelta = $stats['new_last_month'] > 0
        ? round((($stats['new_this_month'] - $stats['new_last_month']) / $stats['new_last_month']) * 100, 1)
        : null;

    $kpis = [
        [
            'label' => 'Total Tenants',
            'value' => number_format($stats['total_tenants']),
            'icon'  => 'building-2',
            'bg'    => 'bg-brand-50',
            'text'  => 'text-brand-600',
            'delta' => $tenantDelta !== null
                ? ($tenantDelta >= 0 ? '+' : '') . $tenantDelta . '% vs last month'
                : '+' . $stats['new_this_month'] . ' this month',
            'up'    => ($tenantDelta ?? 1) >= 0,
        ],
        [
            'label' => 'Active Subscriptions',
            'value' => number_format($stats['active_tenants']),
            'icon'  => 'check-circle',
            'bg'    => 'bg-emerald-50',
            'text'  => 'text-emerald-600',
            'delta' => $stats['total_tenants'] > 0
                ? round(($stats['active_tenants'] / $stats['total_tenants']) * 100) . '% of total'
                : '0% of total',
            'up'    => true,
        ],
        [
            'label' => 'Monthly Revenue',
            'value' => '$' . number_format($stats['revenue_this_month'], 0),
            'icon'  => 'dollar-sign',
            'bg'    => 'bg-amber-50',
            'text'  => 'text-amber-600',
            'delta' => $revDelta !== null
                ? ($revDelta >= 0 ? '+' : '') . $revDelta . '% vs last month'
                : 'This month',
            'up'    => ($revDelta ?? 1) >= 0,
        ],
        [
            'label' => 'Trial Tenants',
            'value' => number_format($stats['trial_tenants']),
            'icon'  => 'clock',
            'bg'    => 'bg-purple-50',
            'text'  => 'text-purple-600',
            'delta' => $stats['expiring_soon'] . ' expiring in 3 days',
            'up'    => $stats['expiring_soon'] === 0,
        ],
    ];
    @endphp

    @foreach($kpis as $i => $kpi)
    <div class="lmt-stat" data-lmt-anim="fade-up" data-lmt-delay="{{ $i * 0.07 }}">
        <div>
            <p class="lmt-stat-label">{{ $kpi['label'] }}</p>
            <p class="lmt-stat-value">{{ $kpi['value'] }}</p>
            <p class="{{ $kpi['up'] ? 'lmt-stat-delta-up' : 'lmt-stat-delta-down' }}">
                <i data-lucide="{{ $kpi['up'] ? 'trending-up' : 'alert-triangle' }}" class="w-3.5 h-3.5"></i>
                {{ $kpi['delta'] }}
            </p>
        </div>
        <div class="lmt-stat-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}">
            <i data-lucide="{{ $kpi['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- MRR callout strip --}}
<div class="bg-white border border-gray-100 rounded-2xl px-6 py-4 mb-8 flex items-center justify-between flex-wrap gap-4"
     style="box-shadow:0 1px 8px rgba(0,0,0,0.04);">
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl lmt-gradient-bg flex items-center justify-center">
            <i data-lucide="trending-up" class="w-5 h-5 text-white"></i>
        </div>
        <div>
            <p class="text-xs text-ink-soft font-semibold uppercase tracking-wider">Monthly Recurring Revenue (MRR)</p>
            <div class="flex items-baseline gap-3 mt-0.5">
                <p class="text-2xl font-black text-ink" style="font-family:'Nunito',sans-serif">${{ number_format($stats['mrr'], 0) }}</p>
                @if($stats['mrr_delta'] !== null)
                <span class="{{ $stats['mrr_delta'] >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-red-500 bg-red-50' }} text-xs font-bold px-2 py-0.5 rounded-full">
                    {{ $stats['mrr_delta'] >= 0 ? '+' : '' }}{{ $stats['mrr_delta'] }}% MoM
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-6">
        <div class="text-center">
            <p class="text-xs text-ink-soft">ARR (est.)</p>
            <p class="font-black text-ink text-lg" style="font-family:'Nunito',sans-serif">${{ number_format($stats['mrr'] * 12, 0) }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-ink-soft">Past Due</p>
            <p class="font-black {{ $stats['past_due_tenants'] > 0 ? 'text-amber-600' : 'text-ink' }} text-lg" style="font-family:'Nunito',sans-serif">{{ $stats['past_due_tenants'] }}</p>
        </div>
        <div class="text-center">
            <p class="text-xs text-ink-soft">Suspended</p>
            <p class="font-black {{ $stats['suspended_tenants'] > 0 ? 'text-red-500' : 'text-ink' }} text-lg" style="font-family:'Nunito',sans-serif">{{ $stats['suspended_tenants'] }}</p>
        </div>
        <a href="{{ route('superadmin.analytics.index') }}" class="lmt-btn-secondary lmt-btn-sm">
            Full Analytics <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>
</div>

{{-- Charts row --}}
<div class="grid lg:grid-cols-3 gap-6 mb-8">

    {{-- Revenue chart --}}
    <div class="lg:col-span-2 lmt-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Revenue Overview</h3>
                <p class="text-xs text-ink-soft mt-0.5">Monthly cash collected · last 6 months</p>
            </div>
            <span class="lmt-badge-brand text-xs">Last 6 months</span>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    {{-- Tenant status breakdown --}}
    <div class="lmt-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Tenant Status</h3>
                <p class="text-xs text-ink-soft mt-0.5">Current breakdown</p>
            </div>
        </div>
        <canvas id="statusChart" height="160"></canvas>
        <div class="mt-4 space-y-2.5">
            @foreach([
                ['label'=>'Active',    'count'=>$stats['active_tenants'],    'color'=>'bg-emerald-500'],
                ['label'=>'Trial',     'count'=>$stats['trial_tenants'],     'color'=>'bg-brand-500'],
                ['label'=>'Past Due',  'count'=>$stats['past_due_tenants'],  'color'=>'bg-amber-500'],
                ['label'=>'Suspended', 'count'=>$stats['suspended_tenants'], 'color'=>'bg-red-500'],
            ] as $s)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full {{ $s['color'] }}"></div>
                    <span class="text-sm text-ink-soft">{{ $s['label'] }}</span>
                </div>
                <span class="text-sm font-bold text-ink">{{ $s['count'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Recent signups + Top tenants by revenue --}}
<div class="grid lg:grid-cols-3 gap-6 mb-8">

    {{-- Recent signups --}}
    <div class="lg:col-span-2 lmt-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Recent Signups</h3>
            <a href="{{ route('superadmin.tenants.index') }}"
               class="text-xs font-semibold text-brand-500 hover:text-brand-600 transition-colors flex items-center gap-1">
                View all <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
        @if($recentTenants->isEmpty())
        <div class="text-center py-10">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="building-2" class="w-6 h-6 text-gray-400"></i>
            </div>
            <p class="text-sm text-ink-soft">No tenants yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr><th>Company</th><th>Plan</th><th>Status</th><th>Joined</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($recentTenants as $tenant)
                    @php
                    $statusClasses = ['active'=>'lmt-badge-green','trial'=>'lmt-badge-brand','past_due'=>'lmt-badge-amber','suspended'=>'lmt-badge-red','cancelled'=>'lmt-badge-gray','pending'=>'lmt-badge-gray'];
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="lmt-avatar-sm font-bold text-xs">{{ substr($tenant->company_name, 0, 1) }}</div>
                                <div>
                                    <p class="font-semibold text-ink text-sm">{{ $tenant->company_name }}</p>
                                    <p class="text-xs text-ink-soft">{{ $tenant->contact_email }}</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="lmt-badge-brand text-xs">{{ $tenant->activeSubscription?->plan?->name ?? 'Trial' }}</span></td>
                        <td><span class="{{ $statusClasses[$tenant->status] ?? 'lmt-badge-gray' }} text-xs">{{ ucfirst(str_replace('_',' ',$tenant->status)) }}</span></td>
                        <td class="text-sm text-ink-soft">{{ $tenant->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}"
                               class="text-brand-500 hover:text-brand-700">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Right column: Plan distribution + Top tenants --}}
    <div class="space-y-5">

        {{-- Plan distribution --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Plan Distribution</h3>
            <div class="space-y-3">
                @forelse($planStats as $plan)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-ink">{{ $plan['name'] }}</span>
                        <span class="text-sm font-bold text-ink">{{ $plan['count'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full lmt-gradient-bg"
                             style="width: {{ $stats['active_tenants'] > 0 ? round(($plan['count'] / max($stats['active_tenants'],1)) * 100) : 0 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-ink-soft text-center py-4">No plans configured.</p>
                @endforelse
            </div>
        </div>

        {{-- Top 5 tenants by revenue --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Top by Revenue</h3>
                <a href="{{ route('superadmin.analytics.index') }}" class="text-xs text-brand-500 hover:text-brand-700 font-semibold">Full report</a>
            </div>
            <div class="space-y-3">
                @forelse($topTenants as $i => $t)
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-ink-soft w-4 text-right flex-shrink-0">{{ $i+1 }}</span>
                    <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">{{ substr($t->company_name,0,1) }}</div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('superadmin.tenants.show', $t) }}"
                           class="text-sm font-semibold text-ink hover:text-brand-600 truncate block">{{ $t->company_name }}</a>
                    </div>
                    <span class="text-sm font-bold text-emerald-600 flex-shrink-0">${{ number_format($t->lifetime_revenue ?? 0, 0) }}</span>
                </div>
                @empty
                <p class="text-sm text-ink-soft text-center py-4">No revenue yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="lmt-card">
            <p class="text-xs font-semibold text-ink-soft uppercase tracking-wider mb-3">Quick Actions</p>
            <div class="space-y-1.5">
                @foreach([
                    ['route'=>'superadmin.plans.index',     'icon'=>'layers',       'label'=>'Manage Plans',    'bg'=>'bg-brand-50',   'text'=>'text-brand-600',  'hover'=>'group-hover:bg-brand-500'],
                    ['route'=>'superadmin.payments.index',  'icon'=>'credit-card',  'label'=>'Payments',        'bg'=>'bg-amber-50',   'text'=>'text-amber-600',  'hover'=>'group-hover:bg-amber-500'],
                    ['route'=>'superadmin.tickets.index',   'icon'=>'life-buoy',    'label'=>'Support Tickets', 'bg'=>'bg-purple-50',  'text'=>'text-purple-600', 'hover'=>'group-hover:bg-purple-500'],
                    ['route'=>'superadmin.audit.index',     'icon'=>'shield-check', 'label'=>'Audit Logs',      'bg'=>'bg-emerald-50', 'text'=>'text-emerald-600','hover'=>'group-hover:bg-emerald-500'],
                ] as $action)
                <a href="{{ route($action['route']) }}"
                   class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-7 h-7 rounded-lg {{ $action['bg'] }} {{ $action['text'] }} flex items-center justify-center {{ $action['hover'] }} group-hover:text-white transition-colors flex-shrink-0">
                        <i data-lucide="{{ $action['icon'] }}" class="w-3.5 h-3.5"></i>
                    </div>
                    <span class="text-sm font-medium text-ink">{{ $action['label'] }}</span>
                    @if($action['route'] === 'superadmin.tickets.index' && $stats['open_tickets'] > 0)
                    <span class="ml-auto lmt-badge-red text-xs">{{ $stats['open_tickets'] }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx && window.Chart) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: @json($revenueChart['labels']),
                datasets: [{
                    label: 'Revenue ($)',
                    data: @json($revenueChart['data']),
                    borderColor: '#6C7DF7',
                    backgroundColor: ctx => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                        g.addColorStop(0, 'rgba(108,125,247,0.15)');
                        g.addColorStop(1, 'rgba(108,125,247,0)');
                        return g;
                    },
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6C7DF7',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { callback: v => '$' + v.toLocaleString(), font: { family: 'Nunito Sans', size: 11 } }
                    },
                    x: { grid: { display: false }, ticks: { font: { family: 'Nunito Sans', size: 11 } } }
                }
            }
        });
    }

    // Status Doughnut
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && window.Chart) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Trial', 'Past Due', 'Suspended'],
                datasets: [{
                    data: [
                        {{ $stats['active_tenants'] }},
                        {{ $stats['trial_tenants'] }},
                        {{ $stats['past_due_tenants'] }},
                        {{ $stats['suspended_tenants'] }},
                    ],
                    backgroundColor: ['#10B981','#6C7DF7','#F59E0B','#EF4444'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }
                }
            }
        });
    }
});
</script>
@endpush
