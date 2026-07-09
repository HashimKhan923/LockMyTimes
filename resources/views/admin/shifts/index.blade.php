@extends('layouts.admin')
@section('title','Shifts')
@section('page-title','Shift Management')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Shift Templates','value'=>$stats['total_shifts'],      'icon'=>'calendar-days','bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Assigned Staff', 'value'=>$stats['assigned_employees'],'icon'=>'user-check',  'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Unassigned',     'value'=>$stats['unassigned'],        'icon'=>'user-x',      'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Pending Swaps',  'value'=>$stats['pending_swaps'],     'icon'=>'arrow-left-right','bg'=>'bg-purple-50','text'=>'text-purple-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['text'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">

    {{-- ===== SHIFT TEMPLATES ===== --}}
    <div class="lg:col-span-1">
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Shift Templates</h3>
                <button onclick="openModal('add-shift-modal')"
                        class="lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New Shift
                </button>
            </div>

            <div class="divide-y divide-gray-50">
                @forelse($shifts as $shift)
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full mt-1 flex-shrink-0"
                                 style="background:{{ $shift->color ?? '#6C7DF7' }}"></div>
                            <div>
                                <p class="font-bold text-gray-900 text-sm">{{ $shift->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time)->format('h:i A') }}
                                    –
                                    {{ Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time)->format('h:i A') }}
                                    · {{ $shift->total_hours }}h
                                </p>
                                {{-- Working days --}}
                                <div class="flex gap-1 mt-2">
                                    @php
                                    $days = ['S','M','T','W','T','F','S'];
                                    $workingDays = $shift->working_days ?? [1,2,3,4,5];
                                    @endphp
                                    @foreach($days as $i => $d)
                                    <span class="w-5 h-5 rounded text-[10px] font-bold flex items-center justify-center
                                        {{ in_array($i, $workingDays) ? 'text-white' : 'bg-gray-100 text-gray-400' }}"
                                          style="{{ in_array($i, $workingDays) ? 'background:'.$shift->color.';' : '' }}">
                                        {{ $d }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <span class="lmt-badge-gray text-xs">{{ $shift->assignments_count }} staff</span>
                            <button onclick="openEditShift({{ $shift->id }}, {{ json_encode($shift) }})"
                                    class="w-7 h-7 rounded-lg bg-gray-100 text-gray-500 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i>
                            </button>
                            @if($shift->assignments_count === 0)
                            <form action="{{ route('admin.shifts.destroy', [$tenant, $shift->id]) }}" method="POST"
                                  onsubmit="return confirm('Delete this shift?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-7 h-7 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    {{-- Assign button --}}
                    <button onclick="openAssignModal({{ $shift->id }}, '{{ addslashes($shift->name) }}')"
                            class="mt-3 w-full text-xs font-semibold text-brand-600 hover:text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-lg py-1.5 transition-colors">
                        + Assign Employees
                    </button>
                </div>
                @empty
                <div class="p-8 text-center">
                    <i data-lucide="calendar-days" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
                    <p class="text-sm text-gray-400">No shifts yet</p>
                    <p class="text-xs text-gray-300 mt-1">Create your first shift template</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== WEEKLY SCHEDULE GRID ===== --}}
    <div class="lg:col-span-2">
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <div>
                    <h3 class="font-black text-gray-900">This Week's Schedule</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $weekDays->first()->format('M j') }} – {{ $weekDays->last()->format('M j, Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.shifts.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Refresh
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    {{-- Day headers --}}
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-36">Employee</th>
                            @foreach($weekDays as $day)
                            <th class="px-2 py-3 text-center text-xs font-semibold {{ $day->isToday() ? 'text-brand-600' : 'text-gray-500' }} min-w-20">
                                <span class="block">{{ $day->format('D') }}</span>
                                <span class="block text-base font-black {{ $day->isToday() ? 'text-brand-600' : 'text-gray-900' }}">
                                    {{ $day->format('j') }}
                                </span>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($weekAssignments as $empId => $assignments)
                        @php $emp = $assignments->first()->employee; @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                                        {{ substr($emp->first_name ?? 'E', 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 text-xs truncate">{{ $emp->first_name ?? 'Employee' }}</p>
                                        <p class="text-gray-400 text-[10px] truncate">{{ $emp->department?->name }}</p>
                                    </div>
                                </div>
                            </td>
                            @foreach($weekDays as $day)
                            @php
                            $dayAssignment = $assignments->first(function($a) use ($day) {
                                $dayNum = (int) $day->format('N') % 7; // 0=Sun,6=Sat
                                $workingDays = $a->shift->working_days ?? [1,2,3,4,5];
                                $inRange = $a->start_date <= $day->toDateString()
                                    && ($a->end_date === null || $a->end_date >= $day->toDateString());
                                return $inRange && in_array($dayNum, array_map('intval', $workingDays));
                            });
                            @endphp
                            <td class="px-1 py-2 text-center">
                                @if($dayAssignment)
                                <div class="rounded-lg px-1.5 py-1 text-center text-[10px] font-bold text-white leading-tight"
                                     style="background:{{ $dayAssignment->shift->color ?? '#6C7DF7' }}">
                                    {{ $dayAssignment->shift->code ?? substr($dayAssignment->shift->name,0,3) }}
                                    <br>
                                    <span class="font-normal opacity-90">
                                        {{ Carbon\Carbon::createFromFormat('H:i:s',$dayAssignment->shift->start_time)->format('h:iA') }}
                                    </span>
                                </div>
                                @else
                                <span class="text-gray-200 text-xs">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-gray-400 text-sm">
                                <i data-lucide="calendar-off" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                                No shift assignments yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ===== PENDING SWAP REQUESTS ===== --}}
@if($pendingSwaps->isNotEmpty())
<div class="lmt-card p-0 overflow-hidden">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900 flex items-center gap-2">
            Pending Shift Swap Requests
            <span class="lmt-badge-amber text-xs">{{ $pendingSwaps->count() }}</span>
        </h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($pendingSwaps as $swap)
        <div class="p-4 flex items-center gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="lmt-avatar-sm text-xs font-bold flex-shrink-0">
                    {{ substr($swap->requester->first_name ?? 'E', 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $swap->requester->full_name ?? 'Employee' }} wants to swap with
                        <span class="text-brand-600">{{ $swap->targetEmployee->full_name ?? 'Employee' }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <span class="font-medium" style="color:{{ $swap->requesterShift->color ?? '#6C7DF7' }}">
                            {{ $swap->requesterShift->name ?? 'Shift' }}
                        </span>
                        
                        <span class="font-medium" style="color:{{ $swap->targetShift->color ?? '#10B981' }}">
                            {{ $swap->targetShift->name ?? 'Shift' }}
                        </span>
                        @if($swap->reason)· "{{ $swap->reason }}"@endif
                    </p>
                </div>
            </div>
            <p class="text-xs text-gray-400 flex-shrink-0">{{ $swap->created_at->diffForHumans() }}</p>
            <div class="flex items-center gap-2 flex-shrink-0">
                <form action="{{ route('admin.shifts.swap.approve', [$tenant, $swap->id]) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="lmt-btn-sm" style="background:#10B981;color:#fff;display:inline-flex;align-items:center;gap:.4rem;padding:.375rem .75rem;border-radius:.75rem;font-weight:600;font-size:.75rem;border:none;cursor:pointer;">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                    </button>
                </form>
                <form action="{{ route('admin.shifts.swap.reject', [$tenant, $swap->id]) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="lmt-btn-danger lmt-btn-sm">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- =====================================================
     MODALS
===================================================== --}}

{{-- Add Shift Modal --}}
<div id="add-shift-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Create Shift Template</h3>
        <form action="{{ route('admin.shifts.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Shift Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Morning Shift"/>
                </div>
                <div>
                    <label class="lmt-label">Code</label>
                    <input type="text" name="code" class="lmt-input" placeholder="MRN"/>
                </div>
                <div>
                    <label class="lmt-label">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" required class="lmt-input" value="09:00"/>
                </div>
                <div>
                    <label class="lmt-label">End Time <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" required class="lmt-input" value="17:00"/>
                </div>
                <div>
                    <label class="lmt-label">Break (minutes)</label>
                    <input type="number" name="break_duration_minutes" class="lmt-input" value="60" min="0" max="120"/>
                </div>
                <div>
                    <label class="lmt-label">Late Grace (minutes)</label>
                    <input type="number" name="grace_period_minutes" class="lmt-input" value="10" min="0" max="60"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Overnight Shift?</label>
                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                        <input type="checkbox" name="is_overnight" value="1" class="w-4 h-4 rounded"/>
                        <span class="text-sm text-gray-600">Crosses midnight</span>
                    </label>
                </div>
            </div>

            {{-- Working days --}}
            <div>
                <label class="lmt-label">Working Days <span class="text-red-500">*</span></label>
                <div class="flex gap-2 flex-wrap mt-1">
                    @foreach(['Sun'=>0,'Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6] as $label => $val)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="working_days[]" value="{{ $val }}"
                               {{ in_array($val,[1,2,3,4,5]) ? 'checked' : '' }}
                               class="sr-only peer"/>
                        <span class="w-10 h-10 rounded-xl border-2 border-gray-200 text-xs font-bold text-gray-500 flex items-center justify-center
                                     peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-white transition-all cursor-pointer">
                            {{ $label }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2" placeholder="Optional notes…"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Shift</button>
                <button type="button" onclick="closeModal('add-shift-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Shift Modal --}}
<div id="edit-shift-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Edit Shift</h3>
        <form id="edit-shift-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Shift Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit-shift-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" id="edit-shift-color" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" id="edit-shift-start" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">End Time <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" id="edit-shift-end" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Break (minutes)</label>
                    <input type="number" name="break_duration_minutes" id="edit-shift-break" class="lmt-input" min="0" max="120"/>
                </div>
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="is_active" class="lmt-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="lmt-label">Working Days</label>
                <div class="flex gap-2 flex-wrap mt-1" id="edit-working-days">
                    @foreach(['Sun'=>0,'Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6] as $label => $val)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="working_days[]" value="{{ $val }}"
                               class="sr-only peer edit-day-cb" data-day="{{ $val }}"/>
                        <span class="w-10 h-10 rounded-xl border-2 border-gray-200 text-xs font-bold text-gray-500 flex items-center justify-center
                                     peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-white transition-all cursor-pointer">
                            {{ $label }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-shift-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Assign Employees Modal --}}
<div id="assign-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-1">Assign to <span id="assign-shift-name" class="text-brand-600"></span></h3>
        <p class="text-sm text-gray-400 mb-5">Select employees and set the assignment period</p>
        <form action="{{ route('admin.shifts.assign', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="shift_id" id="assign-shift-id"/>

            {{-- Employee multi-select --}}
            <div>
                <label class="lmt-label">Employees <span class="text-red-500">*</span></label>
                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-xl p-2 space-y-1">
                    @foreach(\App\Models\Tenant\Employee::active()->with('department')->orderBy('first_name')->get() as $emp)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="w-4 h-4 rounded"/>
                        <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">{{ substr($emp->first_name,0,1) }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-400">{{ $emp->department?->name ?? 'No dept' }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <p class="lmt-help">Selecting an employee already in a shift will end their current assignment</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="{{ today()->toDateString() }}" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">End Date</label>
                    <input type="date" name="end_date" class="lmt-input"/>
                    <p class="lmt-help">Leave blank for permanent</p>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Assign Employees</button>
                <button type="button" onclick="closeModal('assign-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function openAssignModal(shiftId, shiftName) {
    document.getElementById('assign-shift-id').value = shiftId;
    document.getElementById('assign-shift-name').textContent = shiftName;
    openModal('assign-modal');
}

function openEditShift(shiftId, shift) {
    document.getElementById('edit-shift-form').action =
        `/t/{{ $tenant }}/admin/shifts/${shiftId}`;
    document.getElementById('edit-shift-name').value  = shift.name;
    document.getElementById('edit-shift-color').value = shift.color || '#6C7DF7';
    document.getElementById('edit-shift-start').value = shift.start_time?.substring(0,5);
    document.getElementById('edit-shift-end').value   = shift.end_time?.substring(0,5);
    document.getElementById('edit-shift-break').value = shift.break_duration_minutes || 60;

    // Set working days checkboxes
    const workingDays = shift.working_days || [1,2,3,4,5];
    document.querySelectorAll('.edit-day-cb').forEach(cb => {
        cb.checked = workingDays.includes(parseInt(cb.dataset.day));
    });
    // Re-init lucide after DOM changes
    if (window.lucide) lucide.createIcons();
    openModal('edit-shift-modal');
}

// Close modals on backdrop click
['add-shift-modal','edit-shift-modal','assign-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush