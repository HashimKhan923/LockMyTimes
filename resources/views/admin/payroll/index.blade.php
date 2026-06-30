@extends('layouts.admin')
@section('title','Payroll')
@section('page-title','Payroll')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Runs',        'value'=>$stats['total_runs'],       'icon'=>'layers',       'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Total Paid YTD',    'value'=>'$'.number_format($stats['total_paid_ytd'],0), 'icon'=>'dollar-sign','bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Pending Approval',  'value'=>$stats['pending_approval'], 'icon'=>'clock',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Last Run',          'value'=>$stats['last_run'] ? $stats['last_run']->pay_date?->format('M j, Y') : 'None','icon'=>'calendar','bg'=>'bg-purple-50','text'=>'text-purple-600'],
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

<div class="grid lg:grid-cols-3 gap-6 mb-6">

    {{-- Chart --}}
    <div class="lg:col-span-2 lmt-card">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-black text-gray-900">Payroll Spend</h3>
                <p class="text-xs text-gray-400">Net salary paid last 6 months</p>
            </div>
            <a href="{{ route('admin.payroll.components', $tenant) }}"
               class="lmt-btn-secondary lmt-btn-sm">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Salary Components
            </a>
        </div>
        <div class="flex items-end gap-3 h-36">
            @php $max = $chart->max('amount') ?: 1; @endphp
            @foreach($chart as $c)
            <div class="flex-1 flex flex-col items-center gap-1">
                <span class="text-xs font-bold text-gray-500">
                    {{ $c['amount'] > 0 ? '$'.number_format($c['amount']/1000,0).'k' : '—' }}
                </span>
                <div class="w-full rounded-t-lg transition-all duration-700 lmt-gradient-bg"
                     style="height:{{ $max > 0 ? round(($c['amount']/$max)*100) : 4 }}%; min-height:4px;">
                </div>
                <span class="text-xs font-semibold text-gray-400">{{ $c['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- New Run Panel --}}
    <div class="lmt-card">
        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
            <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center">
                <i data-lucide="play-circle" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-black text-gray-900 text-sm">Run Payroll</h3>
                <p class="text-xs text-gray-400">Create a new payroll run</p>
            </div>
        </div>
        <form action="{{ route('admin.payroll.run', $tenant) }}" method="POST" class="space-y-3">
            @csrf
            @php
            $defaultStart = now()->startOfMonth()->toDateString();
            $defaultEnd   = now()->endOfMonth()->toDateString();
            $defaultPay   = now()->endOfMonth()->toDateString();
            @endphp
            <div>
                <label class="lmt-label text-xs">Frequency</label>
                <select name="frequency" class="lmt-select py-2 text-sm">
                    <option value="monthly">Monthly</option>
                    <option value="bi_weekly">Bi-Weekly</option>
                    <option value="semi_monthly">Semi-Monthly</option>
                    <option value="weekly">Weekly</option>
                </select>
            </div>
            <div>
                <label class="lmt-label text-xs">Period Start</label>
                <input type="date" name="period_start" value="{{ $defaultStart }}" class="lmt-input py-2 text-sm"/>
            </div>
            <div>
                <label class="lmt-label text-xs">Period End</label>
                <input type="date" name="period_end" value="{{ $defaultEnd }}" class="lmt-input py-2 text-sm"/>
            </div>
            <div>
                <label class="lmt-label text-xs">Pay Date</label>
                <input type="date" name="pay_date" value="{{ $defaultPay }}" class="lmt-input py-2 text-sm"/>
            </div>
            <div>
                <label class="lmt-label text-xs">Notes</label>
                <input type="text" name="notes" class="lmt-input py-2 text-sm" placeholder="Optional notes…"/>
            </div>
            <button type="submit" class="lmt-btn-primary w-full mt-2">
                <i data-lucide="zap" class="w-4 h-4"></i>
                Generate Payroll
            </button>
        </form>
    </div>
</div>

{{-- Payroll Runs Table --}}
<div class="lmt-card p-0 overflow-hidden">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900">Payroll History</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Run #</th>
                    <th>Period</th>
                    <th>Pay Date</th>
                    <th>Frequency</th>
                    <th>Employees</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                @php
                    $statusColors = [
                        'draft'     => 'lmt-badge-gray',
                        'finalized' => 'lmt-badge-brand',
                        'paid'      => 'lmt-badge-green',
                        'cancelled' => 'lmt-badge-red',
                    ];
                @endphp
                <tr>
                    <td>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                            {{ $run->run_number }}
                        </code>
                    </td>
                    <td class="text-sm text-gray-700">
                        {{ Carbon\Carbon::parse($run->period_start)->format('M j') }}
                        – {{ Carbon\Carbon::parse($run->period_end)->format('M j, Y') }}
                    </td>
                    <td class="text-sm text-gray-700">
                        {{ $run->pay_date?->format('M j, Y') ?? '—' }}
                    </td>
                    <td>
                        <span class="lmt-badge-gray text-xs capitalize">
                            {{ ucfirst(str_replace('_',' ', $run->pay_schedule ?? 'monthly')) }}
                        </span>
                    </td>
                    <td class="text-sm font-bold text-gray-900">{{ $run->total_employees }}</td>
                    <td class="text-sm text-gray-700">${{ number_format($run->total_gross, 0) }}</td>
                    <td class="text-sm font-bold text-emerald-600">${{ number_format($run->total_net, 0) }}</td>
                    <td>
                        <span class="{{ $statusColors[$run->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ str_replace('_',' ',$run->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.payroll.show', [$tenant, $run->id]) }}"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </a>
                            @if($run->status === 'draft')
                            <form action="{{ route('admin.payroll.approve', [$tenant, $run->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Approve">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                            @if($run->status === 'approved')
                            <form action="{{ route('admin.payroll.paid', [$tenant, $run->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Mark Paid"
                                        onclick="return confirm('Mark this payroll as paid?')">
                                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-16">
                        <i data-lucide="dollar-sign" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="font-semibold text-gray-400 mb-1">No payroll runs yet</p>
                        <p class="text-sm text-gray-400">Use the form above to generate your first payroll</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($runs->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $runs->links() }}</div>
    @endif
</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush