<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Leave accrual: apply each active leave type's accrual policy to every eligible employee.
Schedule::command('leave:accrue')->monthlyOn(1, '02:00');

// Leave carry-over: roll unused balances into the new year, capped per leave type.
Schedule::command('leave:carry-over')->yearlyOn(12, 31, '23:30');
