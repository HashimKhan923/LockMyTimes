@extends('layouts.admin')
@section('title','Loans & Advances')
@section('page-title','Loans & Advances')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Active Loans',      'value'=>$stats['active_loans'],                                  'icon'=>'credit-card',  'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Outstanding',       'value'=>'$'.number_format($stats['total_outstanding'],0),         'icon'=>'dollar-sign',  'bg'=>'bg-red-50',    'text'=>'text-red-600'],
        ['label'=>'Pending Approval',  'value'=>$stats['pending_loans'],                                  'icon'=>'clock',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Active Advances',   'value'=>$stats['active_advances'],                                'icon'=>'piggy-bank',   'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
    ] as $s)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $s['label'] }}</p>
            <p class="lmt-stat-value text-2xl">{{ $s['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<div class="flex items-center gap-3 mb-6 border-b border-gray-200">
    @foreach(['loans'=>'Employee Loans','advances'=>'Salary Advances','types'=>'Loan Types'] as $t=>$label)
    <a href="{{ route('admin.loans.index', $tenant) }}?tab={{ $t }}"
       class="px-5 py-2.5 text-sm font-semibold border-b-2 transition-all -mb-px
              {{ $tab === $t
                  ? 'border-brand-500 text-brand-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- ===== LOANS TAB ===== --}}
@if($tab === 'loans')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Employee Loans</h3>
    <button onclick="openModal('add-loan-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Loan
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    {{-- Filter --}}
    <div class="p-4 bg-gray-50 border-b border-gray-100 flex flex-wrap gap-3">
        <form action="{{ route('admin.loans.index', $tenant) }}" method="GET" class="flex gap-3 flex-wrap">
            <input type="hidden" name="tab" value="loans"/>
            <select name="status" class="lmt-select py-2 text-sm w-auto" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach(['pending'=>'Pending','approved'=>'Approved','disbursed'=>'Disbursed','active'=>'Active','completed'=>'Completed','rejected'=>'Rejected'] as $v=>$l)
                <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="employee" class="lmt-select py-2 text-sm w-auto min-w-40" onchange="this.form.submit()">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }}
                </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Loan #</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>EMI</th>
                    <th>Remaining</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                @php
                $statusColors = [
                    'pending'   => 'lmt-badge-amber',
                    'approved'  => 'lmt-badge-brand',
                    'disbursed' => 'lmt-badge-green',
                    'active'    => 'lmt-badge-green',
                    'completed' => 'lmt-badge-gray',
                    'rejected'  => 'lmt-badge-red',
                ];
                $pct = $loan->total_amount > 0
                    ? round(((float)$loan->amount_paid / (float)$loan->total_amount) * 100)
                    : 0;
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">
                                {{ substr($loan->employee->first_name ?? 'E', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $loan->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $loan->employee->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('admin.loans.show', [$tenant, $loan->id]) }}"
                           class="text-xs font-mono text-brand-600 hover:underline">
                            {{ $loan->loan_number }}
                        </a>
                    </td>
                    <td class="text-sm text-gray-600">{{ $loan->loanType->name ?? '—' }}</td>
                    <td class="text-sm font-bold text-gray-900">${{ number_format($loan->total_amount, 0) }}</td>
                    <td class="text-sm text-gray-600">${{ number_format($loan->emi_amount, 0) }}/mo</td>
                    <td>
                        <span class="text-sm font-bold {{ $loan->amount_remaining > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                            ${{ number_format($loan->amount_remaining, 0) }}
                        </span>
                    </td>
                    <td class="min-w-24">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full lmt-gradient-bg"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $pct }}%</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $loan->installments_paid }}/{{ $loan->tenure_months }} installments
                        </p>
                    </td>
                    <td>
                        <span class="{{ $statusColors[$loan->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ $loan->status }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.loans.show', [$tenant, $loan->id]) }}"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </a>
                            @if($loan->status === 'pending')
                            <form action="{{ route('admin.loans.approve', [$tenant, $loan->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <button onclick="openRejectModal({{ $loan->id }}, 'loan')"
                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                            @if($loan->status === 'approved')
                            <button onclick="openDisburseModal({{ $loan->id }})"
                                    class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Disburse">
                                <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-14">
                        <i data-lucide="credit-card" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-400">No loans found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $loans->links() }}</div>
    @endif
</div>

{{-- ===== ADVANCES TAB ===== --}}
@elseif($tab === 'advances')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Salary Advances</h3>
    <button onclick="openModal('add-advance-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Advance
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Advance #</th>
                    <th>Amount</th>
                    <th>Deduction/Month</th>
                    <th>Months</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($advances as $adv)
                @php
                $advStatusColors = [
                    'pending'  => 'lmt-badge-amber',
                    'approved' => 'lmt-badge-brand',
                    'active'   => 'lmt-badge-green',
                    'completed'=> 'lmt-badge-gray',
                    'rejected' => 'lmt-badge-red',
                ];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">
                                {{ substr($adv->employee->first_name ?? 'E', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $adv->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $adv->reason }}</p>
                            </div>
                        </div>
                    </td>
                    <td><code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ $adv->advance_number }}</code></td>
                    <td class="text-sm font-bold text-gray-900">${{ number_format($adv->amount, 0) }}</td>
                    <td class="text-sm text-gray-600">${{ number_format($adv->per_installment_amount, 0) }}/mo</td>
                    <td class="text-sm text-gray-600">{{ $adv->installments_count }} months</td>
                    <td>
                        <span class="text-sm font-bold text-red-500">
                            ${{ number_format($adv->amount_remaining ?? $adv->amount, 0) }}
                        </span>
                    </td>
                    <td>
                        <span class="{{ $advStatusColors[$adv->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ $adv->status }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            @if($adv->status === 'pending')
                            <form action="{{ route('admin.loans.advance.approve', [$tenant, $adv->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <button onclick="openRejectModal({{ $adv->id }}, 'advance')"
                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-14">
                        <i data-lucide="piggy-bank" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-400">No salary advances</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== LOAN TYPES TAB ===== --}}
@elseif($tab === 'types')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Loan Types</h3>
    <button onclick="openModal('add-type-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Type
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($loanTypes as $lt)
    <div class="lmt-card">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0"
                 style="background:{{ $lt->color ?? '#6C7DF7' }}">
                {{ substr($lt->code ?? $lt->name, 0, 2) }}
            </div>
            <div>
                <h3 class="font-black text-gray-900">{{ $lt->name }}</h3>
                <p class="text-xs text-gray-400">{{ $lt->interest_rate ?? $lt->default_interest_rate }}% · {{ ucfirst($lt->interest_type) }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-gray-900">${{ number_format($lt->min_amount ?? 0, 0) }} – ${{ number_format($lt->max_amount ?? 0, 0) }}</p>
                <p class="text-gray-400">Amount Range</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-gray-900">{{ $lt->min_tenure_months }}–{{ $lt->max_tenure_months }} mo</p>
                <p class="text-gray-400">Tenure</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-1.5">
            @if($lt->requires_guarantor)
            <span class="lmt-badge-amber text-xs">Guarantor</span>
            @endif
            @if($lt->auto_deduct_from_payroll)
            <span class="lmt-badge-brand text-xs">Auto Deduct</span>
            @endif
            <span class="{{ $lt->is_active ? 'lmt-badge-green' : 'lmt-badge-red' }} text-xs">
                {{ $lt->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-10 md:col-span-3">
        <i data-lucide="layers" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
        <p class="text-gray-400 text-sm">No loan types configured</p>
    </div>
    @endforelse
</div>
@endif

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- New Loan --}}
<div id="add-loan-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">New Loan Application</h3>
        <form action="{{ route('admin.loans.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                    <select name="employee_id" required class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Loan Type <span class="text-red-500">*</span></label>
                    <select name="loan_type_id" required class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($loanTypes as $lt)
                        <option value="{{ $lt->id }}">
                            {{ $lt->name }} — {{ $lt->default_interest_rate }}% · up to ${{ number_format($lt->max_amount,0) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Principal Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="principal_amount" required step="100" min="100" class="lmt-input" placeholder="5000"/>
                </div>
                <div>
                    <label class="lmt-label">Tenure (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="tenure_months" required min="1" max="120" class="lmt-input" value="12"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">First EMI Date <span class="text-red-500">*</span></label>
                    <input type="date" name="first_emi_date" required class="lmt-input"
                           value="{{ now()->addMonth()->startOfMonth()->toDateString() }}"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Purpose</label>
                    <textarea name="purpose" class="lmt-textarea" rows="2" placeholder="Reason for loan…"></textarea>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create Loan</button>
                <button type="button" onclick="closeModal('add-loan-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- New Advance --}}
<div id="add-advance-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Salary Advance Request</h3>
        <form action="{{ route('admin.loans.advance.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required step="100" min="100" class="lmt-input" placeholder="1000"/>
                </div>
                <div>
                    <label class="lmt-label">Repay Over (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="repayment_months" required min="1" max="12" class="lmt-input" value="3"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="requested_date" required class="lmt-input" value="{{ today()->toDateString() }}"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Reason</label>
                <textarea name="reason" class="lmt-textarea" rows="2" placeholder="Why is this advance needed?"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Submit</button>
                <button type="button" onclick="closeModal('add-advance-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Disburse Modal --}}
<div id="disburse-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Disburse Loan</h3>
        <form id="disburse-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Payment Method <span class="text-red-500">*</span></label>
                <select name="disbursement_method" required class="lmt-select">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="check">Check</option>
                    <option value="cash">Cash</option>
                    <option value="direct_deposit">Direct Deposit</option>
                </select>
            </div>
            <div>
                <label class="lmt-label">Reference / Transaction ID</label>
                <input type="text" name="disbursement_reference" class="lmt-input" placeholder="TXN123456"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Confirm Disbursement</button>
                <button type="button" onclick="closeModal('disburse-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Reject Request</h3>
        <form id="reject-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3" placeholder="Why is this being rejected?"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Reject</button>
                <button type="button" onclick="closeModal('reject-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Loan Type --}}
<div id="add-type-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Add Loan Type</h3>
        <form action="{{ route('admin.loans.types.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input" placeholder="Personal Loan"/>
                </div>
                <div>
                    <label class="lmt-label">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required class="lmt-input" placeholder="PL"/>
                </div>
                <div>
                    <label class="lmt-label">Interest Rate (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="default_interest_rate" required step="0.1" min="0" class="lmt-input" value="0"/>
                </div>
                <div>
                    <label class="lmt-label">Interest Type <span class="text-red-500">*</span></label>
                    <select name="interest_type" required class="lmt-select">
                        <option value="reducing">Reducing Balance</option>
                        <option value="flat">Flat Rate</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Min Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="min_amount" required step="100" class="lmt-input" value="500"/>
                </div>
                <div>
                    <label class="lmt-label">Max Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="max_amount" required step="100" class="lmt-input" value="50000"/>
                </div>
                <div>
                    <label class="lmt-label">Min Tenure (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="min_tenure_months" required min="1" class="lmt-input" value="3"/>
                </div>
                <div>
                    <label class="lmt-label">Max Tenure (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="max_tenure_months" required min="1" class="lmt-input" value="60"/>
                </div>
                <div>
                    <label class="lmt-label">Min Service (months)</label>
                    <input type="number" name="min_service_months" min="0" class="lmt-input" value="6"/>
                </div>
                <div>
                    <label class="lmt-label">Color</label>
                    <input type="color" name="color" value="#6C7DF7" class="lmt-input h-10 p-1"/>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach([['requires_guarantor','Guarantor Required'],['auto_deduct_from_payroll','Auto Deduct from Payroll']] as [$n,$l])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="{{ $n }}" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-700">{{ $l }}</span>
                </label>
                @endforeach
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create</button>
                <button type="button" onclick="closeModal('add-type-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
function openDisburseModal(loanId) {
    document.getElementById('disburse-form').action = `/t/{{ $tenant }}/admin/loans/${loanId}/disburse`;
    openModal('disburse-modal');
}
function openRejectModal(id, type) {
    document.getElementById('reject-form').action =
        type === 'loan'
            ? `/t/{{ $tenant }}/admin/loans/${id}/reject`
            : `/t/{{ $tenant }}/admin/loans/advances/${id}/reject`;
    openModal('reject-modal');
}
['add-loan-modal','add-advance-modal','add-type-modal','disburse-modal','reject-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush