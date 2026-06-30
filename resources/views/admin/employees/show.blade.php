@extends('layouts.admin')
@section('title', $employee->full_name)
@section('page-title', 'Employee Profile')

@section('content')

<div class="max-w-5xl mx-auto">

    {{-- Back --}}
    <a href="{{ route('admin.employees.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Employees
    </a>

    {{-- Profile header --}}
    <div class="lmt-card mb-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-24 lmt-gradient-bg opacity-10"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">

            <div class="w-20 h-20 rounded-2xl overflow-hidden border-4 border-white shadow-pop flex-shrink-0">
                @if($employee->avatar)
                <img src="{{ $employee->avatar_url }}" class="w-full h-full object-cover"/>
                @else
                <div class="w-full h-full lmt-gradient-bg flex items-center justify-center text-white text-2xl font-black">
                    {{ substr($employee->first_name,0,1) }}
                </div>
                @endif
            </div>

            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-3 mb-1">
                    <h2 class="text-xl font-black text-gray-900">{{ $employee->full_name }}</h2>
                    @php
                    $statusColors = ['active'=>'lmt-badge-green','on_leave'=>'lmt-badge-amber','suspended'=>'lmt-badge-red','terminated'=>'lmt-badge-gray'];
                    @endphp
                    <span class="{{ $statusColors[$employee->employment_status] ?? 'lmt-badge-gray' }}">
                        {{ ucfirst(str_replace('_',' ',$employee->employment_status)) }}
                    </span>
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">{{ $employee->employee_code }}</code>
                </div>
                <p class="text-sm text-gray-500">
                    {{ $employee->position?->title ?? 'No Position' }}
                    @if($employee->department)· {{ $employee->department->name }}@endif
                </p>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1"><i data-lucide="mail" class="w-3.5 h-3.5"></i>{{ $employee->email }}</span>
                    @if($employee->phone)
                    <span class="flex items-center gap-1"><i data-lucide="phone" class="w-3.5 h-3.5"></i>{{ $employee->phone }}</span>
                    @endif
                    <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i>Hired {{ $employee->hire_date->format('M j, Y') }}</span>
                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i>{{ $employee->service_months }} months service</span>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.employees.edit', [$tenant, $employee->id]) }}"
                   class="lmt-btn-secondary lmt-btn-sm">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                    Edit
                </a>
                @if($employee->employment_status !== 'terminated')
                <button onclick="document.getElementById('terminate-modal').classList.remove('hidden');document.getElementById('terminate-modal').classList.add('flex');"
                        class="lmt-btn-danger lmt-btn-sm">
                    <i data-lucide="user-x" class="w-4 h-4"></i>
                    Terminate
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Left col — details --}}
        <div class="space-y-6">

            {{-- Employment details --}}
            <div class="lmt-card">
                <h3 class="font-black text-gray-900 mb-4">Employment</h3>
                <div class="space-y-3">
                    @foreach([
                        ['label'=>'Type',       'value'=>ucfirst(str_replace('_',' ',$employee->employment_type))],
                        ['label'=>'Location',   'value'=>$employee->location?->name ?? '—'],
                        ['label'=>'Manager',    'value'=>$employee->manager?->full_name ?? '—'],
                        ['label'=>'Salary',     'value'=>'$'.number_format($employee->base_salary,0).' / '.str_replace('_',' ',$employee->salary_frequency)],
                        ['label'=>'Probation',  'value'=>$employee->probation_end_date?->format('M j, Y') ?? '—'],
                    ] as $row)
                    <div class="flex justify-between py-2 border-b border-gray-50 last:border-none">
                        <span class="text-xs text-gray-400 font-medium">{{ $row['label'] }}</span>
                        <span class="text-xs font-semibold text-gray-700 text-right">{{ $row['value'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Leave Balances --}}
            <div class="lmt-card">
                <h3 class="font-black text-gray-900 mb-4">Leave Balances</h3>
                @forelse($employee->leaveBalances as $balance)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-none">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" style="background:{{ $balance->leaveType->color }}"></div>
                        <span class="text-xs font-medium text-gray-700">{{ $balance->leaveType->name }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-black text-gray-900">{{ $balance->available }}</span>
                        <span class="text-xs text-gray-400">/ {{ $balance->allocated + $balance->accrued }} days</span>
                    </div>
                </div>
                @empty
                <p class="text-xs text-gray-400 text-center py-4">No leave balances set up</p>
                @endforelse
            </div>
        </div>

        {{-- Right col — assets, training, docs --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Current Assets --}}
            <div class="lmt-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-black text-gray-900">Assigned Assets</h3>
                    <span class="lmt-badge-brand text-xs">{{ $employee->assignedAssets->where('returned_at',null)->count() }} active</span>
                </div>
                @forelse($employee->assignedAssets->where('returned_at',null) as $assignment)
                <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-none">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $assignment->asset->name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $assignment->asset->category?->name }} ·
                            {{ $assignment->assigned_at->format('M j, Y') }}
                        </p>
                    </div>
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                        {{ $assignment->asset->asset_code }}
                    </code>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-6">No assets assigned</p>
                @endforelse
            </div>

            {{-- Training --}}
            <div class="lmt-card">
                <h3 class="font-black text-gray-900 mb-4">Training & Certifications</h3>
                @forelse($employee->trainingEnrollments->take(5) as $enrollment)
                <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-none">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $enrollment->training->title ?? 'Training' }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden max-w-24">
                                <div class="h-full rounded-full" style="width:{{ $enrollment->progress }}%; background:#6C7DF7;"></div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $enrollment->progress }}%</span>
                        </div>
                    </div>
                    <span class="lmt-badge text-xs {{ $enrollment->status === 'completed' ? 'lmt-badge-green' : 'lmt-badge-brand' }}">
                        {{ ucfirst($enrollment->status) }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-6">No training enrollments</p>
                @endforelse
            </div>

            {{-- Loans --}}
            @if($employee->loans->isNotEmpty() || $employee->salaryAdvances->isNotEmpty())
            <div class="lmt-card">
                <h3 class="font-black text-gray-900 mb-4">Loans & Advances</h3>
                @foreach($employee->loans->where('status','active') as $loan)
                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $loan->loanType->name }}</p>
                        <p class="text-xs text-gray-400">{{ $loan->loan_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-red-500">${{ number_format($loan->amount_remaining,0) }}</p>
                        <p class="text-xs text-gray-400">remaining</p>
                    </div>
                </div>
                @endforeach
                @foreach($employee->salaryAdvances->where('status','active') as $adv)
                <div class="flex items-center justify-between py-2.5">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Salary Advance</p>
                        <p class="text-xs text-gray-400">{{ $adv->advance_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-amber-500">${{ number_format($adv->amount_remaining,0) }}</p>
                        <p class="text-xs text-gray-400">remaining</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Terminate Modal --}}
<div id="terminate-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="text-center mb-5">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="user-x" class="w-7 h-7"></i>
            </div>
            <h3 class="font-black text-gray-900">Terminate Employee</h3>
            <p class="text-sm text-gray-500 mt-1">{{ $employee->full_name }}</p>
        </div>
        <form action="{{ route('admin.employees.terminate', [$tenant, $employee->id]) }}" method="POST">
            @csrf @method('PATCH')
            <div class="mb-4">
                <label class="lmt-label">Termination Date <span class="text-red-500">*</span></label>
                <input type="date" name="termination_date" value="{{ today()->format('Y-m-d') }}"
                       required class="lmt-input"/>
            </div>
            <div class="mb-4">
                <label class="lmt-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="termination_reason" required class="lmt-textarea" rows="3"
                          placeholder="Reason for termination…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Confirm Termination</button>
                <button type="button"
                        onclick="document.getElementById('terminate-modal').classList.add('hidden');document.getElementById('terminate-modal').classList.remove('flex');"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush