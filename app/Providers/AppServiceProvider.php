<?php

namespace App\Providers;

use App\Services\TenantManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind TenantManager as a singleton so the same instance is used
        // for the whole request lifecycle.
        $this->app->singleton(TenantManager::class, fn () => new TenantManager());

        // Convenience alias: `app('tenant')`
        $this->app->alias(TenantManager::class, 'tenant');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

            Route::bind('role', function ($value) {
                return Role::where('id', $value)->firstOrFail();
            });

        // Main DB migrations live in database/migrations/main
        $this->loadMigrationsFrom(database_path('migrations/main'));

        // @fmtDate($carbon) — formats a Carbon date using the logged-in user's date_format preference
        Blade::directive('fmtDate', function ($expression) {
            return "<?php
                \$_d = $expression;
                \$_fmt = \$userDateFormat ?? 'DMY';
                echo \$_d ? match(\$_fmt) {
                    'MDY' => \$_d->format('m/d/Y'),
                    'YMD' => \$_d->format('Y-m-d'),
                    default => \$_d->format('d/m/Y'),
                } : '—';
            ?>";
        });

        // @fmtTime($carbon) — formats a Carbon time using the logged-in user's time_format preference
        Blade::directive('fmtTime', function ($expression) {
            return "<?php
                \$_t = $expression;
                \$_tfmt = \$userTimeFormat ?? '12';
                echo \$_t ? (\$_tfmt === '24' ? \$_t->format('H:i') : \$_t->format('g:i A')) : '—';
            ?>";
        });

        // @fmtDateTime($carbon) — date + time combined
        Blade::directive('fmtDateTime', function ($expression) {
            return "<?php
                \$_dt = $expression;
                \$_fmt  = \$userDateFormat ?? 'DMY';
                \$_tfmt = \$userTimeFormat ?? '12';
                \$_datePart = \$_dt ? match(\$_fmt) {
                    'MDY' => \$_dt->format('m/d/Y'),
                    'YMD' => \$_dt->format('Y-m-d'),
                    default => \$_dt->format('d/m/Y'),
                } : '—';
                \$_timePart = \$_dt ? (\$_tfmt === '24' ? \$_dt->format('H:i') : \$_dt->format('g:i A')) : '';
                echo \$_dt ? \$_datePart.' '.\$_timePart : '—';
            ?>";
        });
    }
}