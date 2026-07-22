<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskAttachmentResource;
use App\Http\Resources\TaskChecklistResource;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMember;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskActivity;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\TaskAttachment;
use App\Models\Tenant\TaskChecklist;
use App\Models\Tenant\TaskComment;
use App\Models\Tenant\TaskList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON mirror of Employee/TaskController — same authorization rules
 * (assignee/creator/project-member), same status/progress auto-transition
 * logic, same activity logging.
 */
class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $filter = $request->get('filter', 'open');
        $priority = $request->get('priority');
        $projectId = $request->get('project');
        $search = trim((string) $request->get('q', ''));

        $assignedTaskIds = TaskAssignee::where('employee_id', $emp->id)->pluck('task_id');
        $createdByMe = Task::where('created_by', $request->user()->id)->pluck('id');
        $myTaskIds = $assignedTaskIds->merge($createdByMe)->unique();

        $query = Task::with(['project', 'taskList', 'assignees.employee'])
            ->whereIn('id', $myTaskIds)
            ->orderByRaw("FIELD(status, 'in_progress','todo','in_review','on_hold','backlog','done','cancelled')")
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date', 'asc')
            ->orderByDesc('id');

        switch ($filter) {
            case 'open':
                $query->whereNotIn('status', ['done', 'cancelled']);
                break;
            case 'overdue':
                $query->whereNotIn('status', ['done', 'cancelled'])->whereNotNull('due_date')->where('due_date', '<', now());
                break;
            case 'created':
                $query->whereIn('id', $createdByMe);
                break;
            case 'all':
                break;
            default:
                if (in_array($filter, ['todo', 'in_progress', 'in_review', 'on_hold', 'done', 'backlog'])) {
                    $query->where('status', $filter);
                }
                break;
        }

        if ($priority) $query->where('priority', $priority);
        if ($projectId) $query->where('project_id', $projectId);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('task_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->paginate(20);

        $counters = Task::whereIn('id', $myTaskIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status NOT IN ('done','cancelled')) as open_count,
                SUM(status = 'todo') as todo,
                SUM(status = 'in_progress') as in_progress,
                SUM(status = 'in_review') as in_review,
                SUM(status = 'on_hold') as on_hold,
                SUM(status = 'done') as done,
                SUM(status NOT IN ('done','cancelled') AND due_date IS NOT NULL AND due_date < NOW()) as overdue
            ")
            ->first();

        $myProjects = Project::whereIn('id', Task::whereIn('id', $myTaskIds)->pluck('project_id')->unique())
            ->orderBy('name')
            ->get();

        $todayDue = Task::whereIn('id', $myTaskIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', today())
            ->count();

        $thisWeekDue = Task::whereIn('id', $myTaskIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return response()->json([
            'tasks' => TaskResource::collection($tasks->items()),
            'pagination' => ['current_page' => $tasks->currentPage(), 'last_page' => $tasks->lastPage(), 'total' => $tasks->total()],
            'counters' => [
                'total' => (int) $counters->total,
                'open_count' => (int) $counters->open_count,
                'todo' => (int) $counters->todo,
                'in_progress' => (int) $counters->in_progress,
                'in_review' => (int) $counters->in_review,
                'on_hold' => (int) $counters->on_hold,
                'done' => (int) $counters->done,
                'overdue' => (int) $counters->overdue,
            ],
            'created_count' => $createdByMe->count(),
            'my_projects' => ProjectResource::collection($myProjects),
            'today_due' => $todayDue,
            'this_week_due' => $thisWeekDue,
        ]);
    }

    public function store(Request $request, int $projectId): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $project = Project::findOrFail($projectId);

        $member = ProjectMember::where('project_id', $project->id)->where('employee_id', $emp->id)->first();
        abort_unless($member && in_array($member->role, ['owner', 'manager']), 403, 'Only project managers can create tasks.');

        $data = $request->validate([
            'task_list_id' => 'required|exists:task_lists,id',
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'type' => 'required|in:task,bug,feature,epic,story,improvement,support',
            'priority' => 'required|in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:employees,id',
        ]);

        $list = TaskList::findOrFail($data['task_list_id']);
        $status = $this->statusForColumn($list->column_type);

        $task = Task::create([
            'project_id' => $project->id,
            'task_list_id' => $data['task_list_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'priority' => $data['priority'],
            'status' => $status,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0,
            'sort_order' => (Task::where('task_list_id', $data['task_list_id'])->max('sort_order') ?? 0) + 1,
            'created_by' => $request->user()->id,
            'progress' => 0,
        ]);

        foreach (array_values($data['assignee_ids'] ?? []) as $i => $empId) {
            TaskAssignee::create([
                'task_id' => $task->id,
                'employee_id' => $empId,
                'is_primary' => $i === 0,
                'assigned_by' => $request->user()->id,
            ]);
        }

        return response()->json(['success' => true, 'task' => new TaskResource($task->load('assignees.employee'))]);
    }

    public function move(Request $request, int $projectId, int $taskId): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $task = Task::where('project_id', $projectId)->findOrFail($taskId);

        $isAssigned = TaskAssignee::where('task_id', $task->id)->where('employee_id', $emp->id)->exists();
        $isManager = ProjectMember::where('project_id', $projectId)->where('employee_id', $emp->id)->whereIn('role', ['owner', 'manager'])->exists();
        abort_unless($isAssigned || $isManager, 403, 'You can only move tasks assigned to you.');

        $request->validate([
            'task_list_id' => 'required|exists:task_lists,id',
            'sort_order' => 'nullable|integer|min:0',
            'ordered_ids' => 'nullable|array',
        ]);

        $newList = TaskList::findOrFail($request->task_list_id);
        $newStatus = $this->statusForColumn($newList->column_type);

        $task->update([
            'task_list_id' => $request->task_list_id,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $newStatus,
            'completed_at' => $newList->is_closed_status ? ($task->completed_at ?? now()) : null,
        ]);

        if ($request->has('ordered_ids')) {
            foreach ($request->ordered_ids as $index => $tid) {
                Task::where('id', $tid)->update(['sort_order' => $index]);
            }
        }

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    public function show(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $t = Task::with([
            'project', 'taskList', 'assignees.employee',
            'checklists' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'comments' => fn ($q) => $q->with('user')->orderBy('created_at'),
            'attachments', 'parent', 'subtasks',
        ])->findOrFail($task);

        $isAssignee = $t->assignees->contains(fn ($a) => $a->employee_id === $emp->id);
        $isCreator = $t->created_by === $request->user()->id;
        $isProjectMember = $t->project
            ? DB::connection('tenant')->table('project_members')->where('project_id', $t->project_id)->where('employee_id', $emp->id)->exists()
            : false;

        abort_unless($isAssignee || $isCreator || $isProjectMember, 403, 'You do not have access to this task.');

        $checklistTotal = $t->checklists->count();
        $checklistDone = $t->checklists->where('is_completed', true)->count();

        return response()->json([
            'task' => new TaskResource($t),
            'can_edit' => $isAssignee || $isCreator,
            'checklist_total' => $checklistTotal,
            'checklist_done' => $checklistDone,
            'checklist_pct' => $checklistTotal > 0 ? round(($checklistDone / $checklistTotal) * 100) : 0,
        ]);
    }

    public function updateStatus(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $data = $request->validate(['status' => ['required', Rule::in(['todo', 'in_progress', 'in_review', 'on_hold', 'done'])]]);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp, $request);

        $oldStatus = $t->status;
        if ($oldStatus === $data['status']) {
            return response()->json(['success' => true, 'task' => new TaskResource($t), 'message' => 'Status unchanged.']);
        }

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'done') {
            $updates['completed_at'] = now();
            $updates['progress'] = 100;
        } elseif ($oldStatus === 'done') {
            $updates['completed_at'] = null;
            if ($t->progress >= 100) $updates['progress'] = 75;
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $t->update($updates);
            $this->logActivity($t, $request, 'status_changed', 'status', $oldStatus, $data['status']);
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Task status update failed (API): '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not update status.'], 422);
        }

        return response()->json(['success' => true, 'task' => new TaskResource($t->fresh()), 'message' => 'Status updated to '.str_replace('_', ' ', $data['status']).'.']);
    }

    public function updateProgress(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $data = $request->validate(['progress' => ['required', 'integer', 'min:0', 'max:100']]);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp, $request);

        $oldProgress = (int) $t->progress;
        if ($oldProgress === $data['progress']) {
            return response()->json(['success' => true, 'task' => new TaskResource($t), 'message' => 'Progress unchanged.']);
        }

        $updates = ['progress' => $data['progress']];
        if ($data['progress'] === 100 && $t->status !== 'done') {
            $updates['status'] = 'done';
            $updates['completed_at'] = now();
        } elseif ($data['progress'] > 0 && $data['progress'] < 100 && $t->status === 'todo') {
            $updates['status'] = 'in_progress';
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $t->update($updates);
            $this->logActivity($t, $request, 'progress_changed', 'progress', $oldProgress.'%', $data['progress'].'%');
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Task progress update failed (API): '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not update progress.'], 422);
        }

        return response()->json(['success' => true, 'task' => new TaskResource($t->fresh()), 'message' => "Progress updated to {$data['progress']}%."]);
    }

    public function update(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp, $request);

        $data = $request->validate([
            'title' => 'sometimes|string|max:500',
            'description' => 'sometimes|nullable|string',
            'due_date' => 'sometimes|nullable|date',
            'estimated_hours' => 'sometimes|nullable|numeric|min:0',
            'progress' => 'sometimes|integer|min:0|max:100',
            'status' => 'sometimes|in:backlog,todo,in_progress,in_review,on_hold,done,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
        ]);

        $t->update($data);

        return response()->json(['success' => true, 'task' => new TaskResource($t->load('assignees.employee'))]);
    }

    public function storeComment(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $data = $request->validate(['content' => ['required', 'string', 'min:1', 'max:5000']]);

        $t = Task::findOrFail($task);
        $this->assertCanComment($t, $emp);

        DB::connection('tenant')->beginTransaction();
        try {
            $comment = TaskComment::create([
                'task_id' => $t->id,
                'user_id' => $request->user()->id,
                'content' => $data['content'],
            ]);
            $t->increment('comments_count');
            $this->logActivity($t, $request, 'commented', 'comment', null, null);
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Task comment failed (API): '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not add comment.'], 422);
        }

        return response()->json(['success' => true, 'comment' => new \App\Http\Resources\TaskCommentResource($comment->load('user'))]);
    }

    public function storeChecklist(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp, $request);

        $data = $request->validate(['item' => 'required|string|max:500']);

        $item = TaskChecklist::create([
            'task_id' => $t->id,
            'item' => $data['item'],
            'sort_order' => (TaskChecklist::where('task_id', $t->id)->max('sort_order') ?? 0) + 1,
        ]);

        $t->update(['subtasks_count' => $t->checklists()->count()]);

        return response()->json(['success' => true, 'checklist' => new \App\Http\Resources\TaskChecklistResource($item)]);
    }

    public function toggleChecklist(Request $request, int $task, int $checklist): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp, $request);

        $item = TaskChecklist::where('task_id', $t->id)->findOrFail($checklist);

        $nowComplete = ! $item->is_completed;
        $item->update([
            'is_completed' => $nowComplete,
            'completed_at' => $nowComplete ? now() : null,
            'completed_by' => $nowComplete ? $request->user()->id : null,
        ]);

        $t->update([
            'completed_subtasks_count' => $t->checklists()->where('is_completed', true)->count(),
            'subtasks_count' => $t->checklists()->count(),
        ]);

        return response()->json(['success' => true, 'is_completed' => $nowComplete]);
    }

    public function storeAttachment(Request $request, int $task): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);

        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isCreator = $t->created_by === $request->user()->id;
        $isProjectMember = DB::connection('tenant')->table('project_members')->where('project_id', $t->project_id)->where('employee_id', $emp->id)->exists();
        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);

        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:20480',
        ]);

        $attachments = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("task-attachments/{$t->id}", 'public');
            $attachments[] = TaskAttachment::create([
                'task_id' => $t->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return response()->json(['success' => true, 'attachments' => \App\Http\Resources\TaskAttachmentResource::collection($attachments)]);
    }

    public function destroyAttachment(Request $request, int $task, int $attachment): JsonResponse
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);
        $att = TaskAttachment::where('task_id', $t->id)->findOrFail($attachment);

        $isUploader = $att->uploaded_by === $request->user()->id;
        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isManager = DB::connection('tenant')->table('project_members')
            ->where('project_id', $t->project_id)->where('employee_id', $emp->id)
            ->whereIn('role', ['owner', 'manager'])->exists();
        abort_unless($isUploader || $isAssignee || $isManager, 403);

        Storage::disk('public')->delete($att->file_path);
        $att->delete();

        return response()->json(['success' => true]);
    }

    public function attachment(Request $request, int $task, int $attachment): Response
    {
        $emp = $this->employeeOrFail($request);
        $t = Task::findOrFail($task);

        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isCreator = $t->created_by === $request->user()->id;
        $isProjectMember = DB::connection('tenant')->table('project_members')->where('project_id', $t->project_id)->where('employee_id', $emp->id)->exists();
        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);

        $att = TaskAttachment::where('task_id', $t->id)->findOrFail($attachment);
        abort_if(! $att->file_path, 404, 'No file attached.');

        $disk = Storage::disk('public');
        abort_if(! $disk->exists($att->file_path), 404, 'Attachment file is missing.');

        return $disk->download($att->file_path, $att->file_name);
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }

    protected function statusForColumn(string $columnType): string
    {
        return match ($columnType) {
            'backlog' => 'backlog', 'todo' => 'todo', 'in_progress' => 'in_progress',
            'review' => 'in_review', 'done' => 'done', 'cancelled' => 'cancelled',
            default => 'todo',
        };
    }

    protected function assertCanEdit(Task $task, $employee, Request $request): void
    {
        $isAssignee = TaskAssignee::where('task_id', $task->id)->where('employee_id', $employee->id)->exists();
        $isCreator = $task->created_by === $request->user()->id;

        abort_unless($isAssignee || $isCreator, 403, 'You can only update tasks assigned to you.');
    }

    protected function assertCanComment(Task $task, $employee): void
    {
        $isAssignee = TaskAssignee::where('task_id', $task->id)->where('employee_id', $employee->id)->exists();
        $isCreator = $task->created_by === request()->user()->id;
        $isProjectMember = DB::connection('tenant')->table('project_members')
            ->where('project_id', $task->project_id)->where('employee_id', $employee->id)->exists();

        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);
    }

    protected function logActivity(Task $task, Request $request, string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null): void
    {
        if (! Schema::connection('tenant')->hasTable('task_activities')) {
            return;
        }

        try {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'action' => $action,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Activity log failed (API): '.$e->getMessage());
        }
    }
}
