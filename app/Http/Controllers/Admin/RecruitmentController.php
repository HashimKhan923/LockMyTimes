<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Candidate;
use App\Models\Tenant\CandidateStageHistory;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Interview;
use App\Models\Tenant\JobPosting;
use App\Models\Tenant\Location;
use App\Models\Tenant\Position;
use App\Services\ExportService;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruitmentController extends Controller
{
    /* ================================================================
     | INDEX — job board overview
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $status = $request->get('status', 'all');

        $query = JobPosting::with(['department', 'hiringManager'])
            ->withCount('candidates')
            ->latest();

        if ($status !== 'all') $query->where('status', $status);

        $jobs       = $query->paginate(12)->withQueryString();
        $departments= Department::where('is_active', true)->orderBy('name')->get();
        $positions  = Position::where('is_active', true)->orderBy('title')->get();
        $locations  = Location::where('is_active', true)->orderBy('name')->get();
        $employees  = Employee::active()->orderBy('first_name')->get();

        $stats = [
            'active_jobs'  => JobPosting::where('status', 'published')->count(),
            'total_apps'   => Candidate::count(),
            'in_progress'  => Candidate::whereIn('stage', ['screening','interview','assessment','offer'])->count(),
            'hired_month'  => Candidate::where('stage','hired')
                                ->whereMonth('updated_at', now()->month)->count(),
            'interviews_week' => Interview::where('status','scheduled')
                                ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                                ->count(),
        ];

        return view('admin.recruitment.index', compact(
            'jobs', 'departments', 'positions', 'locations', 'employees',
            'stats', 'status', 'tenant'
        ));
    }

    /* ================================================================
     | SHOW JOB — pipeline view (Kanban per stage)
     |================================================================*/
    public function show(string $tenant, JobPosting $job)
    {
        $job->load(['department', 'hiringManager', 'location']);

        $stages = ['applied','screening','interview','assessment','offer','hired','rejected','withdrawn'];

        $candidates = Candidate::with(['interviews'])
            ->where('job_posting_id', $job->id)
            ->get()
            ->groupBy('stage');

        $stageCounts = collect($stages)->mapWithKeys(fn($s) => [
            $s => $candidates->get($s, collect())->count()
        ]);

        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.recruitment.show', compact(
            'job', 'candidates', 'stages', 'stageCounts', 'employees', 'tenant'
        ));
    }

    /* ================================================================
     | STORE JOB
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'department_id'    => 'nullable|exists:departments,id',
            'position_id'      => 'nullable|exists:positions,id',
            'location_id'      => 'nullable|exists:locations,id',
            'description'      => 'required|string',
            'requirements'     => 'nullable|string',
            'benefits'         => 'nullable|string',
            'employment_type'  => 'required|in:full_time,part_time,contract,intern,temporary',
            'work_mode'        => 'required|in:on_site,remote,hybrid',
            'experience_level' => 'required|in:entry,mid,senior,lead,executive',
            'salary_min'       => 'nullable|numeric|min:0',
            'salary_max'       => 'nullable|numeric|min:0',
            'show_salary'      => 'boolean',
            'openings'         => 'required|integer|min:1',
            'closing_date'     => 'nullable|date',
            'status'           => 'required|in:draft,published,paused,closed,filled',
            'hiring_manager_id'=> 'nullable|exists:employees,id',
        ]);

        $slug = Str::slug($data['title']);
        $base = $slug;
        $i = 1;
        while (JobPosting::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $data['slug'] = $slug;

        if ($data['status'] === 'published') {
            $data['posted_date'] = today()->toDateString();
        }

        JobPosting::create($data);
        return back()->with('success', 'Job posting created.');
    }

    /* ================================================================
     | UPDATE JOB
     |================================================================*/
    public function update(string $tenant, Request $request, JobPosting $job)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'status'           => 'required|in:draft,published,paused,closed,filled',
            'employment_type'  => 'required|in:full_time,part_time,contract,intern,temporary',
            'work_mode'        => 'required|in:on_site,remote,hybrid',
            'experience_level' => 'required|in:entry,mid,senior,lead,executive',
            'openings'         => 'required|integer|min:1',
            'closing_date'     => 'nullable|date',
            'salary_min'       => 'nullable|numeric',
            'salary_max'       => 'nullable|numeric',
            'show_salary'      => 'boolean',
        ]);

        if ($data['status'] === 'published' && ! $job->posted_date) {
            $data['posted_date'] = today()->toDateString();
        }

        $job->update($data);
        return back()->with('success', 'Job posting updated.');
    }

    /* ================================================================
     | DESTROY JOB
     |================================================================*/
    public function destroy(string $tenant, JobPosting $job)
    {
        $job->delete();
        return redirect()
            ->route('admin.recruitment.index', $tenant)
            ->with('success', 'Job posting deleted.');
    }

    /* ================================================================
     | CANDIDATE DETAIL
     |================================================================*/
    public function candidate(string $tenant, Candidate $candidate)
    {
        $candidate->load([
            'jobPosting.department',
            'interviews',
            'stageHistory.changedBy',
        ]);

        $employees = Employee::active()->orderBy('first_name')->get();

        return view('admin.recruitment.candidate', compact('candidate', 'employees', 'tenant'));
    }

    /* ================================================================
     | MOVE CANDIDATE STAGE
     |================================================================*/
    public function moveStage(string $tenant, Request $request, Candidate $candidate)
    {
        $request->validate([
            'stage'  => 'required|in:applied,screening,interview,assessment,offer,hired,rejected,withdrawn',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldStage = $candidate->stage;

        $candidate->update(['stage' => $request->stage]);

        CandidateStageHistory::create([
            'candidate_id' => $candidate->id,
            'from_stage'   => $oldStage,
            'to_stage'     => $request->stage,
            'changed_by'   => auth()->id(),
            'reason'       => $request->reason,
        ]);

        // If hired — update applications count
        if ($request->stage === 'hired') {
            $candidate->jobPosting->increment('applications_count');
        }

        $mailer = app(MailService::class);
        $candidate->load(['jobPosting.department']);
        if ($request->stage === 'hired') {
            $mailer->sendCandidateHired($candidate);
        } elseif ($request->stage === 'rejected') {
            $mailer->sendCandidateRejected($candidate);
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'stage' => $request->stage]);
        }

        return back()->with('success', "Candidate moved to " . ucfirst($request->stage) . " stage.");
    }

    /* ================================================================
     | UPDATE CANDIDATE RATING / NOTES
     |================================================================*/
    public function updateCandidate(string $tenant, Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'notes'  => 'nullable|string',
        ]);

        $candidate->update($data);
        return back()->with('success', 'Candidate updated.');
    }

    /* ================================================================
     | SCHEDULE INTERVIEW
     |================================================================*/
    public function scheduleInterview(string $tenant, Request $request, Candidate $candidate)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'type'             => 'required|in:phone,video,in_person,technical,panel,culture',
            'scheduled_at'     => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'meeting_url'      => 'nullable|url',
            'location'         => 'nullable|string|max:200',
            'interviewer_ids'  => 'nullable|array',
        ]);

        $interview = Interview::create(array_merge($data, [
            'candidate_id' => $candidate->id,
            'status'       => 'scheduled',
        ]));

        app(MailService::class)->sendInterviewScheduled($interview->load(['candidate.jobPosting', 'interviewer']));

        // Move to interview stage if not already
        if (! in_array($candidate->stage, ['interview','assessment','offer','hired'])) {
            $candidate->update(['stage' => 'interview']);
            CandidateStageHistory::create([
                'candidate_id' => $candidate->id,
                'from_stage'   => $candidate->stage,
                'to_stage'     => 'interview',
                'changed_by'   => auth()->id(),
            ]);
        }

        return back()->with('success', 'Interview scheduled.');
    }

    /* ================================================================
     | SUBMIT INTERVIEW FEEDBACK
     |================================================================*/
    public function interviewFeedback(string $tenant, Request $request, Interview $interview)
    {
        $data = $request->validate([
            'status'         => 'required|in:completed,cancelled,no_show',
            'rating'         => 'nullable|integer|min:1|max:5',
            'feedback'       => 'nullable|string',
            'recommendation' => 'nullable|in:strong_yes,yes,maybe,no,strong_no',
        ]);

        $interview->update($data);
        return back()->with('success', 'Interview feedback saved.');
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $candidates = Candidate::with(['jobPosting.department'])->latest()->get();

        $columns = ['Name', 'Email', 'Phone', 'Job Title', 'Department', 'Stage', 'Source', 'Applied On', 'Status'];

        $rows = $candidates->map(fn($c) => [
            $c->full_name ?? $c->first_name . ' ' . $c->last_name,
            $c->email ?? '-',
            $c->phone ?? '-',
            $c->jobPosting?->title ?? '-',
            $c->jobPosting?->department?->name ?? '-',
            ucfirst(str_replace('_', ' ', $c->current_stage ?? '-')),
            ucfirst($c->source ?? '-'),
            $c->created_at->format('Y-m-d'),
            ucfirst($c->status ?? 'active'),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Recruitment Report', $columns, $rows, 'recruitment-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'recruitment-'.now()->format('Y-m-d').'.xlsx');
    }
}