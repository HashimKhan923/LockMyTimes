<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Super Admin routes
            \Illuminate\Support\Facades\Route::middleware('web')
                ->prefix('superadmin')
                ->name('superadmin.')
                ->group(base_path('routes/superadmin.php'));

            // Tenant Admin routes (slug based: /t/{tenant}/admin/...)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->prefix('t/{tenant}/admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Employee Portal routes (slug based: /t/{tenant}/portal/...)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->prefix('t/{tenant}/portal')
                ->name('employee.')
                ->group(base_path('routes/employee.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Laravel's default guest-redirect assumes a global `login` route,
        // which this app doesn't have (only tenant-scoped employee.login /
        // admin.login). API clients never redirect — they get a 401 JSON body.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('login'));

        $middleware->priority([
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\IdentifyTenant::class,                 // ← tenant DB connects first
        \Illuminate\Routing\Middleware\SubstituteBindings::class,   // ← THEN model binding runs
        \App\Http\Middleware\EnsureSubscriptionActive::class,
        \App\Http\Middleware\EnsureAdminAuth::class,
        \App\Http\Middleware\EnsureEmployeeAuth::class,
        \Illuminate\Auth\Middleware\Authenticate::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ]);



        // Exempt Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->alias([
            'tenant'              => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.api'          => \App\Http\Middleware\IdentifyTenantFromToken::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'employee.api'        => \App\Http\Middleware\EnsureEmployeeApiAuth::class,
            'superadmin'          => \App\Http\Middleware\EnsureSuperAdmin::class,
            'admin.auth'          => \App\Http\Middleware\EnsureAdminAuth::class,
            'employee.auth'       => \App\Http\Middleware\EnsureEmployeeAuth::class,
            'role'                => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'          => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'module'              => \App\Http\Middleware\CheckModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every /api/* route is a JSON client (mobile app) regardless of the
        // Accept header it happens to send — never redirect or render HTML.
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Redirect with flash message instead of raw 403 on permission denial
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
            }
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        });
    })->create();