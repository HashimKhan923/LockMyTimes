<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeApiAuth
{
    /**
     * JSON analog of EnsureEmployeeAuth for the mobile API — same checks,
     * no redirects. Must run after `tenant.api` + `auth:sanctum`.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Re-assert tenant DB connection (cheap insurance, mirrors web middleware)
        $tenant = app(TenantManager::class)->current();
        if ($tenant && $tenant->database_name) {
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($reason = $user->loginBlockReason()) {
            return response()->json(['message' => $reason], 401);
        }

        if (! $user->hasRole('Employee')) {
            return response()->json(['message' => 'This app is for employees only.'], 403);
        }

        if ($user->must_change_password && ! $this->isPasswordChangeRoute($request)) {
            return response()->json(['message' => 'You must change your password before continuing.', 'must_change_password' => true], 423);
        }

        app()->setLocale($user->locale ?? 'en');
        $tz = $user->timezone ?? config('app.timezone');
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);

        return $next($request);
    }

    protected function isPasswordChangeRoute(Request $request): bool
    {
        return $request->routeIs('api.v1.auth.password') || $request->routeIs('api.v1.auth.logout');
    }
}
