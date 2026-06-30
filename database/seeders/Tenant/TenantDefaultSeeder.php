<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;

class TenantDefaultSeeder extends Seeder
{
    /**
     * Seed the default data for a freshly-provisioned tenant DB.
     *
     * NOTE: This runs on the "tenant" connection (already swapped by
     * TenantManager before this seeder fires).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LeaveTypesSeeder::class,
            HolidaysSeeder::class,
            AssetCategoriesSeeder::class,
            LoanTypesSeeder::class,
            ExpenseCategoriesSeeder::class,
            SalaryComponentsSeeder::class,
            SettingsSeeder::class,
            ShiftsSeeder::class,
        ]);
    }
}