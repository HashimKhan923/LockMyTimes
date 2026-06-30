<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Main\SubscriptionPlan;
use App\Services\TenantManager;

class UpgradeController extends Controller
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function index(string $tenant)
    {
        $currentTenant = $this->tenantManager->current();
        $plans         = SubscriptionPlan::active()->ordered()->get();
        $lockedModule  = session('locked_module');
        $currentPlan   = $currentTenant?->settings['plan_slug'] ?? null;

        return view('admin.upgrade', compact('tenant', 'plans', 'lockedModule', 'currentPlan', 'currentTenant'));
    }
}
