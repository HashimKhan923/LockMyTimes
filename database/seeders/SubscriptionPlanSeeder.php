<?php

namespace Database\Seeders;

use App\Models\Main\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'           => 'Starter',
                'slug'           => 'starter',
                'description'    => 'Perfect for small teams and startups just getting organized.',
                'badge'          => null,
                'monthly_price'  => 29.00,
                'yearly_price'   => 290.00,    // ~2 months free
                'max_employees'  => 25,
                'max_storage_gb' => 10,
                'max_admins'     => 2,
                'has_attendance'   => true,
                'has_payroll'      => true,
                'has_performance'  => true,
                'has_assets'       => true,
                'has_training'     => false,
                'has_recruitment'  => false,
                'has_documents'    => true,
                'has_expenses'     => false,
                'has_api_access'   => false,
                'has_white_label'  => false,
                'has_priority_support' => false,
                'features' => [
                    'Up to 25 employees',
                    '10 GB document storage',
                    'QR code attendance with geofencing',
                    'Payroll management',
                    'Basic performance reviews',
                    'Asset tracking',
                    'Document repository',
                    'Email support',
                ],
                'sort_order'  => 1,
                'is_active'   => true,
                'is_featured' => false,
                'trial_days'  => 14,
            ],
            [
                'name'           => 'Professional',
                'slug'           => 'professional',
                'description'    => 'Best for growing companies that need full HR automation.',
                'badge'          => 'Most Popular',
                'monthly_price'  => 79.00,
                'yearly_price'   => 790.00,
                'max_employees'  => 150,
                'max_storage_gb' => 50,
                'max_admins'     => 5,
                'has_attendance'   => true,
                'has_payroll'      => true,
                'has_performance'  => true,
                'has_assets'       => true,
                'has_training'     => true,
                'has_recruitment'  => true,
                'has_documents'    => true,
                'has_expenses'     => true,
                'has_api_access'   => true,
                'has_white_label'  => false,
                'has_priority_support' => true,
                'features' => [
                    'Up to 150 employees',
                    '50 GB document storage',
                    'QR code attendance with geofencing',
                    'Full payroll with tax calculations',
                    '360° performance reviews & OKRs',
                    'Asset tracking with QR tags',
                    'Training & certifications',
                    'Recruitment / ATS',
                    'Expense management',
                    'Mobile app for employees',
                    'API access',
                    'Priority email & chat support',
                ],
                'sort_order'  => 2,
                'is_active'   => true,
                'is_featured' => true,
                'trial_days'  => 14,
            ],
            [
                'name'           => 'Enterprise',
                'slug'           => 'enterprise',
                'description'    => 'Unlimited scale, white-label branding, and dedicated support.',
                'badge'          => 'Best Value',
                'monthly_price'  => 199.00,
                'yearly_price'   => 1990.00,
                'max_employees'  => 9999,
                'max_storage_gb' => 500,
                'max_admins'     => 20,
                'has_attendance'   => true,
                'has_payroll'      => true,
                'has_performance'  => true,
                'has_assets'       => true,
                'has_training'     => true,
                'has_recruitment'  => true,
                'has_documents'    => true,
                'has_expenses'     => true,
                'has_api_access'   => true,
                'has_white_label'  => true,
                'has_priority_support' => true,
                'features' => [
                    'Unlimited employees',
                    '500 GB document storage',
                    'Everything in Professional',
                    'White-label branding',
                    'Custom domain support',
                    'Advanced reporting & analytics',
                    'SSO / SAML',
                    'Dedicated account manager',
                    '24/7 phone support',
                    'Custom integrations',
                    'SLA guarantee',
                ],
                'sort_order'  => 3,
                'is_active'   => true,
                'is_featured' => false,
                'trial_days'  => 14,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}