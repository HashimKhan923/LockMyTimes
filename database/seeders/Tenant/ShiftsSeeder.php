<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftsSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Standard Day',  'code' => 'DAY',
                'start_time' => '09:00:00', 'end_time' => '17:00:00',
                'break_minutes' => 60, 'crosses_midnight' => false, 'total_hours' => 8.0,
                'color' => '#6C7DF7',
                'late_grace_minutes' => 10, 'early_out_grace_minutes' => 10,
                'working_days' => json_encode(['mon','tue','wed','thu','fri']),
            ],
            [
                'name' => 'Morning Shift', 'code' => 'MORN',
                'start_time' => '06:00:00', 'end_time' => '14:00:00',
                'break_minutes' => 30, 'crosses_midnight' => false, 'total_hours' => 8.0,
                'color' => '#10B981',
                'late_grace_minutes' => 10, 'early_out_grace_minutes' => 10,
                'working_days' => json_encode(['mon','tue','wed','thu','fri']),
            ],
            [
                'name' => 'Evening Shift', 'code' => 'EVE',
                'start_time' => '14:00:00', 'end_time' => '22:00:00',
                'break_minutes' => 30, 'crosses_midnight' => false, 'total_hours' => 8.0,
                'color' => '#F59E0B',
                'late_grace_minutes' => 10, 'early_out_grace_minutes' => 10,
                'working_days' => json_encode(['mon','tue','wed','thu','fri','sat','sun']),
            ],
            [
                'name' => 'Night Shift',   'code' => 'NIGHT',
                'start_time' => '22:00:00', 'end_time' => '06:00:00',
                'break_minutes' => 30, 'crosses_midnight' => true,  'total_hours' => 8.0,
                'color' => '#8B5CF6',
                'late_grace_minutes' => 10, 'early_out_grace_minutes' => 10,
                'working_days' => json_encode(['mon','tue','wed','thu','fri','sat','sun']),
            ],
        ];

        foreach ($shifts as $shift) {
            $shift['is_active']  = true;
            $shift['created_at'] = now();
            $shift['updated_at'] = now();
            DB::table('shifts')->updateOrInsert(['code' => $shift['code']], $shift);
        }
    }
}