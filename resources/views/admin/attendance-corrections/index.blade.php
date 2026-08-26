@extends('layouts.admin')
@section('title','Attendance Corrections')
@section('page-title','Attendance Corrections')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    @foreach([
        ['label'=>'Pending Approval', 'value'=>$stats['pending'],  'icon'=>'clock',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Approved (Month)', 'value'=>$stats['approved'], 'icon'=>'check-circle', 'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Rejected (Month)', 'value'=>$stats['rejected'], 'icon'=>'x-circle',     'bg'=>'bg-gray-100',  'text'=>'text-gray-800'],
    ] as $s)
    <a href="{{ route('admin.attendance-corrections.index', $tenant) }}?status={{ $s['label'] === 'Pending Approval' ? 'pending' : ($s['label'] === 'Approved (Month)' ? 'approved' : 'rejected') }}" class="lmt-stat">
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

<div class="lmt-card p-0 overflow-hidden">

    {{-- Tabs --}}
    <div class="border-b border-gray-100">
        <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto">
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled','all'=>'All'] as $val => $label)
            <a href="{{ route('admin.attendance-corrections.index', $tenant) }}?status={{ $val }}"
               class="px-4 py-2.5 text-sm font-semibold rounded-t-lg whitespace-nowrap transition-all
                      {{ $status === $val
                          ? 'bg-white border-t border-l border-r border-gray-200 text-brand-600 -mb-px'
                          : 'text-gray-800 hover:text-gray-800' }}">
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
        <form action="{{ route('admin.attendance-corrections.index', $tenant) }}" method="GET" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="status" value="{{ $status }}"/>
            <select name="employee" class="lmt-select py-2 text-sm w-auto min-w-40" onchange="this.form.submit()">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">Filter</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Proposed Times</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
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
                                <p class="text-xs text-gray-800">{{ $req->employee->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm text-gray-900">{{ $req->work_date->format('M j, Y') }}</td>
                    <td class="text-sm text-gray-800">
                        @if($req->proposed_clock_in)
                        In: {{ \Carbon\Carbon::parse($req->proposed_clock_in)->format('h:i A') }}<br/>
                        @endif
                        @if($req->proposed_clock_out)
                        Out: {{ \Carbon\Carbon::parse($req->proposed_clock_out)->format('h:i A') }}
                        @endif
                    </td>
                    <td class="text-xs text-gray-800 max-w-48 truncate" title="{{ $req->reason }}">{{ $req->reason }}</td>
                    <td>
                        <div>
                            <span class="{{ $statusColors[$req->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                                {{ $req->status }}
                            </span>
                            @if($req->status === 'approved' && $req->approver)
                            <p class="text-xs text-gray-800 mt-0.5">by {{ $req->approver->name }}</p>
                            @endif
                            @if($req->status === 'rejected' && $req->rejection_reason)
                            <p class="text-xs text-red-600 mt-0.5 max-w-28 truncate" title="{{ $req->rejection_reason }}">
                                {{ $req->rejection_reason }}
                            </p>
                            @endif
                        </div>
                    </td>
                    <td class="text-xs text-gray-800">{{ $req->created_at->diffForHumans() }}</td>
                    <td>
                        @if($req->status === 'pending')
                        <div class="flex items-center gap-1.5">
                            <form action="{{ route('admin.attendance-corrections.approve', [$tenant, $req->id]) }}" method="POST">
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
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-14">
                        <i data-lucide="clock" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="font-semibold text-gray-800">No {{ $status === 'all' ? '' : $status }} correction requests</p>
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

{{-- Reject Modal --}}
<div id="reject-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Reject Correction Request</h3>
        <form id="reject-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Reason for Rejection <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3"
                          placeholder="Explain why this correction is being rejected…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Reject Request</button>
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
function openRejectModal(correctionId) {
    document.getElementById('reject-form').action =
        `/t/{{ $tenant }}/admin/attendance-corrections/${correctionId}/reject`;
    openModal('reject-modal');
}
document.getElementById('reject-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal('reject-modal');
});
</script>
@endpush
