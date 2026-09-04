@extends('layouts.admin')
@section('title','Advance — '.$advance->advance_number)
@section('page-title','Salary Advance Detail')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.loans.index', $tenant) }}?tab=advances"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Advances
    </a>
    <div class="flex items-center gap-2">
        @if($advance->status === 'pending')
        <form action="{{ route('admin.loans.advance.approve', [$tenant, $advance->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                <i data-lucide="check" class="w-4 h-4"></i> Approve
            </button>
        </form>
        @endif
        @if($advance->status === 'approved')
        <form action="{{ route('admin.loans.advance.disburse', [$tenant, $advance->id]) }}" method="POST"
              onsubmit="return confirm('Disburse {{ $advance->advance_number }}? This generates the deduction schedule and cannot be undone.');">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-primary lmt-btn-sm" style="background:#8B5CF6">
                <i data-lucide="banknote" class="w-4 h-4"></i> Disburse
            </button>
        </form>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Advance Info --}}
    <div class="space-y-5">

        {{-- Header card --}}
        <div class="lmt-card">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <code class="font-black text-gray-900 text-lg">{{ $advance->advance_number }}</code>
                    @php
                    $sc = ['pending'=>'lmt-badge-amber','approved'=>'lmt-badge-brand','disbursed'=>'lmt-badge-green','active'=>'lmt-badge-green','completed'=>'lmt-badge-gray','rejected'=>'lmt-badge-red'];
                    @endphp
                    <span class="block mt-1 {{ $sc[$advance->status] ?? 'lmt-badge-gray' }} text-xs w-fit">
                        {{ ucfirst($advance->status) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white" style="background:#8B5CF6">
                    <i data-lucide="piggy-bank" class="w-5 h-5"></i>
                </div>
            </div>

            {{-- Employee --}}
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl mb-4">
                <div class="lmt-avatar-sm font-bold text-xs">
                    {{ substr($advance->employee->first_name ?? 'E', 0, 1) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ $advance->employee->full_name }}</p>
                    <p class="text-xs text-gray-800">{{ $advance->employee->department?->name }} · {{ $advance->employee->employee_code }}</p>
                </div>
            </div>

            {{-- Advance details --}}
            <div class="space-y-2">
                @foreach([
                    ['Amount',          '$'.number_format($advance->amount, 2)],
                    ['Per Installment', '$'.number_format($advance->per_installment_amount, 2).'/month'],
                    ['Installments',    $advance->installments_count.' months'],
                    ['First Deduction', $advance->first_deduction_date?->format('M j, Y') ?? '—'],
                    ['Repayment',       $advance->auto_deduct_from_payroll ? 'Deducted from salary automatically' : 'Employee pays separately'],
                    ['Reason',          $advance->reason ?? '—'],
                ] as [$k,$v])
                <div class="flex justify-between py-2 border-b border-gray-50 last:border-none">
                    <span class="text-xs text-gray-800 font-medium">{{ $k }}</span>
                    <span class="text-xs font-semibold text-gray-800 text-right max-w-44">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Progress --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Repayment Progress</h3>
            @php
            $pct = $advance->amount > 0
                ? round(((float)$advance->amount_repaid / (float)$advance->amount) * 100) : 0;
            @endphp
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-800">Paid</span>
                <span class="text-sm font-black text-emerald-600">{{ $pct }}%</span>
            </div>
            <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden mb-3">
                <div class="h-full rounded-full lmt-gradient-bg transition-all duration-700"
                     style="width:{{ $pct }}%"></div>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-emerald-50 rounded-xl p-2">
                    <p class="text-sm font-black text-emerald-700">${{ number_format($advance->amount_repaid, 0) }}</p>
                    <p class="text-xs text-gray-800">Repaid</p>
                </div>
                <div class="bg-red-50 rounded-xl p-2">
                    <p class="text-sm font-black text-red-600">${{ number_format($advance->amount_remaining, 0) }}</p>
                    <p class="text-xs text-gray-800">Remaining</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-2">
                    <p class="text-sm font-black text-gray-900">{{ $advance->installments_paid }}/{{ $advance->installments_count }}</p>
                    <p class="text-xs text-gray-800">Installments</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Deduction Schedule --}}
    <div class="lg:col-span-2 lmt-card p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-black text-gray-900">Deduction Schedule</h3>
            <span class="lmt-badge-gray text-xs">{{ $advance->deductions->count() }} installments</span>
        </div>
        @if(!$advance->auto_deduct_from_payroll && in_array($advance->status, ['disbursed','active']))
        <div class="lmt-alert lmt-alert-info text-xs m-4 mb-0">
            <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
            Set to "Employee pays separately" — payroll will not deduct these automatically. Record each payment manually below as it's received.
        </div>
        @endif
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($advance->deductions as $ded)
                    @php
                    $dedColors = [
                        'pending'  => 'lmt-badge-amber',
                        'deducted' => 'lmt-badge-green',
                        'skipped'  => 'lmt-badge-gray',
                        'waived'   => 'lmt-badge-gray',
                    ];
                    $isOverdue = $ded->status === 'pending' && $ded->deduction_date->isPast();
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-red-50/30' : '' }}">
                        <td class="text-sm font-bold text-gray-800">{{ $ded->deduction_number }}</td>
                        <td class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                            {{ $ded->deduction_date->format('M j, Y') }}
                            @if($isOverdue)<span class="block text-xs text-red-600">Overdue</span>@endif
                        </td>
                        <td class="text-sm font-bold text-gray-900">${{ number_format($ded->amount, 2) }}</td>
                        <td>
                            <span class="{{ $dedColors[$ded->status] ?? 'lmt-badge-gray' }} text-xs">
                                {{ ucfirst($ded->status) }}
                            </span>
                        </td>
                        <td>
                            @if($ded->status === 'pending' && in_array($advance->status, ['disbursed','active']))
                            <button onclick="openDeductModal({{ $ded->id }}, {{ $ded->amount }})"
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Record Deduction">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-800">
                            Schedule will be generated upon disbursement
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Record Deduction Modal --}}
<div id="deduct-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Record Deduction</h3>
        <form action="{{ route('admin.loans.advance.deduction', [$tenant, $advance->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="deduction_id" id="deduct-id"/>
            <div>
                <label class="lmt-label">Amount ($) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" id="deduct-amount" step="0.01" required class="lmt-input"/>
            </div>
            <div>
                <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                <input type="date" name="deduction_date" required class="lmt-input" value="{{ today()->toDateString() }}"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Record</button>
                <button type="button" onclick="closeModal('deduct-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
function openDeductModal(deductionId, amount) {
    document.getElementById('deduct-id').value = deductionId;
    document.getElementById('deduct-amount').value = amount;
    openModal('deduct-modal');
}
document.getElementById('deduct-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('deduct-modal'); });
</script>
@endpush
