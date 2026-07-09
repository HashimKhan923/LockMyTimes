@extends('layouts.superadmin')
@section('title','Audit Logs')
@section('page-title','Audit Logs')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">Audit Logs</h2>
        <p class="text-sm text-ink-soft mt-0.5">Full audit trail of all superadmin actions</p>
    </div>
</div>

{{-- Filters --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form action="{{ route('superadmin.audit.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div class="relative flex-1 min-w-48">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm" placeholder="Search events, IP, URL…">
            </div>
            <select name="event" class="lmt-select text-sm py-2">
                <option value="">All Events</option>
                @foreach($events as $evt)
                <option value="{{ $evt }}" {{ request('event')===$evt?'selected':'' }}>{{ ucwords(str_replace('_',' ',$evt)) }}</option>
                @endforeach
            </select>
            <select name="admin_id" class="lmt-select text-sm py-2">
                <option value="">All Admins</option>
                @foreach($admins as $admin)
                <option value="{{ $admin->id }}" {{ request('admin_id')==$admin->id?'selected':'' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
            <select name="tenant_id" class="lmt-select text-sm py-2">
                <option value="">All Organizations</option>
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id?'selected':'' }}>{{ $t->company_name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="lmt-input text-sm py-2" title="From">
            <input type="date" name="to"   value="{{ request('to') }}"   class="lmt-input text-sm py-2" title="To">
            <div class="flex gap-2">
                <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
                @if(request()->hasAny(['search','event','admin_id','tenant_id','from','to']))
                <a href="{{ route('superadmin.audit.index') }}" class="lmt-btn-ghost lmt-btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Actor</th>
                    <th>Organization</th>
                    <th>Target</th>
                    <th>IP Address</th>
                    <th>Date</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                $eventColors = [
                    'login'   => 'lmt-badge-green',
                    'logout'  => 'lmt-badge-gray',
                    'create'  => 'lmt-badge-brand',
                    'update'  => 'lmt-badge-amber',
                    'delete'  => 'lmt-badge-red',
                    'suspend' => 'lmt-badge-red',
                    'impersonate' => 'lmt-badge-amber',
                ];
                $eventColor = collect($eventColors)->first(fn($v,$k) => str_contains($log->event,$k), 'lmt-badge-gray');
                $modelName = $log->auditable_type ? class_basename($log->auditable_type) : '—';
                @endphp
                <tr>
                    <td>
                        <span class="{{ $eventColor }} text-xs">
                            {{ ucwords(str_replace('_',' ',$log->event)) }}
                        </span>
                    </td>
                    <td>
                        @if($log->superAdmin)
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                                 style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                                {{ substr($log->superAdmin->name,0,1) }}
                            </div>
                            <span class="text-sm font-medium text-ink">{{ $log->superAdmin->name }}</span>
                        </div>
                        @else
                        <span class="text-xs text-gray-400 italic">System</span>
                        @endif
                    </td>
                    <td>
                        @if($log->tenant)
                        <span class="text-sm text-ink">{{ $log->tenant->company_name }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->auditable_type)
                        <div>
                            <p class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded inline-block">{{ $modelName }}</p>
                            @if($log->auditable_id)
                            <p class="text-xs text-ink-soft mt-0.5">#{{ $log->auditable_id }}</p>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td>
                        <code class="text-xs text-ink-soft font-mono">{{ $log->ip_address ?? '—' }}</code>
                    </td>
                    <td class="text-sm text-ink-soft whitespace-nowrap">
                        <p>{{ $log->created_at->format('M j, Y') }}</p>
                        <p class="text-xs">{{ $log->created_at->format('H:i:s') }}</p>
                    </td>
                    <td>
                        @if(!empty($log->new_values) || !empty($log->old_values))
                        <button onclick="showDiff({{ $log->id }})"
                                class="text-xs font-semibold text-brand-500 hover:text-brand-700 flex items-center gap-1">
                            <i data-lucide="diff" class="w-3.5 h-3.5"></i> View
                        </button>
                        <div id="diff-{{ $log->id }}" class="hidden mt-2">
                            @if(!empty($log->old_values))
                            <div class="text-xs mb-1">
                                <p class="font-semibold text-red-600 mb-0.5">Before:</p>
                                <pre class="bg-red-50 text-red-700 p-2 rounded-lg overflow-x-auto text-xs max-w-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                            @if(!empty($log->new_values))
                            <div class="text-xs">
                                <p class="font-semibold text-emerald-600 mb-0.5">After:</p>
                                <pre class="bg-emerald-50 text-emerald-700 p-2 rounded-lg overflow-x-auto text-xs max-w-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="shield-check" class="w-7 h-7 text-gray-300"></i>
                        </div>
                        <p class="font-semibold text-ink-soft">No audit logs found</p>
                        <p class="text-xs text-gray-400 mt-1">Actions will appear here as they happen</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-sm text-ink-soft">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
        </p>
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });

function showDiff(id) {
    const el = document.getElementById('diff-' + id);
    el.classList.toggle('hidden');
}
</script>
@endpush
