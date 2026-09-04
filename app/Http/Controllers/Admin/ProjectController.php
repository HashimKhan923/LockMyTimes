<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMember;
use App\Models\Tenant\TaskList;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $status = $request->get('status', 'all');

        $query = Project::with(['manager', 'department'])
            ->withCount('members')
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q->where('name','like',"%$search%")
                ->orWhere('code','like',"%$search%"));
        }

        if ($from = $request->get('from')) {
            $query->where('due_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('due_date', '<=', $to);
        }

        $projects    = $query->paginate(12)->withQueryString();
        $employees   = Employee::active()->orderBy('first_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total'    => Project::count(),
            'active'   => Project::where('status','active')->count(),
            'planning' => Project::where('status','planning')->count(),
            'overdue'  => Project::whereNotIn('status',['completed','cancelled','archived'])
                            ->whereNotNull('due_date')
                            ->where('due_date','<',today())->count(),
        ];

        return view('admin.projects.index', compact(
            'projects','employees','departments',
            'stats','status','tenant'
        ));
    }

    /* ================================================================
     | STORE
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:200',
            'description'        => 'nullable|string',
            'type'               => 'required|in:internal,client,product,campaign,maintenance,other',
            'status'             => 'required|in:planning,active,on_hold,completed,cancelled,archived',
            'priority'           => 'required|in:low,normal,high,urgent',
            'project_manager_id' => 'nullable|exists:employees,id',
            'department_id'      => 'nullable|exists:departments,id',
            'start_date'         => 'nullable|date',
            'due_date'           => 'nullable|date',
            'color'              => 'nullable|string|max:7',
            'billing_type'       => 'required|in:hourly,fixed,milestone,non_billable',
            'budget_amount'      => 'nullable|numeric|min:0',
            'is_billable'        => 'boolean',
        ]);

        $project = Project::create($data);

        // Add manager as project member owner
        if ($project->project_manager_id) {
            ProjectMember::create([
                'project_id'       => $project->id,
                'employee_id'      => $project->project_manager_id,
                'role'             => 'owner',
                'can_log_time'     => true,
                'can_manage_tasks' => true,
                'joined_at'        => now(),
            ]);
        }

        // Create default Kanban columns
        $defaultColumns = [
            ['name'=>'Backlog',     'color'=>'#94A3B8','column_type'=>'backlog',      'sort_order'=>0,'is_closed_status'=>false],
            ['name'=>'To Do',       'color'=>'#6C7DF7','column_type'=>'todo',         'sort_order'=>1,'is_closed_status'=>false],
            ['name'=>'In Progress', 'color'=>'#F59E0B','column_type'=>'in_progress',  'sort_order'=>2,'is_closed_status'=>false],
            ['name'=>'In Review',   'color'=>'#8B5CF6','column_type'=>'review',       'sort_order'=>3,'is_closed_status'=>false],
            ['name'=>'Done',        'color'=>'#10B981','column_type'=>'done',         'sort_order'=>4,'is_closed_status'=>true],
        ];

        foreach ($defaultColumns as $col) {
            TaskList::create(array_merge($col, ['project_id' => $project->id]));
        }

        return redirect()
            ->route('admin.projects.show', [$tenant, $project->id])
            ->with('success', "Project \"{$project->name}\" created.");
    }

    /* ================================================================
     | SHOW — project overview
     |================================================================*/
    public function show(string $tenant, Project $project)
    {
        $project->load([
            'manager',
            'department',
            'members.employee.department',
            'milestones',
            'taskLists.tasks.assignees.employee',
        ]);

        $employees  = Employee::active()->orderBy('first_name')->get();
        $taskStats  = [
            'total'      => $project->tasks()->count(),
            'done'       => $project->tasks()->where('status','done')->count(),
            'in_progress'=> $project->tasks()->where('status','in_progress')->count(),
            'overdue'    => $project->tasks()->overdue()->count(),
        ];

        return view('admin.projects.show', compact('project','employees','taskStats','tenant'));
    }

    /* ================================================================
     | UPDATE
     |================================================================*/
    public function update(string $tenant, Request $request, Project $project)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:200',
            'description'        => 'nullable|string',
            'status'             => 'required|in:planning,active,on_hold,completed,cancelled,archived',
            'priority'           => 'required|in:low,normal,high,urgent',
            'project_manager_id' => 'nullable|exists:employees,id',
            'start_date'         => 'nullable|date',
            'due_date'           => 'nullable|date',
            'color'              => 'nullable|string|max:7',
            'budget_amount'      => 'nullable|numeric|min:0',
        ]);

        $project->update($data);
        return back()->with('success', 'Project updated.');
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Project $project)
    {
        $project->delete();
        return redirect()
            ->route('admin.projects.index', $tenant)
            ->with('success', 'Project deleted.');
    }

    /* ================================================================
     | MEMBERS
     |================================================================*/
    public function addMember(string $tenant, Request $request, Project $project)
    {
        $data = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'role'             => 'required|in:owner,manager,member,viewer',
            'can_manage_tasks' => 'boolean',
        ]);

        ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'employee_id' => $data['employee_id']],
            array_merge($data, [
                'can_log_time' => true,
                'joined_at'    => now(),
            ])
        );

        return back()->with('success', 'Member added to project.');
    }

    public function removeMember(string $tenant, Project $project, ProjectMember $member)
    {
        $member->delete();
        return back()->with('success', 'Member removed.');
    }
}