@extends('layouts.admin')
@section('title','Loan — '.$loan->loan_number)
@section('page-title','Loan Detail')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.loans.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Loans
    </a>
    <div class="flex items-center gap-2">
        @if($loan->status === 'pending')
        <form action="{{ route('admin.loans.approve', [$tenant, $loan->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                <i data-lucide="check" class="w-4 h-4"></i> Approve
            </button>
        </form>
        @endif
        @if($loan->status === 'approved')
        <button onclick="openModal('disburse-modal')" class="lmt-btn-primary lmt-btn-sm"
                style="background:#8B5CF6">
            <i data-lucide="banknote" class="w-4 h-4"></i> Disburse
        </button>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Loan Info --}}
    <div class="space-y-5">

        {{-- Header card --}}
        <div class="lmt-card">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <code class="font-black text-gray-900 text-lg">{{ $loan->loan_number }}</code>
                    @php
                    $sc = ['pending'=>'lmt-badge-amber','approved'=>'lmt-badge-brand','disbursed'=>'lmt-badge-green','active'=>'lmt-badge-green','completed'=>'lmt-badge-gray','rejected'=>'lmt-badge-red'];
                    @endphp
                    <span class="block mt-1 {{ $sc[$loan->status] ?? 'lmt-badge-gray' }} text-xs w-fit">
                        {{ ucfirst($loan->status) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-xs font-black"
                     style="background:{{ $loan->loanType->color ?? '#6C7DF7' }}">
                    {{ substr($loan->loanType->code ?? 'LN', 0, 2) }}
                </div>
            </div>

            {{-- Employee --}}
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl mb-4">
                <div class="lmt-avatar-sm font-bold text-xs">
                    {{ substr($loan->employee->first_name ?? 'E', 0, 1) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ $loan->employee->full_name }}</p>
                    <p class="text-xs text-gray-800">{{ $loan->employee->department?->name }} · {{ $loan->employee->employee_code }}</p>
                </div>
            </div>

            {{-- Loan details --}}
            <div class="space-y-2">
                @foreach([
                    ['Type',         $loan->loanType->name ?? '—'],
                    ['Principal',    '$'.number_format($loan->principal_amount, 2)],
                    ['Interest',     $loan->interest_rate.'% '.ucfirst($loan->interest_type)],
                    ['Total Amount', '$'.number_format($loan->total_amount, 2)],
                    ['EMI',          '$'.number_format($loan->emi_amount, 2).'/month'],
                    ['Tenure',       $loan->tenure_months.' months'],
                    ['First EMI',    $loan->first_emi_date?->format('M j, Y') ?? '—'],
                    ['Purpose',      $loan->purpose ?? '—'],
                ] as [$k,$v])
                <div class="flex justify-between py-2 border-b border-gray-50 last:border-none">
                    <span class="text-xs text-gray-800 font-medium">{{ $k }}</span>
                    <span class="text-xs font-semibold text-gray-800 text-right max-w-36">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Progress --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Repayment Progress</h3>
            @php
            $pct = $loan->total_amount > 0
                ? round(((float)$loan->amount_paid / (float)$loan->total_amount) * 100) : 0;
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
                    <p class="text-sm font-black text-emerald-700">${{ number_format($loan->amount_paid, 0) }}</p>
                    <p class="text-xs text-gray-800">Paid</p>
                </div>
                <div class="bg-red-50 rounded-xl p-2">
                    <p class="text-sm font-black text-red-600">${{ number_format($loan->amount_remaining, 0) }}</p>
                    <p class="text-xs text-gray-800">Remaining</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-2">
                    <p class="text-sm font-black text-gray-900">{{ $loan->installments_paid }}/{{ $loan->tenure_months }}</p>
                    <p class="text-xs text-gray-800">Installments</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Repayment Schedule --}}
    <div class="lg:col-span-2 lmt-card p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-black text-gray-900">Repayment Schedule</h3>
            <span class="lmt-badge-gray text-xs">{{ $loan->repayments->count() }} installments</span>
        </div>
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Due Date</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>EMI</th>
                        <th>Balance After</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->repayments as $rep)
                    @php
                    $repColors = [
                        'pending' => 'lmt-badge-amber',
                        'paid'    => 'lmt-badge-green',
                        'overdue' => 'lmt-badge-red',
                        'waived'  => 'lmt-badge-gray',
                    ];
                    $isOverdue = $rep->status === 'pending' && $rep->due_date->isPast();
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-red-50/30' : '' }}">
                        <td class="text-sm font-bold text-gray-800">{{ $rep->installment_number }}</td>
                        <td class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                            {{ $rep->due_date->format('M j, Y') }}
                            @if($isOverdue)<span class="block text-xs text-red-600">Overdue</span>@endif
                        </td>
                        <td class="text-sm text-gray-800">${{ number_format($rep->principal_component, 2) }}</td>
                        <td class="text-sm text-gray-800">${{ number_format($rep->interest_component, 2) }}</td>
                        <td class="text-sm font-bold text-gray-900">${{ number_format($rep->emi_amount, 2) }}</td>
                        <td class="text-sm text-gray-800">${{ number_format($rep->balance_after, 2) }}</td>
                        <td>
                            <span class="{{ $repColors[$rep->status] ?? 'lmt-badge-gray' }} text-xs">
                                {{ $rep->paid_date ? $rep->paid_date->format('M j') : ucfirst($rep->status) }}
                            </span>
                        </td>
                        <td>
                            @if(in_array($rep->status, ['pending','overdue']) && in_array($loan->status, ['disbursed','active']))
                            <button onclick="openRepayModal({{ $rep->id }}, {{ $rep->emi_amount }})"
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Record Payment">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-gray-800">
                            Schedule will be generated upon disbursement
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Disburse Modal --}}
<div id="disburse-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Disburse Loan</h3>
        <form action="{{ route('admin.loans.disburse', [$tenant, $loan->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Payment Method <span class="text-red-500">*</span></label>
                <select name="disbursement_method" required class="lmt-select">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="direct_deposit">Direct Deposit</option>
                    <option value="check">Check</option>
                    <option value="cash">Cash</option>
                </select>
            </div>
            <div>
                <label class="lmt-label">Reference / Transaction ID</label>
                <input type="text" name="disbursement_reference" class="lmt-input" placeholder="TXN123456"/>
            </div>
            <div class="lmt-alert lmt-alert-info text-xs">
                <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                Disbursing will generate the complete repayment schedule automatically.
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Confirm Disbursement</button>
                <button type="button" onclick="closeModal('disburse-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Record Repayment Modal --}}
<div id="repay-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Record Repayment</h3>
        <form action="{{ route('admin.loans.repayment', [$tenant, $loan->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="installment_id" id="repay-installment-id"/>
            <div>
                <label class="lmt-label">Amount Paid ($) <span class="text-red-500">*</span></label>
                <input type="number" name="amount_paid" id="repay-amount" step="0.01" required class="lmt-input"/>
            </div>
            <div>
                <label class="lmt-label">Payment Date <span class="text-red-500">*</span></label>
                <input type="date" name="paid_date" required class="lmt-input" value="{{ today()->toDateString() }}"/>
            </div>
            <div>
                <label class="lmt-label">Payment Source <span class="text-red-500">*</span></label>
                <select name="payment_source" required class="lmt-select">
                    <option value="payroll_deduction">Payroll Deduction</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                </select>
            </div>
            <div>
                <label class="lmt-label">Reference</label>
                <input type="text" name="reference" class="lmt-input" placeholder="Optional reference…"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Record Payment</button>
                <button type="button" onclick="closeModal('repay-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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
function openRepayModal(installmentId, amount) {
    document.getElementById('repay-installment-id').value = installmentId;
    document.getElementById('repay-amount').value = amount;
    openModal('repay-modal');
}
['disburse-modal','repay-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush