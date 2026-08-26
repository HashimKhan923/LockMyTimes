@php
    [$statusLbl, $statusCls, $statusIcon] = match($lr->status) {
        'approved'  => ['Approved',  'lmt-badge-green', 'check-circle'],
        'pending'   => ['Pending',   'lmt-badge-amber', 'clock'],
        'rejected'  => ['Rejected',  'lmt-badge-red',   'x-circle'],
        'cancelled' => ['Cancelled', 'lmt-badge-gray',  'ban'],
        default     => [ucfirst($lr->status), 'lmt-badge-gray', 'help-circle'],
    };
    $start = \Carbon\Carbon::parse($lr->start_date);
    $end   = \Carbon\Carbon::parse($lr->end_date);
    $color = $lr->leaveType?->color ?? '#6C7DF7';
@endphp

<div class="p-6 pt-12">

    {{-- Header --}}
    <div class="flex items-start gap-3 mb-6">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
             style="background:{{ $color }}1a; color:{{ $color }};">
            <i data-lucide="calendar-off" class="w-6 h-6"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-mono text-xs font-bold text-gray-800">{{ $lr->request_number }}</p>
            <h2 class="text-xl font-black text-gray-900 dark:text-slate-100 mt-0.5" style="font-family:'Plus Jakarta Sans',sans-serif">
                {{ $lr->leaveType?->name ?? 'Leave Request' }}
            </h2>
            <div class="flex items-center gap-2 mt-2">
                <span class="{{ $statusCls }}">
                    <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                    {{ $statusLbl }}
                </span>
            </div>
        </div>
    </div>

    {{-- Date range --}}
    <div class="p-4 rounded-2xl mb-4" style="background:var(--brand-50);">
        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:var(--brand-700);">Dates</p>
        <div class="flex items-center gap-3">
            <div class="text-center">
                <p class="text-[10px] font-bold text-gray-800 uppercase tracking-wide">{{ $start->format('M') }}</p>
                <p class="text-2xl font-black font-mono mt-0.5" style="color:var(--brand-700);">{{ $start->format('j') }}</p>
                <p class="text-[10px] text-gray-800 mt-0.5">{{ $start->format('D, Y') }}</p>
            </div>
            <i data-lucide="arrow-right" class="w-5 h-5 mx-3" style="color:var(--brand-500);"></i>
            <div class="text-center">
                <p class="text-[10px] font-bold text-gray-800 uppercase tracking-wide">{{ $end->format('M') }}</p>
                <p class="text-2xl font-black font-mono mt-0.5" style="color:var(--brand-700);">{{ $end->format('j') }}</p>
                <p class="text-[10px] text-gray-800 mt-0.5">{{ $end->format('D, Y') }}</p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-[10px] font-bold text-gray-800 uppercase tracking-wide">Total</p>
                <p class="text-2xl font-black font-mono mt-0.5" style="color:var(--brand-700);">
                    {{ rtrim(rtrim(number_format($lr->total_days, 1), '0'), '.') }}
                </p>
                <p class="text-[10px] text-gray-800 mt-0.5">day{{ $lr->total_days == 1 ? '' : 's' }}</p>
            </div>
        </div>
        @if($lr->day_part && $lr->day_part !== 'full')
            <p class="text-xs text-gray-800 font-semibold mt-3 capitalize">
                <i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i>
                {{ str_replace('_', ' ', $lr->day_part) }} only
            </p>
        @endif
    </div>

    {{-- Reason --}}
    <div class="mb-4">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">Reason</p>
        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-slate-800">
            <p class="text-sm text-gray-800 dark:text-slate-200 whitespace-pre-line leading-relaxed">{{ $lr->reason }}</p>
        </div>
    </div>

    {{-- Contact during leave --}}
    @if($lr->contact_during_leave)
        <div class="mb-4 p-4 rounded-2xl border border-gray-100 dark:border-slate-700">
            <div class="flex items-center gap-2 mb-1">
                <i data-lucide="phone" class="w-4 h-4" style="color:var(--brand-500);"></i>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-800">Contact during leave</p>
            </div>
            <p class="text-sm text-gray-800 dark:text-slate-200">{{ $lr->contact_during_leave }}</p>
        </div>
    @endif

    {{-- Attachments --}}
    @if(is_array($lr->attachments) && count($lr->attachments) > 0)
        <div class="mb-4">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">Attachments</p>
            <div class="space-y-2">
                @foreach($lr->attachments as $att)
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-slate-700">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="paperclip" class="w-4 h-4 text-gray-800"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">{{ $att['name'] ?? 'attachment' }}</p>
                            <p class="text-[10px] text-gray-800">{{ isset($att['size']) ? round($att['size']/1024).' KB' : '' }}</p>
                        </div>
                        @if(isset($att['path']))
                            <a href="{{ asset('storage/'.$att['path']) }}" target="_blank"
                               class="text-xs font-bold transition-colors hover:underline" style="color:var(--brand-500);">
                                <i data-lucide="download" class="w-3.5 h-3.5 inline -mt-0.5"></i>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Approval timeline --}}
    <div class="mt-6">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-800 mb-3">Timeline</p>
        <div class="space-y-3">

            {{-- Submitted --}}
            <div class="flex gap-3">
                <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center flex-shrink-0"
                     style="background:var(--brand-100); color:var(--brand-700);">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                </div>
                <div class="flex-1 pb-1">
                    <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Request submitted</p>
                    <p class="text-xs text-gray-800">{{ \Carbon\Carbon::parse($lr->created_at)->format('M j, Y · h:i A') }}</p>
                </div>
            </div>

            {{-- Approved --}}
            @if($lr->status === 'approved')
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Approved</p>
                        @if($lr->approver)
                            <p class="text-xs text-gray-800">by {{ $lr->approver->name }}</p>
                        @endif
                        @if($lr->approved_at)
                            <p class="text-xs text-gray-800">{{ \Carbon\Carbon::parse($lr->approved_at)->format('M j, Y · h:i A') }}</p>
                        @endif
                        @if($lr->approver_comments)
                            <div class="mt-2 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-xs text-emerald-800 dark:text-emerald-300">
                                <p class="font-bold mb-0.5">Note from approver:</p>
                                <p class="leading-relaxed">{{ $lr->approver_comments }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($lr->status === 'rejected')
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Rejected</p>
                        @if($lr->approver)
                            <p class="text-xs text-gray-800">by {{ $lr->approver->name }}</p>
                        @endif
                        @if($lr->approved_at)
                            <p class="text-xs text-gray-800">{{ \Carbon\Carbon::parse($lr->approved_at)->format('M j, Y · h:i A') }}</p>
                        @endif
                        @if($lr->rejection_reason)
                            <div class="mt-2 p-3 rounded-xl bg-red-50 dark:bg-red-500/10 text-xs text-red-800 dark:text-red-300">
                                <p class="font-bold mb-0.5">Reason:</p>
                                <p class="leading-relaxed">{{ $lr->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($lr->status === 'cancelled')
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-800 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="ban" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Cancelled</p>
                        <p class="text-xs text-gray-800">{{ \Carbon\Carbon::parse($lr->updated_at)->format('M j, Y · h:i A') }}</p>
                    </div>
                </div>
            @elseif($lr->status === 'pending')
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="hourglass" class="w-3.5 h-3.5"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Awaiting approval</p>
                        <p class="text-xs text-gray-800">Your manager will be notified.</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Cancel action --}}
    @if(in_array($lr->status, ['pending']) || ($lr->status === 'approved' && $start->isFuture()))
        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-slate-700">
            <form action="{{ route('employee.leaves.cancel', [$tenantSlug, $lr->id]) }}" method="POST"
                  onsubmit="return confirm('Cancel this leave request? Your balance will be restored.');">
                @csrf
                <button type="submit" class="lmt-btn-danger w-full">
                    <i data-lucide="ban" class="w-4 h-4"></i>
                    Cancel This Request
                </button>
            </form>
        </div>
    @endif
</div>