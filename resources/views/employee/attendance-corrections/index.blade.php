@extends('layouts.employee')

@section('title', 'Attendance Corrections')
@section('page-title', 'Attendance Corrections')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif">
                Attendance Corrections
            </h1>
            <p class="text-sm text-gray-800 mt-1">Requests to fix a missed clock in/out.</p>
        </div>
        <a href="{{ route('employee.attendance-corrections.create', $tenant) }}" class="lmt-btn-primary">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            New Request
        </a>
    </div>

    <div class="lmt-card p-0 overflow-hidden">
        @if($requests->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                    <i data-lucide="clock" class="w-7 h-7 text-gray-800"></i>
                </div>
                <p class="text-sm text-gray-800">No correction requests yet.</p>
                <a href="{{ route('employee.attendance-corrections.create', $tenant) }}" class="lmt-btn-primary lmt-btn-sm mt-4 inline-flex">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> New Request
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Date</th>
                            <th>Proposed Times</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $r)
                        @php
                        [$statusLbl, $statusCls] = match($r->status) {
                            'approved'  => ['Approved',  'lmt-badge-green'],
                            'pending'   => ['Pending',   'lmt-badge-amber'],
                            'rejected'  => ['Rejected',  'lmt-badge-red'],
                            'cancelled' => ['Cancelled', 'lmt-badge-gray'],
                            default     => [ucfirst($r->status), 'lmt-badge-gray'],
                        };
                        @endphp
                        <tr>
                            <td><span class="font-mono text-xs font-bold text-gray-800">{{ $r->request_number }}</span></td>
                            <td class="text-sm text-gray-900">{{ $r->work_date->format('M j, Y') }}</td>
                            <td class="text-sm text-gray-800">
                                @if($r->proposed_clock_in)
                                In: {{ \Carbon\Carbon::parse($r->proposed_clock_in)->format('h:i A') }}<br/>
                                @endif
                                @if($r->proposed_clock_out)
                                Out: {{ \Carbon\Carbon::parse($r->proposed_clock_out)->format('h:i A') }}
                                @endif
                            </td>
                            <td>
                                <span class="{{ $statusCls }}">{{ $statusLbl }}</span>
                                @if($r->status === 'rejected' && $r->rejection_reason)
                                <p class="text-xs text-red-600 mt-0.5 max-w-40 truncate" title="{{ $r->rejection_reason }}">{{ $r->rejection_reason }}</p>
                                @endif
                            </td>
                            <td class="text-xs text-gray-800">{{ $r->created_at->format('M j, Y') }}</td>
                            <td class="text-right">
                                @if($r->status === 'pending')
                                <form action="{{ route('employee.attendance-corrections.cancel', [$tenant, $r->id]) }}" method="POST"
                                      onsubmit="return confirm('Cancel this correction request?')">
                                    @csrf
                                    <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors px-2 py-1">
                                        Cancel
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
