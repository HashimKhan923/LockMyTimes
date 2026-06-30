<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Vacation',         'code' => 'VAC',  'color' => '#10B981', 'is_paid' => true,  'default_days_per_year' => 15, 'max_carryover_days' => 5,  'accrual_method' => 'monthly',  'accrual_rate' => 1.25,  'min_service_months' => 0, 'requires_approval' => true,  'requires_documentation' => false, 'notice_days_required' => 7,  'is_active' => true],
            ['name' => 'Sick Leave',       'code' => 'SICK', 'color' => '#EF4444', 'is_paid' => true,  'default_days_per_year' => 10, 'max_carryover_days' => 0,  'accrual_method' => 'monthly',  'accrual_rate' => 0.83,  'min_service_months' => 0, 'requires_approval' => false, 'requires_documentation' => true,  'notice_days_required' => 0,  'is_active' => true],
            ['name' => 'Personal',         'code' => 'PERS', 'color' => '#6C7DF7', 'is_paid' => true,  'default_days_per_year' => 3,  'max_carryover_days' => 0,  'accrual_method' => 'annually', 'accrual_rate' => 3,     'min_service_months' => 0, 'requires_approval' => true,  'requires_documentation' => false, 'notice_days_required' => 2,  'is_active' => true],
            ['name' => 'FMLA',             'code' => 'FMLA', 'color' => '#8B5CF6', 'is_paid' => false, 'default_days_per_year' => 84, 'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 12,'requires_approval' => true,  'requires_documentation' => true,  'notice_days_required' => 30, 'is_active' => true],
            ['name' => 'Bereavement',      'code' => 'BVMT', 'color' => '#6B7280', 'is_paid' => true,  'default_days_per_year' => 5,  'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 0, 'requires_approval' => true,  'requires_documentation' => false, 'notice_days_required' => 0,  'is_active' => true],
            ['name' => 'Jury Duty',        'code' => 'JURY', 'color' => '#F59E0B', 'is_paid' => true,  'default_days_per_year' => 10, 'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 0, 'requires_approval' => true,  'requires_documentation' => true,  'notice_days_required' => 5,  'is_active' => true],
            ['name' => 'Maternity',        'code' => 'MAT',  'color' => '#EC4899', 'is_paid' => true,  'default_days_per_year' => 84, 'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 12,'requires_approval' => true,  'requires_documentation' => true,  'notice_days_required' => 30, 'is_active' => true],
            ['name' => 'Paternity',        'code' => 'PAT',  'color' => '#3B82F6', 'is_paid' => true,  'default_days_per_year' => 14, 'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 12,'requires_approval' => true,  'requires_documentation' => true,  'notice_days_required' => 30, 'is_active' => true],
            ['name' => 'Unpaid Leave',     'code' => 'UNPD', 'color' => '#9CA3AF', 'is_paid' => false, 'default_days_per_year' => 0,  'max_carryover_days' => 0,  'accrual_method' => 'none',     'accrual_rate' => 0,     'min_service_months' => 0, 'requires_approval' => true,  'requires_documentation' => false, 'notice_days_required' => 14, 'is_active' => true],
        ];

        foreach ($types as $type) {
            $type['created_at'] = now();
            $type['updated_at'] = now();
            DB::table('leave_types')->updateOrInsert(['code' => $type['code']], $type);
        }
    }
}