<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Travel',           'code' => 'TRVL', 'icon' => 'plane',      'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Meals & Dining',   'code' => 'MEAL', 'icon' => 'utensils',   'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Accommodation',    'code' => 'HOTL', 'icon' => 'hotel',      'requires_receipt' => true,  'receipt_required_above' => 0],
            ['name' => 'Transportation',   'code' => 'TRSP', 'icon' => 'car',        'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Office Supplies',  'code' => 'OFFC', 'icon' => 'paperclip',  'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Software',         'code' => 'SOFT', 'icon' => 'code',       'requires_receipt' => true,  'receipt_required_above' => 0],
            ['name' => 'Training & Courses', 'code' => 'TRNG','icon' => 'book-open', 'requires_receipt' => true,  'receipt_required_above' => 0],
            ['name' => 'Mileage',          'code' => 'MILE', 'icon' => 'map',        'requires_receipt' => false, 'receipt_required_above' => 9999],
            ['name' => 'Communication',    'code' => 'COMM', 'icon' => 'phone',      'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Client Entertainment','code'=>'ENT', 'icon' => 'gift',       'requires_receipt' => true,  'receipt_required_above' => 25],
            ['name' => 'Other',            'code' => 'OTHR', 'icon' => 'more-horizontal', 'requires_receipt' => true, 'receipt_required_above' => 25],
        ];

        foreach ($categories as $cat) {
            $cat['is_active']  = true;
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
            DB::table('expense_categories')->updateOrInsert(['code' => $cat['code']], $cat);
        }
    }
}