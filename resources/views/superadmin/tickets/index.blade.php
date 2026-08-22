@extends('layouts.superadmin')
@section('title','Support Tickets')
@section('page-title','Support Tickets')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Support Tickets</h2>
        <p class="text-sm text-ink-soft mt-0.5">{{ number_format($tickets->total()) }} total tickets</p>
    </div>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach([
        ['label'=>'Open',        'value'=>$stats['open'],        'icon'=>'inbox',          'bg'=>'bg-brand-50',   'text'=>'text-brand-600'],
        ['label'=>'In Progress', 'value'=>$stats['in_progress'], 'icon'=>'loader',         'bg'=>'bg-amber-50',   'text'=>'text-amber-600'],
        ['label'=>'Resolved',    'value'=>$stats['resolved'],    'icon'=>'check-circle-2', 'bg'=>'bg-emerald-50', 'text'=>'text-emerald-600'],
        ['label'=>'Urgent',      'value'=>$stats['urgent'],      'icon'=>'alert-octagon',  'bg'=>'bg-red-50',     'text'=>'text-red-500'],
    ] as $kpi)
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

{{-- Status Tabs --}}
<div class="flex items-center gap-2 mb-5 flex-wrap">
    @php
    $statuses = ['all'=>'All', 'open'=>'Open', 'in_progress'=>'In Progress', 'waiting_on_customer'=>'Waiting', 'resolved'=>'Resolved', 'closed'=>'Closed'];
    $currentStatus = request('status', 'all');
    @endphp
    @foreach($statuses as $val => $label)
    <a href="{{ route('superadmin.tickets.index', array_merge(request()->except('status','page'), $val !== 'all' ? ['status'=>$val] : [])) }}"
       class="px-4 py-2 rounded-xl text-sm font-medium transition-all
              {{ ($currentStatus === $val || ($val==='all' && !request('status'))) ? 'bg-brand-500 text-white shadow-pop' : 'bg-white text-ink-soft border border-gray-200 hover:border-brand-300' }}">
        {{ $label }}
        @if(isset($statusCounts[$val]) && $val !== 'all')
        <span class="ml-1 text-xs {{ $currentStatus===$val ? 'bg-white/30 text-white' : 'bg-gray-100 text-ink-soft' }} px-1.5 py-0.5 rounded-full">{{ $statusCounts[$val] }}</span>
        @endif
    </a>
    @endforeach
</div>

{{-- Filters + Table --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form action="{{ route('superadmin.tickets.index') }}" method="GET" class="flex gap-3 mb-0 md:flex-nowrap flex-wrap">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="relative flex-1 min-w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-800"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm" placeholder="Search by subject, ticket #, company…">
            </div>
            <select name="priority" class="lmt-select text-sm py-2">
                <option value="">All Priorities</option>
                <option value="urgent" {{ request('priority')==='urgent' ?'selected':'' }}>Urgent</option>
                <option value="high"   {{ request('priority')==='high'   ?'selected':'' }}>High</option>
                <option value="normal" {{ request('priority')==='normal' ?'selected':'' }}>Normal</option>
                <option value="low"    {{ request('priority')==='low'    ?'selected':'' }}>Low</option>
            </select>
            <select name="category" class="lmt-select text-sm py-2">
                <option value="">All Categories</option>
                <option value="billing"         {{ request('category')==='billing'         ?'selected':'' }}>Billing</option>
                <option value="technical"       {{ request('category')==='technical'       ?'selected':'' }}>Technical</option>
                <option value="feature_request" {{ request('category')==='feature_request' ?'selected':'' }}>Feature Request</option>
                <option value="bug"             {{ request('category')==='bug'             ?'selected':'' }}>Bug</option>
                <option value="other"           {{ request('category')==='other'           ?'selected':'' }}>Other</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
                @if(request()->hasAny(['search','priority','category']))
                <a href="{{ route('superadmin.tickets.index', request()->only('status')) }}" class="lmt-btn-ghost lmt-btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Company</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                @php
                $priorityMap = [
                    'urgent' => 'lmt-badge-red',
                    'high'   => 'lmt-badge-amber',
                    'normal' => 'lmt-badge-brand',
                    'low'    => 'lmt-badge-gray',
                ];
                $statusMap = [
                    'open'                 => 'lmt-badge-brand',
                    'in_progress'          => 'lmt-badge-amber',
                    'waiting_on_customer'  => 'lmt-badge-gray',
                    'resolved'             => 'lmt-badge-green',
                    'closed'               => 'lmt-badge-gray',
                ];
                @endphp
                <tr class="{{ $ticket->priority === 'urgent' && !in_array($ticket->status,['resolved','closed']) ? 'bg-red-50/30' : '' }}">
                    <td>
                        <div>
                            <a href="{{ route('superadmin.tickets.show', $ticket) }}"
                               class="font-semibold text-brand-600 hover:text-brand-800 text-sm">{{ $ticket->subject }}</a>
                            <p class="text-xs text-ink-soft font-mono mt-0.5">{{ $ticket->ticket_number }}</p>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">
                                {{ substr($ticket->tenant?->company_name ?? '?', 0, 1) }}
                            </div>
                            <span class="text-sm font-medium text-ink">{{ $ticket->tenant?->company_name ?? 'Unknown' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="text-sm text-ink capitalize">{{ str_replace('_',' ',$ticket->category) }}</span>
                    </td>
                    <td>
                        <span class="{{ $priorityMap[$ticket->priority] ?? 'lmt-badge-gray' }} text-xs capitalize">{{ $ticket->priority }}</span>
                    </td>
                    <td>
                        <span class="{{ $statusMap[$ticket->status] ?? 'lmt-badge-gray' }} text-xs">
                            {{ ucfirst(str_replace('_',' ',$ticket->status)) }}
                        </span>
                    </td>
                    <td>
                        @if($ticket->assignee)
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                 style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                                {{ substr($ticket->assignee->name, 0, 1) }}
                            </div>
                            <span class="text-xs text-ink-soft">{{ $ticket->assignee->name }}</span>
                        </div>
                        @else
                        <span class="text-xs text-gray-800 italic">Unassigned</span>
                        @endif
                    </td>
                    <td class="text-sm text-ink-soft whitespace-nowrap">{{ $ticket->created_at->diffForHumans() }}</td>
                    <td>
                        <a href="{{ route('superadmin.tickets.show', $ticket) }}"
                           class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors"
                           title="Open ticket">
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="life-buoy" class="w-7 h-7 text-gray-800"></i>
                        </div>
                        <p class="font-semibold text-ink-soft">No tickets found</p>
                        <p class="text-xs text-gray-800 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tickets->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $tickets->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
