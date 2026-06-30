<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Laptop',           'icon' => 'laptop',         'description' => 'Company-issued laptops and notebooks'],
            ['name' => 'Desktop Computer', 'icon' => 'monitor',        'description' => 'Workstation desktops'],
            ['name' => 'Mobile Phone',     'icon' => 'smartphone',     'description' => 'Company mobile phones'],
            ['name' => 'Tablet',           'icon' => 'tablet',         'description' => 'Tablets and iPads'],
            ['name' => 'Monitor',          'icon' => 'monitor',        'description' => 'External monitors and displays'],
            ['name' => 'Peripherals',      'icon' => 'mouse',          'description' => 'Keyboards, mice, headsets'],
            ['name' => 'Vehicle',          'icon' => 'car',            'description' => 'Company vehicles'],
            ['name' => 'Office Equipment', 'icon' => 'printer',        'description' => 'Printers, scanners, fax'],
            ['name' => 'Furniture',        'icon' => 'sofa',           'description' => 'Office furniture'],
            ['name' => 'Access Card',      'icon' => 'credit-card',    'description' => 'Building / parking access cards'],
            ['name' => 'ID Badge',         'icon' => 'badge-check',    'description' => 'Employee ID badges'],
            ['name' => 'Software License', 'icon' => 'key',            'description' => 'Software licenses & subscriptions'],
            ['name' => 'Other',            'icon' => 'package',        'description' => 'Miscellaneous assets'],
        ];

        foreach ($categories as $cat) {
            $cat['is_active']  = true;
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
            DB::table('asset_categories')->updateOrInsert(['name' => $cat['name']], $cat);
        }
    }
}