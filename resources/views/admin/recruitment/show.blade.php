@extends('layouts.admin')
@section('title', $job->title . ' — Pipeline')
@section('page-title', 'Recruitment Pipeline')

@push('head')
<style>
.pipeline-wrapper {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1.5rem;
    min-height: calc(100vh - 230px);
    align-items: flex-start;
}
.pipeline-col {
    flex: 0 0 240px;
    min-width: 240px;
    display: flex;
    flex-direction: column;
}
.pipeline-col-header {
    padding: .6rem 1rem;
    border-radius: .875rem .875rem 0 0;
    border: 1.5px solid #e2e8f0;
    border-bottom: none;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pipeline-col-body {
    flex: 1;
    min-height: 100px;
    border: 1.5px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 .875rem .875rem;
    background: #f8fafc;
    padding: .5rem;
    overflow-y: auto;
    max-height: calc(100vh - 310px);
}
.pipeline-card {
    background: #fff;
    border-radius: .75rem;
    padding: .875rem;
    margin-bottom: .5rem;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition: all .15s ease;
}
.pipeline-card:hover {
    border-color: #6C7DF7;
    box-shadow: 0 4px 12px rgba(108,125,247,.12);
    transform: translateY(-1px);
}
.pipeline-wrapper::-webkit-scrollbar { height: 6px; }
.pipeline-wrapper::-webkit-scrollbar-track { background: transparent; }
.pipeline-wrapper::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
</style>
@endpush

@section('content')

{{-- Job header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.recruitment.index', $tenant) }}"
           class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="font-black text-gray-900 text-xl">{{ $job->title }}</h1>
                @php
                $sc = ['published'=>'bg-emerald-100 text-emerald-700','draft'=>'bg-gray-100 text-gray-600','paused'=>'bg-amber-100 text-amber-700','closed'=>'bg-red-100 text-red-700','filled'=>'bg-brand-100 text-brand-700'];
                @endphp
                <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize {{ $sc[$job->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $job->status }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">
                @if($job->department) {{ $job->department->name }} · @endif
                {{ str_replace('_',' ', $job->employment_type) }} ·
                {{ str_replace('_',' ', $job->work_mode) }}
                @if($job->hiringManager) · HM: {{ $job->hiringManager->full_name }} @endif
            </p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        {{-- Stage counts summary --}}
        @foreach(['applied'=>'#94A3B8','screening'=>'#3B82F6','interview'=>'#6C7DF7','hired'=>'#10B981','rejected'=>'#EF4444'] as $st=>$color)
        @if($stageCounts[$st] > 0)
        <div class="text-center">
            <p class="text-sm font-black" style="color:{{ $color }}">{{ $stageCounts[$st] }}</p>
            <p class="text-[10px] text-gray-400 capitalize">{{ $st }}</p>
        </div>
        @endif
        @endforeach
        <button onclick="openModal('add-candidate-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Add Candidate
        </button>
    </div>
</div>

{{-- Pipeline Kanban --}}
@php
$stageConfig = [
    'applied'    => ['label'=>'Applied',    'color'=>'#94A3B8'],
    'screening'  => ['label'=>'Screening',  'color'=>'#3B82F6'],
    'interview'  => ['label'=>'Interview',  'color'=>'#6C7DF7'],
    'assessment' => ['label'=>'Assessment', 'color'=>'#8B5CF6'],
    'offer'      => ['label'=>'Offer',      'color'=>'#F59E0B'],
    'hired'      => ['label'=>'Hired',      'color'=>'#10B981'],
    'rejected'   => ['label'=>'Rejected',   'color'=>'#EF4444'],
    'withdrawn'  => ['label'=>'Withdrawn',  'color'=>'#94A3B8'],
];
@endphp

<div class="pipeline-wrapper">
    @foreach($stages as $stage)
    @php
    $cfg      = $stageConfig[$stage] ?? ['label'=>ucfirst($stage), 'color'=>'#94A3B8'];
    $stageCands = $candidates->get($stage, collect());
    @endphp
    <div class="pipeline-col">
        {{-- Column header --}}
        <div class="pipeline-col-header">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full" style="background:{{ $cfg['color'] }}"></div>
                <span class="text-sm font-bold text-gray-800">{{ $cfg['label'] }}</span>
                <span class="w-5 h-5 rounded-full bg-gray-200 text-gray-600 text-xs font-bold flex items-center justify-center">
                    {{ $stageCands->count() }}
                </span>
            </div>
        </div>

        {{-- Column body --}}
        <div class="pipeline-col-body">
            @forelse($stageCands as $cand)
            <div class="pipeline-card" onclick="window.location='{{ route('admin.recruitment.candidate', [$tenant, $cand->id]) }}'">

                {{-- Initials + name --}}
                <div class="flex items-center gap-2.5 mb-2">
                    <div class="w-8 h-8 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ $cand->initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-sm truncate leading-snug">{{ $cand->full_name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $cand->email }}</p>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="space-y-1">
                    @if($cand->source)
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <i data-lucide="link" class="w-3 h-3"></i>
                        {{ $cand->source }}
                    </p>
                    @endif
                    @if($cand->interviews->count() > 0)
                    <p class="text-xs text-brand-600 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-3 h-3"></i>
                        {{ $cand->interviews->count() }} interview(s)
                    </p>
                    @endif
                    @if($cand->expected_salary)
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <i data-lucide="dollar-sign" class="w-3 h-3"></i>
                        ${{ number_format($cand->expected_salary, 0) }}
                    </p>
                    @endif
                </div>

                {{-- Rating --}}
                @if($cand->rating)
                <div class="flex items-center gap-0.5 mt-2">
                    @for($i=1;$i<=5;$i++)
                    <div class="w-2 h-2 rounded-full {{ $i <= $cand->rating ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                    @endfor
                </div>
                @endif

                {{-- Applied date --}}
                <p class="text-[10px] text-gray-300 mt-2">{{ $cand->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <div class="text-center py-6">
                <p class="text-xs text-gray-300">No candidates</p>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- Add Candidate Modal --}}
<div id="add-candidate-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Add Candidate</h3>
            <button onclick="closeModal('add-candidate-modal')"
                    class="w-8 h-8 rounded-lg text-gray-400 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.recruitment.candidates.store', [$tenant, $job->id]) }}"
              method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" required class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Phone</label>
                    <input type="text" name="phone" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Source</label>
                    <input type="text" name="source" class="lmt-input" placeholder="LinkedIn, Referral…"/>
                </div>
                <div>
                    <label class="lmt-label">Expected Salary ($)</label>
                    <input type="number" name="expected_salary" step="1000" min="0" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Available From</label>
                    <input type="date" name="available_from" class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">LinkedIn URL</label>
                    <input type="url" name="linkedin_url" class="lmt-input" placeholder="https://linkedin.com/in/…"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Resume (PDF)</label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx" class="lmt-input py-2 text-sm"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Cover Letter / Notes</label>
                <textarea name="cover_letter_text" class="lmt-textarea" rows="3"
                          placeholder="Cover letter or recruiter notes…"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Add Candidate</button>
                <button type="button" onclick="closeModal('add-candidate-modal')"
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
document.getElementById('add-candidate-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('add-candidate-modal'); });
</script>
@endpush