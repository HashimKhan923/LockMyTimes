<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /* ================================================================
     | The 6 standard actions every module supports
     |================================================================*/
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

    /* ================================================================
     | Human-friendly module labels (module_key => Display Name)
     |================================================================*/
    private function moduleLabels(): array
    {
        return [
            'employees'          => 'Employees',
            'departments'        => 'Departments',
            'positions'          => 'Positions',
            'locations'          => 'Locations',
            'attendance'         => 'Attendance',
            'time_tracking'      => 'Time Tracking',
            'qr_codes'           => 'QR Codes',
            'shifts'             => 'Shifts',
            'leaves'             => 'Leave Requests',
            'leave_types'        => 'Leave Types',
            'holidays'           => 'Holidays',
            'payroll'            => 'Payroll',
            'payslips'           => 'Payslips',
            'salary_components'  => 'Salary Components',
            'expenses'           => 'Expenses',
            'loans'              => 'Loans & Advances',
            'salary_advances'    => 'Salary Advances',
            'performance'        => 'Performance Reviews',
            'reviews'            => 'Review Cycles',
            'goals'              => 'Goals & OKRs',
            'kudos'              => 'Kudos',
            'projects'           => 'Projects',
            'tasks'              => 'Tasks',
            'assets'             => 'Assets',
            'asset_categories'   => 'Asset Categories',
            'training'           => 'Training',
            'certifications'     => 'Certifications',
            'documents'          => 'Documents',
            'job_postings'       => 'Job Postings',
            'candidates'         => 'Candidates',
            'recruitment'        => 'Recruitment',
            'announcements'      => 'Announcements',
            'reports'            => 'Reports',
            'settings'           => 'Settings',
            'roles'              => 'Roles & Permissions',
            'users'              => 'Users',
        ];
    }

    /* ================================================================
     | Group modules into logical sections for UI display
     |================================================================*/
    private function moduleSections(): array
    {
        return [
            'People & Organization' => ['employees','departments','positions','locations','users'],
            'Time & Attendance'      => ['attendance','time_tracking','qr_codes','shifts'],
            'Leave Management'       => ['leaves','leave_types','holidays'],
            'Payroll & Finance'      => ['payroll','payslips','salary_components','expenses','loans','salary_advances'],
            'Performance'            => ['performance','reviews','goals','kudos'],
            'Projects'               => ['projects','tasks'],
            'Assets'                 => ['assets','asset_categories'],
            'Training & Development' => ['training','certifications'],
            'Documents'              => ['documents'],
            'Recruitment'            => ['job_postings','candidates','recruitment'],
            'Communication'          => ['announcements'],
            'Reports & Analytics'    => ['reports'],
            'System'                 => ['settings','roles'],
        ];
    }

    /* ================================================================
     | Build the full permission matrix structure:
     | [ section => [ module_key => [label, permissions[action=>name]] ] ]
     |================================================================*/
    private function buildPermissionMatrix(): array
    {
        $labels       = $this->moduleLabels();
        $sections     = $this->moduleSections();
        $allPerms     = Permission::pluck('name')->flip(); // fast lookup
        $matrix       = [];

        foreach ($sections as $sectionName => $modules) {
            $matrix[$sectionName] = [];
            foreach ($modules as $moduleKey) {
                $actions = [];
                foreach (self::ACTIONS as $action) {
                    $permName = "{$moduleKey}.{$action}";
                    if (isset($allPerms[$permName])) {
                        $actions[$action] = $permName;
                    }
                }
                if (! empty($actions)) {
                    $matrix[$sectionName][$moduleKey] = [
                        'label'   => $labels[$moduleKey] ?? ucfirst(str_replace('_',' ',$moduleKey)),
                        'actions' => $actions,
                    ];
                }
            }
        }

        return $matrix;
    }

    /* ================================================================
     | INDEX — roles list
     |================================================================*/
    public function index(string $tenant)
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();

        $userCounts = $roles->mapWithKeys(fn($role) => [
            $role->id => User::role($role->name)->count()
        ]);

        $totalPermissions = Permission::count();
        $totalUsers       = User::count();
        $totalModules     = count($this->moduleLabels());

        return view('admin.roles.index', compact(
            'roles', 'userCounts', 'totalPermissions',
            'totalUsers', 'totalModules', 'tenant'
        ));
    }

    /* ================================================================
     | SHOW — role detail with full permission matrix
     |================================================================*/
    public function show(string $tenant, Role $role)
    {
        $role->load('permissions');

        $matrix          = $this->buildPermissionMatrix();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $users           = User::role($role->name)->with('employee.department')->get();

        $isProtected = in_array($role->name, ['Tenant Admin', 'super_admin']);

        return view('admin.roles.show', compact(
            'role', 'matrix', 'rolePermissions', 'users', 'isProtected', 'tenant'
        ));
    }

    /* ================================================================
     | STORE — create a new custom role
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name'       => trim($data['name']),
            'guard_name' => 'web',
        ]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()
            ->route('admin.roles.show', [$tenant, $role->id])
            ->with('success', "Role \"{$role->name}\" created with " . count($data['permissions'] ?? []) . " permissions.");
    }

    /* ================================================================
     | UPDATE PERMISSIONS — sync role's permission set
     |================================================================*/
    public function updatePermissions(string $tenant, Request $request, Role $role)
    {
        if (in_array($role->name, ['Tenant Admin', 'super_admin'])) {
            return back()->with('error', "\"{$role->name}\" has full system access and cannot be modified.");
        }

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Permissions updated for \"{$role->name}\" — " . count($permissions) . " permission(s) assigned.");
    }

    /* ================================================================
     | RENAME ROLE
     |================================================================*/
    public function update(string $tenant, Request $request, Role $role)
    {
        if (in_array($role->name, ['Tenant Admin', 'super_admin', 'Employee'])) {
            return back()->with('error', "This is a protected default role and cannot be renamed.");
        }

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => trim($data['name'])]);

        return back()->with('success', 'Role renamed successfully.');
    }

    /* ================================================================
     | DESTROY — delete a custom role
     |================================================================*/
    public function destroy(string $tenant, Role $role)
    {
        if (in_array($role->name, ['Tenant Admin', 'super_admin', 'Employee', 'HR Manager', 'Manager'])) {
            return back()->with('error', "\"{$role->name}\" is a default system role and cannot be deleted.");
        }

        $userCount = User::role($role->name)->count();
        if ($userCount > 0) {
            return back()->with('error', "Cannot delete \"{$role->name}\" — {$userCount} user(s) are still assigned to it. Reassign them first.");
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index', $tenant)
            ->with('success', "Role \"{$roleName}\" deleted.");
    }

    /* ================================================================
     | ASSIGN ROLE TO USER
     |================================================================*/
    public function assignRole(string $tenant, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncRoles([$request->role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "\"{$request->role}\" role assigned to {$user->name}.");
    }

    /* ================================================================
     | REMOVE ROLE FROM USER
     |================================================================*/
    public function removeRole(string $tenant, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->roles()->count() <= 1) {
            return back()->with('error', 'User must have at least one role. Assign a new role before removing this one.');
        }

        $user->removeRole($request->role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Role removed from {$user->name}.");
    }
}