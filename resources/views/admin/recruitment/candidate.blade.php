@extends('layouts.admin')
@section('title', $candidate->full_name)
@section('page-title', 'Candidate Profile')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.recruitment.show', [$tenant, $candidate->job_posting_id]) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-800 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Pipeline
    </a>
    <div class="flex items-center gap-2">
        <button onclick="openModal('move-stage-modal')" class="lmt-btn-secondary lmt-btn-sm">
            <i data-lucide="git-branch" class="w-4 h-4"></i>
            Move Stage
        </button>
        <button onclick="openModal('schedule-interview-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="calendar-plus" class="w-4 h-4"></i>
            Schedule Interview
        </button>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left — profile --}}
    <div class="space-y-5">

        {{-- Profile card --}}
        <div class="lmt-card">
            <div class="text-center mb-5">
                <div class="w-20 h-20 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-2xl font-black mx-auto mb-3">
                    {{ $candidate->initials }}
                </div>
                <h2 class="text-xl font-black text-gray-900">{{ $candidate->full_name }}</h2>
                <p class="text-sm text-gray-800 mt-1">{{ $candidate->email }}</p>
                @if($candidate->phone)
                <p class="text-sm text-gray-800">{{ $candidate->phone }}</p>
                @endif
            </div>

            {{-- Stage badge --}}
            <div class="text-center mb-4">
                <span class="px-4 py-2 rounded-xl text-sm font-bold capitalize {{ $candidate->stage_color }}">
                    {{ ucfirst($candidate->stage) }}
                </span>
            </div>

            {{-- Rating --}}
            <div class="flex items-center justify-center gap-2 mb-4">
                @for($i=1;$i<=5;$i++)
                <button onclick="setRating({{ $i }})"
                        class="w-7 h-7 rounded-full flex items-center justify-center transition-all
                               {{ $i <= ($candidate->rating ?? 0) ? 'bg-amber-400 text-white' : 'bg-gray-100 text-gray-800' }}">
                    {{ $i }}
                </button>
                @endfor
            </div>

            <div class="space-y-2 text-xs">
                @foreach([
                    ['Applying For', $candidate->jobPosting->title ?? '—'],
                    ['Source',       $candidate->source ?? '—'],
                    ['Expected',     $candidate->expected_salary ? '$'.number_format($candidate->expected_salary,0) : '—'],
                    ['Available',    $candidate->available_from?->format('M j, Y') ?? '—'],
                    ['Applied',      $candidate->created_at->format('M j, Y')],
                ] as [$k,$v])
                <div class="flex justify-between py-1.5 border-b border-gray-50 last:border-none">
                    <span class="text-gray-800 font-medium">{{ $k }}</span>
                    <span class="text-gray-800 font-semibold text-right max-w-36 truncate">{{ $v }}</span>
                </div>
                @endforeach
            </div>

            {{-- Links --}}
            <div class="flex gap-2 mt-4">
                @if($candidate->linkedin_url)
                <a href="{{ $candidate->linkedin_url }}" target="_blank"
                   class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                    <i data-lucide="linkedin" class="w-4 h-4"></i>
                    LinkedIn
                </a>
                @endif
                @if($candidate->resume_path)
                <a href="{{ asset('storage/'.$candidate->resume_path) }}" target="_blank"
                   class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    Resume
                </a>
                @endif
                @if($candidate->portfolio_url)
                <a href="{{ $candidate->portfolio_url }}" target="_blank"
                   class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                    Portfolio
                </a>
                @endif
            </div>

            {{-- Notes form --}}
            <form action="{{ route('admin.recruitment.candidates.update', [$tenant, $candidate->id]) }}"
                  method="POST" class="mt-4 pt-4 border-t border-gray-100">
                @csrf @method('PATCH')
                <label class="lmt-label">Recruiter Notes</label>
                <textarea name="notes" class="lmt-textarea" rows="3"
                          placeholder="Internal notes…">{{ $candidate->notes }}</textarea>
                <input type="hidden" name="rating" id="rating-input" value="{{ $candidate->rating }}"/>
                <button type="submit" class="lmt-btn-secondary lmt-btn-sm w-full mt-2">Save Notes</button>
            </form>
        </div>

        {{-- Stage history --}}
        @if($candidate->stageHistory->isNotEmpty())
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-3">Stage History</h3>
            <div class="space-y-2">
                @foreach($candidate->stageHistory->take(8) as $hist)
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-1.5 h-1.5 rounded-full bg-brand-400 flex-shrink-0"></div>
                    <span class="text-gray-800 capitalize">{{ $hist->from_stage ?? 'start' }}</span>
                    <i data-lucide="arrow-right" class="w-3 h-3 text-gray-800 flex-shrink-0"></i>
                    <span class="font-bold text-gray-800 capitalize">{{ $hist->to_stage }}</span>
                    <span class="text-gray-800 ml-auto">{{ $hist->created_at->diffForHumans() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right — interviews + cover letter --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Cover letter --}}
        @if($candidate->cover_letter_text)
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-3">Cover Letter</h3>
            <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-line">{{ $candidate->cover_letter_text }}</p>
        </div>
        @endif

        {{-- Interviews --}}
        <div class="lmt-card p-0 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-900">Interviews</h3>
                <button onclick="openModal('schedule-interview-modal')"
                        class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                </button>
            </div>

            @forelse($candidate->interviews->sortByDesc('scheduled_at') as $interview)
            @php
            $intStatusColors = ['scheduled'=>'lmt-badge-brand','completed'=>'lmt-badge-green','cancelled'=>'lmt-badge-red','no_show'=>'lmt-badge-red','rescheduled'=>'lmt-badge-amber'];
            $recColors = ['strong_yes'=>'text-emerald-600','yes'=>'text-emerald-500','maybe'=>'text-amber-500','no'=>'text-red-500','strong_no'=>'text-red-600'];
            @endphp
            <div class="p-4 border-b border-gray-50 last:border-none">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-bold text-gray-900 text-sm">{{ $interview->title }}</p>
                            <span class="{{ $intStatusColors[$interview->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                                {{ str_replace('_',' ', $interview->status) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-gray-800">
                            <span class="flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                {{ $interview->scheduled_at->format('M j, Y h:i A') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                {{ $interview->duration_minutes }}m
                            </span>
                            <span class="flex items-center gap-1 capitalize">
                                <i data-lucide="video" class="w-3 h-3"></i>
                                {{ str_replace('_',' ', $interview->type) }}
                            </span>
                        </div>
                        @if($interview->meeting_url)
                        <a href="{{ $interview->meeting_url }}" target="_blank"
                           class="text-xs text-brand-600 hover:underline mt-1 block">
                             Join Meeting
                        </a>
                        @endif
                        @if($interview->feedback)
                        <p class="text-xs text-gray-800 mt-2 italic">"{{ $interview->feedback }}"</p>
                        @endif
                        @if($interview->recommendation)
                        <span class="text-xs font-bold capitalize {{ $recColors[$interview->recommendation] ?? 'text-gray-800' }} mt-1 block">
                            Recommendation: {{ str_replace('_',' ', $interview->recommendation) }}
                        </span>
                        @endif
                    </div>
                    @if($interview->status === 'scheduled')
                    <button onclick="openFeedbackModal({{ $interview->id }})"
                            class="lmt-btn-secondary lmt-btn-sm flex-shrink-0">
                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                        Feedback
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-10">
                <i data-lucide="calendar" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
                <p class="text-sm text-gray-800">No interviews scheduled</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Move Stage Modal --}}
<div id="move-stage-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Move to Stage</h3>
            <button onclick="closeModal('move-stage-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.recruitment.candidates.stage', [$tenant, $candidate->id]) }}"
              method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-2">
                @foreach(['applied'=>['#94A3B8','Applied'],'screening'=>['#3B82F6','Screening'],'interview'=>['#6C7DF7','Interview'],'assessment'=>['#8B5CF6','Assessment'],'offer'=>['#F59E0B','Offer'],'hired'=>['#10B981','Hired '],'rejected'=>['#EF4444','Rejected'],'withdrawn'=>['#94A3B8','Withdrawn']] as $val=>[$color,$label])
                <label class="cursor-pointer">
                    <input type="radio" name="stage" value="{{ $val }}" class="sr-only peer"
                           {{ $candidate->stage === $val ? 'checked' : '' }}/>
                    <div class="p-3 rounded-xl border-2 border-gray-200 text-center text-sm font-semibold text-gray-800
                                peer-checked:border-brand-500 peer-checked:text-brand-700 peer-checked:bg-brand-50
                                hover:border-gray-300 transition-all">
                        <div class="w-2 h-2 rounded-full mx-auto mb-1" style="background:{{ $color }}"></div>
                        {{ $label }}
                    </div>
                </label>
                @endforeach
            </div>
            <div>
                <label class="lmt-label">Reason / Note</label>
                <textarea name="reason" class="lmt-textarea" rows="2"
                          placeholder="Optional reason for stage change…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Move Stage</button>
                <button type="button" onclick="closeModal('move-stage-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Schedule Interview Modal --}}
<div id="schedule-interview-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Schedule Interview</h3>
            <button onclick="closeModal('schedule-interview-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.recruitment.candidates.interview', [$tenant, $candidate->id]) }}"
              method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Interview Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input"
                       placeholder="e.g. Technical Interview Round 1"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['video'=>'Video Call','phone'=>'Phone','in_person'=>'In Person','technical'=>'Technical','panel'=>'Panel','culture'=>'Culture Fit'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Duration (mins) <span class="text-red-500">*</span></label>
                    <select name="duration_minutes" required class="lmt-select">
                        @foreach([30=>'30 min',45=>'45 min',60=>'1 hour',90=>'1.5 hours',120=>'2 hours'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v===60?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Date & Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="scheduled_at" required class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Meeting URL / Location</label>
                    <input type="text" name="meeting_url" class="lmt-input"
                           placeholder="Zoom link or office address…"/>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Schedule</button>
                <button type="button" onclick="closeModal('schedule-interview-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Interview Feedback Modal --}}
<div id="feedback-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Interview Feedback</h3>
            <button onclick="closeModal('feedback-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="feedback-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Outcome <span class="text-red-500">*</span></label>
                <select name="status" required class="lmt-select">
                    @foreach(['completed'=>'Completed','cancelled'=>'Cancelled','no_show'=>'No Show'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Rating (1–5)</label>
                <select name="rating" class="lmt-select">
                    <option value="">— No Rating —</option>
                    @foreach([1=>' 1',2=>' 2',3=>' 3',4=>' 4',5=>' 5'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Recommendation</label>
                <select name="recommendation" class="lmt-select">
                    <option value="">— None —</option>
                    @foreach(['strong_yes'=>'Strong Yes ','yes'=>'Yes','maybe'=>'Maybe','no'=>'No','strong_no'=>'Strong No '] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Feedback</label>
                <textarea name="feedback" class="lmt-textarea" rows="3"
                          placeholder="Share your thoughts on the candidate…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save Feedback</button>
                <button type="button" onclick="closeModal('feedback-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }

function setRating(val) {
    document.getElementById('rating-input').value = val;
    document.querySelectorAll('[onclick^="setRating"]').forEach((btn, i) => {
        const isActive = i < val;
        btn.className = btn.className.replace(/bg-\w+-\d+|text-\w+-\d+/g, '');
        btn.classList.add(isActive ? 'bg-amber-400' : 'bg-gray-100');
        btn.classList.add(isActive ? 'text-white' : 'text-gray-800');
    });
}

function openFeedbackModal(interviewId) {
    document.getElementById('feedback-form').action =
        `/t/{{ $tenant }}/admin/recruitment/interviews/${interviewId}/feedback`;
    openModal('feedback-modal');
}

['move-stage-modal','schedule-interview-modal','feedback-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush