@extends('layouts.admin')
@section('title','Leave Management')
@section('page-title','Leave Management')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Pending Approval', 'value'=>$stats['pending'],       'icon'=>'clock',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600',  'route'=>'?status=pending'],
        ['label'=>'On Leave Today',   'value'=>$stats['on_leave_today'],'icon'=>'user-minus',   'bg'=>'bg-red-50',    'text'=>'text-red-600',    'route'=>'?status=approved'],
        ['label'=>'Approved (Month)', 'value'=>$stats['approved'],      'icon'=>'check-circle', 'bg'=>'bg-emerald-50','text'=>'text-emerald-600','route'=>'?status=approved'],
        ['label'=>'Rejected (Month)', 'value'=>$stats['rejected'],      'icon'=>'x-circle',     'bg'=>'bg-gray-100',  'text'=>'text-gray-600',   'route'=>'?status=rejected'],
    ] as $s)
    <a href="{{ route('admin.leaves.index', $tenant) }}{{ $s['route'] }}" class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $s['label'] }}</p>
            <p class="lmt-stat-value">{{ $s['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
    </a>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Leave Requests Table --}}
    <div class="lg:col-span-2">
        <div class="lmt-card p-0 overflow-hidden">

            {{-- Tabs + filters --}}
            <div class="border-b border-gray-100">
                <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto">
                    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled','all'=>'All'] as $val => $label)
                    <a href="{{ route('admin.leaves.index', $tenant) }}?status={{ $val }}"
                       class="px-4 py-2.5 text-sm font-semibold rounded-t-lg whitespace-nowrap transition-all
                              {{ $status === $val
                                  ? 'bg-white border-t border-l border-r border-gray-200 text-brand-600 -mb-px'
                                  : 'text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                        @if($val === 'pending' && $stats['pending'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['pending'] }}</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Filter bar --}}
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <form action="{{ route('admin.leaves.index', $tenant) }}" method="GET"
                      class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="status" value="{{ $status }}"/>
                    <select name="employee" class="lmt-select py-2 text-sm w-auto min-w-40">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }}
                        </option>
                        @endforeach
                    </select>
                    <select name="type" class="lmt-select py-2 text-sm w-auto min-w-36">
                        <option value="">All Types</option>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>
                            {{ $lt->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
                    <button type="button"
                            onclick="openModal('add-leave-modal')"
                            class="lmt-btn-secondary lmt-btn-sm ml-auto">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Leave
                    </button>
                    @include('exports.buttons', ['route' => 'admin.leaves.export', 'params' => [$tenant]])
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        @php
                        $statusColors = [
                            'pending'   => 'lmt-badge-amber',
                            'approved'  => 'lmt-badge-green',
                            'rejected'  => 'lmt-badge-red',
                            'cancelled' => 'lmt-badge-gray',
                        ];
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="lmt-avatar-sm font-bold text-xs">
                                        {{ substr($req->employee->first_name ?? 'E', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm">{{ $req->employee->full_name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $req->employee->department?->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full"
                                         style="background:{{ $req->leaveType->color ?? '#6C7DF7' }}"></div>
                                    <span class="text-sm text-gray-700">{{ $req->leaveType->name ?? '—' }}</span>
                                    @if($req->is_half_day)
                                    <span class="lmt-badge-brand text-xs">½ Day</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <p class="text-sm text-gray-900">{{ $req->start_date->format('M j') }}
                                    @if($req->start_date->ne($req->end_date))
                                    – {{ $req->end_date->format('M j, Y') }}
                                    @else
                                    , {{ $req->start_date->format('Y') }}
                                    @endif
                                </p>
                            </td>
                            <td>
                                <span class="text-sm font-bold text-gray-900">
                                    {{ $req->total_days }}
                                    <span class="font-normal text-gray-400">day{{ $req->total_days != 1 ? 's' : '' }}</span>
                                </span>
                            </td>
                            <td>
                                <div>
                                    <span class="{{ $statusColors[$req->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                                        {{ $req->status }}
                                    </span>
                                    @if($req->status === 'approved' && $req->approver)
                                    <p class="text-xs text-gray-400 mt-0.5">by {{ $req->approver->name }}</p>
                                    @endif
                                    @if($req->status === 'rejected' && $req->rejection_reason)
                                    <p class="text-xs text-red-400 mt-0.5 max-w-28 truncate" title="{{ $req->rejection_reason }}">
                                        {{ $req->rejection_reason }}
                                    </p>
                                    @endif
                                </div>
                            </td>
                            <td class="text-xs text-gray-500">
                                {{ $req->created_at->diffForHumans() }}
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    @if($req->status === 'pending')
                                    <form action="{{ route('admin.leaves.approve', [$tenant, $req->id]) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                                title="Approve">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    <button onclick="openRejectModal({{ $req->id }})"
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                                            title="Reject">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif
                                    @if(in_array($req->status, ['pending','approved']))
                                    <form action="{{ route('admin.leaves.cancel', [$tenant, $req->id]) }}" method="POST"
                                          onsubmit="return confirm('Cancel this leave?')">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-500 hover:text-white flex items-center justify-center transition-colors"
                                                title="Cancel">
                                            <i data-lucide="ban" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-14">
                                <i data-lucide="calendar-check" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                                <p class="font-semibold text-gray-400">No {{ $status === 'all' ? '' : $status }} leave requests</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="p-4 border-t border-gray-100">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Calendar + Quick Actions --}}
    <div class="space-y-5">

        {{-- Month picker + Calendar --}}
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Leave Calendar</h3>
                <form method="GET" action="{{ route('admin.leaves.index', $tenant) }}">
                    <input type="hidden" name="status" value="{{ $status }}"/>
                    <input type="month" name="month" value="{{ $month }}"
                           class="lmt-input py-1.5 text-xs w-32" onchange="this.form.submit()"/>
                </form>
            </div>

            <div class="p-4">
                {{-- Day labels --}}
                <div class="grid grid-cols-7 mb-1">
                    @foreach(['S','M','T','W','T','F','S'] as $d)
                    <div class="text-center text-xs font-bold text-gray-400 py-1">{{ $d }}</div>
                    @endforeach
                </div>

                @php
                $firstDay    = $start->copy()->startOfMonth();
                $startBlanks = $firstDay->dayOfWeek;
                $daysInMonth = $start->daysInMonth;
                @endphp

                <div class="grid grid-cols-7 gap-0.5">
                    @for($i = 0; $i < $startBlanks; $i++)
                    <div></div>
                    @endfor

                    @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                    $date       = $start->copy()->setDay($d);
                    $dateStr    = $date->toDateString();
                    $isToday    = $dateStr === today()->toDateString();
                    $isWeekend  = $date->isWeekend();
                    $leavesOnDay = $calendarLeaves->filter(fn($l)
                        => $l->start_date->lte($date) && $l->end_date->gte($date));
                    @endphp
                    <div class="relative rounded-lg min-h-8 flex flex-col items-center py-1 {{ $isToday ? 'ring-2 ring-brand-500 bg-brand-50' : ($isWeekend ? 'bg-gray-50' : '') }}">
                        <span class="text-xs {{ $isToday ? 'font-black text-brand-600' : 'text-gray-500' }}">{{ $d }}</span>
                        @if($leavesOnDay->count() > 0)
                        <div class="flex flex-col gap-0.5 w-full px-0.5 mt-0.5">
                            @foreach($leavesOnDay->take(2) as $lv)
                            <div class="w-full h-1.5 rounded-full"
                                 style="background:{{ $lv->leaveType->color ?? '#6C7DF7' }}"
                                 title="{{ $lv->employee->full_name }} - {{ $lv->leaveType->name }}">
                            </div>
                            @endforeach
                            @if($leavesOnDay->count() > 2)
                            <span class="text-[9px] text-gray-400 text-center">+{{ $leavesOnDay->count() - 2 }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endfor
                </div>

                {{-- Legend --}}
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                    @foreach($leaveTypes->take(5) as $lt)
                    <div class="flex items-center gap-2 text-xs">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $lt->color ?? '#6C7DF7' }}"></div>
                        <span class="text-gray-600 truncate">{{ $lt->name }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-3">Manage</h3>
            <div class="space-y-1.5">
                @foreach([
                    ['label'=>'Leave Balances', 'icon'=>'bar-chart-2', 'route'=>'admin.leaves.balances'],
                    ['label'=>'Leave Types',    'icon'=>'tag',         'route'=>'admin.leaves.types'],
                    ['label'=>'Holidays',       'icon'=>'calendar',    'route'=>'admin.leaves.holidays'],
                ] as $link)
                <a href="{{ route($link['route'], $tenant) }}"
                   class="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-50 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 group-hover:bg-brand-500 group-hover:text-white flex items-center justify-center transition-all">
                        <i data-lucide="{{ $link['icon'] }}" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">{{ $link['label'] }}</span>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 ml-auto"></i>
                </a>
                @endforeach

                <form action="{{ route('admin.leaves.balances.sync', $tenant) }}" method="POST"
                      onsubmit="return confirm('Initialize missing leave balances for all active employees for {{ now()->year }}?')">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-amber-50 transition-colors group text-left">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white flex items-center justify-center transition-all">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">Sync Balances</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 ml-auto"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Add Leave Modal --}}
<div id="add-leave-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Leave (Admin)</h3>
        <form action="{{ route('admin.leaves.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Leave Type <span class="text-red-500">*</span></label>
                <select name="leave_type_id" required class="lmt-select">
                    <option value="">— Select Type —</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->default_days_per_year }} days/yr)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">End Date <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" required class="lmt-input"/>
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_half_day" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-700">Half Day</span>
            </label>
            <div>
                <label class="lmt-label">Reason</label>
                <textarea name="reason" class="lmt-textarea" rows="2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Add & Approve</button>
                <button type="button" onclick="closeModal('add-leave-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Reject Leave Request</h3>
        <form id="reject-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Reason for Rejection <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3"
                          placeholder="Explain why this leave is being rejected…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Reject Leave</button>
                <button type="button" onclick="closeModal('reject-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
function openRejectModal(leaveId) {
    document.getElementById('reject-form').action =
        `/t/{{ $tenant }}/admin/leaves/${leaveId}/reject`;
    openModal('reject-modal');
}
['add-leave-modal','reject-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush