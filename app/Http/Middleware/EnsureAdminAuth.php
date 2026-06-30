<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuth
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantManager->current();

        if ($tenant && $tenant->database_name) {
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');
        }

        if (! Auth::guard('web')->check()) {
            return redirect()->route('admin.login', $request->route('tenant'));
        }

        $user = Auth::guard('web')->user();

        if (! $user?->is_active) {
            Auth::guard('web')->logout();
            return redirect()->route('admin.login', $request->route('tenant'))
                ->with('error', 'Your account has been deactivated.');
        }

        // Block pure Employee-role users — they belong on the employee portal.
        // Any other role (including custom roles) is allowed into the admin panel.
        if ($user->hasRole('Employee') && ! $user->hasAnyRole(['Tenant Admin', 'HR Manager', 'Manager'])) {
            return redirect()->route('employee.dashboard', $request->route('tenant'));
        }

        // Must have at least one role to enter admin panel
        if ($user->roles()->count() === 0) {
            Auth::guard('web')->logout();
            return redirect()->route('admin.login', $request->route('tenant'))
                ->with('error', 'Your account has no role assigned. Contact your administrator.');
        }

        if ($user->must_change_password) {
            return redirect()->route('admin.password.change', $request->route('tenant'));
        }

        return $next($request);
    }
}
