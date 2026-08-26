@extends('layouts.employee')

@section('title', 'Leave Approvals')
@section('page-title', 'Leave Approvals')

@section('content')
<div>
    <div class="flex items-center justify-between mb-5" data-lmt-anim="fade-up">
        <div>
            <a href="{{ route('employee.team.index', $tenantSlug) }}"
               class="inline-flex items-center gap-1 text-xs font-bold text-gray-800 hover:text-gray-800 dark:hover:text-slate-300 mb-1">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> My Team
            </a>
            <h1 class="text-xl font-black text-gray-900 dark:text-slate-100">Leave Approvals</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="lmt-alert lmt-alert-success mb-5">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- Counters --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" data-lmt-anim="fade-up">
        @foreach([
            ['label'=>'Total',    'value'=>$counters->total,    'icon'=>'calendar',      'bg'=>'bg-gray-100',   'text'=>'text-gray-800',    'status'=>'all'],
            ['label'=>'Pending',  'value'=>$counters->pending,  'icon'=>'clock',         'bg'=>'bg-amber-50',   'text'=>'text-amber-600',   'status'=>'pending'],
            ['label'=>'Approved', 'value'=>$counters->approved, 'icon'=>'check-circle',  'bg'=>'bg-emerald-50', 'text'=>'text-emerald-600', 'status'=>'approved'],
            ['label'=>'Rejected', 'value'=>$counters->rejected, 'icon'=>'x-circle',      'bg'=>'bg-red-50',     'text'=>'text-red-500',     'status'=>'rejected'],
        ] as $s)
        <a href="{{ route('employee.team.approvals.leaves', ['tenant' => $tenantSlug, 'status' => $s['status']]) }}" class="lmt-stat">
            <div>
                <p class="lmt-stat-label">{{ $s['label'] }}</p>
                <p class="lmt-stat-value">{{ (int) $s['value'] }}</p>
            </div>
            <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
                <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
            </div>
        </a>
        @endforeach
    </div>

    <div class="lmt-card p-0 overflow-hidden" data-lmt-anim="fade-up">

        {{-- Status tabs --}}
        <div class="border-b border-gray-100 dark:border-slate-700">
            <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto">
                @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $val => $label)
                <a href="{{ route('employee.team.approvals.leaves', ['tenant' => $tenantSlug, 'status' => $val]) }}"
                   class="px-4 py-2.5 text-sm font-semibold rounded-t-lg whitespace-nowrap transition-all
                          {{ $status === $val
                              ? 'bg-white dark:bg-slate-800 border-t border-l border-r border-gray-200 dark:border-slate-700 text-brand-600 -mb-px'
                              : 'text-gray-800 hover:text-gray-800 dark:hover:text-slate-300' }}">
                    {{ $label }}
                    @if($val === 'pending' && $counters->pending > 0)
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $counters->pending }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Days</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $req)
                    @php
                        $statusColors = [
                            'pending'       => 'lmt-badge-amber',
                            'pending_final' => 'lmt-badge-amber',
                            'approved'      => 'lmt-badge-green',
                            'rejected'      => 'lmt-badge-red',
                            'cancelled'     => 'lmt-badge-gray',
                        ];
                        $statusLabels = [
                            'pending'       => 'Pending',
                            'pending_final' => 'Awaiting final sign-off',
                            'approved'      => 'Approved',
                            'rejected'      => 'Rejected',
                            'cancelled'     => 'Cancelled',
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <img src="{{ $req->employee?->avatar_url }}" alt="{{ $req->employee?->full_name }}"
                                     class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-slate-100 text-sm">{{ $req->employee?->full_name ?? '—' }}</p>
                                    <p class="text-xs text-gray-800">{{ $req->employee?->position?->title }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full" style="background:{{ $req->leaveType->color ?? '#6C7DF7' }}"></div>
                                <span class="text-sm text-gray-800 dark:text-slate-300">{{ $req->leaveType->name ?? '—' }}</span>
                                @if($req->day_part && $req->day_part !== 'full_day')
                                <span class="lmt-badge-brand text-xs">½ Day</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <p class="text-sm text-gray-900 dark:text-slate-100">
                                {{ $req->start_date->format('M j') }}
                                @if($req->start_date->ne($req->end_date))
                                    – {{ $req->end_date->format('M j, Y') }}
                                @else
                                    , {{ $req->start_date->format('Y') }}
                                @endif
                            </p>
                            @if($req->_teammates_on_leave > 0)
                            <p class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                               title="Other direct reports already on leave the same week">
                                <i data-lucide="users" class="w-2.5 h-2.5"></i>
                                {{ $req->_teammates_on_leave }} teammate{{ $req->_teammates_on_leave === 1 ? '' : 's' }} already off this week
                            </p>
                            @endif
                        </td>
                        <td>
                            <span class="text-sm font-bold text-gray-900 dark:text-slate-100">
                                {{ (float) $req->total_days }}
                                <span class="font-normal text-gray-800">day{{ (float) $req->total_days != 1 ? 's' : '' }}</span>
                            </span>
                        </td>
                        <td class="text-sm">
                            @if($req->_available_balance !== null)
                                <span class="{{ $req->_available_balance < 0 ? 'text-red-500' : 'text-gray-800 dark:text-slate-300' }} font-semibold">
                                    {{ number_format($req->_available_balance, 1) }}
                                </span>
                            @else
                                <span class="text-gray-800">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $statusColors[$req->status] ?? 'lmt-badge-gray' }} text-xs">
                                {{ $statusLabels[$req->status] ?? ucfirst($req->status) }}
                            </span>
                            @if($req->status === 'rejected' && $req->rejection_reason)
                            <p class="text-xs text-red-600 mt-0.5 max-w-40 truncate" title="{{ $req->rejection_reason }}">
                                {{ $req->rejection_reason }}
                            </p>
                            @endif
                        </td>
                        <td>
                            @if($req->status === 'pending')
                            <div class="flex items-center gap-1.5">
                                <form action="{{ route('employee.team.leaves.approve', [$tenantSlug, $req->id]) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                            title="Approve">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                                <button type="button"
                                        onclick="openRejectModal('{{ route('employee.team.leaves.reject', [$tenantSlug, $req->id]) }}')"
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Reject">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            @else
                            <span class="text-xs text-gray-800">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-14">
                            <i data-lucide="calendar-check" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                            <p class="font-semibold text-gray-800">No {{ $status === 'all' ? '' : $status }} leave requests</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaves->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-slate-700">{{ $leaves->links() }}</div>
        @endif
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
                <textarea name="reason" required minlength="5" class="lmt-textarea" rows="3"
                          placeholder="Explain why this leave is being rejected…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Reject Leave</button>
                <button type="button" onclick="closeModal('reject-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

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
function openRejectModal(actionUrl) {
    document.getElementById('reject-form').action = actionUrl;
    openModal('reject-modal');
}
document.getElementById('reject-modal')?.addEventListener('click', function (e) {
    if (e.target === this) closeModal('reject-modal');
});
</script>
@endpush

@endsection
