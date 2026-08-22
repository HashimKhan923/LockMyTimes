@extends('layouts.admin')
@section('title','Leave Balances')
@section('page-title','Leave Balances')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Leave Balances — {{ now()->year }}</h2>
        <p class="text-sm text-gray-800 mt-0.5">View and adjust employee leave allocations</p>
    </div>
    <a href="{{ route('admin.leaves.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back
    </a>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th class="w-48">Employee</th>
                    @foreach($leaveTypes as $lt)
                    <th class="text-center min-w-28">
                        <div class="flex items-center justify-center gap-1.5">
                            <div class="w-2 h-2 rounded-full" style="background:{{ $lt->color }}"></div>
                            {{ $lt->name }}
                        </div>
                        <div class="text-xs font-normal text-gray-800 mt-0.5">{{ $lt->days_allowed }}d/yr</div>
                    </th>
                    @endforeach
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">{{ substr($emp->first_name,0,1) }}</div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $emp->full_name }}</p>
                                <p class="text-xs text-gray-800">{{ $emp->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    @foreach($leaveTypes as $lt)
                    @php
                    $balance = $emp->leaveBalances->where('leave_type_id', $lt->id)->first();
                    $allocated = $balance?->allocated ?? $lt->days_allowed;
                    $used      = $balance?->used ?? 0;
                    $available = max(0, $allocated - $used);
                    $pct       = $allocated > 0 ? round(($used / $allocated) * 100) : 0;
                    @endphp
                    <td class="text-center">
                        <div class="inline-flex flex-col items-center">
                            <span class="text-sm font-black {{ $available <= 0 ? 'text-red-500' : ($available <= 2 ? 'text-amber-600' : 'text-gray-900') }}">
                                {{ $available }}
                            </span>
                            <span class="text-xs text-gray-800">/ {{ $allocated }}</span>
                            <div class="w-16 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width:{{ $pct }}%; background:{{ $lt->color ?? '#6C7DF7' }}"></div>
                            </div>
                        </div>
                    </td>
                    @endforeach
                    <td>
                        <button onclick="openAdjustModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}')"
                                class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $employees->links() }}</div>
    @endif
</div>

{{-- Adjust Balance Modal --}}
<div id="adjust-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-1">Adjust Leave Balance</h3>
        <p class="text-sm text-gray-800 mb-5" id="adjust-employee-name"></p>
        <form action="{{ route('admin.leaves.balance.update', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="employee_id" id="adjust-employee-id"/>
            <div>
                <label class="lmt-label">Leave Type <span class="text-red-500">*</span></label>
                <select name="leave_type_id" required class="lmt-select">
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Allocated Days</label>
                <input type="number" name="allocated" step="0.5" min="0" required class="lmt-input"
                       placeholder="e.g. 15"/>
                <p class="lmt-help">Total days this employee is entitled to per year</p>
            </div>
            <div>
                <label class="lmt-label">Manual Adjustment</label>
                <input type="number" name="adjustment" step="0.5" class="lmt-input"
                       placeholder="e.g. +2 or -1"/>
                <p class="lmt-help">Positive adds days, negative removes days</p>
            </div>
            <div>
                <label class="lmt-label">Note</label>
                <input type="text" name="note" class="lmt-input" placeholder="Reason for adjustment…"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('adjust-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
function openAdjustModal(empId, empName) {
    document.getElementById('adjust-employee-id').value = empId;
    document.getElementById('adjust-employee-name').textContent = empName;
    openModal('adjust-modal');
}
document.getElementById('adjust-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal('adjust-modal');
});
</script>
@endpush