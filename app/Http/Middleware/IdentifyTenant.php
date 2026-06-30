<?php

namespace App\Http\Middleware;

use App\Models\Main\Tenant;
use App\Models\Tenant\Setting;
use App\Services\TenantManager;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(protected TenantManager $tenantManager) {}

    /**
     * Resolve the tenant from the URL slug, switch the DB connection,
     * and share the tenant with the views.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        if (! $slug) {
            abort(404, 'Tenant not specified.');
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        if (! $tenant->database_provisioned) {
            abort(503, 'This workspace is still being prepared. Please try again in a moment.');
        }

        // Activate the tenant DB connection
        $this->tenantManager->connect($tenant);

        // Apply tenant-level settings (timezone, currency, etc.)
        $timezone = Setting::get('general.timezone', $tenant->timezone ?? config('app.timezone'));
        $currency = Setting::get('general.currency', 'USD');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        Carbon::setTestNow(); // clear any test overrides
        setlocale(LC_MONETARY, ''); // allow currency formatting

        // Share with views
        view()->share('currentTenant', $tenant);
        view()->share('tenantCurrency', $currency);
        view()->share('tenantTimezone', $timezone);

        $response = $next($request);

        // (We deliberately do NOT disconnect here — Laravel will dispose
        // of the PDO at end of request automatically.)

        return $response;
    }
}