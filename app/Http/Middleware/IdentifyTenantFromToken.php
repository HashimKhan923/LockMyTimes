<?php

namespace App\Http\Middleware;

use App\Models\Main\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantFromToken
{
    public function __construct(protected TenantManager $tenantManager) {}

    /**
     * For mobile API: derive the tenant from a header (X-Tenant) sent by
     * the app, or from the authenticated user's tenant slug.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant');

        if (! $slug) {
            return response()->json(['message' => 'Missing X-Tenant header.'], 400);
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            return response()->json(['message' => 'Invalid tenant.'], 404);
        }

        if (! $tenant->database_provisioned) {
            return response()->json(['message' => 'Workspace not ready.'], 503);
        }

        $this->tenantManager->connect($tenant);

        return $next($request);
    }
}