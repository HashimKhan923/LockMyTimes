<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMember;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /* ================================================================
     | INDEX — Projects I'm a member of (or have tasks in)
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $status = $request->get('status', 'active');
        $search = trim((string) $request->get('q', ''));

        // Projects where I'm a member
        $memberProjectIds = ProjectMember::where('employee_id', $emp->id)
            ->pluck('project_id');

        // Projects where I have tasks assigned (even without explicit membership)
        $taskProjectIds = Task::whereIn('id',
                TaskAssignee::where('employee_id', $emp->id)->pluck('task_id')
            )
            ->pluck('project_id')
            ->unique();

        $myProjectIds = $memberProjectIds->merge($taskProjectIds)->unique();

        // Build query
        $query = Project::with('manager')
            ->whereIn('id', $myProjectIds)
            ->whereNotIn('status', ['archived'])
            ->orderByRaw("FIELD(status, 'active','planning','on_hold','completed','cancelled')")
            ->orderBy('name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->paginate(12)->withQueryString();

        // Add per-project task counts for the current user
        $projects->getCollection()->transform(function ($p) use ($emp) {
            $myTaskIdsInProject = Task::where('project_id', $p->id)
                ->whereIn('id',
                    TaskAssignee::where('employee_id', $emp->id)->pluck('task_id')
                )
                ->pluck('id');

            $p->_my_total      = $myTaskIdsInProject->count();
            $p->_my_open       = $myTaskIdsInProject->isNotEmpty()
                ? Task::whereIn('id', $myTaskIdsInProject)
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->count()
                : 0;
            $p->_my_overdue    = $myTaskIdsInProject->isNotEmpty()
                ? Task::whereIn('id', $myTaskIdsInProject)
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->count()
                : 0;

            // Get my role on this project
            $member = ProjectMember::where('project_id', $p->id)
                ->where('employee_id', $emp->id)
                ->first();
            $p->_my_role = $member?->role;

            return $p;
        });

        // Counters
        $counters = Project::whereIn('id', $myProjectIds)
            ->whereNotIn('status', ['archived'])
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'active')    as active,
                SUM(status = 'planning')  as planning,
                SUM(status = 'on_hold')   as on_hold,
                SUM(status = 'completed') as completed
            ")
            ->first();

        return view('employee.projects.index', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'projects'   => $projects,
            'counters'   => $counters,
            'status'     => $status,
            'search'     => $search,
        ]);
    }

    /* ================================================================
     | BOARD — Kanban board for a project
     |================================================================*/
    public function board(string $tenant, int $project)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $proj = Project::with(['manager', 'taskLists' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($project);

        $isMember = ProjectMember::where('project_id', $proj->id)->where('employee_id', $emp->id)->exists();
        $hasTasks = Task::where('project_id', $proj->id)
            ->whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
            ->exists();
        abort_unless($isMember || $hasTasks, 403);

        $myMember  = ProjectMember::where('project_id', $proj->id)->where('employee_id', $emp->id)->first();
        $isManager = $myMember && in_array($myMember->role, ['owner', 'manager']);

        // All task lists with ALL tasks and their assignees
        $taskLists = TaskList::where('project_id', $proj->id)
            ->with(['tasks' => function ($q) {
                $q->whereNull('parent_task_id')
                  ->with(['assignees.employee'])
                  ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        // IDs of tasks assigned to current employee (used in JS to enable drag)
        $myTaskIds = TaskAssignee::where('employee_id', $emp->id)
            ->whereIn('task_id', Task::where('project_id', $proj->id)->pluck('id'))
            ->pluck('task_id')
            ->flip(); // use as set for O(1) lookup

        // Members available for assignee picker (managers only need this)
        $members = ProjectMember::with('employee')->where('project_id', $proj->id)->get();

        $taskStats = [
            'done'        => Task::where('project_id', $proj->id)->where('status', 'done')->count(),
            'in_progress' => Task::where('project_id', $proj->id)->where('status', 'in_progress')->count(),
            'overdue'     => Task::where('project_id', $proj->id)
                                 ->whereNotIn('status', ['done','cancelled'])
                                 ->whereNotNull('due_date')->where('due_date', '<', now())->count(),
        ];

        return view('employee.projects.board', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'project'    => $proj,
            'myMember'   => $myMember,
            'isManager'  => $isManager,
            'taskLists'  => $taskLists,
            'myTaskIds'  => $myTaskIds,
            'members'    => $members,
            'taskStats'  => $taskStats,
        ]);
    }

    /* ================================================================
     | SHOW — Project detail (focused on my tasks within it)
     |================================================================*/
    public function show(string $tenant, int $project)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $proj = Project::with(['manager', 'taskLists' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($project);

        // Authorization — must be a member OR have task assignments
        $isMember = ProjectMember::where('project_id', $proj->id)
            ->where('employee_id', $emp->id)
            ->exists();

        $hasTasks = Task::where('project_id', $proj->id)
            ->whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
            ->exists();

        abort_unless($isMember || $hasTasks, 403, 'You do not have access to this project.');

        // My role
        $myMember = ProjectMember::where('project_id', $proj->id)
            ->where('employee_id', $emp->id)
            ->first();

        // My tasks in this project — grouped by task list (kanban-style view)
        $myTaskIds = Task::where('project_id', $proj->id)
            ->whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
            ->pluck('id');

        $myTasks = Task::with(['assignees.employee', 'taskList'])
            ->whereIn('id', $myTaskIds)
            ->orderByRaw("FIELD(status, 'in_progress','todo','in_review','on_hold','backlog','done','cancelled')")
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date')
            ->get();

        // Group by task list for display
        $tasksByList = $myTasks->groupBy('task_list_id');

        // Overall project stats
        $projectStats = Task::where('project_id', $proj->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'done') as completed,
                SUM(status NOT IN ('done','cancelled') AND due_date < NOW() AND due_date IS NOT NULL) as overdue
            ")
            ->first();

        // My stats within project
        $myStats = Task::whereIn('id', $myTaskIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'todo')         as todo,
                SUM(status = 'in_progress')  as in_progress,
                SUM(status = 'in_review')    as in_review,
                SUM(status = 'on_hold')      as on_hold,
                SUM(status = 'done')         as done,
                SUM(status NOT IN ('done','cancelled') AND due_date < NOW() AND due_date IS NOT NULL) as overdue
            ")
            ->first();

        // Team members on this project
        $members = ProjectMember::with('employee')
            ->where('project_id', $proj->id)
            ->get();

        return view('employee.projects.show', [
            'tenantSlug'    => $tenant,
            'emp'           => $emp,
            'project'       => $proj,
            'myMember'      => $myMember,
            'myTasks'       => $myTasks,
            'tasksByList'   => $tasksByList,
            'projectStats'  => $projectStats,
            'myStats'       => $myStats,
            'members'       => $members,
        ]);
    }
}