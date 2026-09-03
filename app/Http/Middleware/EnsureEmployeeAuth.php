<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class EnsureEmployeeAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Re-assert tenant DB connection
        $tenant = app(TenantManager::class)->current();
        if ($tenant && $tenant->database_name) {
            Config::set('database.connections.tenant.database', $tenant->database_name);
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');
        }

        if (! Auth::guard('web')->check()) {
            return redirect()->route('employee.login', $request->route('tenant'));
        }

        $user = Auth::guard('web')->user();

        if ($reason = $user->loginBlockReason()) {
            Auth::guard('web')->logout();
            return redirect()->route('employee.login', $request->route('tenant'))
                ->with('error', $reason);
        }

        // Only users with the Employee role belong on the employee portal.
        // Admin/Manager/HR users should log in through the admin portal.
        if (! $user->hasRole('Employee')) {
            return redirect()->route('admin.dashboard', $request->route('tenant'));
        }

        if ($user->must_change_password) {
            return redirect()->route('employee.password.change', $request->route('tenant'));
        }

        // Apply user preferences for the entire request
        app()->setLocale($user->locale ?? 'en');
        $tz = $user->timezone ?? config('app.timezone');
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz); // makes Carbon::now() and all date functions use this timezone

        // Share date/time format preferences with all views
        view()->share('userDateFormat', $user->date_format ?? 'DMY');
        view()->share('userTimeFormat', $user->time_format ?? '12');
        view()->share('userTheme',      $user->theme      ?? 'light');

        return $next($request);
    }
}
