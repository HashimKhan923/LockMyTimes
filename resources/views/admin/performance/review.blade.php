@extends('layouts.admin')
@section('title','Performance Review')
@section('page-title','Performance Review')

@section('content')

<div class="max-w-3xl mx-auto">

    <a href="{{ route('admin.performance.index', $tenant) }}?tab=reviews"
       class="inline-flex items-center gap-2 text-sm text-gray-800 hover:text-gray-700 mb-6 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Reviews
    </a>

    {{-- Review header --}}
    <div class="lmt-card mb-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 rounded-full lmt-gradient-bg opacity-5 -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl lmt-gradient-bg flex items-center justify-center text-white text-xl font-black">
                {{ substr($review->employee->first_name ?? 'E', 0, 1) }}
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-3 mb-1">
                    <h2 class="text-xl font-black text-gray-900">{{ $review->employee->full_name }}</h2>
                    @php
                    $sc = ['pending'=>'lmt-badge-amber','in_progress'=>'lmt-badge-brand','completed'=>'lmt-badge-green'];
                    @endphp
                    <span class="{{ $sc[$review->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                        {{ str_replace('_',' ',$review->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-800">
                    {{ $review->employee->position?->title }}
                    @if($review->employee->department) · {{ $review->employee->department->name }} @endif
                </p>
                <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-800">
                    <span>Reviewer: <strong class="text-gray-700">{{ $review->reviewer->full_name ?? 'Self' }}</strong></span>
                    <span>Type: <strong class="text-gray-700 capitalize">{{ str_replace('_',' ', $review->review_type ?? 'annual') }}</strong></span>
                    <span>Due: <strong class="text-gray-700">{{ $review->due_date?->format('M j, Y') ?? '—' }}</strong></span>
                    @if($review->cycle)
                    <span>Cycle: <strong class="text-gray-700">{{ $review->cycle->name }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($review->status === 'completed')
    {{-- Completed review display --}}
    <div class="lmt-card mb-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Review Results</h3>
            <div class="flex items-center gap-3">
                @if($review->overall_rating)
                <div class="flex items-center gap-2 bg-amber-50 px-4 py-2 rounded-xl">
                    <div class="flex gap-0.5">
                        @for($i=1;$i<=5;$i++)
                        <div class="w-4 h-4 rounded-full {{ $i <= $review->overall_rating ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                        @endfor
                    </div>
                    <span class="text-lg font-black text-amber-600">{{ number_format($review->overall_rating,1) }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            @foreach([
                ['label'=>'Strengths',             'value'=>$review->strengths,             'icon'=>'thumbs-up',  'color'=>'text-emerald-600 bg-emerald-50'],
                ['label'=>'Areas for Improvement', 'value'=>$review->areas_for_improvement, 'icon'=>'trending-up','color'=>'text-brand-600 bg-brand-50'],
                ['label'=>'Manager Comments',      'value'=>$review->manager_comments,      'icon'=>'message-square','color'=>'text-purple-600 bg-purple-50'],
                ['label'=>'Employee Comments',     'value'=>$review->employee_comments,     'icon'=>'user',       'color'=>'text-gray-600 bg-gray-50'],
            ] as $section)
            @if($section['value'])
            <div class="p-4 rounded-xl bg-gray-50">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg {{ $section['color'] }} flex items-center justify-center">
                        <i data-lucide="{{ $section['icon'] }}" class="w-3.5 h-3.5"></i>
                    </div>
                    <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">{{ $section['label'] }}</span>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $section['value'] }}</p>
            </div>
            @endif
            @endforeach
        </div>

        @if($review->submitted_at)
        <p class="text-xs text-gray-800 mt-4 text-right">
            Submitted {{ $review->submitted_at->format('M j, Y h:i A') }}
        </p>
        @endif
    </div>

    @else
    {{-- Review form --}}
    <div class="lmt-card">
        <h3 class="font-black text-gray-900 mb-5">Submit Review</h3>
        <form action="{{ route('admin.performance.reviews.submit', [$tenant, $review->id]) }}"
              method="POST" class="space-y-5">
            @csrf @method('PATCH')

            {{-- Overall Rating --}}
            <div>
                <label class="lmt-label">Overall Rating <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-3 mt-2" x-data="{ rating: {{ $review->overall_rating ?? 3 }} }">
                    @for($i=1;$i<=5;$i++)
                    <button type="button" @click="rating={{ $i }}"
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-lg transition-all"
                            :class="rating >= {{ $i }} ? 'bg-amber-400 text-white scale-110' : 'bg-gray-100 text-gray-800'">
                        {{ $i }}
                    </button>
                    @endfor
                    <input type="hidden" name="overall_rating" :value="rating"/>
                    <span class="text-sm font-bold ml-2" x-text="['','Unsatisfactory','Needs Improvement','Meets Expectations','Exceeds Expectations','Outstanding'][rating]"></span>
                </div>
                @php
                $ratingLabels = [1=>'Unsatisfactory',2=>'Needs Improvement',3=>'Meets Expectations',4=>'Exceeds Expectations',5=>'Outstanding'];
                @endphp
            </div>

            {{-- Text sections --}}
            @foreach([
                ['name'=>'strengths',            'label'=>'Strengths',             'placeholder'=>'What has this employee done exceptionally well?'],
                ['name'=>'areas_for_improvement','label'=>'Areas for Improvement', 'placeholder'=>'What can they work on to grow further?'],
                ['name'=>'manager_comments',     'label'=>'Manager Comments',      'placeholder'=>'Overall assessment and key observations…'],
            ] as $field)
            <div>
                <label class="lmt-label">{{ $field['label'] }}</label>
                <textarea name="{{ $field['name'] }}" rows="3" class="lmt-textarea"
                          placeholder="{{ $field['placeholder'] }}">{{ old($field['name'], $review->{$field['name']} ?? '') }}</textarea>
            </div>
            @endforeach

            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Submit Review
                </button>
                <a href="{{ route('admin.performance.index', $tenant) }}?tab=reviews"
                   class="lmt-btn-secondary flex-1 text-center">
                    Save for Later
                </a>
            </div>
        </form>
    </div>
    @endif

</div>

@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
@endpush