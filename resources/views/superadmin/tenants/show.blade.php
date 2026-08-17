@extends('layouts.superadmin')
@section('title', $tenant->company_name)
@section('page-title', $tenant->company_name)

@section('content')

@php
$statusMap  = ['active'=>'lmt-badge-green','trial'=>'lmt-badge-brand','past_due'=>'lmt-badge-amber','suspended'=>'lmt-badge-red','cancelled'=>'lmt-badge-gray','pending'=>'lmt-badge-gray'];
$sub        = $tenant->subscriptions->where('status','active')->first()
           ?? $tenant->subscriptions->where('status','trialing')->first()
           ?? $tenant->subscriptions->first();
$plan       = $sub?->plan;
$notes      = $tenant->settings['admin_notes'] ?? [];
@endphp

{{-- Page header --}}
<div class="flex items-start justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.organizations.index') }}"
           class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4 text-gray-500"></i>
        </a>
        <div class="w-14 h-14 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-xl font-black flex-shrink-0">
            {{ substr($tenant->company_name, 0, 1) }}
        </div>
        <div>
            <h2 class="text-xl font-black text-ink" style="font-family:'Nunito',sans-serif">{{ $tenant->company_name }}</h2>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <code class="text-xs bg-gray-100 px-2 py-0.5 rounded font-mono">{{ $tenant->slug }}</code>
                <span class="{{ $statusMap[$tenant->status] ?? 'lmt-badge-gray' }} text-xs">
                    {{ ucfirst(str_replace('_',' ',$tenant->status)) }}
                </span>
                @if($tenant->isOnTrial())
                <span class="text-xs text-amber-600 font-semibold">
                    <i data-lucide="clock" class="w-3 h-3 inline-block mr-0.5"></i>
                    Trial ends {{ $tenant->trial_ends_at->diffForHumans() }}
                </span>
                @endif
                @if($tenant->last_activity_at)
                <span class="text-xs text-ink-soft">Last active: {{ $tenant->last_activity_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('superadmin.organizations.edit', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Edit
        </a>
        @if($tenant->database_provisioned)
        <a href="{{ $tenant->adminUrl() }}" target="_blank" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Open Portal
        </a>
        <a href="{{ route('superadmin.organizations.impersonate', $tenant) }}" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Impersonate
        </a>
        @endif
        @if($tenant->status === 'suspended')
        <form action="{{ route('superadmin.organizations.unsuspend', $tenant) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-sm" style="background:#10B981;color:#fff;display:inline-flex;align-items:center;gap:.5rem;padding:.375rem .75rem;border-radius:.75rem;font-weight:600;font-size:.75rem;border:none;cursor:pointer;">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Reactivate
            </button>
        </form>
        @elseif(!in_array($tenant->status,['suspended','cancelled']))
        <button onclick="openModal('suspend-modal')" class="lmt-btn-sm border border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-500 hover:text-white rounded-xl px-3 py-1.5 text-xs font-semibold flex items-center gap-1.5 transition-colors">
            <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i> Suspend
        </button>
        @endif
    </div>
</div>

{{-- KPI row --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
    @foreach([
        ['label'=>'Employees',        'value'=>number_format($tenant->employees_count), 'icon'=>'users',        'bg'=>'bg-brand-50',   'text'=>'text-brand-600'],
        ['label'=>'Lifetime Revenue', 'value'=>'$'.number_format($lifetimeRevenue,0),   'icon'=>'dollar-sign',  'bg'=>'bg-emerald-50', 'text'=>'text-emerald-600'],
        ['label'=>'Support Tickets',  'value'=>$tenant->supportTickets->count(),        'icon'=>'life-buoy',    'bg'=>'bg-purple-50',  'text'=>'text-purple-600'],
    ] as $s)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $s['label'] }}</p>
            <p class="lmt-stat-value">{{ $s['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left column --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Usage Meters --}}
        @if($plan)
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Plan Usage</h3>
                <span class="lmt-badge-brand text-xs">{{ $plan->name }}</span>
            </div>
            <div class="space-y-5">
                @php
                $meters = [
                    ['label'=>'Employees', 'used'=>$tenant->employees_count, 'max'=>$plan->max_employees, 'icon'=>'users'],
                    ['label'=>'Admins',    'used'=>$tenant->admins_count,    'max'=>$plan->max_admins,    'icon'=>'shield'],
                ];
                @endphp
                @foreach($meters as $meter)
                @php
                $pct = ($meter['max'] && $meter['max'] > 0) ? min(round(($meter['used'] / $meter['max']) * 100), 100) : 0;
                $barColor = $pct >= 90 ? 'bg-red-500' : ($pct >= 75 ? 'bg-amber-500' : 'lmt-gradient-bg');
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2">
                            <i data-lucide="{{ $meter['icon'] }}" class="w-4 h-4 text-ink-soft"></i>
                            <span class="text-sm font-medium text-ink">{{ $meter['label'] }}</span>
                        </div>
                        <span class="text-sm font-bold text-ink">
                            {{ $meter['used'] }}{{ isset($meter['unit']) ? ' '.$meter['unit'] : '' }}
                            <span class="text-ink-soft font-normal">/ {{ $meter['max'] ? $meter['max'].( isset($meter['unit']) ? ' '.$meter['unit'] : '') : '∞' }}</span>
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        @if($meter['max'])
                        <div class="h-2.5 rounded-full {{ $barColor }} transition-all duration-700"
                             style="width: {{ $pct }}%"></div>
                        @else
                        <div class="h-2.5 rounded-full bg-gray-200"></div>
                        @endif
                    </div>
                    @if($meter['max'] && $pct >= 90)
                    <p class="text-xs text-red-500 mt-1 font-medium">Approaching limit — consider upgrading</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Subscription history --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Subscriptions</h3>
                <button onclick="openModal('plan-modal')" class="lmt-btn-ghost lmt-btn-sm">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Change Plan
                </button>
            </div>
            @forelse($tenant->subscriptions as $s)
            <div class="p-4 rounded-xl {{ in_array($s->status,['active','trialing']) ? 'bg-brand-50 border border-brand-200' : 'bg-gray-50 border border-gray-200' }} mb-3 last:mb-0">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-ink">{{ $s->plan?->name ?? 'Unknown Plan' }}</span>
                        <span class="{{ in_array($s->status,['active','trialing']) ? 'lmt-badge-brand' : 'lmt-badge-gray' }} text-xs capitalize">{{ $s->status }}</span>
                    </div>
                    <p class="font-bold text-ink">${{ number_format($s->amount, 2) }}<span class="text-xs text-ink-soft font-normal">/{{ $s->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</span></p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-ink-soft">
                    <div class="flex justify-between"><span>Billing cycle:</span><span class="font-medium text-ink capitalize">{{ $s->billing_cycle }}</span></div>
                    <div class="flex justify-between"><span>Period ends:</span><span class="font-medium text-ink">{{ $s->current_period_ends_at?->format('M j, Y') ?? '—' }}</span></div>
                    @if($s->trial_ends_at)
                    <div class="flex justify-between col-span-2"><span>Trial ends:</span><span class="font-medium text-amber-600">{{ $s->trial_ends_at->format('M j, Y') }}</span></div>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-sm text-ink-soft text-center py-8">No subscriptions yet.</p>
            @endforelse
        </div>

        {{-- Payment history --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Recent Payments</h3>
                <a href="{{ route('superadmin.payments.index', ['tenant_id'=>$tenant->id]) }}"
                   class="text-xs text-brand-500 hover:text-brand-700 font-semibold flex items-center gap-1">
                    View all <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>
            @if($tenant->payments->isEmpty())
            <p class="text-sm text-ink-soft text-center py-8">No payments recorded.</p>
            @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead><tr><th>Amount</th><th>Status</th><th>Method</th><th>Date</th><th>Receipt</th></tr></thead>
                    <tbody>
                        @foreach($tenant->payments as $pay)
                        <tr>
                            <td><span class="font-bold text-ink">${{ number_format($pay->amount, 2) }}</span></td>
                            <td><span class="text-xs {{ $pay->status==='succeeded'?'lmt-badge-green':'lmt-badge-red' }}">{{ ucfirst($pay->status) }}</span></td>
                            <td>
                                @if($pay->card_brand)
                                <span class="text-xs text-ink-soft capitalize">{{ $pay->card_brand }} ••••{{ $pay->card_last4 }}</span>
                                @else<span class="text-gray-300">—</span>@endif
                            </td>
                            <td class="text-sm text-ink-soft">{{ $pay->paid_at?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @if($pay->receipt_url)
                                <a href="{{ $pay->receipt_url }}" target="_blank" class="text-brand-500 hover:text-brand-700">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                </a>
                                @else<span class="text-gray-300">—</span>@endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Support tickets --}}
        @if($tenant->supportTickets->isNotEmpty())
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Support Tickets</h3>
                <a href="{{ route('superadmin.tickets.index') }}" class="text-xs text-brand-500 hover:text-brand-700 font-semibold flex items-center gap-1">
                    View all <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>
            <div class="space-y-3">
                @foreach($tenant->supportTickets as $ticket)
                @php $tStatusMap=['open'=>'lmt-badge-brand','in_progress'=>'lmt-badge-amber','resolved'=>'lmt-badge-green','closed'=>'lmt-badge-gray','waiting_on_customer'=>'lmt-badge-gray']; @endphp
                <div class="flex items-start justify-between py-2 border-b border-gray-50 last:border-none gap-3">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('superadmin.tickets.show', $ticket) }}"
                           class="text-sm font-semibold text-ink hover:text-brand-600 truncate block">{{ $ticket->subject }}</a>
                        <p class="text-xs text-ink-soft font-mono">{{ $ticket->ticket_number }} · {{ $ticket->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="{{ $tStatusMap[$ticket->status]??'lmt-badge-gray' }} text-xs flex-shrink-0">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Internal Notes --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="sticky-note" class="w-4 h-4 text-amber-500"></i>
                    <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Internal Notes</h3>
                </div>
                <span class="text-xs text-ink-soft">Staff-only · not visible to organization</span>
            </div>

            {{-- Add note --}}
            <form action="{{ route('superadmin.organizations.notes.add', $tenant) }}" method="POST" class="mb-4">
                @csrf
                <textarea name="note" rows="2" class="lmt-textarea mb-2 text-sm"
                          placeholder="Add an internal note about this organization…" required></textarea>
                <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Note
                </button>
            </form>

            {{-- Existing notes --}}
            @if(empty($notes))
            <p class="text-sm text-ink-soft text-center py-4 border-2 border-dashed border-gray-200 rounded-xl">
                No notes yet.
            </p>
            @else
            <div class="space-y-3">
                @foreach($notes as $i => $note)
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 flex gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-ink">{{ $note['text'] }}</p>
                        <p class="text-xs text-ink-soft mt-1">
                            <span class="font-semibold">{{ $note['by'] }}</span> · {{ \Carbon\Carbon::parse($note['at'])->format('M j, Y H:i') }}
                        </p>
                    </div>
                    <form action="{{ route('superadmin.organizations.notes.delete', $tenant) }}" method="POST" class="flex-shrink-0">
                        @csrf @method('DELETE')
                        <input type="hidden" name="index" value="{{ $i }}">
                        <button type="submit" class="w-7 h-7 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition-colors">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Right sidebar --}}
    <div class="space-y-5">

        {{-- Company details --}}
        <div class="lmt-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Organization Details</h3>
                <a href="{{ route('superadmin.organizations.edit', $tenant) }}"
                   class="text-xs text-brand-500 hover:text-brand-700 font-semibold flex items-center gap-1">
                    <i data-lucide="edit-2" class="w-3 h-3"></i> Edit
                </a>
            </div>
            <dl class="space-y-2.5">
                @foreach([
                    ['label'=>'Contact',    'value'=>$tenant->contact_name],
                    ['label'=>'Email',      'value'=>$tenant->contact_email],
                    ['label'=>'Phone',      'value'=>$tenant->contact_phone ?? '—'],
                    ['label'=>'Industry',   'value'=>$tenant->industry ?? '—'],
                    ['label'=>'Size',       'value'=>$tenant->company_size ? number_format($tenant->company_size).' employees' : '—'],
                    ['label'=>'Country',    'value'=>$tenant->country],
                    ['label'=>'Timezone',   'value'=>$tenant->timezone],
                    ['label'=>'Website',    'value'=>$tenant->website ?? '—'],
                    ['label'=>'Database',   'value'=>$tenant->database_name, 'mono'=>true],
                ] as $row)
                <div class="flex justify-between py-1.5 border-b border-gray-50 last:border-none">
                    <dt class="text-xs text-ink-soft">{{ $row['label'] }}</dt>
                    <dd class="text-xs font-semibold text-ink text-right max-w-[160px] truncate {{ isset($row['mono']) ? 'font-mono' : '' }}">{{ $row['value'] }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

        {{-- Trial controls --}}
        @if(in_array($tenant->status, ['trial', 'past_due']))
        <div class="lmt-card border-amber-200">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Trial Period</h3>
            </div>
            <div class="bg-amber-50 rounded-xl p-3 mb-4">
                <p class="text-xs text-ink-soft">Ends</p>
                <p class="text-sm font-bold text-amber-700 mt-0.5">
                    {{ $tenant->trial_ends_at?->format('M j, Y') ?? '—' }}
                    @if($tenant->trial_ends_at)
                    <span class="font-normal text-xs">({{ $tenant->trial_ends_at->diffForHumans() }})</span>
                    @endif
                </p>
            </div>
            <form action="{{ route('superadmin.organizations.extend-trial', $tenant) }}" method="POST">
                @csrf @method('PATCH')
                <label class="lmt-label text-xs">Extend by (days)</label>
                <div class="flex gap-2 mt-1">
                    <input type="number" name="days" min="1" max="365" value="14"
                           class="lmt-input text-sm flex-1">
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm flex-shrink-0">Extend</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-4" style="font-family:'Nunito',sans-serif">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('superadmin.payments.index', ['tenant_id'=>$tenant->id]) }}"
                   class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-medium text-ink">All Payments</span>
                </a>
                <a href="{{ route('superadmin.tickets.index') }}"
                   class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-500 group-hover:text-white transition-colors">
                        <i data-lucide="life-buoy" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-medium text-ink">Support Tickets</span>
                </a>
                <button onclick="openModal('suspend-modal')"
                        class="w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-amber-50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <i data-lucide="pause-circle" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-medium text-ink">Suspend Organization</span>
                </button>
            </div>
        </div>

        {{-- Metadata --}}
        <div class="lmt-card">
            <h3 class="font-bold text-ink mb-3" style="font-family:'Nunito',sans-serif">Metadata</h3>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between"><dt class="text-ink-soft">Joined</dt><dd class="font-semibold text-ink">{{ $tenant->created_at->format('M j, Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">Trial started</dt><dd class="font-semibold text-ink">{{ $tenant->created_at->format('M j, Y') }}</dd></div>
                @if($tenant->suspended_at)
                <div class="flex justify-between"><dt class="text-ink-soft">Suspended</dt><dd class="font-semibold text-red-600">{{ $tenant->suspended_at->format('M j, Y') }}</dd></div>
                @endif
                @if($tenant->suspension_reason)
                <div class="col-span-2">
                    <dt class="text-ink-soft mb-1">Suspension reason</dt>
                    <dd class="bg-red-50 text-red-700 text-xs p-2 rounded-lg">{{ $tenant->suspension_reason }}</dd>
                </div>
                @endif
                <div class="flex justify-between"><dt class="text-ink-soft">Onboarded</dt><dd class="{{ $tenant->is_onboarded ? 'text-emerald-600' : 'text-amber-600' }} font-semibold">{{ $tenant->is_onboarded ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-soft">DB Provisioned</dt><dd class="{{ $tenant->database_provisioned ? 'text-emerald-600' : 'text-red-500' }} font-semibold">{{ $tenant->database_provisioned ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-ink-soft">Branding</dt>
                    <dd class="flex items-center gap-1">
                        <span class="w-4 h-4 rounded-sm border border-gray-200 inline-block" style="background:{{ $tenant->primary_color }}"></span>
                        <span class="font-mono text-ink">{{ $tenant->primary_color }}</span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Danger zone --}}
        <div class="lmt-card border-red-100">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i>
                <h3 class="font-bold text-red-600 text-sm" style="font-family:'Nunito',sans-serif">Danger Zone</h3>
            </div>
            <p class="text-xs text-ink-soft mb-3">Permanently drops the organization database. Cannot be undone.</p>
            <button onclick="openModal('delete-modal')" class="lmt-btn-danger lmt-btn-sm w-full justify-center">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Organization
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════ MODALS ═══════════════════════════════ --}}

{{-- Change Plan modal --}}
<div id="plan-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Change Subscription Plan</h3>
                <p class="text-sm text-ink-soft">{{ $tenant->company_name }}</p>
            </div>
        </div>
        <form action="{{ route('superadmin.organizations.change-plan', $tenant) }}" method="POST">
            @csrf @method('PATCH')
            <div class="mb-4">
                <label class="lmt-label">Select Plan <span class="text-red-500">*</span></label>
                <select name="plan_id" class="lmt-select" required>
                    @foreach($plans as $p)
                    <option value="{{ $p->id }}" {{ $sub?->plan_id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} — ${{ number_format($p->monthly_price,0) }}/mo or ${{ number_format($p->yearly_price,0) }}/yr
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="lmt-label">Billing Cycle</label>
                <select name="billing_cycle" class="lmt-select">
                    <option value="monthly" {{ $sub?->billing_cycle==='monthly'?'selected':'' }}>Monthly</option>
                    <option value="yearly"  {{ $sub?->billing_cycle==='yearly' ?'selected':'' }}>Yearly</option>
                </select>
            </div>
            <p class="text-xs text-amber-700 bg-amber-50 rounded-xl p-3 mb-4">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline-block mr-1"></i>
                Current active subscriptions will be cancelled and a new one created immediately.
            </p>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Apply Change</button>
                <button type="button" onclick="closeModal('plan-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Suspend Modal --}}
<div id="suspend-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="pause-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Suspend Organization</h3>
                <p class="text-sm text-ink-soft">{{ $tenant->company_name }}</p>
            </div>
        </div>
        <form action="{{ route('superadmin.organizations.suspend', $tenant) }}" method="POST">
            @csrf @method('PATCH')
            <div class="mb-4">
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3"
                          placeholder="Explain why this organization is being suspended…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Suspend</button>
                <button type="button" onclick="closeModal('suspend-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="text-center mb-5">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="alert-triangle" class="w-7 h-7"></i>
            </div>
            <h3 class="font-black text-ink text-lg" style="font-family:'Nunito',sans-serif">Delete Organization</h3>
            <p class="text-sm text-ink-soft mt-1">This will permanently drop <strong>{{ $tenant->database_name }}</strong> and all data.</p>
        </div>
        <form action="{{ route('superadmin.organizations.destroy', $tenant) }}" method="POST">
            @csrf @method('DELETE')
            <div class="mb-4">
                <label class="lmt-label">Type <strong>{{ $tenant->company_name }}</strong> to confirm</label>
                <input type="text" name="confirm_name" required class="lmt-input" placeholder="{{ $tenant->company_name }}">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Delete Permanently</button>
                <button type="button" onclick="closeModal('delete-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });

function openModal(id) {
    const m = document.getElementById(id);
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeModal(id) {
    const m = document.getElementById(id);
    m.classList.add('hidden');
    m.classList.remove('flex');
}
// Close on backdrop click
document.querySelectorAll('.lmt-modal-backdrop').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); });
});
</script>
@endpush
