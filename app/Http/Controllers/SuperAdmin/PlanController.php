<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class PlanController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->withCount('subscriptions')->get();
        return view('superadmin.plans.index', compact('plans'));
    }

    /* ================================================================
     | CREATE
     |================================================================*/
    public function create()
    {
        return view('superadmin.plans.create');
    }

    /* ================================================================
     | STORE — create plan locally, then auto-provision in Stripe
     |================================================================*/
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['slug']     = $data['slug'] ?? Str::slug($data['name']);
        $data['features'] = array_values(array_filter($data['features'] ?? []));
        $data             = $this->castBooleans($request, $data);

        $plan = SubscriptionPlan::create($data);

        // Auto-provision in Stripe
        [$ok, $msg] = $this->syncPlanToStripe($plan);

        return redirect()->route('superadmin.plans.index')
            ->with($ok ? 'success' : 'warning',
                $ok
                    ? "Plan \"{$plan->name}\" created and synced to Stripe."
                    : "Plan created but Stripe sync failed: {$msg}");
    }

    /* ================================================================
     | EDIT
     |================================================================*/
    public function edit(SubscriptionPlan $plan)
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    /* ================================================================
     | UPDATE — update locally, update Stripe product name/metadata
     |================================================================*/
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $this->validated($request, $plan->id);

        $data['slug']     = $data['slug'] ?? Str::slug($data['name']);
        $data['features'] = array_values(array_filter($data['features'] ?? []));
        $data             = $this->castBooleans($request, $data);

        $plan->update($data);

        // Re-sync to Stripe (update product name / archive old prices / create new prices if changed)
        [$ok, $msg] = $this->syncPlanToStripe($plan->fresh());

        return redirect()->route('superadmin.plans.index')
            ->with($ok ? 'success' : 'warning',
                $ok
                    ? "Plan \"{$plan->name}\" updated and synced to Stripe."
                    : "Plan updated but Stripe sync failed: {$msg}");
    }

    /* ================================================================
     | SYNC TO STRIPE — manual re-sync button
     |================================================================*/
    public function syncStripe(SubscriptionPlan $plan)
    {
        [$ok, $msg] = $this->syncPlanToStripe($plan);

        return back()->with($ok ? 'success' : 'error',
            $ok ? "Plan \"{$plan->name}\" synced to Stripe." : "Stripe sync failed: {$msg}");
    }

    /* ================================================================
     | TOGGLE
     |================================================================*/
    public function toggle(SubscriptionPlan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);
        $state = $plan->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Plan \"{$plan->name}\" {$state}.");
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a plan with active subscriptions.');
        }

        // Archive Stripe product
        if ($plan->stripe_product_id) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $stripe->products->update($plan->stripe_product_id, ['active' => false]);
            } catch (\Throwable) {}
        }

        $name = $plan->name;
        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', "Plan \"{$name}\" deleted.");
    }

    /* ================================================================
     | STRIPE SYNC — core logic
     |  1. Create or update Stripe Product
     |  2. Create monthly Price (archive old one if price changed)
     |  3. Create yearly Price  (archive old one if price changed)
     |  4. Save stripe IDs back to local plan
     |================================================================*/
    protected function syncPlanToStripe(SubscriptionPlan $plan): array
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $metadata = [
                'plan_slug'       => $plan->slug,
                'max_employees'   => (string) ($plan->max_employees ?? 'unlimited'),
                'max_storage_gb'  => (string) ($plan->max_storage_gb ?? 'unlimited'),
                'has_payroll'     => $plan->has_payroll ? '1' : '0',
                'has_recruitment' => $plan->has_recruitment ? '1' : '0',
            ];

            /* ── 1. Product ── */
            if ($plan->stripe_product_id) {
                $product = $stripe->products->update($plan->stripe_product_id, [
                    'name'        => $plan->name,
                    'description' => $plan->description ?? $plan->name . ' - HR Platform Plan',
                    'metadata'    => $metadata,
                    'active'      => (bool) $plan->is_active,
                ]);
            } else {
                $product = $stripe->products->create([
                    'name'        => $plan->name,
                    'description' => $plan->description ?? $plan->name . ' - HR Platform Plan',
                    'metadata'    => $metadata,
                    'type'        => 'service',
                ]);
            }

            $productId  = $product->id;
            $currency   = strtolower($plan->currency ?? 'usd');

            /* ── 2. Monthly Price ── */
            $monthlyId  = $this->ensurePrice(
                $stripe,
                $plan->stripe_monthly_price_id,
                $productId,
                (int) round((float) $plan->monthly_price * 100),
                $currency,
                'month',
                $plan->name . ' – Monthly'
            );

            /* ── 3. Yearly Price ── */
            $yearlyId   = $this->ensurePrice(
                $stripe,
                $plan->stripe_yearly_price_id,
                $productId,
                (int) round((float) $plan->yearly_price * 100),
                $currency,
                'year',
                $plan->name . ' – Yearly'
            );

            /* ── 4. Save back ── */
            $plan->update([
                'stripe_product_id'       => $productId,
                'stripe_monthly_price_id' => $monthlyId,
                'stripe_yearly_price_id'  => $yearlyId,
            ]);

            return [true, null];

        } catch (ApiErrorException $e) {
            return [false, $e->getMessage()];
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * Return the existing Price ID if the amount matches, otherwise
     * archive the old price and create a new one.
     */
    protected function ensurePrice(
        StripeClient $stripe,
        ?string $existingPriceId,
        string $productId,
        int $unitAmountCents,
        string $currency,
        string $interval,
        string $nickname
    ): string {
        // Free plan — Stripe does not support $0 recurring prices, use a nominal amount
        $unitAmountCents = max($unitAmountCents, 0);

        if ($existingPriceId) {
            try {
                $existing = $stripe->prices->retrieve($existingPriceId);
                // If amount matches, reuse
                if ($existing->unit_amount === $unitAmountCents && $existing->active) {
                    return $existingPriceId;
                }
                // Archive the old price
                $stripe->prices->update($existingPriceId, ['active' => false]);
            } catch (\Throwable) {
                // Old price doesn't exist — fall through to create
            }
        }

        // Create new price
        $params = [
            'product'     => $productId,
            'currency'    => $currency,
            'nickname'    => $nickname,
            'recurring'   => ['interval' => $interval],
            'metadata'    => ['interval' => $interval],
        ];

        if ($unitAmountCents > 0) {
            $params['unit_amount'] = $unitAmountCents;
        } else {
            // Free: use unit_amount 0 (only works for some Stripe accounts; use trial instead)
            $params['unit_amount'] = 0;
        }

        $price = $stripe->prices->create($params);
        return $price->id;
    }

    /* ================================================================
     | Validation helper
     |================================================================*/
    protected function validated(Request $request, ?int $planId = null): array
    {
        $uniqueSlug = $planId
            ? 'nullable|string|max:100|unique:main.subscription_plans,slug,' . $planId
            : 'nullable|string|max:100|unique:main.subscription_plans,slug';

        return $request->validate([
            'name'                    => 'required|string|max:100',
            'slug'                    => $uniqueSlug,
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
    }

    protected function castBooleans(Request $request, array $data): array
    {
        foreach ([
            'has_attendance','has_payroll','has_performance','has_assets',
            'has_training','has_recruitment','has_documents','has_expenses',
            'has_api_access','has_white_label','has_priority_support',
            'is_active','is_featured',
        ] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }
        return $data;
    }
}
