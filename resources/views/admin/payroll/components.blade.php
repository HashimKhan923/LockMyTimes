@extends('layouts.admin')
@section('title','Salary Components')
@section('page-title','Salary Components')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-gray-900">Salary Components</h2>
        <p class="text-sm text-gray-800">Define earnings, deductions, reimbursements and taxes — and assign them to employees</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.payroll.index', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back
        </a>
        <button onclick="openModal('add-comp-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Component
        </button>
    </div>
</div>

@php
$grouped = $components->groupBy('type');
$typeColors = ['earning'=>'lmt-badge-green','deduction'=>'lmt-badge-red','reimbursement'=>'lmt-badge-brand','tax'=>'lmt-badge-amber'];
@endphp

<div class="grid md:grid-cols-2 gap-6">
    @foreach(['earning'=>'Earnings','deduction'=>'Deductions','reimbursement'=>'Reimbursements','tax'=>'Taxes'] as $type => $label)
    <div class="lmt-card p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <span class="{{ $typeColors[$type] ?? 'lmt-badge-gray' }} text-xs">{{ $label }}</span>
            </div>
            <span class="text-xs text-gray-800">{{ $grouped->get($type, collect())->count() }} components</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($grouped->get($type, collect()) as $comp)
            <div class="flex items-center justify-between px-4 py-3 gap-2">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $comp->name }}</p>
                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                        <code class="text-[10px] bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $comp->code }}</code>
                        <span class="text-xs text-gray-800 capitalize">{{ str_replace('_',' ',$comp->calculation) }}</span>
                        @if($comp->default_value)
                        <span class="text-xs text-gray-800">
                            · {{ $comp->calculation === 'percentage' ? $comp->default_value.'%' : '$'.$comp->default_value }}
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                        @if($comp->is_taxable)
                        <span class="lmt-badge-amber text-xs">Taxable</span>
                        @endif
                        @if(!$comp->shows_on_payslip)
                        <span class="lmt-badge-gray text-xs">Hidden on payslip</span>
                        @endif
                        <span class="{{ $comp->is_active ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                            {{ $comp->is_active ? 'Active' : 'Off' }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <button onclick="openModal('assign-modal-{{ $comp->id }}')"
                            title="{{ $type === 'tax' ? 'Click to assign a fixed amount that overrides the automatic calculation for this employee' : 'Click to assign this component to employees' }}"
                            class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white transition-colors whitespace-nowrap">
                        <i data-lucide="user-plus" class="w-3 h-3"></i>
                        {{ $comp->employee_salary_components_count }} staff
                    </button>
                    <button onclick="openEditCompModal({{ Js::from([
                                'id' => $comp->id,
                                'name' => $comp->name,
                                'type' => $comp->type,
                                'calculation' => $comp->calculation,
                                'default_value' => $comp->default_value,
                                'is_taxable' => $comp->is_taxable,
                                'is_recurring' => $comp->is_recurring,
                                'shows_on_payslip' => $comp->shows_on_payslip,
                                'is_active' => $comp->is_active,
                            ]) }})"
                            class="w-7 h-7 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                        <i data-lucide="pencil" class="w-3 h-3"></i>
                    </button>
                    @if($comp->employee_salary_components_count === 0)
                    <form action="{{ route('admin.payroll.components.destroy', [$tenant, $comp->id]) }}" method="POST"
                          onsubmit="return confirm('Delete {{ addslashes($comp->name) }}? This cannot be undone.');">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-7 h-7 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition-colors">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                        </button>
                    </form>
                    @else
                    <span class="w-7 h-7 rounded-lg bg-gray-50 text-gray-300 flex items-center justify-center"
                          title="Unassign from all employees before deleting">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                    </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-4 py-6 text-center text-sm text-gray-800">
                No {{ strtolower($label) }} defined
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- Add Component Modal --}}
<div id="add-comp-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Add Salary Component</h3>
        <form action="{{ route('admin.payroll.components.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="e.g. Housing Allowance"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="HRA"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                        <option value="reimbursement">Reimbursement</option>
                        <option value="tax">Tax</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Calculation <span class="text-red-500">*</span></label>
                    <select name="calculation" required class="lmt-select">
                        <option value="fixed">Fixed Amount ($)</option>
                        <option value="percentage">% of Basic</option>
                        <option value="formula">Formula</option>
                        <option value="hours_x_rate">Hours × Rate</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Default Amount</label>
                    <input type="number" name="default_value" step="0.01" min="0" class="lmt-input" placeholder="0.00"/>
                    <p class="lmt-help">Suggested amount when assigning this to an employee — can be overridden per-employee.</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                @foreach([['is_taxable','Taxable'],['is_recurring','Recurring'],['shows_on_payslip','Show on Payslip']] as [$n,$l])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="{{ $n }}" value="1" class="w-4 h-4 rounded" {{ in_array($n,['is_recurring','shows_on_payslip']) ? 'checked' : '' }}/>
                    <span class="text-xs font-medium text-gray-800">{{ $l }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="closeModal('add-comp-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Component Modal (shared, populated via JS) --}}
<div id="edit-comp-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Edit Salary Component</h3>
        <form id="edit-comp-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit-comp-name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="edit-comp-type" required class="lmt-select">
                        <option value="earning">Earning</option>
                        <option value="deduction">Deduction</option>
                        <option value="reimbursement">Reimbursement</option>
                        <option value="tax">Tax</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Calculation <span class="text-red-500">*</span></label>
                    <select name="calculation" id="edit-comp-calculation" required class="lmt-select">
                        <option value="fixed">Fixed Amount ($)</option>
                        <option value="percentage">% of Basic</option>
                        <option value="formula">Formula</option>
                        <option value="hours_x_rate">Hours × Rate</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Default Amount</label>
                    <input type="number" name="default_value" id="edit-comp-default" step="0.01" min="0" class="lmt-input"/>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="is_taxable" id="edit-comp-taxable" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-800">Taxable</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="is_recurring" id="edit-comp-recurring" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-800">Recurring</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="shows_on_payslip" id="edit-comp-shows" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-800">Show on Payslip</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="is_active" id="edit-comp-active" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-800">Active</span>
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-comp-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Assign Modals (one per component) --}}
@foreach($components as $comp)
<div id="assign-modal-{{ $comp->id }}" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-1">{{ $comp->name }}</h3>
        <p class="text-sm text-gray-800 mb-5">Manage which employees have this component assigned.</p>
        @if($comp->type === 'tax')
        <div class="mb-4 p-3 rounded-lg bg-amber-50 text-amber-700 text-xs flex items-start gap-2">
            <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i>
            <span>Nothing is deducted for this tax unless it's assigned here — there's no automatic calculation running in the background.</span>
        </div>
        @endif

        {{-- Current assignees --}}
        <div class="mb-5 max-h-56 overflow-y-auto space-y-2">
            @forelse($comp->employeeSalaryComponents as $assignment)
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $assignment->employee?->full_name ?? '—' }}</p>
                    <p class="text-xs text-gray-800">
                        ${{ number_format($assignment->amount, 2) }}
                        @if($assignment->effective_to)
                        · until {{ $assignment->effective_to->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <form action="{{ route('admin.payroll.components.unassign', [$tenant, $comp->id, $assignment->id]) }}" method="POST"
                      onsubmit="return confirm('Unassign this component from {{ addslashes($assignment->employee?->full_name ?? 'this employee') }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-7 h-7 rounded-lg bg-white text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors flex-shrink-0">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-800 text-center py-3">Not assigned to anyone yet.</p>
            @endforelse
        </div>

        {{-- Add assignment --}}
        <form action="{{ route('admin.payroll.components.assign', [$tenant, $comp->id]) }}" method="POST" class="space-y-3 pt-4 border-t border-gray-100"
              onsubmit="return !document.getElementById('apply-all-{{ $comp->id }}').checked || confirm('Assign {{ addslashes($comp->name) }} to ALL active employees? This will overwrite any existing assignment of this component for every employee.');">
            @csrf
            <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                <input type="checkbox" id="apply-all-{{ $comp->id }}" name="apply_to_all" value="1" class="w-4 h-4 rounded"
                       onchange="document.getElementById('emp-select-{{ $comp->id }}').disabled = this.checked; document.getElementById('emp-select-{{ $comp->id }}').required = !this.checked;">
                <span class="text-xs font-semibold text-gray-800">Apply to all active employees instead of one</span>
            </label>
            <div>
                <label class="lmt-label">Assign To <span class="text-red-500">*</span></label>
                <select name="employee_id" id="emp-select-{{ $comp->id }}" required class="lmt-select">
                    <option value="">— Select Employee —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lmt-label">Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0" required class="lmt-input"
                           value="{{ $comp->default_value ?: '' }}"/>
                </div>
                <div>
                    <label class="lmt-label">Effective From <span class="text-red-500">*</span></label>
                    <input type="date" name="effective_from" required class="lmt-input" value="{{ today()->toDateString() }}"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Effective To <span class="text-gray-800 font-normal">(optional)</span></label>
                <input type="date" name="effective_to" class="lmt-input"/>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="lmt-btn-primary flex-1">Assign</button>
                <button type="button" onclick="closeModal('assign-modal-{{ $comp->id }}')" class="lmt-btn-secondary flex-1">Close</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });

function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }

function openEditCompModal(comp) {
    document.getElementById('edit-comp-form').action = `/t/{{ $tenant }}/admin/payroll/settings/components/${comp.id}`;
    document.getElementById('edit-comp-name').value = comp.name ?? '';
    document.getElementById('edit-comp-type').value = comp.type ?? 'earning';
    document.getElementById('edit-comp-calculation').value = comp.calculation ?? 'fixed';
    document.getElementById('edit-comp-default').value = comp.default_value ?? '';
    document.getElementById('edit-comp-taxable').checked = !!comp.is_taxable;
    document.getElementById('edit-comp-recurring').checked = !!comp.is_recurring;
    document.getElementById('edit-comp-shows').checked = !!comp.shows_on_payslip;
    document.getElementById('edit-comp-active').checked = !!comp.is_active;
    openModal('edit-comp-modal');
}

document.querySelectorAll('.lmt-modal-backdrop').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});
</script>
@endpush
