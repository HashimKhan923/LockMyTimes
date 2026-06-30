@extends('layouts.admin')
@section('title','Training')
@section('page-title','Training & LMS')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Courses',         'value'=>$stats['total_courses'],  'icon'=>'book-open',    'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Enrollments',     'value'=>$stats['enrollments'],    'icon'=>'users',        'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
        ['label'=>'Completed',       'value'=>$stats['completed'],      'icon'=>'check-circle', 'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Certifications',  'value'=>$stats['certifications'], 'icon'=>'award',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Expiring (30d)',  'value'=>$stats['expiring_soon'],  'icon'=>'alert-circle', 'bg'=>'bg-red-50',    'text'=>'text-red-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['text'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-400">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<div class="flex items-center gap-1 mb-6 border-b border-gray-200">
    @foreach(['courses'=>'📚 Courses','enrollments'=>'👥 Enrollments','certifications'=>'🏆 Certifications'] as $t=>$label)
    <a href="{{ route('admin.training.index', $tenant) }}?tab={{ $t }}"
       class="px-5 py-2.5 text-sm font-semibold border-b-2 transition-all -mb-px whitespace-nowrap
              {{ $tab === $t ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- ===== COURSES TAB ===== --}}
@if($tab === 'courses')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <form action="{{ route('admin.training.index', $tenant) }}" method="GET" class="flex items-center gap-2">
        <input type="hidden" name="tab" value="courses"/>
        <select name="category" class="lmt-select py-2 text-sm w-auto" onchange="this.form.submit()">
            <option value="">All Categories</option>
            @foreach($categories as $v=>$l)
            <option value="{{ $v }}" {{ request('category')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search courses…" class="lmt-input py-2 text-sm w-44"/>
    </form>
    <button onclick="openModal('add-training-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Course
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($trainings as $training)
    @php
    $catColors = [
        'onboarding'  => ['bg'=>'bg-emerald-100','text'=>'text-emerald-700'],
        'compliance'  => ['bg'=>'bg-red-100',    'text'=>'text-red-700'],
        'technical'   => ['bg'=>'bg-brand-100',  'text'=>'text-brand-700'],
        'soft_skills' => ['bg'=>'bg-purple-100', 'text'=>'text-purple-700'],
        'leadership'  => ['bg'=>'bg-amber-100',  'text'=>'text-amber-700'],
        'safety'      => ['bg'=>'bg-orange-100', 'text'=>'text-orange-700'],
        'other'       => ['bg'=>'bg-gray-100',   'text'=>'text-gray-700'],
    ];
    $cc = $catColors[$training->category] ?? $catColors['other'];
    $typeIcons = ['online'=>'monitor','in_person'=>'users','hybrid'=>'layout','self_paced'=>'book-open'];
    @endphp
    <div class="lmt-card p-0 overflow-hidden hover:shadow-md transition-shadow">
        {{-- Thumbnail / Header --}}
        <div class="h-32 relative overflow-hidden"
             style="background:linear-gradient(135deg,#6C7DF720,#4A5BE810)">
            @if($training->thumbnail)
            <img src="{{ asset('storage/'.$training->thumbnail) }}" class="w-full h-full object-cover"/>
            @else
            <div class="w-full h-full flex items-center justify-center">
                <i data-lucide="{{ $typeIcons[$training->type] ?? 'book-open' }}"
                   class="w-12 h-12 text-brand-200"></i>
            </div>
            @endif
            {{-- Badges --}}
            <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $cc['bg'] }} {{ $cc['text'] }} capitalize">
                    {{ str_replace('_',' ', $training->category) }}
                </span>
                @if($training->is_mandatory)
                <span class="lmt-badge-red text-xs">Mandatory</span>
                @endif
            </div>
            @if($training->issues_certificate)
            <div class="absolute top-3 right-3">
                <span class="lmt-badge-amber text-xs flex items-center gap-1">
                    <i data-lucide="award" class="w-3 h-3"></i> Certificate
                </span>
            </div>
            @endif
        </div>

        <div class="p-4">
            <h3 class="font-black text-gray-900 mb-1 line-clamp-2">{{ $training->title }}</h3>
            @if($training->instructor)
            <p class="text-xs text-gray-400 mb-2">by {{ $training->instructor }}</p>
            @endif

            <div class="flex flex-wrap gap-3 text-xs text-gray-500 mb-3">
                <span class="flex items-center gap-1">
                    <i data-lucide="{{ $typeIcons[$training->type] ?? 'book-open' }}" class="w-3.5 h-3.5"></i>
                    {{ ucfirst(str_replace('_',' ',$training->type)) }}
                </span>
                @if($training->duration_hours > 0)
                <span class="flex items-center gap-1">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    {{ $training->duration_hours }}h
                </span>
                @endif
                @if($training->cost > 0)
                <span class="flex items-center gap-1">
                    <i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i>
                    ${{ number_format($training->cost,0) }}
                </span>
                @endif
            </div>

            {{-- Dates --}}
            @if($training->start_date)
            <p class="text-xs text-gray-400 mb-3">
                <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>
                {{ $training->start_date->format('M j, Y') }}
                @if($training->end_date && $training->end_date->ne($training->start_date))
                – {{ $training->end_date->format('M j, Y') }}
                @endif
            </p>
            @endif

            {{-- Enrollment bar --}}
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400">
                    {{ $training->enrollments_count }} enrolled
                    @if($training->max_participants)
                    / {{ $training->max_participants }}
                    @endif
                </span>
                @if($training->max_participants)
                @php $pct = round($training->enrollments_count / $training->max_participants * 100); @endphp
                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full lmt-gradient-bg" style="width:{{ $pct }}%"></div>
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <a href="{{ route('admin.training.show', [$tenant, $training->id]) }}"
                   class="flex-1 lmt-btn-secondary lmt-btn-sm text-center">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    View
                </a>
                <button onclick="openEnrollModal({{ $training->id }}, '{{ addslashes($training->title) }}')"
                        class="flex-1 lmt-btn-primary lmt-btn-sm">
                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                    Enroll
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-3">
        <i data-lucide="book-open" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
        <p class="font-black text-gray-400 text-lg">No courses yet</p>
        <button onclick="openModal('add-training-modal')" class="lmt-btn-primary lmt-btn-sm inline-flex mt-4">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Course
        </button>
    </div>
    @endforelse
</div>
@if($trainings->hasPages())
<div class="mt-5">{{ $trainings->links() }}</div>
@endif

{{-- ===== ENROLLMENTS TAB ===== --}}
@elseif($tab === 'enrollments')

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Course</th>
                    <th>Enrolled</th>
                    <th>Progress</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $en)
                @php
                $statusColors = [
                    'enrolled'   => 'lmt-badge-gray',
                    'in_progress'=> 'lmt-badge-brand',
                    'completed'  => 'lmt-badge-green',
                    'failed'     => 'lmt-badge-red',
                    'dropped'    => 'lmt-badge-gray',
                ];
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">{{ substr($en->employee->first_name??'E',0,1) }}</div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $en->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $en->employee->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="font-semibold text-gray-900 text-sm max-w-40 truncate">{{ $en->training->title ?? '—' }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ str_replace('_',' ',$en->training->category??'') }}</p>
                    </td>
                    <td class="text-xs text-gray-500">{{ $en->enrolled_at->format('M j, Y') }}</td>
                    <td class="min-w-28">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full lmt-gradient-bg rounded-full" style="width:{{ $en->progress }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-600">{{ $en->progress }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($en->score !== null)
                        <span class="text-sm font-bold {{ $en->score >= 70 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ number_format($en->score,1) }}%
                        </span>
                        @else
                        <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $statusColors[$en->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ str_replace('_',' ',$en->status) }}
                        </span>
                        @if($en->completed_at)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $en->completed_at->format('M j') }}</p>
                        @endif
                    </td>
                    <td>
                        @if($en->rating)
                        <div class="flex items-center gap-0.5">
                            @for($i=1;$i<=5;$i++)
                            <div class="w-2.5 h-2.5 rounded-full {{ $i <= $en->rating ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                            @endfor
                        </div>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td>
                        <button onclick="openUpdateModal({{ $en->id }}, {{ $en->progress }}, '{{ $en->status }}', {{ $en->score ?? 'null' }})"
                                class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-14">
                        <i data-lucide="users" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-400">No enrollments yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($enrollments->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $enrollments->links() }}</div>
    @endif
</div>

{{-- ===== CERTIFICATIONS TAB ===== --}}
@elseif($tab === 'certifications')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Employee Certifications</h3>
    <button onclick="openModal('add-cert-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add Certification
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Certification</th>
                    <th>Issuer</th>
                    <th>Issued</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certifications as $cert)
                @php
                $isExpired      = $cert->expiry_date && $cert->expiry_date->isPast();
                $isExpiringSoon = !$isExpired && $cert->expiry_date && $cert->expiry_date->diffInDays(now()) <= 30;
                @endphp
                <tr class="{{ $isExpired ? 'bg-red-50/20' : ($isExpiringSoon ? 'bg-amber-50/20' : '') }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">{{ substr($cert->employee->first_name??'E',0,1) }}</div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $cert->employee->full_name ?? '—' }}</p>
                        </div>
                    </td>
                    <td>
                        <p class="font-semibold text-gray-900 text-sm">{{ $cert->name }}</p>
                        @if($cert->credential_id)
                        <p class="text-xs text-gray-400 font-mono">{{ $cert->credential_id }}</p>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600">{{ $cert->issuer }}</td>
                    <td class="text-sm text-gray-600">{{ $cert->issue_date->format('M j, Y') }}</td>
                    <td>
                        @if($cert->expiry_date)
                        <span class="text-sm {{ $isExpired ? 'text-red-600 font-bold' : ($isExpiringSoon ? 'text-amber-600 font-semibold' : 'text-gray-600') }}">
                            {{ $cert->expiry_date->format('M j, Y') }}
                        </span>
                        @if($isExpiringSoon)
                        <span class="block text-xs text-amber-500">Expires in {{ now()->diffInDays($cert->expiry_date) }}d</span>
                        @endif
                        @else
                        <span class="text-gray-400 text-sm">No expiry</span>
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
                        <span class="block lmt-badge-brand text-xs mt-0.5">Verified ✓</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            @if($cert->credential_url)
                            <a href="{{ $cert->credential_url }}" target="_blank"
                               class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                            @endif
                            <form action="{{ route('admin.training.certifications.destroy', [$tenant, $cert->id]) }}"
                                  method="POST" onsubmit="return confirm('Remove certification?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-14">
                        <i data-lucide="award" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-400">No certifications recorded</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- Add Training Modal --}}
<div id="add-training-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl">
        <h3 class="font-black text-gray-900 mb-5">Create Training Course</h3>
        <form action="{{ route('admin.training.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required class="lmt-input" placeholder="e.g. Advanced Excel for HR"/>
                </div>
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['online'=>'Online','in_person'=>'In Person','hybrid'=>'Hybrid','self_paced'=>'Self-Paced'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="lmt-select">
                        @foreach($categories as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Instructor</label>
                    <input type="text" name="instructor" class="lmt-input" placeholder="Jane Smith"/>
                </div>
                <div>
                    <label class="lmt-label">Provider</label>
                    <input type="text" name="provider" class="lmt-input" placeholder="Coursera, Udemy…"/>
                </div>
                <div>
                    <label class="lmt-label">Start Date</label>
                    <input type="date" name="start_date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">End Date</label>
                    <input type="date" name="end_date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Duration (hours)</label>
                    <input type="number" name="duration_hours" min="0" class="lmt-input" value="0"/>
                </div>
                <div>
                    <label class="lmt-label">Cost ($)</label>
                    <input type="number" name="cost" step="0.01" min="0" class="lmt-input" value="0"/>
                </div>
                <div>
                    <label class="lmt-label">Max Participants</label>
                    <input type="number" name="max_participants" min="1" class="lmt-input" placeholder="Unlimited"/>
                </div>
                <div>
                    <label class="lmt-label">Location / URL</label>
                    <input type="text" name="location" class="lmt-input" placeholder="Room 301 or Zoom link"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Description</label>
                    <textarea name="description" class="lmt-textarea" rows="2"
                              placeholder="Course overview and objectives…"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Thumbnail</label>
                    <input type="file" name="thumbnail" accept="image/*" class="lmt-input py-2 text-sm"/>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach([['is_mandatory','Mandatory Training'],['issues_certificate','Issues Certificate']] as [$n,$l])
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <input type="checkbox" name="{{ $n }}" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-xs font-medium text-gray-700">{{ $l }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Course</button>
                <button type="button" onclick="closeModal('add-training-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Enroll Modal --}}
<div id="enroll-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-1">Enroll Employees</h3>
        <p class="text-sm text-gray-400 mb-5" id="enroll-training-name"></p>
        <form id="enroll-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Select Employees <span class="text-red-500">*</span></label>
                <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-xl p-2 space-y-1">
                    @foreach($employees as $emp)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" class="w-4 h-4 rounded"/>
                        <div class="lmt-avatar-sm font-bold text-xs flex-shrink-0">{{ substr($emp->first_name,0,1) }}</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $emp->full_name }}</p>
                            <p class="text-xs text-gray-400">{{ $emp->department?->name }}</p>
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

{{-- Update Enrollment Modal --}}
<div id="update-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Update Enrollment</h3>
        <form id="update-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                <select name="status" id="update-status" required class="lmt-select">
                    @foreach(['enrolled'=>'Enrolled','in_progress'=>'In Progress','completed'=>'Completed','failed'=>'Failed','dropped'=>'Dropped'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Progress (%)</label>
                <input type="range" name="progress" id="update-progress" min="0" max="100" step="5"
                       class="w-full" oninput="document.getElementById('prog-val').textContent=this.value+'%'"/>
                <p class="text-sm font-bold text-brand-600 mt-1" id="prog-val">0%</p>
            </div>
            <div>
                <label class="lmt-label">Score (%)</label>
                <input type="number" name="score" id="update-score" step="0.1" min="0" max="100"
                       class="lmt-input" placeholder="Leave blank if not assessed"/>
            </div>
            <div>
                <label class="lmt-label">Feedback</label>
                <textarea name="feedback" class="lmt-textarea" rows="2"
                          placeholder="Optional feedback…"></textarea>
            </div>
            <div>
                <label class="lmt-label">Rating (1–5)</label>
                <select name="rating" class="lmt-select">
                    <option value="">— No Rating —</option>
                    @foreach([1=>'⭐ 1 - Poor',2=>'⭐⭐ 2 - Fair',3=>'⭐⭐⭐ 3 - Good',4=>'⭐⭐⭐⭐ 4 - Very Good',5=>'⭐⭐⭐⭐⭐ 5 - Excellent'] as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save</button>
                <button type="button" onclick="closeModal('update-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Certification Modal --}}
<div id="add-cert-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Add Certification</h3>
        <form action="{{ route('admin.training.certifications.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="lmt-label">Certification Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="lmt-input"
                           placeholder="e.g. AWS Certified Solutions Architect"/>
                </div>
                <div>
                    <label class="lmt-label">Issuer <span class="text-red-500">*</span></label>
                    <input type="text" name="issuer" required class="lmt-input" placeholder="Amazon Web Services"/>
                </div>
                <div>
                    <label class="lmt-label">Credential ID</label>
                    <input type="text" name="credential_id" class="lmt-input" placeholder="ABC-123456"/>
                </div>
                <div>
                    <label class="lmt-label">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" name="issue_date" required class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="lmt-input"/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Credential URL</label>
                    <input type="url" name="credential_url" class="lmt-input" placeholder="https://verify.aws.com/..."/>
                </div>
                <div class="col-span-2">
                    <label class="lmt-label">Certificate File (PDF/Image)</label>
                    <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png"
                           class="lmt-input py-2 text-sm"/>
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_verified" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-700">Mark as Verified</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Add Certification</button>
                <button type="button" onclick="closeModal('add-cert-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
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

function openEnrollModal(trainingId, trainingName) {
    document.getElementById('enroll-form').action = `/t/{{ $tenant }}/admin/training/${trainingId}/enroll`;
    document.getElementById('enroll-training-name').textContent = trainingName;
    // Uncheck all
    document.querySelectorAll('#enroll-form input[type=checkbox]').forEach(cb => cb.checked = false);
    openModal('enroll-modal');
}

function openUpdateModal(enrollId, progress, status, score) {
    document.getElementById('update-form').action = `/t/{{ $tenant }}/admin/training/enrollments/${enrollId}`;
    document.getElementById('update-status').value   = status;
    document.getElementById('update-progress').value = progress;
    document.getElementById('prog-val').textContent  = progress + '%';
    document.getElementById('update-score').value    = score ?? '';
    openModal('update-modal');
}

['add-training-modal','enroll-modal','update-modal','add-cert-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush