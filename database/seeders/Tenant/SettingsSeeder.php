<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'company.name',             'value' => 'My Company',          'group' => 'general',    'type' => 'string'],
            ['key' => 'company.currency',         'value' => 'USD',                 'group' => 'general',    'type' => 'string'],
            ['key' => 'company.timezone',         'value' => 'America/New_York',    'group' => 'general',    'type' => 'string'],
            ['key' => 'company.date_format',      'value' => 'M d, Y',              'group' => 'general',    'type' => 'string'],
            ['key' => 'company.time_format',      'value' => 'h:i A',               'group' => 'general',    'type' => 'string'],
            ['key' => 'company.week_starts_on',   'value' => 'monday',              'group' => 'general',    'type' => 'string'],
            ['key' => 'company.fiscal_year_start','value' => '01-01',               'group' => 'general',    'type' => 'string'],

            // Attendance
            ['key' => 'attendance.allow_qr',              'value' => '1',           'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'attendance.allow_web',             'value' => '1',           'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'attendance.allow_mobile',          'value' => '1',           'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'attendance.require_selfie',        'value' => '0',           'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'attendance.geofence_strict',       'value' => '1',           'group' => 'attendance', 'type' => 'boolean'],
            ['key' => 'attendance.late_grace_minutes',    'value' => '10',          'group' => 'attendance', 'type' => 'integer'],
            ['key' => 'attendance.overtime_threshold',    'value' => '8',           'group' => 'attendance', 'type' => 'integer'],

            // Payroll
            ['key' => 'payroll.pay_schedule',     'value' => 'bi_weekly',           'group' => 'payroll',    'type' => 'string'],
            ['key' => 'payroll.overtime_rate',    'value' => '1.5',                 'group' => 'payroll',    'type' => 'string'],
            ['key' => 'payroll.fica_ss_rate',     'value' => '6.2',                 'group' => 'payroll',    'type' => 'string'],
            ['key' => 'payroll.fica_medicare_rate','value'=> '1.45',                'group' => 'payroll',    'type' => 'string'],
            ['key' => 'payroll.ss_wage_base',     'value' => '168600',              'group' => 'payroll',    'type' => 'string'],
            ['key' => 'payroll.mileage_rate',     'value' => '0.67',                'group' => 'payroll',    'type' => 'string'],

            // Notifications
            ['key' => 'notifications.email_enabled', 'value' => '1',                'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notifications.sms_enabled',   'value' => '0',                'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notifications.push_enabled',  'value' => '1',                'group' => 'notifications', 'type' => 'boolean'],

            // Leave
            ['key' => 'leaves.auto_approve_under_days', 'value' => '0',             'group' => 'leaves',     'type' => 'integer'],
            ['key' => 'leaves.allow_negative_balance',  'value' => '0',             'group' => 'leaves',     'type' => 'boolean'],

            // Theme
            ['key' => 'theme.primary_color',   'value' => '#6C7DF7',                'group' => 'theme',      'type' => 'string'],
            ['key' => 'theme.secondary_color', 'value' => '#FFB547',                'group' => 'theme',      'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            $setting['is_public']  = false;
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('settings')->updateOrInsert(['key' => $setting['key']], $setting);
        }
    }
}