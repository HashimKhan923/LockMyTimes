<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Confirm a company code (tenant slug) is valid before showing the
     * login form. The `tenant.api` middleware has already resolved and
     * connected the tenant by this point (404/503 otherwise), so this
     * just echoes back display info for the onboarding screen.
     */
    public function resolve(Request $request): JsonResponse
    {
        $tenant = app(TenantManager::class)->current();

        return response()->json([
            'company_name' => $tenant->company_name,
            'logo_url' => $tenant->logo_url,
            'status' => $tenant->status,
        ]);
    }
}
