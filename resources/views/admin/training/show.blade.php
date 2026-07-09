@extends('layouts.admin')
@section('title', $training->title)
@section('page-title', 'Course Detail')

@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('admin.training.index', $tenant) }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Training
    </a>
    <div class="flex items-center gap-2">
        <button onclick="openModal('enroll-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Enroll Employees
        </button>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Left --}}
    <div class="space-y-5">
        <div class="lmt-card p-0 overflow-hidden">
            {{-- Thumbnail --}}
            <div class="h-40 bg-gradient-to-br from-brand-50 to-brand-100 flex items-center justify-center relative">
                @if($training->thumbnail)
                <img src="{{ asset('storage/'.$training->thumbnail) }}" class="w-full h-full object-cover"/>
                @else
                <i data-lucide="book-open" class="w-14 h-14 text-brand-300"></i>
                @endif
                @if($training->is_mandatory)
                <div class="absolute top-3 left-3">
                    <span class="lmt-badge-red text-xs">Mandatory</span>
                </div>
                @endif
                @if($training->issues_certificate)
                <div class="absolute top-3 right-3">
                    <span class="lmt-badge-amber text-xs flex items-center gap-1">
                        <i data-lucide="award" class="w-3 h-3"></i> Certificate
                    </span>
                </div>
                @endif
            </div>
            <div class="p-5">
                <h2 class="text-xl font-black text-gray-900 mb-2">{{ $training->title }}</h2>
                @if($training->description)
                <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ $training->description }}</p>
                @endif

                <div class="space-y-2">
                    @foreach([
                        ['Type',       ucfirst(str_replace('_',' ',$training->type))],
                        ['Category',   ucfirst(str_replace('_',' ',$training->category))],
                        ['Instructor', $training->instructor ?? '—'],
                        ['Provider',   $training->provider ?? '—'],
                        ['Duration',   $training->duration_hours ? $training->duration_hours.'h' : '—'],
                        ['Cost',       $training->cost > 0 ? '$'.number_format($training->cost,2) : 'Free'],
                        ['Spots',      $training->max_participants ? $training->enrollments->count().'/'.$training->max_participants : 'Unlimited'],
                    ] as [$k,$v])
                    <div class="flex justify-between py-1.5 border-b border-gray-50 last:border-none">
                        <span class="text-xs text-gray-400">{{ $k }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                @if($training->start_date)
                <div class="mt-4 p-3 bg-brand-50 rounded-xl">
                    <p class="text-xs font-bold text-brand-700 mb-1"> Schedule</p>
                    <p class="text-sm text-brand-600">
                        {{ $training->start_date->format('M j, Y') }}
                        @if($training->start_time) at {{ \Carbon\Carbon::parse($training->start_time)->format('h:i A') }} @endif
                    </p>
                    @if($training->end_date && $training->end_date->ne($training->start_date))
                    <p class="text-xs text-brand-500 mt-0.5">
                        Until {{ $training->end_date->format('M j, Y') }}
                    </p>
                    @endif
                    @if($training->location)
                    <p class="text-xs text-brand-500 mt-0.5"> {{ $training->location }}</p>
                    @endif
                    @if($training->online_url)
                    <a href="{{ $training->online_url }}" target="_blank"
                       class="text-xs text-brand-600 hover:underline mt-0.5 block">
                         Join Online
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Stats --}}
        <div class="lmt-card">
            <h3 class="font-black text-gray-900 mb-4">Statistics</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    ['Enrolled',   $training->enrollments->count(),                                  'text-gray-900'],
                    ['Completed',  $training->enrollments->where('status','completed')->count(),      'text-emerald-600'],
                    ['In Progress',$training->enrollments->where('status','in_progress')->count(),    'text-brand-600'],
                    ['Avg Score',  $avgScore ? number_format($avgScore,1).'%' : '—',                 'text-amber-600'],
                ] as [$label,$val,$color])
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-xl font-black {{ $color }}">{{ $val }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $label }}</p>
                </div>
                @endforeach
            </div>
            @if($avgRating)
            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400 mb-2">Average Rating</p>
                <div class="flex items-center justify-center gap-1">
                    @for($i=1;$i<=5;$i++)
                    <div class="w-5 h-5 rounded-full {{ $i <= round($avgRating) ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                    @endfor
                    <span class="text-sm font-black text-amber-600 ml-2">{{ number_format($avgRating,1) }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Right — Enrollments --}}
    <div class="lg:col-span-2 lmt-card p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="font-black text-gray-900">Enrolled Employees</h3>
            <span class="lmt-badge-gray text-xs">{{ $training->enrollments->count() }}</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($training->enrollments as $en)
            @php
            $statusColors = ['enrolled'=>'lmt-badge-gray','in_progress'=>'lmt-badge-brand','completed'=>'lmt-badge-green','failed'=>'lmt-badge-red','dropped'=>'lmt-badge-gray'];
            @endphp
            <div class="flex items-center gap-4 p-4">
                <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">
                    {{ substr($en->employee->first_name??'E',0,1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="font-semibold text-gray-900 text-sm truncate">{{ $en->employee->full_name ?? '—' }}</p>
                        <span class="{{ $statusColors[$en->status] ?? 'lmt-badge-gray' }} text-xs capitalize flex-shrink-0">
                            {{ str_replace('_',' ',$en->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full lmt-gradient-bg rounded-full" style="width:{{ $en->progress }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-500 w-8">{{ $en->progress }}%</span>
                        @if($en->score !== null)
                        <span class="text-xs font-bold {{ $en->score >= 70 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ number_format($en->score,0) }}%
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <i data-lucide="users" class="w-8 h-8 text-gray-200 mx-auto mb-2"></i>
                <p class="text-sm text-gray-400">No enrollments yet</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Enroll Modal --}}
<div id="enroll-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Enroll in {{ $training->title }}</h3>
        <form action="{{ route('admin.training.enroll', [$tenant, $training->id]) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Select Employees</label>
                <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-xl p-2 space-y-1">
                    @foreach($employees as $emp)
                    @php $alreadyEnrolled = $training->enrollments->where('employee_id',$emp->id)->isNotEmpty(); @endphp
                    <label class="flex items-center gap-3 p-2 rounded-lg {{ $alreadyEnrolled ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-50 cursor-pointer' }}">
                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                               {{ $alreadyEnrolled ? 'disabled' : '' }} class="w-4 h-4 rounded"/>
                        <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">{{ substr($emp->first_name,0,1) }}</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</p>
                            @if($alreadyEnrolled)<span class="text-xs text-gray-400">Already enrolled</span>@endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Enroll</button>
                <button type="button" onclick="closeModal('enroll-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('flex'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.getElementById(id).classList.remove('flex'); }
document.getElementById('enroll-modal')?.addEventListener('click', function(e) { if(e.target===this) closeModal('enroll-modal'); });
</script>
@endpush