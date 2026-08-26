@extends('layouts.admin')
@section('title','Announcements')
@section('page-title','Announcements & Polls')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Published',    'value'=>$stats['published'],   'icon'=>'megaphone',   'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Scheduled',    'value'=>$stats['scheduled'],   'icon'=>'clock',       'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Drafts',       'value'=>$stats['drafts'],      'icon'=>'file-pen',    'bg'=>'bg-gray-100',  'text'=>'text-gray-800'],
        ['label'=>'Active Polls', 'value'=>$stats['active_polls'],'icon'=>'bar-chart-2', 'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
    ] as $s)
    <div class="lmt-stat">
        <div>
            <p class="lmt-stat-label">{{ $s['label'] }}</p>
            <p class="lmt-stat-value text-2xl">{{ $s['value'] }}</p>
        </div>
        <div class="lmt-stat-icon {{ $s['bg'] }} {{ $s['text'] }}">
            <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<div class="flex items-center gap-1 mb-6 border-b border-gray-200">
    @foreach(['announcements'=>' Announcements','polls'=>' Polls'] as $t=>$label)
    <a href="{{ route('admin.announcements.index', $tenant) }}?tab={{ $t }}"
       class="px-5 py-2.5 text-sm font-semibold border-b-2 transition-all -mb-px whitespace-nowrap
              {{ $tab === $t ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-800 hover:text-gray-800' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- ===== ANNOUNCEMENTS TAB ===== --}}
@if($tab === 'announcements')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    {{-- Status filters --}}
    <div class="flex items-center gap-2">
        @foreach(['all'=>'All','published'=>'Published','scheduled'=>'Scheduled','draft'=>'Drafts','archived'=>'Archived'] as $val=>$label)
        <a href="{{ route('admin.announcements.index', $tenant) }}?tab=announcements&status={{ $val }}"
           class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all
                  {{ $status === $val ? 'lmt-gradient-bg text-white' : 'bg-white border border-gray-200 text-gray-800 hover:border-brand-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <button onclick="openModal('add-announcement-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Announcement
    </button>
</div>

<div class="space-y-4">
    @forelse($announcements as $ann)
    @php
    $priorityConfig = [
        'urgent' => ['bar'=>'bg-red-500',   'badge'=>'bg-red-100 text-red-700',     'icon'=>'alert-octagon'],
        'high'   => ['bar'=>'bg-amber-500', 'badge'=>'bg-amber-100 text-amber-700', 'icon'=>'alert-triangle'],
        'normal' => ['bar'=>'bg-brand-500', 'badge'=>'bg-brand-100 text-brand-700', 'icon'=>'megaphone'],
        'low'    => ['bar'=>'bg-gray-400',  'badge'=>'bg-gray-100 text-gray-800',   'icon'=>'info'],
    ];
    $pc = $priorityConfig[$ann->priority] ?? $priorityConfig['normal'];
    $statusColors = ['published'=>'lmt-badge-green','scheduled'=>'lmt-badge-amber','draft'=>'lmt-badge-gray','archived'=>'lmt-badge-gray'];
    @endphp

    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-sm transition-shadow">
        {{-- Priority bar --}}
        <div class="h-1 {{ $pc['bar'] }}"></div>

        <div class="p-5">
            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $pc['badge'] }}">
                    <i data-lucide="{{ $pc['icon'] }}" class="w-5 h-5"></i>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <h3 class="font-black text-gray-900 text-base">{{ $ann->title }}</h3>
                        <span class="{{ $statusColors[$ann->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                            {{ $ann->status }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $pc['badge'] }} capitalize">
                            {{ $ann->priority }}
                        </span>
                        @if($ann->requires_acknowledgment)
                        <span class="lmt-badge-amber text-xs">Requires Ack.</span>
                        @endif
                        @if($ann->show_on_login)
                        <span class="lmt-badge-brand text-xs">Login Popup</span>
                        @endif
                    </div>

                    <p class="text-sm text-gray-800 line-clamp-2 mb-3">
                        {{ strip_tags($ann->content) }}
                    </p>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-800">
                        <span class="flex items-center gap-1">
                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                            {{ $ann->creator->name ?? 'Admin' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            {{ ucfirst($ann->audience) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            {{ $ann->views_count }} views · {{ $ann->read_count }} reads
                        </span>
                        @if($ann->publish_at)
                        <span class="flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            {{ $ann->publish_at->format('M j, Y') }}
                        </span>
                        @endif
                        @if($ann->expires_at)
                        <span class="flex items-center gap-1 {{ $ann->expires_at->isPast() ? 'text-red-600' : '' }}">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            Exp {{ $ann->expires_at->format('M j, Y') }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="openEditModal({{ $ann->id }}, {{ json_encode(['title'=>$ann->title,'content'=>strip_tags($ann->content),'priority'=>$ann->priority,'status'=>$ann->status,'expires_at'=>$ann->expires_at?->toDateString()]) }})"
                            class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </button>
                    <form action="{{ route('admin.announcements.destroy', [$tenant, $ann->id]) }}"
                          method="POST" onsubmit="return confirm('Delete this announcement?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Banner image preview --}}
            @if($ann->banner_image)
            <div class="mt-3">
                <img src="{{ asset('storage/'.$ann->banner_image) }}"
                     class="w-full h-32 object-cover rounded-xl"/>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16">
        <i data-lucide="megaphone" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
        <p class="font-black text-gray-800 text-lg">No announcements yet</p>
        <p class="text-sm text-gray-800 mt-1 mb-5">Share news and updates with your team</p>
        <button onclick="openModal('add-announcement-modal')"
                class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Announcement
        </button>
    </div>
    @endforelse
</div>
@if($announcements->hasPages())
<div class="mt-5">{{ $announcements->links() }}</div>
@endif

{{-- ===== POLLS TAB ===== --}}
@elseif($tab === 'polls')

<div class="flex items-center justify-between mb-5">
    <h3 class="font-black text-gray-900">Employee Polls</h3>
    <button onclick="openModal('add-poll-modal')" class="lmt-btn-primary lmt-btn-sm">
        <i data-lucide="plus" class="w-4 h-4"></i>
        New Poll
    </button>
</div>

<div class="grid md:grid-cols-2 gap-5">
    @forelse($polls as $poll)
    @php
    $results    = $poll->results;
    $totalVotes = $poll->total_votes;
    $statusColors = ['active'=>'lmt-badge-green','draft'=>'lmt-badge-gray','closed'=>'lmt-badge-gray'];
    @endphp
    <div class="lmt-card">
        {{-- Poll header --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <span class="{{ $statusColors[$poll->status] ?? 'lmt-badge-gray' }} text-xs capitalize">
                        {{ $poll->status }}
                    </span>
                    <span class="lmt-badge-gray text-xs capitalize">
                        {{ str_replace('_',' ', $poll->type) }}
                    </span>
                    @if($poll->is_anonymous)
                    <span class="lmt-badge-brand text-xs">Anonymous</span>
                    @endif
                </div>
                <h3 class="font-black text-gray-900 leading-snug">{{ $poll->question }}</h3>
                @if($poll->description)
                <p class="text-xs text-gray-800 mt-1">{{ $poll->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-1 ml-3 flex-shrink-0">
                @if($poll->status === 'active')
                <form action="{{ route('admin.announcements.polls.close', [$tenant, $poll->id]) }}"
                      method="POST" onsubmit="return confirm('Close this poll?')">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-amber-500 hover:text-white flex items-center justify-center transition-colors"
                            title="Close Poll">
                        <i data-lucide="square" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
                @endif
                <form action="{{ route('admin.announcements.polls.destroy', [$tenant, $poll->id]) }}"
                      method="POST" onsubmit="return confirm('Delete this poll?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Results bars --}}
        <div class="space-y-2.5 mb-4">
            @foreach($results as $result)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-800 font-medium truncate flex-1 mr-2">{{ $result['option'] }}</span>
                    <span class="text-xs font-bold text-gray-900 flex-shrink-0">
                        {{ $result['votes'] }} ({{ $result['percent'] }}%)
                    </span>
                </div>
                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full lmt-gradient-bg rounded-full transition-all duration-500"
                         style="width:{{ $result['percent'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs text-gray-800">
            <span class="flex items-center gap-1">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                {{ $totalVotes }} vote{{ $totalVotes !== 1 ? 's' : '' }}
            </span>
            @if($poll->ends_at)
            <span class="flex items-center gap-1 {{ $poll->ends_at->isPast() ? 'text-red-600' : '' }}">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                {{ $poll->ends_at->isPast() ? 'Ended' : 'Ends' }} {{ $poll->ends_at->format('M j, Y') }}
            </span>
            @endif

            {{-- Vote form (if active and not voted) --}}
            @if($poll->is_active && ! $poll->votes()->where('user_id', auth()->id())->exists())
            <button onclick="openVoteModal({{ $poll->id }}, {{ json_encode($poll->options) }}, '{{ addslashes($poll->question) }}', '{{ $poll->type }}')"
                    class="lmt-btn-primary lmt-btn-sm">
                Vote
            </button>
            @elseif($poll->votes()->where('user_id', auth()->id())->exists())
            <span class="text-emerald-600 font-semibold flex items-center gap-1">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Voted
            </span>
            @endif
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-14 md:col-span-2">
        <i data-lucide="bar-chart-2" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
        <p class="font-black text-gray-800">No polls yet</p>
        <button onclick="openModal('add-poll-modal')"
                class="lmt-btn-primary lmt-btn-sm inline-flex mt-4">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Poll
        </button>
    </div>
    @endforelse
</div>
@endif

{{-- ============================================================
     MODALS
============================================================ --}}

{{-- Add Announcement --}}
<div id="add-announcement-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">New Announcement</h3>
            <button onclick="closeModal('add-announcement-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.announcements.store', $tenant) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input"
                       placeholder="e.g. Office Closed on Friday"/>
            </div>
            <div>
                <label class="lmt-label">Content <span class="text-red-500">*</span></label>
                <textarea name="content" required class="lmt-textarea" rows="5"
                          placeholder="Announcement details…"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}" {{ $v==='normal'?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Audience <span class="text-red-500">*</span></label>
                    <select name="audience" required class="lmt-select">
                        @foreach(['all'=>'All Employees','department'=>'By Department','location'=>'By Location','role'=>'By Role','specific'=>'Specific'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="lmt-select">
                        <option value="draft">Draft</option>
                        <option value="published">Publish Now</option>
                        <option value="scheduled">Schedule</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Publish At</label>
                    <input type="datetime-local" name="publish_at" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" class="lmt-input"/>
                </div>
            </div>
            <div>
                <label class="lmt-label">Banner Image</label>
                <input type="file" name="banner_image" accept="image/*"
                       class="lmt-input py-2 text-sm"/>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="requires_acknowledgment" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-800">Requires Acknowledgment</span>
                </label>
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="show_on_login" value="1" class="w-4 h-4 rounded"/>
                    <span class="text-sm font-medium text-gray-800">Show Popup on Login</span>
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Announcement</button>
                <button type="button" onclick="closeModal('add-announcement-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Announcement --}}
<div id="edit-announcement-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Edit Announcement</h3>
            <button onclick="closeModal('edit-announcement-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="edit-ann-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="lmt-label">Title</label>
                <input type="text" name="title" id="edit-ann-title" required class="lmt-input"/>
            </div>
            <div>
                <label class="lmt-label">Content</label>
                <textarea name="content" id="edit-ann-content" required class="lmt-textarea" rows="4"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Priority</label>
                    <select name="priority" id="edit-ann-priority" required class="lmt-select">
                        @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="status" id="edit-ann-status" required class="lmt-select">
                        @foreach(['draft'=>'Draft','published'=>'Published','scheduled'=>'Scheduled','archived'=>'Archived'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Expires At</label>
                    <input type="date" name="expires_at" id="edit-ann-expires" class="lmt-input"/>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-announcement-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Poll --}}
<div id="add-poll-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Create Poll</h3>
            <button onclick="closeModal('add-poll-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.announcements.polls.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Question <span class="text-red-500">*</span></label>
                <input type="text" name="question" required class="lmt-input"
                       placeholder="e.g. What day should we have the team lunch?"/>
            </div>
            <div>
                <label class="lmt-label">Description</label>
                <input type="text" name="description" class="lmt-input"
                       placeholder="Optional context…"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="lmt-select">
                        @foreach(['single_choice'=>'Single Choice','multiple_choice'=>'Multiple Choice','rating'=>'Rating'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="lmt-select">
                        <option value="active">Launch Now</option>
                        <option value="draft">Save as Draft</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Ends At</label>
                    <input type="datetime-local" name="ends_at" class="lmt-input"/>
                </div>
            </div>

            {{-- Dynamic options --}}
            <div>
                <label class="lmt-label">Options <span class="text-red-500">*</span> (min 2)</label>
                <div id="poll-options" class="space-y-2">
                    <div class="flex gap-2">
                        <input type="text" name="options[]" required class="lmt-input flex-1" placeholder="Option 1"/>
                        <button type="button" onclick="removeOption(this)"
                                class="w-9 h-9 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-100 hover:text-red-500 flex items-center justify-center transition-colors flex-shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="options[]" required class="lmt-input flex-1" placeholder="Option 2"/>
                        <button type="button" onclick="removeOption(this)"
                                class="w-9 h-9 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-100 hover:text-red-500 flex items-center justify-center transition-colors flex-shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
                <button type="button" onclick="addOption()"
                        class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1 transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Option
                </button>
            </div>

            <label class="flex items-center gap-2.5 cursor-pointer p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <input type="checkbox" name="is_anonymous" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-800">Anonymous Voting</span>
            </label>

            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Create Poll</button>
                <button type="button" onclick="closeModal('add-poll-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Vote Modal --}}
<div id="vote-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-black text-gray-900">Cast Your Vote</h3>
            <button onclick="closeModal('vote-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <p class="text-sm text-gray-800 mb-5" id="vote-question"></p>
        <form id="vote-form" method="POST" class="space-y-4">
            @csrf
            <div id="vote-options" class="space-y-2"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Submit Vote</button>
                <button type="button" onclick="closeModal('vote-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
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

// Edit announcement
function openEditModal(id, data) {
    document.getElementById('edit-ann-form').action = `/t/{{ $tenant }}/admin/announcements/${id}`;
    document.getElementById('edit-ann-title').value    = data.title;
    document.getElementById('edit-ann-content').value  = data.content;
    document.getElementById('edit-ann-priority').value = data.priority;
    document.getElementById('edit-ann-status').value   = data.status;
    document.getElementById('edit-ann-expires').value  = data.expires_at ?? '';
    openModal('edit-announcement-modal');
}

// Poll options
let optionCount = 2;
function addOption() {
    optionCount++;
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="options[]" required class="lmt-input flex-1" placeholder="Option ${optionCount}"/>
        <button type="button" onclick="removeOption(this)"
                class="w-9 h-9 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-100 hover:text-red-500 flex items-center justify-center transition-colors flex-shrink-0">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>`;
    document.getElementById('poll-options').appendChild(div);
    if (window.lucide) lucide.createIcons();
}

function removeOption(btn) {
    const rows = document.querySelectorAll('#poll-options > div');
    if (rows.length <= 2) return;
    btn.closest('div').remove();
}

// Vote modal
function openVoteModal(pollId, options, question, type) {
    document.getElementById('vote-form').action = `/t/{{ $tenant }}/admin/announcements/polls/${pollId}/vote`;
    document.getElementById('vote-question').textContent = question;

    const inputType = type === 'multiple_choice' ? 'checkbox' : 'radio';
    const container = document.getElementById('vote-options');
    container.innerHTML = '';

    options.forEach((opt, i) => {
        const label = document.createElement('label');
        label.className = 'flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:bg-brand-50 hover:border-brand-300 cursor-pointer transition-all';
        label.innerHTML = `
            <input type="${inputType}" name="selected_options[]" value="${i}"
                   class="w-4 h-4 rounded"/>
            <span class="text-sm font-medium text-gray-800">${opt}</span>`;
        container.appendChild(label);
    });

    openModal('vote-modal');
}

// Close on backdrop click
['add-announcement-modal','edit-announcement-modal','add-poll-modal','vote-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endpush