@extends('layouts.admin')
@section('title','Leave Types')
@section('page-title','Leave Types')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Leave Types</h2>
        <p class="text-sm text-gray-800 mt-0.5">Configure your company's leave policies</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.leaves.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
        <form action="{{ route('admin.leaves.balances.sync', $tenant) }}" method="POST">
            @csrf
            <button type="submit" class="lmt-btn-secondary lmt-btn-sm"
                    title="Create missing balance records for all employees"
                    onclick="return confirm('Initialize missing leave balances for all active employees?')">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Sync Balances
            </button>
        </form>
        <button onclick="openModal('add-type-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Leave Type
        </button>
    </div>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($types as $type)
    <div class="lmt-card">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm"
                     style="background:{{ $type->color ?? '#6C7DF7' }}">
                    {{ substr($type->code, 0, 2) }}
                </div>
                <div>
                    <h3 class="font-black text-gray-900">{{ $type->name }}</h3>
                    <p class="text-xs text-gray-800">{{ $type->code }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="openEditType({{ $type->id }}, {{ json_encode($type) }})"
                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                    <i data-lucide="pencil" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-gray-900 text-lg">{{ $type->default_days_per_year }}</p>
                <p class="text-gray-800">Days/year</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-gray-900 text-lg">{{ $type->leave_requests_count }}</p>
                <p class="text-gray-800">Requests</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            @if($type->is_paid)
            <span class="lmt-badge-green text-xs">Paid</span>
            @else
            <span class="lmt-badge-gray text-xs">Unpaid</span>
            @endif
            @if($type->max_carryover_days > 0)
            <span class="lmt-badge-brand text-xs">Carry Forward ({{ $type->max_carryover_days }}d)</span>
            @endif
            @if($type->requires_documentation)
            <span class="lmt-badge-amber text-xs">Docs Required</span>
            @endif
            @if($type->gender_specific && $type->gender_specific !== 'all')
            <span class="lmt-badge-brand text-xs capitalize">{{ $type->gender_specific }} only</span>
            @endif
            <span class="{{ $type->is_active ? 'lmt-badge-green' : 'lmt-badge-red' }} text-xs">
                {{ $type->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>

        @if($type->notice_days_required)
        <p class="text-xs text-gray-800 mt-2">
            <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
            {{ $type->notice_days_required }} days notice required
        </p>
        @endif
    </div>
    @empty
    <div class="lmt-card text-center py-12 md:col-span-3">
        <i data-lucide="tag" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="text-gray-800">No leave types configured yet</p>
    </div>
    @endforelse
</div>

{{-- Add Type Modal --}}
<div id="add-type-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Add Leave Type</h3>
        <form action="{{ route('admin.leaves.types.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Annual Leave"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="AL"/>
                </div>
                <div>
                    <label class="lmt-label">Days Per Year <span class="text-red-500">*</span></label>
                    <input type="number" name="default_days_per_year" required step="0.5" min="0" class="lmt-input" value="10"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Min Notice (days)</label>
                    <input type="number" name="notice_days_required" class="lmt-input" value="1" min="0"/>
                </div>
                <div>
                    <label class="lmt-label">Max Carry Forward (days)</label>
                    <input type="number" name="max_carryover_days" class="lmt-input" placeholder="0" min="0"/>
                </div>
                <div>
                    <label class="lmt-label">Max Consecutive Days</label>
                    <input type="number" name="max_consecutive_days" class="lmt-input" placeholder="0" min="0"/>
                </div>
                <div>
                    <label class="lmt-label">Applicable To</label>
                    <select name="gender_specific" class="lmt-select">
                        <option value="all">All Employees</option>
                        <option value="male">Male Only</option>
                        <option value="female">Female Only</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach([
                    ['is_paid',                'Paid Leave'],
                    ['requires_documentation', 'Docs Required'],
                    ['requires_approval',      'Requires Approval'],
                ] as [$fname, $flabel])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="hidden" name="{{ $fname }}" value="0"/>
                    <input type="checkbox" name="{{ $fname }}" value="1" class="w-4 h-4 rounded"
                           {{ in_array($fname, ['requires_approval']) ? 'checked' : '' }}/>
                    <span class="text-xs font-medium text-gray-800">{{ $flabel }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="closeModal('add-type-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Type Modal --}}
<div id="edit-type-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Edit Leave Type</h3>
        <form id="edit-type-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name</label>
                    <input type="text" name="name" id="edit-type-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Days Per Year</label>
                    <input type="number" name="default_days_per_year" id="edit-type-days" step="0.5" min="0" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" id="edit-type-color" class="lmt-input h-10 p-1"/>
                </div>
                <div>
                    <label class="lmt-label">Min Notice (days)</label>
                    <input type="number" name="notice_days_required" id="edit-type-notice" min="0" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Max Carry Forward (days)</label>
                    <input type="number" name="max_carryover_days" id="edit-type-carryover" min="0" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Max Consecutive Days</label>
                    <input type="number" name="max_consecutive_days" id="edit-type-consecutive" min="0" class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Status</label>
                    <select name="is_active" id="edit-type-active" class="lmt-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach([
                    ['is_paid',                'Paid Leave'],
                    ['requires_documentation', 'Docs Required'],
                    ['requires_approval',      'Requires Approval'],
                ] as [$fname, $flabel])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="hidden" name="{{ $fname }}" value="0"/>
                    <input type="checkbox" name="{{ $fname }}" value="1"
                           class="w-4 h-4 rounded edit-type-cb"
                           data-field="{{ $fname }}"/>
                    <span class="text-xs font-medium text-gray-800">{{ $flabel }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('edit-type-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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

function openEditType(id, type) {
    document.getElementById('edit-type-form').action = `/t/{{ $tenant }}/admin/leaves/types/${id}`;
    document.getElementById('edit-type-name').value        = type.name          ?? '';
    document.getElementById('edit-type-days').value        = type.default_days_per_year ?? '';
    document.getElementById('edit-type-color').value       = type.color         || '#6C7DF7';
    document.getElementById('edit-type-notice').value      = type.notice_days_required  ?? '';
    document.getElementById('edit-type-carryover').value   = type.max_carryover_days    ?? '';
    document.getElementById('edit-type-consecutive').value = type.max_consecutive_days  ?? '';

    const activeSelect = document.getElementById('edit-type-active');
    if (activeSelect) activeSelect.value = type.is_active ? '1' : '0';

    // Set checkboxes — only target the visible checkbox inputs, not the hidden fallback inputs
    document.querySelectorAll('.edit-type-cb').forEach(cb => {
        cb.checked = !!type[cb.dataset.field];
    });

    openModal('edit-type-modal');
}

['add-type-modal', 'edit-type-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush
