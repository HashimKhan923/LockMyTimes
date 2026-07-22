<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectMemberResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskListResource;
use App\Http\Resources\TaskResource;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMember;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\TaskList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON mirror of Employee/ProjectController — same membership/task-based
 * authorization, same per-project counters.
 */
class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $status = $request->get('status', 'active');
        $search = trim((string) $request->get('q', ''));

        $memberProjectIds = ProjectMember::where('employee_id', $emp->id)->pluck('project_id');
        $taskProjectIds = Task::whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
            ->pluck('project_id')
            ->unique();
        $myProjectIds = $memberProjectIds->merge($taskProjectIds)->unique();

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

        $projects = $query->paginate(12);

        $projects->getCollection()->transform(function ($p) use ($emp) {
            $myTaskIdsInProject = Task::where('project_id', $p->id)
                ->whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
                ->pluck('id');

            $p->_my_total = $myTaskIdsInProject->count();
            $p->_my_open = $myTaskIdsInProject->isNotEmpty()
                ? Task::whereIn('id', $myTaskIdsInProject)->whereNotIn('status', ['done', 'cancelled'])->count()
                : 0;
            $p->_my_overdue = $myTaskIdsInProject->isNotEmpty()
                ? Task::whereIn('id', $myTaskIdsInProject)
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->count()
                : 0;

            $member = ProjectMember::where('project_id', $p->id)->where('employee_id', $emp->id)->first();
            $p->_my_role = $member?->role;

            return $p;
        });

        $counters = Project::whereIn('id', $myProjectIds)
            ->whereNotIn('status', ['archived'])
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'active') as active,
                SUM(status = 'planning') as planning,
                SUM(status = 'on_hold') as on_hold,
                SUM(status = 'completed') as completed
            ")
            ->first();

        return response()->json([
            'projects' => ProjectResource::collection($projects->items()),
            'pagination' => ['current_page' => $projects->currentPage(), 'last_page' => $projects->lastPage(), 'total' => $projects->total()],
            'counters' => [
                'total' => (int) $counters->total,
                'active' => (int) $counters->active,
                'planning' => (int) $counters->planning,
                'on_hold' => (int) $counters->on_hold,
                'completed' => (int) $counters->completed,
            ],
        ]);
    }

    public function board(Request $request, int $project): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $proj = Project::with(['manager', 'taskLists' => fn ($q) => $q->orderBy('sort_order')])->findOrFail($project);
        $this->assertProjectAccess($proj, $emp);

        $myMember = ProjectMember::where('project_id', $proj->id)->where('employee_id', $emp->id)->first();
        $isManager = $myMember && in_array($myMember->role, ['owner', 'manager']);

        $taskLists = TaskList::where('project_id', $proj->id)
            ->with(['tasks' => function ($q) {
                $q->whereNull('parent_task_id')->with(['assignees.employee'])->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        $members = ProjectMember::with('employee')->where('project_id', $proj->id)->get();

        $taskStats = [
            'done' => Task::where('project_id', $proj->id)->where('status', 'done')->count(),
            'in_progress' => Task::where('project_id', $proj->id)->where('status', 'in_progress')->count(),
            'overdue' => Task::where('project_id', $proj->id)
                ->whereNotIn('status', ['done', 'cancelled'])
                ->whereNotNull('due_date')->where('due_date', '<', now())->count(),
        ];

        return response()->json([
            'project' => new ProjectResource($proj),
            'is_manager' => $isManager,
            'task_lists' => TaskListResource::collection($taskLists),
            'members' => ProjectMemberResource::collection($members),
            'task_stats' => $taskStats,
        ]);
    }

    public function show(Request $request, int $project): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $proj = Project::with(['manager', 'taskLists' => fn ($q) => $q->orderBy('sort_order')])->findOrFail($project);
        $this->assertProjectAccess($proj, $emp);

        $myMember = ProjectMember::where('project_id', $proj->id)->where('employee_id', $emp->id)->first();

        $myTaskIds = Task::where('project_id', $proj->id)
            ->whereIn('id', TaskAssignee::where('employee_id', $emp->id)->pluck('task_id'))
            ->pluck('id');

        $myTasks = Task::with(['assignees.employee', 'taskList'])
            ->whereIn('id', $myTaskIds)
            ->orderByRaw("FIELD(status, 'in_progress','todo','in_review','on_hold','backlog','done','cancelled')")
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date')
            ->get();

        $projectStats = Task::where('project_id', $proj->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'done') as completed,
                SUM(status NOT IN ('done','cancelled') AND due_date < NOW() AND due_date IS NOT NULL) as overdue
            ")
            ->first();

        $myStats = Task::whereIn('id', $myTaskIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'todo') as todo,
                SUM(status = 'in_progress') as in_progress,
                SUM(status = 'in_review') as in_review,
                SUM(status = 'on_hold') as on_hold,
                SUM(status = 'done') as done,
                SUM(status NOT IN ('done','cancelled') AND due_date < NOW() AND due_date IS NOT NULL) as overdue
            ")
            ->first();

        $members = ProjectMember::with('employee')->where('project_id', $proj->id)->get();

        return response()->json([
            'project' => new ProjectResource($proj),
            'my_role' => $myMember?->role,
            'my_tasks' => TaskResource::collection($myTasks),
            'project_stats' => [
                'total' => (int) $projectStats->total,
                'completed' => (int) $projectStats->completed,
                'overdue' => (int) $projectStats->overdue,
            ],
            'my_stats' => [
                'total' => (int) $myStats->total,
                'todo' => (int) $myStats->todo,
                'in_progress' => (int) $myStats->in_progress,
                'in_review' => (int) $myStats->in_review,
                'on_hold' => (int) $myStats->on_hold,
                'done' => (int) $myStats->done,
                'overdue' => (int) $myStats->overdue,
            ],
            'members' => ProjectMemberResource::collection($members),
        ]);
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }

    protected function assertProjectAccess(Project $project, $employee): void
    {
        $isMember = ProjectMember::where('project_id', $project->id)->where('employee_id', $employee->id)->exists();
        $hasTasks = Task::where('project_id', $project->id)
            ->whereIn('id', TaskAssignee::where('employee_id', $employee->id)->pluck('task_id'))
            ->exists();

        abort_unless($isMember || $hasTasks, 403, 'You do not have access to this project.');
    }
}
