@extends('layouts.admin')
@section('title', 'Device Change Requests')
@section('page-title', 'Device Change Requests')

@section('content')

<div class="lmt-card mb-6">
    <p class="text-sm text-gray-800">
        Each employee's mobile app is locked to the first device they log in from. When they get a new
        device, they can request a change here — approving clears the lock so their next login binds
        the new device automatically.
    </p>
</div>

<div class="lmt-card p-0 overflow-hidden" x-data="{ rejectOpen: false, rejectAction: '' }">

    <div class="overflow-x-auto">
        <table class="lmt-table w-full">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Requested Device</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Reviewed By</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td>
                        <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $req->user->name }}</p>
                        <p class="text-xs text-gray-800">{{ $req->user->email }}</p>
                    </td>
                    <td class="text-sm">
                        @if($req->requested_device_name || $req->requested_device_id)
                            {{ $req->requested_device_name ?: 'Unnamed device' }}
                            @if($req->requested_device_id)
                                <p class="text-xs text-gray-800 font-mono">{{ \Illuminate\Support\Str::limit($req->requested_device_id, 24) }}</p>
                            @endif
                        @else
                            <span class="text-gray-800">—</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-800">{{ $req->reason ?: '—' }}</td>
                    <td>
                        @php
                            $badge = match($req->status) {
                                'pending'  => 'lmt-badge-amber',
                                'approved' => 'lmt-badge-green',
                                'rejected' => 'lmt-badge-red',
                                default    => 'lmt-badge-gray',
                            };
                        @endphp
                        <span class="{{ $badge }} text-xs capitalize">{{ $req->status }}</span>
                    </td>
                    <td class="text-sm text-gray-800">{{ $req->created_at->diffForHumans() }}</td>
                    <td class="text-sm text-gray-800">{{ $req->reviewer?->name ?? '—' }}</td>
                    <td class="text-right">
                        @if($req->status === 'pending')
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.device-change-requests.approve', [$tenantSlug, $req->id]) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="lmt-btn-primary lmt-btn-sm">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                                </button>
                            </form>
                            <button type="button" class="lmt-btn-secondary lmt-btn-sm"
                                    @click="rejectAction = '{{ route('admin.device-change-requests.reject', [$tenantSlug, $req->id]) }}'; rejectOpen = true">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                            </button>
                        </div>
                        @else
                            <span class="text-xs text-gray-800">{{ $req->reviewed_at?->format('M j, Y g:i A') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-sm text-gray-800">No device change requests yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Reject reason modal --}}
    <div x-show="rejectOpen" x-cloak class="lmt-modal-backdrop" @keydown.escape.window="rejectOpen=false">
        <div class="lmt-modal" @click.outside="rejectOpen=false">
            <h3 class="text-lg font-black text-gray-900 dark:text-slate-100 mb-4">Reject Request</h3>
            <form method="POST" :action="rejectAction">
                @csrf @method('PATCH')
                <label class="lmt-label">Reason <span class="text-gray-800 font-normal">(optional)</span></label>
                <input type="text" name="rejection_reason" class="lmt-input" maxlength="255"
                       placeholder="e.g. Please verify identity with HR first"/>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="rejectOpen=false" class="lmt-btn-secondary">Cancel</button>
                    <button type="submit" class="lmt-btn-primary">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="mt-6">
    {{ $requests->links() }}
</div>

@endsection
