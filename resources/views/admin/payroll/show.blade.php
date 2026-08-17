@extends('layouts.admin')
@section('title', 'Payroll Run — '.$payrollRun->run_number)
@section('page-title','Payroll Run Detail')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.payroll.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Payroll
    </a>
    <div class="flex items-center gap-3">
        @if($payrollRun->status === 'draft')
        <form action="{{ route('admin.payroll.regenerate', [$tenant, $payrollRun->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-secondary lmt-btn-sm"
                    onclick="return confirm('Recalculate all payslips?')">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Recalculate
            </button>
        </form>
        <button type="button" class="lmt-btn-secondary lmt-btn-sm" style="color:#EF4444;border-color:#FCA5A5;"
                onclick="openModal('reject-modal')">
            <i data-lucide="x" class="w-4 h-4"></i>
            Reject Run
        </button>
        <form action="{{ route('admin.payroll.approve', [$tenant, $payrollRun->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                <i data-lucide="check" class="w-4 h-4"></i>
                Approve Run
            </button>
        </form>
        @elseif($payrollRun->status === 'approved')
        <form action="{{ route('admin.payroll.paid', [$tenant, $payrollRun->id]) }}" method="POST">
            @csrf @method('PATCH')
            <button type="submit" class="lmt-btn-primary lmt-btn-sm"
                    onclick="return confirm('Mark all {{ $payrollRun->total_employees }} employees as paid?')"
                    style="background:#10B981;">
                <i data-lucide="banknote" class="w-4 h-4"></i>
                Mark as Paid
            </button>
        </form>
        @endif
    </div>
</div>

@if($payrollRun->status === 'rejected')
<div class="lmt-card mb-6" style="background:#FEF2F2;border-color:#FCA5A5;">
    <div class="flex items-start gap-3">
        <i data-lucide="x-circle" class="w-5 h-5 flex-shrink-0" style="color:#EF4444;"></i>
        <div>
            <p class="font-bold text-gray-900">Rejected by {{ $payrollRun->rejecter?->name ?? 'an admin' }}
                @if($payrollRun->rejected_at) on {{ $payrollRun->rejected_at->format('M j, Y g:i A') }} @endif
            </p>
            <p class="text-sm text-gray-600 mt-1">{{ $payrollRun->rejection_reason }}</p>
        </div>
    </div>
</div>
@endif

{{-- Run Header --}}
<div class="lmt-card mb-6 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-48 h-48 rounded-full lmt-gradient-bg opacity-5 -translate-y-1/2 translate-x-1/2"></div>
    <div class="relative grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <p class="text-xs text-gray-400 mb-1">Run Number</p>
            <code class="font-black text-gray-900 text-lg">{{ $payrollRun->run_number }}</code>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Period</p>
            <p class="font-bold text-gray-900">
                {{ Carbon\Carbon::parse($payrollRun->period_start)->format('M j') }}
                – {{ Carbon\Carbon::parse($payrollRun->period_end)->format('M j, Y') }}
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Pay Date</p>
            <p class="font-bold text-gray-900">{{ $payrollRun->pay_date?->format('F j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Status</p>
            @php
            $statusColors = ['draft'=>'lmt-badge-gray','finalized'=>'lmt-badge-brand','paid'=>'lmt-badge-green'];
            @endphp
            <span class="{{ $statusColors[$payrollRun->status] ?? 'lmt-badge-gray' }} text-sm font-bold">
                {{ ucfirst($payrollRun->status) }}
            </span>
        </div>
    </div>
</div>

{{-- Totals --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Employees',    'value'=>$payrollRun->total_employees,                            'icon'=>'users',       'color'=>'text-brand-600',  'bg'=>'bg-brand-50'],
        ['label'=>'Total Gross',  'value'=>'$'.number_format($payrollRun->total_gross, 2),           'icon'=>'trending-up', 'color'=>'text-gray-700',   'bg'=>'bg-gray-100'],
        ['label'=>'Total Tax',    'value'=>'$'.number_format($payrollRun->total_tax ?? 0, 2),        'icon'=>'percent',     'color'=>'text-red-600',    'bg'=>'bg-red-50'],
        ['label'=>'Total Net',    'value'=>'$'.number_format($payrollRun->total_net, 2),             'icon'=>'dollar-sign', 'color'=>'text-emerald-600','bg'=>'bg-emerald-50'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['color'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400">{{ $s['label'] }}</p>
            <p class="text-lg font-black {{ $s['color'] }}">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Payslips Table --}}
    <div class="lg:col-span-2 lmt-card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-black text-gray-900">Payslips ({{ $payrollRun->payslips->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="lmt-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Base</th>
                        <th>Gross</th>
                        <th>Tax</th>
                        <th>Deductions</th>
                        <th class="text-emerald-600">Net</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrollRun->payslips as $ps)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="lmt-avatar-sm font-bold text-xs">
                                    {{ substr($ps->employee->first_name ?? 'E', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">{{ $ps->employee->full_name ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $ps->employee->department?->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm text-gray-700">${{ number_format($ps->base_pay, 0) }}</td>
                        <td class="text-sm text-gray-700">${{ number_format($ps->gross_pay, 0) }}</td>
                        <td class="text-sm text-red-500">-${{ number_format($ps->tax_amount, 0) }}</td>
                        <td class="text-sm text-red-500">-${{ number_format($ps->total_deductions, 0) }}</td>
                        <td class="text-sm font-black text-emerald-600">${{ number_format($ps->net_pay, 0) }}</td>
                        <td>
                            <a href="{{ route('admin.payroll.payslip', [$tenant, $ps->id]) }}"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors"
                               title="View Payslip">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Department Breakdown --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">By Department</h3>
        <div class="space-y-3">
            @foreach($deptBreakdown as $dept => $data)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700 truncate">{{ $dept }}</span>
                    <span class="text-sm font-black text-gray-900 ml-2">
                        ${{ number_format($data['net_total'], 0) }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full lmt-gradient-bg"
                             style="width:{{ $payrollRun->total_net > 0 ? round(($data['net_total']/$payrollRun->total_net)*100) : 0 }}%">
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 w-8 text-right">{{ $data['count'] }}</span>
                </div>
            </div>
            @endforeach
        </div>

        @if($payrollRun->notes)
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs text-gray-400 mb-1">Notes</p>
            <p class="text-sm text-gray-700">{{ $payrollRun->notes }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Reject Payroll Run</h3>
        <form action="{{ route('admin.payroll.reject', [$tenant, $payrollRun->id]) }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3"
                          placeholder="Why is this payroll run being rejected?"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Reject</button>
                <button type="button" onclick="closeModal('reject-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
document.getElementById('reject-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal('reject-modal');
});
</script>
@endpush