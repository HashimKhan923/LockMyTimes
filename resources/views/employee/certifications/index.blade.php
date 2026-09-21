@extends('layouts.employee')

@section('title', 'My Certifications')
@section('page-title', 'My Certifications')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-gray-900" style="font-family:'Plus Jakarta Sans',sans-serif">
                My Certifications
            </h1>
            <p class="text-sm text-gray-800 mt-1">Certifications your admin has recorded against your profile.</p>
        </div>
        @can('certifications.export')
        @if($certifications->isNotEmpty())
        <a href="{{ route('employee.certifications.export', $tenant) }}" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="download" class="w-3.5 h-3.5"></i> Export PDF
        </a>
        @endif
        @endcan
    </div>

    <div class="lmt-card p-0 overflow-hidden">
        @if($certifications->isEmpty())
            <div class="text-center py-14">
                <i data-lucide="award" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                <p class="text-sm text-gray-800">No certifications recorded yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="lmt-table">
                    <thead>
                        <tr>
                            <th>Certification</th>
                            <th>Issuer</th>
                            <th>Issued</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($certifications as $cert)
                        @php
                            // diffInDays() is direction-sensitive (negative when the argument is in
                            // the future relative to the caller) unless both sides are asked to give
                            // an absolute value — comparing that raw signed number to "<= 30" made any
                            // future expiry date (however far off) satisfy the check. Normalizing both
                            // sides to startOfDay and taking a signed diff the other way round (today
                            // -> expiry) gives a clean "days remaining" number: negative means expired.
                            $daysUntilExpiry = $cert->expiry_date
                                ? now()->startOfDay()->diffInDays($cert->expiry_date->copy()->startOfDay(), false)
                                : null;
                            $isExpired      = $daysUntilExpiry !== null && $daysUntilExpiry < 0;
                            $isExpiringSoon = $daysUntilExpiry !== null && $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
                        @endphp
                        <tr class="{{ $isExpired ? 'bg-red-50/20' : ($isExpiringSoon ? 'bg-amber-50/20' : '') }}">
                            <td>
                                <p class="font-semibold text-gray-900 text-sm">{{ $cert->name }}</p>
                                @if($cert->credential_id)
                                <p class="text-xs text-gray-800 font-mono">{{ $cert->credential_id }}</p>
                                @endif
                            </td>
                            <td class="text-sm text-gray-800">{{ $cert->issuer ?: '—' }}</td>
                            <td class="text-sm text-gray-800">{{ $cert->issue_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @if($cert->expiry_date)
                                <span class="text-sm {{ $isExpired ? 'text-red-600 font-bold' : ($isExpiringSoon ? 'text-amber-600 font-semibold' : 'text-gray-800') }}">
                                    {{ $cert->expiry_date->format('M j, Y') }}
                                </span>
                                @if($isExpiringSoon)
                                <span class="block text-xs text-amber-500">Expires in {{ $daysUntilExpiry }}d</span>
                                @endif
                                @else
                                <span class="text-gray-800 text-sm">No expiry</span>
                                @endif
                            </td>
                            <td>
                                @if($isExpired)
                                <span class="lmt-badge-red text-xs">Expired</span>
                                @elseif($isExpiringSoon)
                                <span class="lmt-badge-amber text-xs">Expiring Soon</span>
                                @else
                                <span class="lmt-badge-green text-xs">Active</span>
                                @endif
                                @if($cert->is_verified)
                                <span class="block lmt-badge-brand text-xs mt-0.5">Verified</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    @if($cert->certificate_file)
                                    <a href="{{ asset('storage/'.$cert->certificate_file) }}" target="_blank"
                                       title="View certificate file"
                                       class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <a href="{{ asset('storage/'.$cert->certificate_file) }}" download
                                       title="Download certificate file"
                                       class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif
                                    @if($cert->credential_url)
                                    <a href="{{ $cert->credential_url }}" target="_blank"
                                       title="View credential"
                                       class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
