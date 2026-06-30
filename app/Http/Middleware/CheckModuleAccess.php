<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = $this->tenantManager->current();

        if ($tenant && ! $tenant->hasModule($module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "The {$module} module is not included in your current plan.",
                ], 403);
            }

            return redirect()
                ->route('admin.upgrade', $request->route('tenant'))
                ->with('locked_module', $module);
        }

        return $next($request);
    }
}
