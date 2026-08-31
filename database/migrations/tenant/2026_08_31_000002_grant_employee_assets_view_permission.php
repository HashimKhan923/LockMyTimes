<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * The "My Assets" employee-portal page (routes/employee.php, previously a
     * comingSoon placeholder) is now live, gated by the 'assets.view' permission
     * — but RolesAndPermissionsSeeder only runs once on brand-new tenant
     * creation, so existing tenants' Employee role never got this grant.
     * The 'assets.view' permission row itself already exists in every tenant
     * (seeded for all modules on provisioning); this just attaches it to the
     * Employee role.
     */
    public function up(): void
    {
        $permission = Permission::where('name', 'assets.view')->where('guard_name', 'web')->first();
        $employee   = Role::where('name', 'Employee')->where('guard_name', 'web')->first();

        if ($permission && $employee && ! $employee->hasPermissionTo($permission)) {
            $employee->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $employee = Role::where('name', 'Employee')->where('guard_name', 'web')->first();
        $employee?->revokePermissionTo('assets.view');
    }
};
