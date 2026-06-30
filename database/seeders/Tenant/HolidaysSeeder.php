<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaysSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        // US Federal Holidays for the current year
        $holidays = [
            ['name' => "New Year's Day",                     'date' => "{$year}-01-01"],
            ['name' => "Martin Luther King Jr. Day",         'date' => $this->nthDayOfMonth($year, 1, 1, 3)],     // 3rd Monday of January
            ['name' => "Presidents' Day",                    'date' => $this->nthDayOfMonth($year, 2, 1, 3)],     // 3rd Monday of February
            ['name' => "Memorial Day",                       'date' => $this->lastDayOfMonth($year, 5, 1)],       // Last Monday of May
            ['name' => "Juneteenth",                         'date' => "{$year}-06-19"],
            ['name' => "Independence Day",                   'date' => "{$year}-07-04"],
            ['name' => "Labor Day",                          'date' => $this->nthDayOfMonth($year, 9, 1, 1)],     // 1st Monday of September
            ['name' => "Columbus Day",                       'date' => $this->nthDayOfMonth($year, 10, 1, 2)],    // 2nd Monday of October
            ['name' => "Veterans Day",                       'date' => "{$year}-11-11"],
            ['name' => "Thanksgiving Day",                   'date' => $this->nthDayOfMonth($year, 11, 4, 4)],    // 4th Thursday of November
            ['name' => "Day After Thanksgiving",             'date' => $this->nthDayOfMonth($year, 11, 5, 4)],    // 4th Friday of November
            ['name' => "Christmas Eve",                      'date' => "{$year}-12-24"],
            ['name' => "Christmas Day",                      'date' => "{$year}-12-25"],
            ['name' => "New Year's Eve",                     'date' => "{$year}-12-31"],
        ];

        foreach ($holidays as $holiday) {
            DB::table('holidays')->updateOrInsert(
                ['name' => $holiday['name'], 'date' => $holiday['date']],
                array_merge($holiday, [
                    'type'         => 'federal',
                    'is_recurring' => true,
                    'is_paid'      => true,
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ])
            );
        }
    }

    /** Get the Nth occurrence of a given weekday in a month (e.g. 3rd Monday of Jan). */
    private function nthDayOfMonth(int $year, int $month, int $dayOfWeek, int $n): string
    {
        $firstDay = strtotime("{$year}-{$month}-01");
        $firstWeekday = (int) date('w', $firstDay);
        $offset = ($dayOfWeek - $firstWeekday + 7) % 7;
        $day = 1 + $offset + ($n - 1) * 7;
        return date('Y-m-d', strtotime("{$year}-{$month}-{$day}"));
    }

    /** Get the LAST occurrence of a given weekday in a month (e.g. Last Monday of May). */
    private function lastDayOfMonth(int $year, int $month, int $dayOfWeek): string
    {
        $lastDayOfMonth = (int) date('t', strtotime("{$year}-{$month}-01"));
        for ($day = $lastDayOfMonth; $day > 0; $day--) {
            if ((int) date('w', strtotime("{$year}-{$month}-{$day}")) === $dayOfWeek) {
                return date('Y-m-d', strtotime("{$year}-{$month}-{$day}"));
            }
        }
        return "{$year}-{$month}-01";
    }
}