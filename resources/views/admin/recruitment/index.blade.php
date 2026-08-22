@extends('layouts.admin')
@section('title','Recruitment')
@section('page-title','Recruitment & ATS')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Active Jobs',       'value'=>$stats['active_jobs'],      'icon'=>'briefcase',    'bg'=>'bg-brand-50',  'text'=>'text-brand-600'],
        ['label'=>'Total Applicants',  'value'=>$stats['total_apps'],       'icon'=>'users',        'bg'=>'bg-purple-50', 'text'=>'text-purple-600'],
        ['label'=>'In Pipeline',       'value'=>$stats['in_progress'],      'icon'=>'git-branch',   'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
        ['label'=>'Hired This Month',  'value'=>$stats['hired_month'],      'icon'=>'user-check',   'bg'=>'bg-emerald-50','text'=>'text-emerald-600'],
        ['label'=>'Interviews (Week)', 'value'=>$stats['interviews_week'],  'icon'=>'calendar',     'bg'=>'bg-blue-50',   'text'=>'text-blue-600'],
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

{{-- Toolbar --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-2 flex-wrap">
        @foreach(['all'=>'All','published'=>'Active','draft'=>'Draft','paused'=>'Paused','closed'=>'Closed','filled'=>'Filled'] as $val=>$label)
        <a href="{{ route('admin.recruitment.index', $tenant) }}?status={{ $val }}"
           class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all
                  {{ $status === $val
                      ? 'lmt-gradient-bg text-white'
                      : 'bg-white border border-gray-200 text-gray-600 hover:border-brand-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        @include('exports.buttons', ['route' => 'admin.recruitment.export', 'params' => [$tenant]])
        <button onclick="openModal('add-job-modal')" class="lmt-btn-primary lmt-btn-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Post a Job
        </button>
    </div>
</div>

{{-- Job Cards --}}
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($jobs as $job)
    @php
    $statusConfig = [
        'published' => ['badge'=>'bg-emerald-100 text-emerald-700', 'dot'=>'bg-emerald-500'],
        'draft'     => ['badge'=>'bg-gray-100 text-gray-600',       'dot'=>'bg-gray-400'],
        'paused'    => ['badge'=>'bg-amber-100 text-amber-700',     'dot'=>'bg-amber-500'],
        'closed'    => ['badge'=>'bg-red-100 text-red-700',         'dot'=>'bg-red-500'],
        'filled'    => ['badge'=>'bg-brand-100 text-brand-700',     'dot'=>'bg-brand-500'],
    ];
    $sc = $statusConfig[$job->status] ?? $statusConfig['draft'];

    $typeLabels = ['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern','temporary'=>'Temporary'];
    $modeIcons  = ['on_site'=>'building-2','remote'=>'wifi','hybrid'=>'git-merge'];
    @endphp

    <div class="lmt-card hover:shadow-md transition-shadow">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $sc['badge'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                        {{ ucfirst($job->status) }}
                    </span>
                    <span class="text-xs text-gray-800 capitalize">
                        {{ str_replace('_',' ', $job->employment_type) }}
                    </span>
                </div>
                <h3 class="font-black text-gray-900 text-base leading-tight">{{ $job->title }}</h3>
                <p class="text-xs text-gray-800 mt-1">
                    @if($job->department) {{ $job->department->name }} @endif
                    @if($job->location) · {{ $job->location->name }} @endif
                </p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center flex-shrink-0 ml-3">
                <i data-lucide="{{ $modeIcons[$job->work_mode] ?? 'building-2' }}" class="w-5 h-5 text-brand-600"></i>
            </div>
        </div>

        {{-- Tags --}}
        <div class="flex flex-wrap gap-1.5 mb-4">
            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-lg capitalize font-medium">
                {{ str_replace('_',' ', $job->work_mode) }}
            </span>
            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-lg capitalize font-medium">
                {{ str_replace('_',' ', $job->experience_level) }}
            </span>
            @if($job->show_salary && ($job->salary_min || $job->salary_max))
            <span class="px-2 py-1 bg-emerald-50 text-emerald-700 text-xs rounded-lg font-medium">
                {{ $job->salary_range }}
            </span>
            @endif
            @if($job->openings > 1)
            <span class="px-2 py-1 bg-amber-50 text-amber-700 text-xs rounded-lg font-medium">
                {{ $job->openings }} openings
            </span>
            @endif
        </div>

        {{-- Applicants progress --}}
        <div class="flex items-center justify-between text-xs mb-4">
            <div class="flex items-center gap-1.5 text-gray-800">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                <span><strong class="text-gray-900">{{ $job->candidates_count }}</strong> applicants</span>
            </div>
            @if($job->closing_date)
            <span class="{{ $job->is_closing ? 'text-red-500 font-semibold' : 'text-gray-800' }}">
                Closes {{ $job->closing_date->format('M j, Y') }}
            </span>
            @endif
        </div>

        @if($job->hiringManager)
        <div class="flex items-center gap-2 pt-3 border-t border-gray-100 mb-4">
            <div class="w-6 h-6 rounded-full lmt-gradient-bg flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ substr($job->hiringManager->first_name, 0, 1) }}
            </div>
            <span class="text-xs text-gray-800 truncate">{{ $job->hiringManager->full_name }}</span>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.recruitment.show', [$tenant, $job->id]) }}"
               class="flex-1 lmt-btn-primary lmt-btn-sm text-center">
                <i data-lucide="layout-kanban" class="w-3.5 h-3.5"></i>
                Pipeline
            </a>
            <button onclick="openEditJob({{ $job->id }}, {{ json_encode($job) }})"
                    class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-brand-50 hover:text-brand-600 flex items-center justify-center transition-colors">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
            </button>
            <form action="{{ route('admin.recruitment.destroy', [$tenant, $job->id]) }}"
                  method="POST" onsubmit="return confirm('Delete this job posting?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-800 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="lmt-card text-center py-16 md:col-span-3">
        <i data-lucide="briefcase" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
        <p class="font-black text-gray-800 text-lg">No job postings yet</p>
        <p class="text-sm text-gray-800 mt-1 mb-5">Create your first job posting to start recruiting</p>
        <button onclick="openModal('add-job-modal')" class="lmt-btn-primary lmt-btn-sm inline-flex">
            <i data-lucide="plus" class="w-4 h-4"></i> Post a Job
        </button>
    </div>
    @endforelse
</div>

@if($jobs->hasPages())
<div class="mt-5">{{ $jobs->links() }}</div>
@endif

{{-- ============================================================
     ADD JOB MODAL
============================================================ --}}
<div id="add-job-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Post a New Job</h3>
            <button onclick="closeModal('add-job-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('admin.recruitment.store', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="lmt-label">Job Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="lmt-input" placeholder="e.g. Senior Software Engineer"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Department</label>
                    <select name="department_id" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Location</label>
                    <select name="location_id" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Employment Type <span class="text-red-500">*</span></label>
                    <select name="employment_type" required class="lmt-select">
                        @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern','temporary'=>'Temporary'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Work Mode <span class="text-red-500">*</span></label>
                    <select name="work_mode" required class="lmt-select">
                        @foreach(['on_site'=>'On Site','remote'=>'Remote','hybrid'=>'Hybrid'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Experience Level <span class="text-red-500">*</span></label>
                    <select name="experience_level" required class="lmt-select">
                        @foreach(['entry'=>'Entry Level','mid'=>'Mid Level','senior'=>'Senior','lead'=>'Lead','executive'=>'Executive'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Openings <span class="text-red-500">*</span></label>
                    <input type="number" name="openings" required min="1" value="1" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Salary Min ($)</label>
                    <input type="number" name="salary_min" step="1000" min="0" class="lmt-input" placeholder="50000"/>
                </div>
                <div>
                    <label class="lmt-label">Salary Max ($)</label>
                    <input type="number" name="salary_max" step="1000" min="0" class="lmt-input" placeholder="80000"/>
                </div>
                <div>
                    <label class="lmt-label">Closing Date</label>
                    <input type="date" name="closing_date" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="lmt-select">
                        <option value="draft">Draft</option>
                        <option value="published">Publish Now</option>
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Hiring Manager</label>
                    <select name="hiring_manager_id" class="lmt-select">
                        <option value="">— Select —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="show_salary" value="1" class="w-4 h-4 rounded"/>
                <span class="text-sm font-medium text-gray-700">Show salary range publicly</span>
            </label>
            <div>
                <label class="lmt-label">Job Description <span class="text-red-500">*</span></label>
                <textarea name="description" required class="lmt-textarea" rows="4"
                          placeholder="Describe the role and responsibilities…"></textarea>
            </div>
            <div>
                <label class="lmt-label">Requirements</label>
                <textarea name="requirements" class="lmt-textarea" rows="3"
                          placeholder="Must-have skills and qualifications…"></textarea>
            </div>
            <div>
                <label class="lmt-label">Benefits</label>
                <textarea name="benefits" class="lmt-textarea" rows="2"
                          placeholder="What you offer — health insurance, 401k, remote work…"></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="lmt-btn-primary flex-1">Create Job Posting</button>
                <button type="button" onclick="closeModal('add-job-modal')"
                        class="lmt-btn-secondary flex-1">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT JOB MODAL --}}
<div id="edit-job-modal" class="lmt-modal-backdrop hidden">
    <div class="lmt-modal max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-gray-900">Edit Job Posting</h3>
            <button onclick="closeModal('edit-job-modal')"
                    class="w-8 h-8 rounded-lg text-gray-800 hover:bg-gray-100 flex items-center justify-center">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="edit-job-form" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="lmt-label">Title</label>
                <input type="text" name="title" id="edit-job-title" required class="lmt-input"/>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="lmt-label">Status</label>
                    <select name="status" id="edit-job-status" required class="lmt-select">
                        @foreach(['draft'=>'Draft','published'=>'Published','paused'=>'Paused','closed'=>'Closed','filled'=>'Filled'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Openings</label>
                    <input type="number" name="openings" id="edit-job-openings" min="1" class="lmt-input"/>
                </div>
                <div>
                    <label class="lmt-label">Employment Type</label>
                    <select name="employment_type" id="edit-job-type" required class="lmt-select">
                        @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern','temporary'=>'Temporary'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Work Mode</label>
                    <select name="work_mode" id="edit-job-mode" required class="lmt-select">
                        @foreach(['on_site'=>'On Site','remote'=>'Remote','hybrid'=>'Hybrid'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Experience Level</label>
                    <select name="experience_level" id="edit-job-exp" required class="lmt-select">
                        @foreach(['entry'=>'Entry','mid'=>'Mid','senior'=>'Senior','lead'=>'Lead','executive'=>'Executive'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lmt-label">Closing Date</label>
                    <input type="date" name="closing_date" id="edit-job-closing" class="lmt-input"/>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="lmt-btn-primary flex-1">Save Changes</button>
                <button type="button" onclick="closeModal('edit-job-modal')"
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

function openEditJob(id, job) {
    document.getElementById('edit-job-form').action = `/t/{{ $tenant }}/admin/recruitment/${id}`;
    document.getElementById('edit-job-title').value   = job.title;
    document.getElementById('edit-job-status').value  = job.status;
    document.getElementById('edit-job-openings').value= job.openings;
    document.getElementById('edit-job-type').value    = job.employment_type;
    document.getElementById('edit-job-mode').value    = job.work_mode;
    document.getElementById('edit-job-exp').value     = job.experience_level;
    document.getElementById('edit-job-closing').value = job.closing_date ?? '';
    openModal('edit-job-modal');
}
['add-job-modal','edit-job-modal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if(e.target===this) closeModal(id); });
});
</script>
@endpush