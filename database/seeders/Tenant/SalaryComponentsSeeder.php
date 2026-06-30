<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryComponentsSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            // Earnings
            ['name' => 'Base Salary',        'code' => 'BASE',     'type' => 'earning',       'calculation' => 'fixed',         'is_taxable' => true,  'is_recurring' => true],
            ['name' => 'Overtime Pay',       'code' => 'OT',       'type' => 'earning',       'calculation' => 'hours_x_rate',  'is_taxable' => true,  'is_recurring' => false],
            ['name' => 'Bonus',              'code' => 'BONUS',    'type' => 'earning',       'calculation' => 'fixed',         'is_taxable' => true,  'is_recurring' => false],
            ['name' => 'Commission',         'code' => 'COMM',     'type' => 'earning',       'calculation' => 'percentage',    'is_taxable' => true,  'is_recurring' => false],
            ['name' => 'Holiday Pay',        'code' => 'HOLPAY',   'type' => 'earning',       'calculation' => 'hours_x_rate',  'is_taxable' => true,  'is_recurring' => false],

            // Reimbursements
            ['name' => 'Expense Reimbursement','code' => 'EXP',    'type' => 'reimbursement', 'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => false],
            ['name' => 'Mileage Reimbursement','code' => 'MILE',   'type' => 'reimbursement', 'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => false],

            // Taxes (auto-calculated)
            ['name' => 'Federal Income Tax', 'code' => 'FED_TAX',  'type' => 'tax',           'calculation' => 'formula',       'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'State Income Tax',   'code' => 'STATE_TAX','type' => 'tax',           'calculation' => 'formula',       'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'FICA - Social Security','code'=>'FICA_SS', 'type' => 'tax',           'calculation' => 'percentage',    'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'FICA - Medicare',    'code' => 'FICA_MED', 'type' => 'tax',           'calculation' => 'percentage',    'is_taxable' => false, 'is_recurring' => true],

            // Deductions
            ['name' => 'Health Insurance',   'code' => 'HEALTH',   'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Dental Insurance',   'code' => 'DENTAL',   'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Vision Insurance',   'code' => 'VISION',   'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => true],
            ['name' => '401(k) Contribution','code' => '401K',     'type' => 'deduction',     'calculation' => 'percentage',    'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Loan Deduction',     'code' => 'LOAN',     'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Salary Advance Deduction','code' => 'ADV', 'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => false],
            ['name' => 'Garnishment',        'code' => 'GARN',     'type' => 'deduction',     'calculation' => 'fixed',         'is_taxable' => false, 'is_recurring' => true],
        ];

        foreach ($components as $comp) {
            $comp['default_value']      = 0;
            $comp['shows_on_payslip']   = true;
            $comp['is_active']          = true;
            $comp['created_at']         = now();
            $comp['updated_at']         = now();
            DB::table('salary_components')->updateOrInsert(['code' => $comp['code']], $comp);
        }
    }
}