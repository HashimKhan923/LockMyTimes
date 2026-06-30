<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->withCount('subscriptions')->get();
        return view('superadmin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('superadmin.plans.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:100',
            'slug'                    => 'nullable|string|max:100|unique:main.subscription_plans,slug',
            'description'             => 'nullable|string|max:500',
            'badge'                   => 'nullable|string|max:50',
            'monthly_price'           => 'required|numeric|min:0',
            'yearly_price'            => 'required|numeric|min:0',
            'currency'                => 'required|string|size:3',
            'max_employees'           => 'nullable|integer|min:1',
            'max_storage_gb'          => 'nullable|integer|min:1',
            'max_admins'              => 'nullable|integer|min:1',
            'trial_days'              => 'required|integer|min:0',
            'sort_order'              => 'required|integer|min:0',
            'stripe_monthly_price_id' => 'nullable|string|max:100',
            'stripe_yearly_price_id'  => 'nullable|string|max:100',
            'stripe_product_id'       => 'nullable|string|max:100',
            'features'                => 'nullable|array',
            'features.*'              => 'string|max:200',
            'has_attendance'          => 'boolean',
            'has_payroll'             => 'boolean',
            'has_performance'         => 'boolean',
            'has_assets'              => 'boolean',
            'has_training'            => 'boolean',
            'has_recruitment'         => 'boolean',
            'has_documents'           => 'boolean',
            'has_expenses'            => 'boolean',
            'has_api_access'          => 'boolean',
            'has_white_label'         => 'boolean',
            'has_priority_support'    => 'boolean',
            'is_active'               => 'boolean',
            'is_featured'             => 'boolean',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['features'] = array_values(array_filter($data['features'] ?? []));

        foreach ([
            'has_attendance','has_payroll','has_performance','has_assets',
            'has_training','has_recruitment','has_documents','has_expenses',
            'has_api_access','has_white_label','has_priority_support',
            'is_active','is_featured',
        ] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        SubscriptionPlan::create($data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan "' . $data['name'] . '" created successfully.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:100',
            'slug'                    => 'nullable|string|max:100|unique:main.subscription_plans,slug,' . $plan->id,
            'description'             => 'nullable|string|max:500',
            'badge'                   => 'nullable|string|max:50',
            'monthly_price'           => 'required|numeric|min:0',
            'yearly_price'            => 'required|numeric|min:0',
            'currency'                => 'required|string|size:3',
            'max_employees'           => 'nullable|integer|min:1',
            'max_storage_gb'          => 'nullable|integer|min:1',
            'max_admins'              => 'nullable|integer|min:1',
            'trial_days'              => 'required|integer|min:0',
            'sort_order'              => 'required|integer|min:0',
            'stripe_monthly_price_id' => 'nullable|string|max:100',
            'stripe_yearly_price_id'  => 'nullable|string|max:100',
            'stripe_product_id'       => 'nullable|string|max:100',
            'features'                => 'nullable|array',
            'features.*'              => 'string|max:200',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['features'] = array_values(array_filter($data['features'] ?? []));

        foreach ([
            'has_attendance','has_payroll','has_performance','has_assets',
            'has_training','has_recruitment','has_documents','has_expenses',
            'has_api_access','has_white_label','has_priority_support',
            'is_active','is_featured',
        ] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        $plan->update($data);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan "' . $plan->name . '" updated successfully.');
    }

    public function toggle(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $state = $plan->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Plan \"{$plan->name}\" {$state}.");
    }

    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with active subscriptions.');
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$name}\" deleted.");
    }
}
