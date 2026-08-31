<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // No need to clear cache — this seeder only runs once on a brand new tenant DB.

        // ============== Permissions ==============
        $modules = [
            'employees', 'departments', 'positions', 'locations',
            'attendance', 'qr_codes',
            'leaves', 'leave_types', 'holidays',
            'shifts',
            'payroll', 'payslips', 'salary_components',
            'performance', 'goals', 'reviews', 'kudos',
            'assets', 'asset_categories',
            'training', 'certifications',
            'documents',
            'recruitment', 'job_postings', 'candidates',
            'expenses',
            'announcements',
            'projects', 'tasks',
            'loans', 'salary_advances',
            'reports',
            'settings', 'users', 'roles',
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];

        $allPermissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $allPermissions[] = "{$module}.{$action}";
            }
        }

        // Insert (idempotent)
        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ============== Roles ==============
        $admin = Role::firstOrCreate(['name' => 'Tenant Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());     // admin gets EVERYTHING

        $hr = Role::firstOrCreate(['name' => 'HR Manager', 'guard_name' => 'web']);
        $hr->syncPermissions(Permission::whereNotIn('name', [
            'settings.delete', 'roles.delete', 'roles.create',
        ])->get());

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->syncPermissions(Permission::where(function ($q) {
            $q->where('name', 'like', '%.view')
              ->orWhere('name', 'like', 'attendance.%')
              ->orWhere('name', 'like', 'leaves.approve')
              ->orWhere('name', 'like', 'expenses.approve')
              ->orWhere('name', 'like', 'tasks.%')
              ->orWhere('name', 'like', 'projects.%')

              ->orWhere('name', 'like', 'performance.%')
              ->orWhere('name', 'like', 'goals.%')
              ->orWhere('name', 'like', 'reviews.%')
              ->orWhere('name', 'like', 'kudos.%');
        })->get());

        $employee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $employee->syncPermissions(Permission::whereIn('name', [
            'attendance.view', 'attendance.create',
            'leaves.view', 'leaves.create',
            'payslips.view',
            'documents.view',
            'goals.view', 'goals.edit',
            'kudos.view', 'kudos.create',
            'expenses.view', 'expenses.create',
            'tasks.view', 'tasks.edit',
            'projects.view',
            'announcements.view',

            'loans.view', 'loans.create',
            'salary_advances.view', 'salary_advances.create',
            'assets.view',
        ])->get());
    }
}