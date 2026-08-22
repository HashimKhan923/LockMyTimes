<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\PayrollIntegration;
use App\Models\Tenant\Setting;
use App\Models\Tenant\TaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /* ================================================================
     | INDEX — tabbed settings page
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $tab = $request->get('tab', 'general');

        // Pull all settings grouped by `group`, keyed by short key (after the dot)
        $allSettings = Setting::all()->groupBy('group')->map(function ($group) {
            return $group->mapWithKeys(fn($s) => [
                substr($s->key, strpos($s->key, '.') + 1) => $s->casted_value
            ]);
        });

        $general       = $allSettings->get('general', collect());
        $attendance    = $allSettings->get('attendance', collect());
        $payroll       = $allSettings->get('payroll', collect());
        $leaves        = $allSettings->get('leaves', collect());
        $notifications = $allSettings->get('notifications', collect());
        $theme         = $allSettings->get('theme', collect());

        $taxSettings    = TaxSetting::orderByDesc('year')->orderBy('tax_type')->get();
        $emailTemplates = EmailTemplate::orderBy('category')->orderBy('name')->get();
        $payrollIntegration = PayrollIntegration::where('provider', 'payroll_relief')->first();

        $currentYear = now()->year;

        return view('admin.settings.index', compact(
            'tab', 'general', 'attendance', 'payroll', 'leaves',
            'notifications', 'theme', 'taxSettings', 'emailTemplates',
            'payrollIntegration', 'currentYear', 'tenant'
        ));
    }

    /* ================================================================
     | UPDATE GROUP — generic handler for general/attendance/payroll/leaves/notifications
     |================================================================*/
    public function updateGroup(string $tenant, Request $request, string $group)
    {
        $validGroups = ['general', 'attendance', 'payroll', 'leaves', 'notifications', 'theme'];
        if (! in_array($group, $validGroups)) {
            abort(404);
        }

        // Type map per group — tells us how to cast/save each field
        $typeMap = $this->fieldTypeMap($group);

        foreach ($typeMap as $field => $type) {
            $key = "{$group}.{$field}";

            if ($type === 'boolean') {
                $value = $request->has($field) ? '1' : '0';
            } else {
                $value = $request->input($field);
                if ($value === null) continue; // don't overwrite if not submitted
            }

            Setting::setValue($key, $value, $group, $type);
        }

        // Special: sync theme colors back to main tenant record for branding consistency
        if ($group === 'theme') {
            $mainTenant = \App\Models\Main\Tenant::where('slug', $tenant)->first();
            if ($mainTenant) {
                $mainTenant->update([
                    'primary_color'   => $request->input('primary_color', $mainTenant->primary_color),
                    'secondary_color' => $request->input('secondary_color', $mainTenant->secondary_color),
                ]);
            }
        }

        return back()->with('success', ucfirst($group) . ' settings updated successfully.');
    }

    /* ================================================================
     | Field type map — defines exactly which fields exist per group
     |================================================================*/
    private function fieldTypeMap(string $group): array
    {
        return match($group) {
            'general' => [
                'name'              => 'string',
                'currency'          => 'string',
                'timezone'          => 'string',
                'date_format'       => 'string',
                'time_format'       => 'string',
                'week_starts_on'    => 'string',
                'fiscal_year_start' => 'string',
                'employee_code_prefix'  => 'string',
                'employee_code_padding' => 'integer',
            ],
            'attendance' => [
                'allow_qr'               => 'boolean',
                'allow_web'              => 'boolean',
                'allow_mobile'           => 'boolean',
                'geofence_strict'        => 'boolean',
                'shift_window_strict'    => 'boolean',
                'late_grace_minutes'     => 'integer',
                'early_clockin_minutes'  => 'integer',
                'early_out_grace_minutes'=> 'integer',
                'overtime_threshold'     => 'integer',
            ],
            'payroll' => [
                'pay_schedule'        => 'string',
                'overtime_rate'       => 'string',
                'fica_ss_rate'        => 'string',
                'fica_medicare_rate'  => 'string',
                'ss_wage_base'        => 'string',
                'mileage_rate'        => 'string',
            ],
            'leaves' => [
                'auto_approve_under_days' => 'integer',
                'allow_negative_balance'  => 'boolean',
            ],
            'notifications' => [
                'email_enabled' => 'boolean',
                'sms_enabled'   => 'boolean',
                'push_enabled'  => 'boolean',
            ],
            'theme' => [
                'primary_color'   => 'string',
                'secondary_color' => 'string',
            ],
            default => [],
        };
    }

    /* ================================================================
     | TAX SETTINGS — store
     |================================================================*/
    public function storeTax(string $tenant, Request $request)
    {
        $data = $request->validate([
            'year'       => 'required|integer|min:2020|max:2100',
            'state'      => 'nullable|string|max:50',
            'tax_type'   => 'required|in:federal_income,state_income,fica_ss,fica_medicare,futa,suta,sdi,local',
            'flat_rate'  => 'nullable|numeric|min:0|max:100',
            'wage_base'  => 'nullable|numeric|min:0',
            'brackets'   => 'nullable|string', // JSON string from textarea
        ]);

        if (! empty($data['brackets'])) {
            $decoded = json_decode($data['brackets'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid JSON format in tax brackets.');
            }
            $data['brackets'] = $decoded;
        }

        $data['is_active'] = true;

        TaxSetting::create($data);
        return back()->with('success', 'Tax setting added successfully.');
    }

    /* ================================================================
     | TAX SETTINGS — update
     |================================================================*/
    public function updateTax(string $tenant, Request $request, TaxSetting $taxSetting)
    {
        $data = $request->validate([
            'flat_rate'  => 'nullable|numeric|min:0|max:100',
            'wage_base'  => 'nullable|numeric|min:0',
            'is_active'  => 'boolean',
        ]);

        $taxSetting->update($data);
        return back()->with('success', 'Tax setting updated.');
    }

    /* ================================================================
     | TAX SETTINGS — destroy
     |================================================================*/
    public function destroyTax(string $tenant, TaxSetting $taxSetting)
    {
        $taxSetting->delete();
        return back()->with('success', 'Tax setting removed.');
    }

    /* ================================================================
     | EMAIL TEMPLATES — update
     |================================================================*/
    public function updateEmailTemplate(string $tenant, Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $emailTemplate->update($data);
        return back()->with('success', "Template \"{$emailTemplate->name}\" updated.");
    }

    /* ================================================================
     | COMPANY BRANDING — logo upload
     |================================================================*/
    public function updateBranding(string $tenant, Request $request)
    {
        $request->validate([
            'logo'    => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:512',
        ]);

        $mainTenant = \App\Models\Main\Tenant::where('slug', $tenant)->first();
        if (! $mainTenant) {
            return back()->with('error', 'Tenant not found.');
        }

        if ($request->hasFile('logo')) {
            if ($mainTenant->logo) {
                Storage::disk('public')->delete($mainTenant->logo);
            }
            $mainTenant->logo = $request->file('logo')->store('tenants/logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($mainTenant->favicon) {
                Storage::disk('public')->delete($mainTenant->favicon);
            }
            $mainTenant->favicon = $request->file('favicon')->store('tenants/favicons', 'public');
        }

        $mainTenant->save();

        return back()->with('success', 'Branding updated successfully.');
    }
}