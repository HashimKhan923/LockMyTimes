@extends('layouts.employee')

@section('title', 'My Assets')
@section('page-title', 'My Assets')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif">
            My Assets
        </h1>
        <p class="text-sm text-gray-800 mt-1">Equipment currently assigned to you, plus your assignment history.</p>
    </div>

    {{-- Currently assigned --}}
    <div class="mb-8">
        <h2 class="text-sm font-black text-gray-900 uppercase tracking-wider mb-3">Currently Assigned ({{ $current->count() }})</h2>

        @if($current->isEmpty())
            <div class="lmt-card text-center py-12">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                    <i data-lucide="package" class="w-7 h-7 text-gray-800"></i>
                </div>
                <p class="text-sm text-gray-800">No assets currently assigned to you.</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($current as $assignment)
                @php $asset = $assignment->asset; @endphp
                <div class="lmt-card">
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ $asset?->category?->icon ?: 'package' }}" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-sm truncate">{{ $asset?->name ?? 'Unknown asset' }}</p>
                            <p class="text-xs text-gray-800 mt-0.5">{{ $asset?->category?->name }}</p>
                            @if($asset?->brand || $asset?->model)
                            <p class="text-xs text-gray-800">{{ trim(($asset->brand ?? '').' '.($asset->model ?? '')) }}</p>
                            @endif
                            @if($asset?->serial_number)
                            <p class="text-xs text-gray-800 font-mono mt-1">SN: {{ $asset->serial_number }}</p>
                            @endif
                            <p class="text-xs text-gray-800 mt-2">
                                Assigned {{ $assignment->assigned_at->format('M j, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- History --}}
    <div>
        <h2 class="text-sm font-black text-gray-900 uppercase tracking-wider mb-3">Assignment History</h2>

        <div class="lmt-card p-0 overflow-hidden">
            @if($history->isEmpty())
                <div class="text-center py-12">
                    <p class="text-sm text-gray-800">No returned assets on record.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="lmt-table">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Assigned</th>
                                <th>Returned</th>
                                <th>Condition at Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $assignment)
                            @php $asset = $assignment->asset; @endphp
                            <tr>
                                <td>
                                    <p class="font-semibold text-gray-900 text-sm">{{ $asset?->name ?? 'Unknown asset' }}</p>
                                    <p class="text-xs text-gray-800">{{ $asset?->category?->name }}</p>
                                </td>
                                <td class="text-sm text-gray-800">{{ $assignment->assigned_at->format('M j, Y') }}</td>
                                <td class="text-sm text-gray-800">{{ $assignment->returned_at->format('M j, Y') }}</td>
                                <td class="text-sm text-gray-800 capitalize">{{ $assignment->condition_at_return ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-100">{{ $history->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
