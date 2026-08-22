@extends('layouts.employee')

@section('title', 'Poll')
@section('page-title', 'Poll')

@section('content')
<div class="max-w-3xl mx-auto" x-data="{
        choices: @json($myChoices),
        type: '{{ $poll->type }}',
        submitting: false,
        toggle(idx) {
            if (this.type === 'single_choice' || this.type === 'rating') {
                this.choices = [idx];
            } else {
                const i = this.choices.indexOf(idx);
                if (i === -1) this.choices.push(idx);
                else this.choices.splice(i, 1);
            }
        },
        isChecked(idx) {
            return this.choices.includes(idx);
        }
     }">

    @php
        [$statusLbl, $statusBg, $statusFg, $statusIcon] = match(true) {
            $isActive && $hasVoted   => ['You\'ve voted',  '#ecfdf5', '#10b981', 'check-circle'],
            $isActive                => ['Open for voting','#fffbeb', '#d97706', 'circle'],
            $poll->status === 'closed' => ['Closed',         '#f3f4f6', '#6b7280', 'lock'],
            default                  => [ucfirst($poll->status), '#f3f4f6', '#6b7280', 'circle'],
        };
    @endphp

    {{-- Back nav --}}
    <a href="{{ route('employee.announcements.index', ['tenant' => $tenantSlug, 'filter' => 'polls']) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 dark:hover:text-slate-200 transition-colors mb-5">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        <span>All polls</span>
    </a>

    @if(session('success'))
        <div class="lmt-alert lmt-alert-success mb-5">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="lmt-alert lmt-alert-error mb-5">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         POLL CARD
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="lmt-card" data-lmt-anim="fade-up">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-3 flex-wrap mb-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                      style="background:{{ $statusBg }};color:{{ $statusFg }};">
                    <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
                    {{ $statusLbl }}
                </span>
                @php
                    $typeLabel = match($poll->type) {
                        'single_choice'   => 'Pick one',
                        'multiple_choice' => 'Pick multiple',
                        'rating'          => 'Rating',
                        default           => ucfirst(str_replace('_',' ', $poll->type)),
                    };
                @endphp
                <span class="text-[10px] font-bold text-gray-800 uppercase tracking-wider">{{ $typeLabel }}</span>
                @if($poll->is_anonymous)
                    <span class="inline-flex items-center gap-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                        <i data-lucide="eye-off" class="w-2.5 h-2.5"></i>
                        Anonymous
                    </span>
                @endif
            </div>
            @if($poll->ends_at)
                <span class="text-xs text-gray-800 inline-flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    @if($isActive)
                        Ends {{ $poll->ends_at->diffForHumans() }}
                    @else
                        Ended {{ $poll->ends_at->diffForHumans() }}
                    @endif
                </span>
            @endif
        </div>

        {{-- Question --}}
        <h1 class="text-xl lg:text-2xl font-black text-gray-900 dark:text-slate-100" style="font-family:'Plus Jakarta Sans',sans-serif">
            {{ $poll->question }}
        </h1>

        @if($poll->description)
            <p class="text-sm text-gray-600 dark:text-slate-400 mt-2 whitespace-pre-line leading-relaxed">{{ $poll->description }}</p>
        @endif

        <div class="flex items-center gap-3 mt-3 text-[11px] text-gray-800">
            @if($poll->creator)
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="user" class="w-3 h-3"></i>
                    Posted by {{ $poll->creator->name }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3 h-3"></i>
                {{ $poll->created_at->diffForHumans() }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="users" class="w-3 h-3"></i>
                {{ $totalVotes }} {{ $totalVotes === 1 ? 'vote' : 'votes' }}
            </span>
        </div>

        {{-- ═════════════════════════════════════════════════════
             VOTING vs RESULTS
        ═════════════════════════════════════════════════════ --}}
        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-700">

            @if($showResults)
                {{-- Results view --}}
                <p class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">
                    @if($hasVoted) Results &middot; <span style="color:var(--brand-600);">Your choices marked</span>
                    @else Final results
                    @endif
                </p>
                <div class="space-y-3">
                    @foreach($results as $r)
                        @php
                            $isMine = in_array($r['index'], $myChoices);
                            $isWinner = $totalVotes > 0 && $r['votes'] === collect($results)->max('votes');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5 gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    @if($isMine)
                                        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0" style="color:var(--brand-600);"></i>
                                    @endif
                                    <span class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate">{{ $r['option'] }}</span>
                                    @if($isWinner && $totalVotes > 0)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 uppercase flex-shrink-0">Leading</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-800">{{ $r['votes'] }}</span>
                                    <span class="font-mono font-black text-sm" style="color:var(--brand-600);">{{ $r['percent'] }}%</span>
                                </div>
                            </div>
                            <div class="h-3 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width:{{ $r['percent'] }}%; background:{{ $isMine ? 'linear-gradient(90deg,var(--brand-500),var(--brand-600))' : '#9ca3af' }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($isActive && $hasVoted)
                    {{-- Allow re-vote --}}
                    <details class="mt-5">
                        <summary class="text-xs font-bold cursor-pointer hover:underline inline-flex items-center gap-1" style="color:var(--brand-500);">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            Change my vote
                        </summary>
                        <div class="mt-3">
                            @include('employee.announcements._vote-form', ['poll' => $poll, 'tenantSlug' => $tenantSlug])
                        </div>
                    </details>
                @endif
            @else
                {{-- Voting form (active poll, not voted yet) --}}
                @include('employee.announcements._vote-form', ['poll' => $poll, 'tenantSlug' => $tenantSlug])
            @endif
        </div>
    </div>

    @if(! $isActive && ! $hasVoted)
        <div class="lmt-alert lmt-alert-info mt-5">
            <i data-lucide="info" class="w-5 h-5 shrink-0"></i>
            <p>This poll has closed. You can view results but voting is no longer open.</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>
@endpush

@endsection