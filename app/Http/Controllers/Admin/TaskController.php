<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\TaskAssignee;
use App\Models\Tenant\TaskChecklist;
use App\Models\Tenant\TaskAttachment;
use App\Models\Tenant\TaskComment;
use App\Models\Tenant\Employee;
use App\Models\Tenant\TaskList;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /* ================================================================
     | KANBAN BOARD — main view
     |================================================================*/
    public function board(string $tenant, Project $project)
    {
        $project->load('members.employee');

        $taskLists = TaskList::where('project_id', $project->id)
            ->with([
                'tasks' => function($q) {
                    $q->whereNull('parent_task_id')
                      ->with(['assignees.employee','checklists'])
                      ->orderBy('sort_order');
                }
            ])
            ->orderBy('sort_order')
            ->get();

        $employees = \App\Models\Tenant\Employee::active()->orderBy('first_name')->get();

        $projectTaskIds = Task::whereIn('task_list_id', $taskLists->pluck('id'))->pluck('id');

        $taskStats = [
            'done'        => Task::whereIn('id', $projectTaskIds)->where('status', 'done')->count(),
            'in_progress' => Task::whereIn('id', $projectTaskIds)->where('status', 'in_progress')->count(),
            'overdue'     => Task::whereIn('id', $projectTaskIds)
                                 ->whereNotIn('status', ['done', 'cancelled'])
                                 ->whereNotNull('due_date')
                                 ->where('due_date', '<', now())
                                 ->count(),
        ];

        return view('admin.projects.board', compact('project','taskLists','employees','tenant','taskStats'));
    }

    /* ================================================================
     | STORE TASK
     |================================================================*/
    public function store(string $tenant, Request $request, Project $project)
    {
        $data = $request->validate([
            'task_list_id'   => 'required|exists:task_lists,id',
            'title'          => 'required|string|max:500',
            'description'    => 'nullable|string',
            'type'           => 'required|in:task,bug,feature,epic,story,improvement,support',
            'priority'       => 'required|in:low,normal,high,urgent',
            'due_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric|min:0',
            'assignee_ids'   => 'nullable|array',
            'assignee_ids.*' => 'exists:employees,id',
        ]);

        // Get sort order
        $maxOrder = Task::where('task_list_id', $data['task_list_id'])->max('sort_order') ?? 0;

        $task = Task::create([
            'project_id'      => $project->id,
            'task_list_id'    => $data['task_list_id'],
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'],
            'priority'        => $data['priority'],
            'status'          => $this->getStatusFromList($data['task_list_id']),
            'due_date'        => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0,
            'sort_order'      => $maxOrder + 1,
            'created_by'      => auth()->id(),
            'progress'        => 0,
        ]);

        // Assign employees
        if (! empty($data['assignee_ids'])) {
            $mailer = app(MailService::class);
            $task->load('project');
            foreach ($data['assignee_ids'] as $i => $empId) {
                TaskAssignee::create([
                    'task_id'     => $task->id,
                    'employee_id' => $empId,
                    'is_primary'  => $i === 0,
                    'assigned_by' => auth()->id(),
                ]);
                $employee = Employee::find($empId);
                if ($employee) $mailer->sendTaskAssigned($employee, $task);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'task'    => $task->load('assignees.employee','checklists'),
            ]);
        }

        return back()->with('success', "Task \"{$task->title}\" created.");
    }

    /* ================================================================
     | SHOW TASK (modal data)
     |================================================================*/
    public function show(string $tenant, Project $project, Task $task)
    {
        $task->load([
            'assignees.employee',
            'checklists',
            'comments.user',
            'attachments',
            'taskList',
            'subtasks.assignees.employee',
        ]);

        return response()->json($task);
    }

    /* ================================================================
     | UPDATE TASK
     |================================================================*/
    public function update(string $tenant, Request $request, Project $project, Task $task)
    {
        $data = $request->validate([
            'title'          => 'sometimes|required|string|max:500',
            'description'    => 'nullable|string',
            'type'           => 'sometimes|in:task,bug,feature,epic,story,improvement,support',
            'status'         => 'sometimes|in:backlog,todo,in_progress,in_review,on_hold,done,cancelled',
            'priority'       => 'sometimes|in:low,normal,high,urgent',
            'due_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric|min:0',
            'progress'       => 'nullable|integer|min:0|max:100',
            'assignee_ids'   => 'sometimes|nullable|array',
            'assignee_ids.*' => 'exists:employees,id',
        ]);

        if (isset($data['status']) && $data['status'] === 'done' && ! $task->completed_at) {
            $data['completed_at'] = now();
            $data['progress']     = 100;
        }

        // Handle assignee sync if provided
        if ($request->has('assignee_ids')) {
            $newIds = array_filter((array) ($data['assignee_ids'] ?? []));
            TaskAssignee::where('task_id', $task->id)->delete();
            foreach (array_values($newIds) as $i => $empId) {
                TaskAssignee::create([
                    'task_id'     => $task->id,
                    'employee_id' => $empId,
                    'is_primary'  => $i === 0,
                    'assigned_by' => auth()->id(),
                ]);
            }
            unset($data['assignee_ids']);
        }

        $task->update($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'task' => $task->load('assignees.employee')]);
        }

        return back()->with('success', 'Task updated.');
    }

    /* ================================================================
     | MOVE TASK (drag & drop — called via AJAX)
     |================================================================*/
    public function move(string $tenant, Request $request, Project $project, Task $task)
    {
        $request->validate([
            'task_list_id' => 'required|exists:task_lists,id',
            'sort_order'   => 'required|integer|min:0',
            'adjacent_ids' => 'nullable|array',  // task IDs before/after for ordering
        ]);

        $newList   = TaskList::findOrFail($request->task_list_id);
        $newStatus = $this->getStatusForColumnType($newList->column_type);

        $task->update([
            'task_list_id' => $request->task_list_id,
            'sort_order'   => $request->sort_order,
            'status'       => $newStatus,
            'completed_at' => $newList->is_closed_status ? ($task->completed_at ?? now()) : null,
        ]);

        // Reorder other tasks in the new column
        if ($request->has('ordered_ids')) {
            foreach ($request->ordered_ids as $index => $taskId) {
                Task::where('id', $taskId)->update(['sort_order' => $index]);
            }
        }

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    /* ================================================================
     | DESTROY TASK
     |================================================================*/
    public function destroy(string $tenant, Project $project, Task $task)
    {
        $task->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Task deleted.');
    }

    /* ================================================================
     | CHECKLIST
     |================================================================*/
    public function storeChecklist(string $tenant, Request $request, Project $project, Task $task)
    {
        $request->validate(['item' => 'required|string|max:500']);

        $item = TaskChecklist::create([
            'task_id'    => $task->id,
            'item'       => $request->item,
            'sort_order' => $task->checklists()->count(),
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function toggleChecklist(string $tenant, Project $project, Task $task, TaskChecklist $checklist)
    {
        $checklist->update([
            'is_completed' => ! $checklist->is_completed,
            'completed_at' => ! $checklist->is_completed ? now() : null,
            'completed_by' => ! $checklist->is_completed ? auth()->id() : null,
        ]);

        // Update task's completed_subtasks_count
        $task->update([
            'completed_subtasks_count' => $task->checklists()->where('is_completed', true)->count(),
            'subtasks_count'           => $task->checklists()->count(),
        ]);

        return response()->json(['success' => true, 'is_completed' => $checklist->is_completed]);
    }

    /* ================================================================
     | COMMENTS
     |================================================================*/
    public function storeComment(string $tenant, Request $request, Project $project, Task $task)
    {
        $request->validate(['content' => 'required|string|max:5000']);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        $task->increment('comments_count');

        return response()->json([
            'success' => true,
            'comment' => $comment->load('user'),
        ]);
    }

    /* ================================================================
     | ATTACHMENTS
     |================================================================*/
    public function storeAttachment(string $tenant, Request $request, Project $project, Task $task)
    {
        $request->validate(['files.*' => 'required|file|max:10240']);

        $stored = [];
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("tasks/{$task->id}/attachments", 'public');
            $attachment = TaskAttachment::create([
                'task_id'     => $task->id,
                'uploaded_by' => auth()->id(),
                'file_path'   => $path,
                'file_name'   => $file->getClientOriginalName(),
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
            ]);
            $stored[] = $attachment;
        }

        $task->increment('attachments_count', count($stored));

        return response()->json(['success' => true, 'attachments' => $stored]);
    }

    public function destroyAttachment(string $tenant, Project $project, Task $task, TaskAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        $task->decrement('attachments_count');

        return response()->json(['success' => true]);
    }

    /* ================================================================
     | ADD TASK LIST COLUMN
     |================================================================*/
    public function storeList(string $tenant, Request $request, Project $project)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'nullable|string|max:7',
        ]);

        $maxOrder = TaskList::where('project_id', $project->id)->max('sort_order') ?? 0;

        $list = TaskList::create([
            'project_id'  => $project->id,
            'name'        => $request->name,
            'color'       => $request->color ?? '#6C7DF7',
            'column_type' => 'custom',
            'sort_order'  => $maxOrder + 1,
        ]);

        return response()->json(['success' => true, 'list' => $list]);
    }

    public function updateList(string $tenant, Request $request, Project $project, TaskList $taskList)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $taskList->update(['name' => $request->name, 'color' => $request->color ?? $taskList->color]);
        return response()->json(['success' => true]);
    }

    public function destroyList(string $tenant, Project $project, TaskList $taskList)
    {
        if ($taskList->tasks()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a list with tasks.'], 422);
        }
        $taskList->delete();
        return response()->json(['success' => true]);
    }

    /* ================================================================
     | HELPERS
     |================================================================*/
    private function getStatusFromList(int $listId): string
    {
        $list = TaskList::find($listId);
        return $this->getStatusForColumnType($list?->column_type ?? 'todo');
    }

    private function getStatusForColumnType(string $columnType): string
    {
        return match($columnType) {
            'backlog'     => 'backlog',
            'todo'        => 'todo',
            'in_progress' => 'in_progress',
            'review'      => 'in_review',
            'done'        => 'done',
            'cancelled'   => 'cancelled',
            default       => 'todo',
        };
    }
}