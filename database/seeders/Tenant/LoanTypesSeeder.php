<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoanTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Personal Loan',  'code' => 'PERSONAL', 'icon' => 'wallet', 'color' => '#6C7DF7',
                'description' => 'General-purpose loan for personal use',
                'default_interest_rate' => 6.00, 'interest_type' => 'reducing_balance',
                'max_amount' => 25000, 'min_amount' => 500,
                'max_tenure_months' => 36, 'min_tenure_months' => 3,
                'min_service_months' => 6,  'max_salary_multiplier' => 3.0,
                'requires_guarantor' => false, 'auto_deduct_from_payroll' => true, 'allow_early_repayment' => true,
            ],
            [
                'name' => 'Emergency Loan', 'code' => 'EMERGENCY', 'icon' => 'alert-triangle', 'color' => '#EF4444',
                'description' => 'For urgent medical or emergency expenses',
                'default_interest_rate' => 0.00, 'interest_type' => 'zero_interest',
                'max_amount' => 5000, 'min_amount' => 200,
                'max_tenure_months' => 12, 'min_tenure_months' => 1,
                'min_service_months' => 3,  'max_salary_multiplier' => 1.0,
                'requires_guarantor' => false, 'auto_deduct_from_payroll' => true, 'allow_early_repayment' => true,
            ],
            [
                'name' => 'Education Loan', 'code' => 'EDUCATION', 'icon' => 'graduation-cap', 'color' => '#10B981',
                'description' => 'For employee or dependent education expenses',
                'default_interest_rate' => 3.00, 'interest_type' => 'reducing_balance',
                'max_amount' => 50000, 'min_amount' => 1000,
                'max_tenure_months' => 60, 'min_tenure_months' => 6,
                'min_service_months' => 12, 'max_salary_multiplier' => 5.0,
                'requires_guarantor' => false, 'requires_documentation' => true,
                'auto_deduct_from_payroll' => true, 'allow_early_repayment' => true,
            ],
            [
                'name' => 'Home Loan',      'code' => 'HOME',     'icon' => 'home',  'color' => '#F59E0B',
                'description' => 'For home purchase, repair, or renovation',
                'default_interest_rate' => 5.00, 'interest_type' => 'reducing_balance',
                'max_amount' => 100000, 'min_amount' => 5000,
                'max_tenure_months' => 120, 'min_tenure_months' => 12,
                'min_service_months' => 24, 'max_salary_multiplier' => 10.0,
                'requires_guarantor' => true,  'requires_documentation' => true, 'requires_collateral' => true,
                'auto_deduct_from_payroll' => true, 'allow_early_repayment' => true,
            ],
            [
                'name' => 'Vehicle Loan',   'code' => 'VEHICLE',  'icon' => 'car',   'color' => '#3B82F6',
                'description' => 'For vehicle purchase',
                'default_interest_rate' => 4.50, 'interest_type' => 'reducing_balance',
                'max_amount' => 40000, 'min_amount' => 1000,
                'max_tenure_months' => 60, 'min_tenure_months' => 6,
                'min_service_months' => 12, 'max_salary_multiplier' => 5.0,
                'requires_guarantor' => false, 'requires_documentation' => true,
                'auto_deduct_from_payroll' => true, 'allow_early_repayment' => true,
            ],
        ];

        foreach ($types as $type) {
            $type['is_active']  = true;
            $type['created_at'] = now();
            $type['updated_at'] = now();
            DB::table('loan_types')->updateOrInsert(['code' => $type['code']], $type);
        }
    }
}