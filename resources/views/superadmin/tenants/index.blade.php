@extends('layouts.superadmin')
@section('title','Organizations')
@section('page-title','Organizations')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-ink" style="font-family:'Plus Jakarta Sans',sans-serif">All Organizations</h2>
        <p class="text-sm text-ink-soft mt-0.5">{{ $tenants->total() }} total organizations</p>
    </div>
    <a href="{{ route('superadmin.organizations.create') }}" class="lmt-btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Create Organization
    </a>
</div>

{{-- Status filter tabs --}}
<div class="flex items-center gap-2 mb-6 flex-wrap">
    @php
    $statuses = ['all'=>'All', 'active'=>'Active', 'trial'=>'Trial', 'past_due'=>'Past Due', 'suspended'=>'Suspended', 'cancelled'=>'Cancelled'];
    $currentStatus = request('status', 'all');
    @endphp
    @foreach($statuses as $val => $label)
    <a href="{{ route('superadmin.organizations.index', array_merge(request()->except('status','page'), $val !== 'all' ? ['status'=>$val] : [])) }}"
       class="px-4 py-2 rounded-xl text-sm font-medium transition-all
              {{ ($currentStatus === $val || ($val === 'all' && !request('status'))) ? 'bg-brand-500 text-white shadow-pop' : 'bg-white text-ink-soft border border-gray-200 hover:border-brand-300' }}">
        {{ $label }}
        @if($val !== 'all' && isset($statusCounts[$val]))
        <span class="ml-1 {{ ($currentStatus === $val) ? 'bg-white/30 text-white' : 'bg-gray-100 text-ink-soft' }} text-xs px-1.5 py-0.5 rounded-full">
            {{ $statusCounts[$val] }}
        </span>
        @endif
    </a>
    @endforeach
</div>

{{-- Search + table --}}
<div class="lmt-card p-0 overflow-hidden">
    {{-- Search bar --}}
    <div class="p-4 border-b border-gray-100">
        <form action="{{ route('superadmin.organizations.index') }}" method="GET" class="flex gap-3 mb-0 ">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="relative flex-1 max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="lmt-input pl-10 py-2 text-sm"
                       placeholder="Search by company, email, slug…"/>
            </div>
            <button type="submit" class="lmt-btn-primary lmt-btn-sm">Search</button>
            @if(request('search'))
            <a href="{{ route('superadmin.organizations.index', request()->except('search','page')) }}"
               class="lmt-btn-ghost lmt-btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Slug</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Employees</th>
                    <th>Joined</th>
                    <th>Trial Ends</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                @php
                $statusMap = [
                    'active'    => 'lmt-badge-green',
                    'trial'     => 'lmt-badge-brand',
                    'past_due'  => 'lmt-badge-amber',
                    'suspended' => 'lmt-badge-red',
                    'cancelled' => 'lmt-badge-gray',
                    'pending'   => 'lmt-badge-gray',
                ];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                                {{ substr($tenant->company_name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-ink text-sm">{{ $tenant->company_name }}</p>
                                <p class="text-xs text-ink-soft">{{ $tenant->contact_email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded-lg font-mono">{{ $tenant->slug }}</code>
                    </td>
                    <td>
                        <span class="text-sm text-ink">
                            {{ $tenant->activeSubscription?->plan?->name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <span class="{{ $statusMap[$tenant->status] ?? 'lmt-badge-gray' }} text-xs">
                            {{ ucfirst(str_replace('_',' ',$tenant->status)) }}
                        </span>
                    </td>
                    <td class="text-sm text-ink-soft">{{ number_format($tenant->employees_count) }}</td>
                    <td class="text-sm text-ink-soft">{{ $tenant->created_at->format('M j, Y') }}</td>
                    <td class="text-sm text-ink-soft">
                        @if($tenant->trial_ends_at)
                            <span class="{{ $tenant->trial_ends_at->isPast() ? 'text-red-500' : ($tenant->trial_ends_at->diffInDays() <= 3 ? 'text-amber-600' : '') }}">
                                {{ $tenant->trial_ends_at->format('M j, Y') }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('superadmin.organizations.show', $tenant) }}"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors"
                               title="View">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </a>
                            @if($tenant->database_provisioned)
                            <a href="{{ route('superadmin.organizations.impersonate', $tenant) }}"
                               class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-500 hover:text-white flex items-center justify-center transition-colors"
                               title="Impersonate">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            </a>
                            @endif
                            @if($tenant->status === 'suspended')
                            <form action="{{ route('superadmin.organizations.unsuspend', $tenant) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition-colors"
                                        title="Unsuspend">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            @elseif($tenant->status !== 'cancelled')
                            <button onclick="openSuspendModal('{{ $tenant->id }}','{{ addslashes($tenant->company_name) }}')"
                                    class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white flex items-center justify-center transition-colors"
                                    title="Suspend">
                                <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-12 text-ink-soft">
                        <i data-lucide="building-2" class="w-8 h-8 mx-auto mb-3 text-gray-300"></i>
                        <p class="font-medium">No organizations found</p>
                        <p class="text-sm mt-1">Try adjusting your search or filters</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($tenants->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $tenants->links() }}
    </div>
    @endif
</div>

{{-- Suspend Modal --}}
<div id="suspend-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="pause-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-ink" style="font-family:'Nunito',sans-serif">Suspend Organization</h3>
                <p class="text-sm text-ink-soft" id="suspend-company-name"></p>
            </div>
        </div>
        <form id="suspend-form" method="POST">
            @csrf
            <div class="mb-4">
                <label class="lmt-label">Reason for suspension <span class="text-red-500">*</span></label>
                <textarea name="reason" required class="lmt-textarea" placeholder="Explain why this organization is being suspended…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-danger flex-1">Suspend Organization</button>
                <button type="button" onclick="closeSuspendModal()" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});

function openSuspendModal(tenantId, companyName) {
    document.getElementById('suspend-company-name').textContent = companyName;
    document.getElementById('suspend-form').action = `/superadmin/tenants/${tenantId}/suspend`;
    const modal = document.getElementById('suspend-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSuspendModal() {
    const modal = document.getElementById('suspend-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush