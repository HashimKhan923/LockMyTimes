@extends('layouts.admin')
@section('title','Expenses')
@section('page-title','Expense Management')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Pending Approval',  'value'=>$stats['pending'],                              'icon'=>'clock',       'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Approved (Month)',  'value'=>'$'.number_format($stats['approved_month'],0),   'icon'=>'check-circle','bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Rejected (Month)',  'value'=>$stats['rejected_month'],                        'icon'=>'x-circle',    'bg'=>'bg-red-50',    'text'=>'text-red-600'],
        ['label'=>'Total Paid YTD',    'value'=>'$'.number_format($stats['total_ytd'],0),        'icon'=>'dollar-sign', 'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
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
                <h3 class="font-black text-gray-900">Expense Spend</h3>
                <p class="text-xs text-gray-800">Approved expenses last 6 months</p>
            </div>
            <div class="flex items-center gap-2">
                @include('exports.buttons', ['route' => 'admin.expenses.export', 'params' => [$tenant]])
                <a href="{{ route('admin.expenses.categories', $tenant) }}"
                   class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="tag" class="w-4 h-4"></i>
                    Categories
                </a>
                <button onclick="openModal('add-expense-modal')"
                        class="lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Expense
                </button>
            </div>
        </div>
        <div class="flex items-end gap-3 h-32">
            @php $max = $chart->max('amount') ?: 1; @endphp
            @foreach($chart as $c)
            <div class="flex-1 flex flex-col items-center gap-1.5">
                <span class="text-xs font-bold text-gray-800">
                    {{ $c['amount'] > 0 ? '$'.number_format($c['amount']/1000,1).'k' : '—' }}
                </span>
                <div class="w-full rounded-t-lg lmt-gradient-bg"
                     style="height:{{ $max > 0 ? max(round(($c['amount']/$max)*100),4) : 4 }}%; min-height:4px;"></div>
                <span class="text-xs text-gray-800 font-semibold">{{ $c['label'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Category breakdown --}}
        @if($categoryBreakdown->isNotEmpty())
        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">By Category (YTD)</p>
            <div class="space-y-2">
                @foreach($categoryBreakdown as $cb)
                @php $pct = $stats['total_ytd'] > 0 ? round(($cb->total/$stats['total_ytd'])*100) : 0; @endphp
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                         style="background:{{ $cb->category->color ?? '#6C7DF7' }}"></div>
                    <span class="text-xs text-gray-600 w-28 truncate">{{ $cb->category->name ?? 'Unknown' }}</span>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full"
                             style="width:{{ $pct }}%; background:{{ $cb->category->color ?? '#6C7DF7' }}"></div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 w-16 text-right">
                        ${{ number_format($cb->total,0) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Quick info --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-4">Quick Actions</h3>
        <div class="space-y-2">
            @foreach([
                ['label'=>'View Pending',   'icon'=>'clock',       'status'=>'submitted'],
                ['label'=>'View Approved',  'icon'=>'check-circle','status'=>'approved'],
                ['label'=>'View Rejected',  'icon'=>'x-circle',    'status'=>'rejected'],
                ['label'=>'All Expenses',   'icon'=>'list',        'status'=>'all'],
            ] as $link)
            <a href="{{ route('admin.expenses.index', $tenant) }}?status={{ $link['status'] }}"
               class="flex items-center gap-3 p-3 rounded-xl hover:bg-brand-50 transition-colors group {{ $status === $link['status'] ? 'bg-brand-50' : '' }}">
                <div class="w-8 h-8 rounded-lg {{ $status === $link['status'] ? 'bg-brand-500 text-white' : 'bg-brand-50 text-brand-600' }} group-hover:bg-brand-500 group-hover:text-white flex items-center justify-center transition-all">
                    <i data-lucide="{{ $link['icon'] }}" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">{{ $link['label'] }}</span>
                @if($link['status'] === 'submitted' && $stats['pending'] > 0)
                <span class="ml-auto lmt-badge-amber text-xs">{{ $stats['pending'] }}</span>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Filter by date --}}
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-800 uppercase mb-3">Filter by Date</p>
            <form action="{{ route('admin.expenses.index', $tenant) }}" method="GET" class="space-y-2">
                <input type="hidden" name="status" value="{{ $status }}"/>
                <div>
                    <label class="lmt-label text-xs">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="lmt-input py-1.5 text-sm"/>
                </div>
                <div>
                    <label class="lmt-label text-xs">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="lmt-input py-1.5 text-sm"/>
                </div>
                <button type="submit" class="lmt-btn-primary w-full lmt-btn-sm">Apply Filter</button>
            </form>
        </div>
    </div>
</div>

{{-- Expenses Table --}}
<div class="lmt-card p-0 overflow-hidden">

    {{-- Tabs --}}
    <div class="border-b border-gray-100">
        <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto">
            @foreach(['submitted'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','paid'=>'Paid','all'=>'All'] as $val=>$label)
            <a href="{{ route('admin.expenses.index', $tenant) }}?status={{ $val }}"
               class="px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition-all rounded-t-lg
                      {{ $status === $val
                          ? 'bg-white border-t border-l border-r border-gray-200 text-brand-600 -mb-px'
                          : 'text-gray-800 hover:text-gray-700' }}">
                {{ $label }}
                @if($val === 'submitted' && $stats['pending'] > 0)
                <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['pending'] }}</span>
                @endif
            </a>
            @endforeach
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="p-4 border-b border-gray-100 bg-gray-50 flex flex-wrap gap-3">
        <form action="{{ route('admin.expenses.index', $tenant) }}" method="GET"
              class="flex flex-wrap gap-3 items-center flex-1">
            <input type="hidden" name="status" value="{{ $status }}"/>
            <select name="employee" class="lmt-select py-2 text-sm w-auto min-w-40">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->full_name }}
                </option>
                @endforeach
            </select>
            <select name="category" class="lmt-select py-2 text-sm w-auto min-w-36">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>
            @if(request()->hasAny(['employee','category','from','to']))
            <a href="{{ route('admin.expenses.index', $tenant) }}?status={{ $status }}"
               class="lmt-btn-ghost lmt-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Expense #</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $exp)
                @php
                $statusColors = [
                    'draft'     => 'lmt-badge-gray',
                    'submitted' => 'lmt-badge-amber',
                    'approved'  => 'lmt-badge-green',
                    'rejected'  => 'lmt-badge-red',
                    'paid'      => 'lmt-badge-brand',
                ];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">
                                {{ substr($exp->employee->first_name ?? 'E', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $exp->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-800">{{ $exp->employee->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                            {{ $exp->expense_number }}
                        </code>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full"
                                 style="background:{{ $exp->category->color ?? '#6C7DF7' }}"></div>
                            <span class="text-sm text-gray-700">{{ $exp->category->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="text-sm text-gray-600">{{ $exp->expense_date->format('M j, Y') }}</td>
                    <td>
                        <span class="text-sm font-black text-gray-900">
                            ${{ number_format($exp->amount, 2) }}
                        </span>
                        @if($exp->is_billable)
                        <span class="block lmt-badge-brand text-xs mt-0.5">Billable</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600 max-w-36 truncate">
                        {{ $exp->title ?? $exp->description }}
                    </td>
                    <td>
                        @if($exp->receipt_path)
                        <a href="{{ $exp->receipt_url }}" target="_blank"
                           class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                           title="View Receipt">
                            <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                        </a>
                        @else
                        <span class="text-xs text-gray-800">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $statusColors[$exp->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ $exp->status }}
                        </span>
                        @if($exp->status === 'rejected' && $exp->rejection_reason)
                        <p class="text-xs text-red-400 mt-0.5 max-w-24 truncate" title="{{ $exp->rejection_reason }}">
                            {{ $exp->rejection_reason }}
                        </p>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            @if($exp->status === 'submitted')
                            <form action="{{ route('admin.expenses.approve', [$tenant, $exp->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Approve">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <button onclick="openRejectModal({{ $exp->id }})"
                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Reject">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                            @if($exp->status === 'approved')
                            <form action="{{ route('admin.expenses.paid', [$tenant, $exp->id]) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Mark Paid">
                                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                            @if(in_array($exp->status, ['draft','submitted']))
                            <form action="{{ route('admin.expenses.destroy', [$tenant, $exp->id]) }}"
                                  method="POST" onsubmit="return confirm('Delete this expense?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Delete">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-14">
                        <i data-lucide="receipt" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="font-semibold text-gray-800">No {{ $status === 'all' ? '' : $status }} expenses</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($expenses->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $expenses->links() }}</div>
    @endif
</div>

{{-- Add Expense Modal --}}
<div id="add-expense-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Add Expense</h3>
        <form action="{{ route('admin.expenses.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
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
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="expense_date" required class="lmt-input"
                           value="{{ today()->toDateString() }}"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required class="lmt-input"
                           placeholder="e.g. Flight to NYC"/>
                </div>
                <div>
                    <label class="lmt-label">Amount ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="lmt-input"
                           placeholder="0.00"/>
                </div>
                <div>
                    <label class="lmt-label">Merchant</label>
                    <input type="text" name="merchant" class="lmt-input" placeholder="e.g. Delta Airlines"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Description</label>
                    <textarea name="description" class="lmt-textarea" rows="2"
                              placeholder="Additional details…"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Receipt (optional)</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                           class="lmt-input py-2 text-sm"/>
                    <p class="lmt-help">JPG, PNG or PDF up to 5MB</p>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Submit Expense</button>
                <button type="button" onclick="closeModal('add-expense-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="reject-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Reject Expense</h3>
        <form id="reject-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" rows="3"
                          placeholder="Why is this expense being rejected?"></textarea>
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
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
function openRejectModal(expenseId) {
    document.getElementById('reject-form').action =
        `/t/{{ $tenant }}/admin/expenses/${expenseId}/reject`;
    openModal('reject-modal');
}
['add-expense-modal','reject-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush