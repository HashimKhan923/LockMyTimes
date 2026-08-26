@extends('layouts.admin')
@section('title','Performance')
@section('page-title','Performance Management')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Active Cycles',    'value'=>$stats['active_cycles'],     'icon'=>'refresh-cw',  'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Pending Reviews',  'value'=>$stats['pending_reviews'],   'icon'=>'clock',       'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Completed (YTD)',  'value'=>$stats['completed_reviews'], 'icon'=>'check-circle','bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Active Goals',     'value'=>$stats['active_goals'],      'icon'=>'target',      'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
        ['label'=>'Kudos This Month', 'value'=>$stats['kudos_this_month'],  'icon'=>'star',        'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
    ] as $s)
    <div class="lmt-card flex items-center gap-3 p-4">
        <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} {{ $s['text'] }} flex items-center justify-center flex-shrink-0">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-800">{{ $s['label'] }}</p>
            <p class="text-xl font-black text-gray-900">{{ $s['value'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<div class="flex items-center gap-1 mb-6 border-b border-gray-200">
    @foreach(['reviews'=>' Reviews','goals'=>' Goals','kudos'=>' Kudos Wall','cycles'=>' Cycles'] as $t=>$label)
    <a href="{{ route('admin.performance.index', $tenant) }}?tab={{ $t }}"
       class="px-5 py-2.5 text-sm font-semibold border-b-2 transition-all -mb-px whitespace-nowrap
              {{ $tab === $t
                  ? 'border-brand-500 text-brand-600'
                  : 'border-transparent text-gray-800 hover:text-gray-800' }}">
        {{ $label }}
        @if($t === 'reviews' && $stats['pending_reviews'] > 0)
        <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['pending_reviews'] }}</span>
        @endif
    </a>
    @endforeach
</div>

{{-- ===== REVIEWS TAB ===== --}}
@if($tab === 'reviews')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Performance Reviews</h3>
    <div class="flex items-center gap-2">
        @include('exports.buttons', ['route' => 'admin.performance.export', 'params' => [$tenant]])
        <button onclick="openModal('add-review-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Review
        </button>
    </div>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Reviewer</th>
                    <th>Cycle</th>
                    <th>Type</th>
                    <th>Due Date</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                @php
                $statusColors = [
                    'pending'    => 'lmt-badge-amber',
                    'in_progress'=> 'lmt-badge-brand',
                    'completed'  => 'lmt-badge-green',
                    'cancelled'  => 'lmt-badge-gray',
                ];
                $isOverdue = $review->status === 'pending' && $review->due_date?->isPast();
                @endphp
                <tr class="{{ $isOverdue ? 'bg-red-50/20' : '' }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">{{ substr($review->employee->first_name??'E',0,1) }}</div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $review->employee->full_name ?? '—' }}</p>
                                <p class="text-xs text-gray-800">{{ $review->employee->department?->name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm text-gray-800">{{ $review->reviewer->full_name ?? 'Self' }}</td>
                    <td class="text-sm text-gray-800 max-w-32 truncate">{{ $review->cycle?->name ?? '—' }}</td>
                    <td>
                        <span class="lmt-badge-gray text-xs capitalize">
                            {{ str_replace('_',' ', $review->review_type ?? 'annual') }}
                        </span>
                    </td>
                    <td>
                        <span class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                            {{ $review->due_date?->format('M j, Y') ?? '—' }}
                        </span>
                        @if($isOverdue)<span class="block text-xs text-red-600">Overdue</span>@endif
                    </td>
                    <td>
                        @if($review->overall_rating)
                        <div class="flex items-center gap-1">
                            @for($i=1;$i<=5;$i++)
                            <div class="w-2 h-2 rounded-full {{ $i <= $review->overall_rating ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                            @endfor
                            <span class="text-xs font-bold ml-1">{{ number_format($review->overall_rating,1) }}</span>
                        </div>
                        @else
                        <span class="text-xs text-gray-800">Not rated</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $statusColors[$review->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ str_replace('_',' ',$review->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.performance.review', [$tenant, $review->id]) }}"
                           class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="{{ $review->status === 'pending' ? 'pencil' : 'eye' }}" class="w-3.5 h-3.5"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-14">
                        <i data-lucide="clipboard-list" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-800">No performance reviews yet</p>
                        <p class="text-sm text-gray-800 mt-1">Create a review cycle to get started</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())
    <div class="p-4 border-t border-gray-100">{{ $reviews->links() }}</div>
    @endif
</div>

{{-- ===== GOALS TAB ===== --}}
@elseif($tab === 'goals')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Goals & OKRs</h3>
    <button onclick="openModal('add-goal-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Goal
    </button>
</div>

<div class="lmt-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="lmt-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Goal</th>
                    <th>Category</th>
                    <th>Due Date</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($goals as $goal)
                @php
                $goalColors = ['active'=>'lmt-badge-brand','completed'=>'lmt-badge-green','cancelled'=>'lmt-badge-gray','on_hold'=>'lmt-badge-amber'];
                $isOverdue  = $goal->is_overdue ?? false;
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="lmt-avatar-sm font-bold text-xs">{{ substr($goal->employee->first_name??'E',0,1) }}</div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $goal->employee->full_name ?? '—' }}</p>
                        </div>
                    </td>
                    <td>
                        <p class="font-semibold text-gray-900 text-sm max-w-48 truncate">{{ $goal->title }}</p>
                        @if($goal->key_results)
                        <p class="text-xs text-gray-800 mt-0.5">{{ count((array)$goal->key_results) }} key results</p>
                        @endif
                    </td>
                    <td>
                        @if($goal->category)
                        <span class="lmt-badge-gray text-xs">{{ $goal->category }}</span>
                        @else<span class="text-gray-800 text-xs">—</span>@endif
                    </td>
                    <td>
                        <span class="text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                            {{ $goal->end_date?->format('M j, Y') ?? '—' }}
                        </span>
                    </td>
                    <td class="min-w-32">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     style="width:{{ $goal->progress }}%;
                                            background:{{ $goal->progress >= 100 ? '#10B981' : ($goal->progress >= 50 ? '#6C7DF7' : '#F59E0B') }}">
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-800 w-8">{{ $goal->progress }}%</span>
                        </div>
                    </td>
                    <td>
                        <span class="{{ $goalColors[$goal->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ $goal->status }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <button onclick="openProgressModal({{ $goal->id }}, {{ $goal->progress }})"
                                    class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-500 hover:text-white flex items-center justify-center transition-colors">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                            </button>
                            <form action="{{ route('admin.performance.goals.destroy', [$tenant, $goal->id]) }}"
                                  method="POST" onsubmit="return confirm('Delete this goal?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-14">
                        <i data-lucide="target" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                        <p class="text-gray-800">No goals set yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== KUDOS TAB ===== --}}
@elseif($tab === 'kudos')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Kudos Wall </h3>
    <button onclick="openModal('add-kudo-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="star" class="w-4 h-4"></i>
        Give Kudos
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($kudos as $kudo)
    @php
    $badgeMap = \App\Models\Tenant\Kudo::badges();
    @endphp
    <div class="lmt-card relative">
        {{-- Delete button --}}
        <form action="{{ route('admin.performance.kudos.destroy', [$tenant, $kudo->id]) }}"
              method="POST" class="absolute top-3 right-3"
              onsubmit="return confirm('Remove this kudo?')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="w-7 h-7 rounded-lg text-gray-800 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </form>

        {{-- Badge --}}
        @if($kudo->badge && isset($badgeMap[$kudo->badge]))
        <div class="lmt-badge-brand text-xs mb-3 w-fit">{{ $badgeMap[$kudo->badge] }}</div>
        @endif

        {{-- Message --}}
        <p class="text-sm text-gray-800 italic mb-4 leading-relaxed">"{{ $kudo->message }}"</p>

        {{-- From To --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="lmt-avatar-sm font-bold text-xs">
                    {{ substr($kudo->fromEmployee->first_name??'?',0,1) }}
                </div>
                <span class="text-xs text-gray-800">{{ $kudo->fromEmployee->first_name ?? '?' }}</span>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4 text-gray-800"></i>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-brand-600">{{ $kudo->toEmployee->first_name ?? '?' }}</span>
                <div class="lmt-avatar-sm font-bold text-xs" style="background:linear-gradient(135deg,#6C7DF7,#4A5BE8);">
                    {{ substr($kudo->toEmployee->first_name??'?',0,1) }}
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-800 text-right mt-2">{{ $kudo->created_at->diffForHumans() }}</p>
    </div>
    @empty
    <div class="lmt-card text-center py-14 md:col-span-3">
        <p class="text-4xl mb-3"></p>
        <p class="font-semibold text-gray-800">No kudos yet</p>
        <p class="text-sm text-gray-800 mt-1">Be the first to recognise a team member!</p>
        <button onclick="openModal('add-kudo-modal')"
                class="lmt-btn-primary lmt-btn-sm inline-flex mt-4">
            <i data-lucide="star" class="w-4 h-4"></i> Give First Kudos
        </button>
    </div>
    @endforelse
</div>

{{-- ===== CYCLES TAB ===== --}}
@elseif($tab === 'cycles')

<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-gray-900">Review Cycles</h3>
    <button onclick="openModal('add-cycle-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Cycle
    </button>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($cycles as $cycle)
    <div class="lmt-card">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-black text-gray-900">{{ $cycle->name }}</h3>
                <p class="text-xs text-gray-800 mt-0.5 capitalize">{{ str_replace('_',' ',$cycle->type) }}</p>
            </div>
            <span class="{{ $cycle->status === 'active' ? 'lmt-badge-green' : 'lmt-badge-gray' }} text-xs">
                {{ ucfirst($cycle->status) }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-gray-900 text-base">{{ $cycle->reviews()->count() }}</p>
                <p class="text-gray-800">Reviews</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-black text-emerald-600 text-base">{{ $cycle->reviews()->where('status','completed')->count() }}</p>
                <p class="text-gray-800">Completed</p>
            </div>
        </div>
        <div class="text-xs text-gray-800 space-y-1">
            <p> {{ $cycle->start_date?->format('M j') }} – {{ $cycle->end_date?->format('M j, Y') }}</p>
            <p>⏰ Due: {{ $cycle->due_date?->format('M j, Y') }}</p>
        </div>
        @if($cycle->description)
        <p class="text-xs text-gray-800 mt-2">{{ $cycle->description }}</p>
        @endif
    </div>
    @empty
    <div class="lmt-card text-center py-12 md:col-span-3">
        <i data-lucide="refresh-cw" class="w-8 h-8 text-gray-200 mx-auto mb-3"></i>
        <p class="text-gray-800">No review cycles created yet</p>
    </div>
    @endforelse
</div>
@endif

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- New Review --}}
<div id="add-review-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Create Performance Review</h3>
        <form action="{{ route('admin.performance.reviews.store', $tenant) }}" method="POST" class="space-y-4">
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
            <div>
                <label class="lmt-label">Reviewer <span class="text-red-500">*</span></label>
                <select name="reviewer_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Review Cycle <span class="text-red-500">*</span></label>
                    <select name="review_cycle_id" required class="lmt-select">
                        <option value="">— Select Cycle —</option>
                        @foreach($cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Review Type <span class="text-red-500">*</span></label>
                    <select name="review_type" required class="lmt-select">
                        <option value="manager">Manager Review</option>
                        <option value="self">Self Assessment</option>
                        <option value="peer">Peer Review</option>
                        <option value="subordinate">Subordinate Review</option>
                        <option value="360">360° Review</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="lmt-label">Due Date <span class="text-red-500">*</span></label>
                <input type="date" name="due_date" required class="lmt-input"
                       value="{{ now()->addDays(30)->toDateString() }}"/>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create Review</button>
                <button type="button" onclick="closeModal('add-review-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- New Goal --}}
<div id="add-goal-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Set New Goal</h3>
        <form action="{{ route('admin.performance.goals.store', $tenant) }}" method="POST" class="space-y-4">
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
            <div>
                <label class="lmt-label">Goal Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input"
                       placeholder="e.g. Increase customer satisfaction score by 15%"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Goal Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        <option value="smart">SMART Goal</option>
                        <option value="okr">OKR</option>
                        <option value="kpi">KPI</option>
                        <option value="personal">Personal Development</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="lmt-select">
                        <option value="individual">Individual</option>
                        <option value="team">Team</option>
                        <option value="department">Department</option>
                        <option value="company">Company</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required class="lmt-input"
                           value="{{ today()->toDateString() }}"/>
                </div>
                <div>
                    <label class="lmt-label">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" required class="lmt-input"
                           value="{{ now()->addMonths(3)->toDateString() }}"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"
                          placeholder="Describe the goal and success criteria…"></textarea>
            </div>
            <div>
                <label class="lmt-label">Key Results (one per line)</label>
                <textarea name="key_results" class="lmt-textarea" rows="3"
                          placeholder="Complete 5 client calls per week&#10;Achieve 90% satisfaction score&#10;Reduce response time to under 2 hours"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Set Goal</button>
                <button type="button" onclick="closeModal('add-goal-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Update Goal Progress --}}
<div id="progress-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-5">Update Goal Progress</h3>
        <form id="progress-form" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="lmt-label">Progress (%)</label>
                <input type="range" name="progress" id="progress-range" min="0" max="100" step="5"
                       class="w-full h-2 rounded-full appearance-none bg-gray-200 cursor-pointer"
                       oninput="document.getElementById('progress-val').textContent=this.value+'%'"/>
                <div class="flex justify-between mt-2">
                    <span class="text-xs text-gray-800">0%</span>
                    <span class="text-sm font-black text-brand-600" id="progress-val">0%</span>
                    <span class="text-xs text-gray-800">100%</span>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Update</button>
                <button type="button" onclick="closeModal('progress-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Give Kudos --}}
<div id="add-kudo-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <h3 class="font-black text-gray-900 mb-1">Give Kudos </h3>
        <p class="text-sm text-gray-800 mb-5">Recognise a team member's great work</p>
        <form action="{{ route('admin.performance.kudos.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">From <span class="text-red-500">*</span></label>
                <select name="from_employee_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">To <span class="text-red-500">*</span></label>
                <select name="to_employee_id" required class="lmt-select">
                    <option value="">— Select —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Badge</label>
                <select name="badge" class="lmt-select">
                    <option value="">— No Badge —</option>
                    @foreach(\App\Models\Tenant\Kudo::badges() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lmt-label">Message <span class="text-red-500">*</span></label>
                <textarea name="message" required class="lmt-textarea" rows="3"
                          placeholder="What did they do that deserves recognition?"></textarea>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_public" value="1" checked class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-800">Show on Kudos Wall</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Send Kudos </button>
                <button type="button" onclick="closeModal('add-kudo-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- New Cycle --}}
<div id="add-cycle-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <h3 class="font-black text-gray-900 mb-5">Create Review Cycle</h3>
        <form action="{{ route('admin.performance.cycles.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Cycle Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="lmt-input"
                       placeholder="e.g. Q2 2026 Performance Review"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['annual'=>'Annual','semi_annual'=>'Semi-Annual','quarterly'=>'Quarterly','monthly'=>'Monthly','project_based'=>'Project Based','probation'=>'Probation'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" required class="lmt-input"
                           value="{{ now()->addDays(30)->toDateString() }}"/>
                </div>
                <div>
                    <label class="lmt-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" required class="lmt-input"
                           value="{{ today()->toDateString() }}"/>
                </div>
                <div>
                    <label class="lmt-label">End Date <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" required class="lmt-input"
                           value="{{ now()->addMonths(3)->toDateString() }}"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <textarea name="description" class="lmt-textarea" rows="2"></textarea>
            </div>
            <label class="flex items-center gap-2 cursor-pointer p-3 bg-brand-50 rounded-xl border border-brand-200">
                <input type="checkbox" name="auto_assign" value="1" checked class="w-4 h-4 rounded"/>
                <div>
                    <p class="text-sm font-semibold text-brand-700">Auto-assign to all active employees</p>
                    <p class="text-xs text-brand-500">Creates a review record for every active employee</p>
                </div>
            </label>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create Cycle</button>
                <button type="button" onclick="closeModal('add-cycle-modal')" class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
function openProgressModal(goalId, currentProgress) {
    document.getElementById('progress-form').action =
        `/t/{{ $tenant }}/admin/performance/goals/${goalId}`;
    const range = document.getElementById('progress-range');
    range.value = currentProgress;
    document.getElementById('progress-val').textContent = currentProgress + '%';
    openModal('progress-modal');
}

// Close on backdrop click
['add-review-modal','add-goal-modal','add-kudo-modal','add-cycle-modal','progress-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush