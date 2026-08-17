<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 2024 federal income tax brackets (previously hardcoded in PayrollService) as real
 * tax_settings rows, so PayrollService::federalTax() has real data to read from tenant DBs
 * that predate the tax-engine rework, without changing the numbers anyone's payroll already
 * relies on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::connection('tenant')->table('tax_settings')
            ->where('tax_type', 'federal_income')
            ->whereNull('state')
            ->where('year', 2024)
            ->exists();

        if ($exists) {
            return;
        }

        DB::connection('tenant')->table('tax_settings')->insert([
            'year' => 2024,
            'state' => null,
            'tax_type' => 'federal_income',
            'brackets' => json_encode([
                ['min' => 0,      'max' => 11600,      'base' => 0,      'rate' => 0.10],
                ['min' => 11600,  'max' => 47150,      'base' => 1160,   'rate' => 0.12],
                ['min' => 47150,  'max' => 100525,     'base' => 5426,   'rate' => 0.22],
                ['min' => 100525, 'max' => 191950,     'base' => 17168,  'rate' => 0.24],
                ['min' => 191950, 'max' => 243725,     'base' => 39110,  'rate' => 0.32],
                ['min' => 243725, 'max' => 609350,     'base' => 55678,  'rate' => 0.35],
                ['min' => 609350, 'max' => PHP_INT_MAX, 'base' => 183647, 'rate' => 0.37],
            ]),
            'flat_rate' => null,
            'wage_base' => 14600, // standard deduction subtracted before applying brackets
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::connection('tenant')->table('tax_settings')
            ->where('tax_type', 'federal_income')
            ->whereNull('state')
            ->where('year', 2024)
            ->delete();
    }
};
