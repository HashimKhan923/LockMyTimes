<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskActivity;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\TaskAttachment;
use App\Models\Tenant\TaskChecklist;
use App\Models\Tenant\TaskComment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /* ================================================================
     | INDEX — My tasks across all projects
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        // Filters
        $filter   = $request->get('filter', 'open');        // open|todo|in_progress|in_review|on_hold|done|overdue|all|created
        $priority = $request->get('priority');              // low|normal|high|urgent
        $projectId= $request->get('project');               // optional project_id filter
        $search   = trim((string) $request->get('q', ''));

        // Base query — tasks where user is assignee OR creator (linked via user.id)
        $assignedTaskIds = TaskAssignee::where('employee_id', $emp->id)->pluck('task_id');
        $createdByMe     = Task::where('created_by', auth()->id())->pluck('id');
        $myTaskIds       = $assignedTaskIds->merge($createdByMe)->unique();

        $query = Task::with(['project', 'taskList', 'assignees.employee'])
            ->whereIn('id', $myTaskIds)
            ->orderByRaw("FIELD(status, 'in_progress','todo','in_review','on_hold','backlog','done','cancelled')")
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderBy('due_date', 'asc')
            ->orderByDesc('id');

        // Apply filters
        switch ($filter) {
            case 'open':
                $query->whereNotIn('status', ['done', 'cancelled']);
                break;
            case 'overdue':
                $query->whereNotIn('status', ['done', 'cancelled'])
                      ->whereNotNull('due_date')
                      ->where('due_date', '<', now());
                break;
            case 'created':
                $query->whereIn('id', $createdByMe);
                break;
            case 'all':
                // no status filter
                break;
            default:
                // specific status
                if (in_array($filter, ['todo','in_progress','in_review','on_hold','done','backlog'])) {
                    $query->where('status', $filter);
                }
                break;
        }

        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('task_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->paginate(20)->withQueryString();

        // Counters (filter chips)
        $counters = Task::whereIn('id', $myTaskIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status NOT IN ('done','cancelled')) as open_count,
                SUM(status = 'todo')        as todo,
                SUM(status = 'in_progress') as in_progress,
                SUM(status = 'in_review')   as in_review,
                SUM(status = 'on_hold')     as on_hold,
                SUM(status = 'done')        as done,
                SUM(
                    status NOT IN ('done','cancelled')
                    AND due_date IS NOT NULL
                    AND due_date < NOW()
                ) as overdue
            ")
            ->first();

        $createdCount = $createdByMe->count();

        // Distinct projects involved
        $myProjects = Project::whereIn('id',
            Task::whereIn('id', $myTaskIds)->pluck('project_id')->unique()
        )->orderBy('name')->get();

        // Aggregate metrics for hero
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

        return view('employee.tasks.index', [
            'tenantSlug'   => $tenant,
            'emp'          => $emp,
            'tasks'        => $tasks,
            'counters'     => $counters,
            'createdCount' => $createdCount,
            'myProjects'   => $myProjects,
            'todayDue'     => $todayDue,
            'thisWeekDue'  => $thisWeekDue,
            'filter'       => $filter,
            'priority'     => $priority,
            'projectId'    => $projectId,
            'search'       => $search,
        ]);
    }

    /* ================================================================
     | STORE — manager/owner creates a task from the employee board
     |================================================================*/
    public function store(string $tenant, Request $request, int $projectId)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $project = \App\Models\Tenant\Project::findOrFail($projectId);

        $member = \App\Models\Tenant\ProjectMember::where('project_id', $project->id)
            ->where('employee_id', $emp->id)->first();
        abort_unless($member && in_array($member->role, ['owner','manager']), 403, 'Only project managers can create tasks.');

        $data = $request->validate([
            'task_list_id'    => 'required|exists:task_lists,id',
            'title'           => 'required|string|max:500',
            'description'     => 'nullable|string',
            'type'            => 'required|in:task,bug,feature,epic,story,improvement,support',
            'priority'        => 'required|in:low,normal,high,urgent',
            'due_date'        => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assignee_ids'    => 'nullable|array',
            'assignee_ids.*'  => 'exists:employees,id',
        ]);

        $list   = \App\Models\Tenant\TaskList::findOrFail($data['task_list_id']);
        $status = match($list->column_type) {
            'backlog'     => 'backlog', 'todo' => 'todo', 'in_progress' => 'in_progress',
            'review'      => 'in_review', 'done' => 'done', 'cancelled' => 'cancelled',
            default       => 'todo',
        };

        $task = \App\Models\Tenant\Task::create([
            'project_id'      => $project->id,
            'task_list_id'    => $data['task_list_id'],
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'],
            'priority'        => $data['priority'],
            'status'          => $status,
            'due_date'        => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0,
            'sort_order'      => (\App\Models\Tenant\Task::where('task_list_id', $data['task_list_id'])->max('sort_order') ?? 0) + 1,
            'created_by'      => auth()->id(),
            'progress'        => 0,
        ]);

        foreach (array_values($data['assignee_ids'] ?? []) as $i => $empId) {
            \App\Models\Tenant\TaskAssignee::create([
                'task_id' => $task->id, 'employee_id' => $empId,
                'is_primary' => $i === 0, 'assigned_by' => auth()->id(),
            ]);
        }

        return response()->json(['success' => true, 'task' => $task->load('assignees.employee')]);
    }

    /* ================================================================
     | MOVE — employee drags their own task to a different column
     |================================================================*/
    public function move(string $tenant, Request $request, int $projectId, int $taskId)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $task = \App\Models\Tenant\Task::where('project_id', $projectId)->findOrFail($taskId);

        // Only the task's assignee (or project manager) can move it
        $isAssigned = \App\Models\Tenant\TaskAssignee::where('task_id', $task->id)
            ->where('employee_id', $emp->id)->exists();
        $isManager  = \App\Models\Tenant\ProjectMember::where('project_id', $projectId)
            ->where('employee_id', $emp->id)->whereIn('role', ['owner','manager'])->exists();
        abort_unless($isAssigned || $isManager, 403, 'You can only move tasks assigned to you.');

        $request->validate([
            'task_list_id' => 'required|exists:task_lists,id',
            'sort_order'   => 'nullable|integer|min:0',
            'ordered_ids'  => 'nullable|array',
        ]);

        $newList   = \App\Models\Tenant\TaskList::findOrFail($request->task_list_id);
        $newStatus = match($newList->column_type) {
            'backlog' => 'backlog', 'todo' => 'todo', 'in_progress' => 'in_progress',
            'review'  => 'in_review', 'done' => 'done', 'cancelled' => 'cancelled',
            default   => 'todo',
        };

        $task->update([
            'task_list_id' => $request->task_list_id,
            'sort_order'   => $request->sort_order ?? 0,
            'status'       => $newStatus,
            'completed_at' => $newList->is_closed_status ? ($task->completed_at ?? now()) : null,
        ]);

        if ($request->has('ordered_ids')) {
            foreach ($request->ordered_ids as $index => $tid) {
                \App\Models\Tenant\Task::where('id', $tid)->update(['sort_order' => $index]);
            }
        }

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    /* ================================================================
     | SHOW — Task detail
     |================================================================*/
    public function show(string $tenant, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::with([
                'project',
                'taskList',
                'assignees.employee',
                'checklists' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'comments'   => fn ($q) => $q->with('user')->orderBy('created_at'),
                'attachments',
                'parent',
                'subtasks',
            ])
            ->findOrFail($task);

        // Authorization: must be an assignee, creator, OR project member
        $isAssignee = $t->assignees->contains(fn ($a) => $a->employee_id === $emp->id);
        $isCreator  = $t->created_by === auth()->id();
        $isProjectMember = $t->project
            ? DB::connection('tenant')->table('project_members')
                ->where('project_id', $t->project_id)
                ->where('employee_id', $emp->id)
                ->exists()
            : false;

        abort_unless($isAssignee || $isCreator || $isProjectMember, 403, 'You do not have access to this task.');

        // Return JSON for AJAX / API requests (e.g. Kanban board modal)
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($t);
        }

        // Activities (newest first)
        $activities = collect();
        if (Schema::connection('tenant')->hasTable('task_activities')) {
            $activities = TaskActivity::with('user')
                ->where('task_id', $t->id)
                ->orderByDesc('created_at')
                ->limit(40)
                ->get();
        }

        // Checklist progress
        $checklistTotal = $t->checklists->count();
        $checklistDone  = $t->checklists->where('is_completed', true)->count();
        $checklistPct   = $checklistTotal > 0 ? round(($checklistDone / $checklistTotal) * 100) : 0;

        return view('employee.tasks.show', [
            'tenantSlug'     => $tenant,
            'emp'            => $emp,
            'task'           => $t,
            'activities'     => $activities,
            'checklistTotal' => $checklistTotal,
            'checklistDone'  => $checklistDone,
            'checklistPct'   => $checklistPct,
            'canEdit'        => $isAssignee || $isCreator,
        ]);
    }

    /* ================================================================
     | UPDATE STATUS
     |================================================================*/
    public function updateStatus(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['todo','in_progress','in_review','on_hold','done'])],
        ]);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp);

        $oldStatus = $t->status;
        if ($oldStatus === $data['status']) {
            return $this->okResponse($request, $t, 'Status unchanged.');
        }

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'done') {
            $updates['completed_at'] = now();
            $updates['progress']     = 100;
        } elseif ($oldStatus === 'done') {
            // Reopening
            $updates['completed_at'] = null;
            if ($t->progress >= 100) {
                $updates['progress'] = 75;
            }
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $t->update($updates);
            $this->logActivity($t, 'status_changed', 'status', $oldStatus, $data['status']);
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Employee task status update failed: '.$e->getMessage());
            return $this->errorResponse($request, 'Could not update status.');
        }

        return $this->okResponse($request, $t->fresh(), "Status updated to " . str_replace('_', ' ', $data['status']) . '.');
    }

    /* ================================================================
     | UPDATE PROGRESS
     |================================================================*/
    public function updateProgress(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp);

        $oldProgress = (int) $t->progress;
        if ($oldProgress === $data['progress']) {
            return $this->okResponse($request, $t, 'Progress unchanged.');
        }

        $updates = ['progress' => $data['progress']];

        // Auto-transition logic
        if ($data['progress'] === 100 && $t->status !== 'done') {
            $updates['status']       = 'done';
            $updates['completed_at'] = now();
        } elseif ($data['progress'] > 0 && $data['progress'] < 100 && $t->status === 'todo') {
            $updates['status'] = 'in_progress';
        }

        DB::connection('tenant')->beginTransaction();
        try {
            $t->update($updates);
            $this->logActivity($t, 'progress_changed', 'progress', $oldProgress . '%', $data['progress'] . '%');
            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Employee task progress update failed: '.$e->getMessage());
            return $this->errorResponse($request, 'Could not update progress.');
        }

        return $this->okResponse($request, $t->fresh(), "Progress updated to {$data['progress']}%.");
    }

    /* ================================================================
     | STORE COMMENT
     |================================================================*/
    public function storeComment(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $t = Task::findOrFail($task);
        $this->assertCanComment($t, $emp);

        DB::connection('tenant')->beginTransaction();
        try {
            $comment = TaskComment::create([
                'task_id' => $t->id,
                'user_id' => auth()->id(),
                'content' => $data['content'],
            ]);

            $t->increment('comments_count');
            $this->logActivity($t, 'commented', 'comment', null, null);

            DB::connection('tenant')->commit();
        } catch (\Throwable $e) {
            DB::connection('tenant')->rollBack();
            \Log::error('Employee task comment failed: '.$e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Could not add comment.'], 422);
            }
            return back()->with('error', 'Could not add your comment. Please try again.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment->load('user'),
            ]);
        }

        return back()->with('success', 'Comment added.');
    }

    /* ================================================================
     | TOGGLE CHECKLIST ITEM
     |================================================================*/
    public function toggleChecklist(string $tenant, int $task, int $checklist)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp);

        $item = TaskChecklist::where('task_id', $t->id)->findOrFail($checklist);

        $nowComplete = ! $item->is_completed;
        $item->update([
            'is_completed' => $nowComplete,
            'completed_at' => $nowComplete ? now() : null,
            'completed_by' => $nowComplete ? auth()->id() : null,
        ]);

        // Update task counts
        $t->update([
            'completed_subtasks_count' => $t->checklists()->where('is_completed', true)->count(),
            'subtasks_count'           => $t->checklists()->count(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success'      => true,
                'is_completed' => $nowComplete,
            ]);
        }

        return back();
    }

    /* ================================================================
     | UPLOAD ATTACHMENT
     |================================================================*/
    public function storeAttachment(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::findOrFail($task);

        // Anyone on the project can attach files
        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isCreator  = $t->created_by === auth()->id();
        $isProjectMember = DB::connection('tenant')->table('project_members')
            ->where('project_id', $t->project_id)->where('employee_id', $emp->id)->exists();
        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);

        $request->validate([
            'files'   => 'required|array|max:10',
            'files.*' => 'file|max:20480', // 20 MB
        ]);

        $attachments = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("task-attachments/{$t->id}", 'public');
            $att  = TaskAttachment::create([
                'task_id'   => $t->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
            $attachments[] = $att;
        }

        return response()->json(['success' => true, 'attachments' => $attachments]);
    }

    /* ================================================================
     | DELETE ATTACHMENT
     |================================================================*/
    public function destroyAttachment(string $tenant, int $task, int $attachment)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t   = Task::findOrFail($task);
        $att = TaskAttachment::where('task_id', $t->id)->findOrFail($attachment);

        // Only uploader, task assignee, or manager can delete
        $isUploader = $att->uploaded_by === auth()->id();
        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isManager  = DB::connection('tenant')->table('project_members')
            ->where('project_id', $t->project_id)->where('employee_id', $emp->id)
            ->whereIn('role', ['owner', 'manager'])->exists();
        abort_unless($isUploader || $isAssignee || $isManager, 403);

        Storage::disk('public')->delete($att->file_path);
        $att->delete();

        return response()->json(['success' => true]);
    }

    /* ================================================================
     | DOWNLOAD ATTACHMENT (secure stream)
     |================================================================*/
    public function attachment(string $tenant, int $task, int $attachment)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::findOrFail($task);
        // Read access: assignee, creator, project member
        $isAssignee = TaskAssignee::where('task_id', $t->id)->where('employee_id', $emp->id)->exists();
        $isCreator  = $t->created_by === auth()->id();
        $isProjectMember = DB::connection('tenant')->table('project_members')
            ->where('project_id', $t->project_id)
            ->where('employee_id', $emp->id)
            ->exists();

        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);

        $att = TaskAttachment::where('task_id', $t->id)->findOrFail($attachment);

        if (! $att->file_path) {
            abort(404, 'No file attached.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($att->file_path)) {
            abort(404, 'Attachment file is missing.');
        }

        return $disk->download($att->file_path, $att->file_name);
    }

    /* ================================================================
     | UPDATE — employee edits their own task fields
     |================================================================*/
    public function update(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp);

        $data = $request->validate([
            'title'           => 'sometimes|string|max:500',
            'description'     => 'sometimes|nullable|string',
            'due_date'        => 'sometimes|nullable|date',
            'estimated_hours' => 'sometimes|nullable|numeric|min:0',
            'progress'        => 'sometimes|integer|min:0|max:100',
            'status'          => 'sometimes|in:backlog,todo,in_progress,in_review,on_hold,done,cancelled',
            'priority'        => 'sometimes|in:low,normal,high,urgent',
        ]);

        $t->update($data);

        return response()->json([
            'success' => true,
            'task'    => $t->load('assignees.employee'),
        ]);
    }

    /* ================================================================
     | STORE CHECKLIST — add checklist item to own task
     |================================================================*/
    public function storeChecklist(string $tenant, Request $request, int $task)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $t = Task::findOrFail($task);
        $this->assertCanEdit($t, $emp);

        $data = $request->validate(['item' => 'required|string|max:500']);

        $item = TaskChecklist::create([
            'task_id'    => $t->id,
            'item'       => $data['item'],
            'sort_order' => (TaskChecklist::where('task_id', $t->id)->max('sort_order') ?? 0) + 1,
        ]);

        // Update count
        $t->update(['subtasks_count' => $t->checklists()->count()]);

        return response()->json(['success' => true, 'checklist' => $item]);
    }

    /* ================================================================
     | Helpers
     |================================================================*/

    protected function assertCanEdit(Task $task, $employee): void
    {
        $isAssignee = TaskAssignee::where('task_id', $task->id)
            ->where('employee_id', $employee->id)
            ->exists();
        $isCreator = $task->created_by === auth()->id();

        abort_unless($isAssignee || $isCreator, 403, 'You can only update tasks assigned to you.');
    }

    protected function assertCanComment(Task $task, $employee): void
    {
        // Anyone with read access can comment
        $isAssignee = TaskAssignee::where('task_id', $task->id)
            ->where('employee_id', $employee->id)
            ->exists();
        $isCreator = $task->created_by === auth()->id();
        $isProjectMember = DB::connection('tenant')->table('project_members')
            ->where('project_id', $task->project_id)
            ->where('employee_id', $employee->id)
            ->exists();

        abort_unless($isAssignee || $isCreator || $isProjectMember, 403);
    }

    protected function logActivity(Task $task, string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null): void
    {
        if (! Schema::connection('tenant')->hasTable('task_activities')) {
            return;
        }

        try {
            TaskActivity::create([
                'task_id'   => $task->id,
                'user_id'   => auth()->id(),
                'action'    => $action,
                'field'     => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]);
        } catch (\Throwable $e) {
            // Activity logging is best-effort, never blocks the main action
            \Log::warning('Activity log failed: '.$e->getMessage());
        }
    }

    protected function okResponse(Request $request, Task $task, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'task'    => $task,
                'message' => $message,
            ]);
        }
        return back()->with('success', $message);
    }

    protected function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }
        return back()->with('error', $message);
    }
}