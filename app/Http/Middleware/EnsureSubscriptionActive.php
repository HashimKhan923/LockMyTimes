<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantManager->current();

        if (! $tenant) {
            return $next($request);
        }

        if (in_array($tenant->status, ['suspended', 'cancelled'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription is not active. Please contact support.',
                    'status'  => $tenant->status,
                ], 402);
            }

            abort(402, "This workspace is currently {$tenant->status}. Please contact support.");
        }

        return $next($request);
    }
}