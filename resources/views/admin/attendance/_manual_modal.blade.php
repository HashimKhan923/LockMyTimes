{{-- Manual Entry Modal — shared by both the List and Calendar views --}}
<div id="manual-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Manual Attendance Entry</h3>
        <form action="{{ route('admin.attendance.manual', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach(\App\Models\Tenant\Employee::active()->orderBy('first_name')->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                <input type="date" name="work_date" value="{{ ($date ?? today())->toDateString() }}" required class="lmt-input"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Clock In <span class="text-red-500">*</span></label>
                    <input type="time" name="clock_in_at" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Clock Out</label>
                    <input type="time" name="clock_out_at" class="lmt-input"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="lmt-select">
                    @foreach(['present'=>'Present','absent'=>'Absent','half_day'=>'Half Day','on_leave'=>'On Leave','holiday'=>'Holiday'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Notes</label>
                <input type="text" name="notes" class="lmt-input" placeholder="Reason for manual entry…"/>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Save Entry</button>
                <button type="button"
                        onclick="document.getElementById('manual-modal').classList.add('hidden');document.getElementById('manual-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>
