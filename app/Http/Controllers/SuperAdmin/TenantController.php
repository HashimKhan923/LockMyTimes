<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SubscriptionPlan;
use App\Models\Main\Subscription;
use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with('activeSubscription.plan')
            ->withCount('subscriptions');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->latest()->paginate(20)->withQueryString();

        $statusCounts = Tenant::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('superadmin.tenants.index', compact('tenants', 'statusCounts'));
    }

    public function create()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(Request $request, TenantManager $manager)
    {
        $data = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|unique:main.tenants,contact_email',
            'contact_phone'  => 'nullable|string|max:30',
            'industry'       => 'nullable|string|max:100',
            'company_size'   => 'nullable|integer|min:1',
            'website'        => 'nullable|url|max:255',
            'address_line1'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|size:2',
            'timezone'       => 'nullable|string|max:60',
            'plan_id'        => 'nullable|exists:main.subscription_plans,id',
            'billing_cycle'  => 'nullable|in:monthly,yearly',
            'trial_days'      => 'nullable|integer|min:0|max:365',
            'admin_password'  => 'required|string|min:8',
        ]);

        $slug = Str::slug($data['company_name']);
        $base = $slug;
        $i    = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $tenant = Tenant::create([
            'company_name'   => $data['company_name'],
            'slug'           => $slug,
            'database_name'  => Tenant::generateDatabaseName($slug),
            'contact_name'   => $data['contact_name'],
            'contact_email'  => $data['contact_email'],
            'contact_phone'  => $data['contact_phone'] ?? null,
            'industry'       => $data['industry'] ?? null,
            'company_size'   => $data['company_size'] ?? null,
            'website'        => $data['website'] ?? null,
            'address_line1'  => $data['address_line1'] ?? null,
            'city'           => $data['city'] ?? null,
            'state'          => $data['state'] ?? null,
            'postal_code'    => $data['postal_code'] ?? null,
            'country'        => $data['country'] ?? 'US',
            'timezone'       => $data['timezone'] ?? 'America/New_York',
            'status'         => 'trial',
            'trial_ends_at'  => now()->addDays((int) ($data['trial_days'] ?? 14)),
        ]);

        // Provision the tenant database
        $manager->provision($tenant);

        // Create the initial tenant admin user inside the tenant DB
        $manager->runFor($tenant, function () use ($data) {
            $user = \App\Models\Tenant\User::firstOrCreate(
                ['email' => $data['contact_email']],
                [
                    'name'              => $data['contact_name'],
                    'password'          => Hash::make($data['admin_password']),
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('Tenant Admin');
        });

        // Create subscription if a plan was chosen
        if (!empty($data['plan_id'])) {
            $plan  = SubscriptionPlan::find($data['plan_id']);
            $cycle = $data['billing_cycle'] ?? 'monthly';
            Subscription::create([
                'tenant_id'                => $tenant->id,
                'plan_id'                  => $plan->id,
                'billing_cycle'            => $cycle,
                'amount'                   => $cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
                'currency'                 => $plan->currency ?? 'USD',
                'status'                   => 'trialing',
                'trial_starts_at'          => now(),
                'trial_ends_at'            => $tenant->trial_ends_at,
                'current_period_starts_at' => now(),
                'current_period_ends_at'   => $tenant->trial_ends_at,
            ]);

            // Sync plan feature flags and limits to the tenant
            $tenant->applyPlan($plan);
        }

        return redirect()
            ->route('superadmin.tenants.show', $tenant)
            ->with('success', "Tenant \"{$tenant->company_name}\" created and database provisioned successfully.");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'subscriptions.plan',
            'payments' => fn($q) => $q->latest()->limit(10),
            'supportTickets' => fn($q) => $q->latest()->limit(5),
        ]);

        $plans = SubscriptionPlan::active()->ordered()->get();

        $lifetimeRevenue = $tenant->payments()
            ->where('status', 'succeeded')
            ->sum('amount');

        return view('superadmin.tenants.show', compact('tenant', 'plans', 'lifetimeRevenue'));
    }

    public function edit(Tenant $tenant)
    {
        return view('superadmin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'company_name'  => 'required|string|max:255',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|unique:main.tenants,contact_email,'.$tenant->id,
            'contact_phone' => 'nullable|string|max:30',
            'industry'      => 'nullable|string|max:100',
            'company_size'  => 'nullable|integer|min:1',
            'website'       => 'nullable|url|max:255',
            'address_line1' => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:50',
            'postal_code'   => 'nullable|string|max:20',
            'country'       => 'nullable|string|size:2',
            'timezone'      => 'nullable|string|max:60',
            'status'        => 'required|in:pending,trial,active,past_due,suspended,cancelled',
        ]);

        $tenant->update($data);

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', 'Tenant details updated successfully.');
    }

    public function extendTrial(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $base = ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
            ? $tenant->trial_ends_at
            : now();

        $tenant->update([
            'trial_ends_at' => $base->addDays($data['days']),
            'status'        => 'trial',
        ]);

        return back()->with('success', "Trial extended by {$data['days']} days.");
    }

    public function changePlan(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'plan_id'       => 'required|exists:main.subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        // Cancel existing active subscriptions
        $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled']);

        Subscription::create([
            'tenant_id'                => $tenant->id,
            'plan_id'                  => $plan->id,
            'billing_cycle'            => $data['billing_cycle'],
            'amount'                   => $data['billing_cycle'] === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
            'currency'                 => $plan->currency ?? 'USD',
            'status'                   => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at'   => $data['billing_cycle'] === 'yearly'
                ? now()->addYear()
                : now()->addMonth(),
        ]);

        $tenant->update(['status' => 'active']);

        // Sync the new plan's feature flags and limits to the tenant
        $tenant->applyPlan($plan);

        return back()->with('success', "Plan changed to {$plan->name} ({$data['billing_cycle']}).");
    }

    public function addNote(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $settings = $tenant->settings ?? [];
        $notes    = $settings['admin_notes'] ?? [];
        $notes[]  = [
            'text'    => $data['note'],
            'by'      => Auth::guard('superadmin')->user()->name,
            'at'      => now()->toDateTimeString(),
        ];
        $settings['admin_notes'] = $notes;
        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Note added.');
    }

    public function deleteNote(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['index' => 'required|integer|min:0']);

        $settings = $tenant->settings ?? [];
        $notes    = $settings['admin_notes'] ?? [];
        array_splice($notes, $data['index'], 1);
        $settings['admin_notes'] = $notes;
        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Note deleted.');
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        $tenant->update([
            'status'             => 'suspended',
            'suspended_at'       => now(),
            'suspension_reason'  => $request->reason,
        ]);

        return back()->with('success', "Tenant \"{$tenant->company_name}\" has been suspended.");
    }

    public function unsuspend(Tenant $tenant)
    {
        $tenant->update([
            'status'            => 'active',
            'suspended_at'      => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', "Tenant \"{$tenant->company_name}\" has been reactivated.");
    }

    public function impersonate(Tenant $tenant)
    {
        if (! $tenant->database_provisioned) {
            return back()->with('error', 'This tenant database is not yet provisioned.');
        }

        // Store impersonation data in session
        session([
            'impersonating_tenant'    => $tenant->slug,
            'impersonating_as_admin'  => true,
            'superadmin_id'           => Auth::guard('superadmin')->id(),
        ]);

        return redirect($tenant->adminUrl().'/login')
            ->with('success', "You are now impersonating {$tenant->company_name}. Use their admin login.");
    }

    public function destroy(Request $request, Tenant $tenant, TenantManager $manager)
    {
        $request->validate(['confirm_name' => 'required|string']);

        if ($request->confirm_name !== $tenant->company_name) {
            return back()->with('error', 'Company name confirmation did not match.');
        }

        $manager->deprovision($tenant);
        $name = $tenant->company_name;
        $tenant->forceDelete();

        return redirect()
            ->route('superadmin.tenants.index')
            ->with('success', "Tenant \"{$name}\" and all their data has been permanently deleted.");
    }
}