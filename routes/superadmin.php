<?php

use App\Http\Controllers\SuperAdmin\AnalyticsController;
use App\Http\Controllers\SuperAdmin\AuditController;
use App\Http\Controllers\SuperAdmin\AuthController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\SettingsController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
| Prefix: /superadmin  |  Name prefix: superadmin.
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Protected routes
Route::middleware('superadmin')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Tenants
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/',                             [TenantController::class, 'index'])->name('index');
        Route::get('/create',                       [TenantController::class, 'create'])->name('create');
        Route::post('/',                            [TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}',                     [TenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit',                [TenantController::class, 'edit'])->name('edit');
        Route::put('/{tenant}',                     [TenantController::class, 'update'])->name('update');
        Route::patch('/{tenant}/suspend',           [TenantController::class, 'suspend'])->name('suspend');
        Route::patch('/{tenant}/unsuspend',         [TenantController::class, 'unsuspend'])->name('unsuspend');
        Route::patch('/{tenant}/extend-trial',      [TenantController::class, 'extendTrial'])->name('extend-trial');
        Route::patch('/{tenant}/change-plan',       [TenantController::class, 'changePlan'])->name('change-plan');
        Route::get('/{tenant}/impersonate',         [TenantController::class, 'impersonate'])->name('impersonate');
        Route::post('/{tenant}/notes',              [TenantController::class, 'addNote'])->name('notes.add');
        Route::delete('/{tenant}/notes',            [TenantController::class, 'deleteNote'])->name('notes.delete');
        Route::delete('/{tenant}',                  [TenantController::class, 'destroy'])->name('destroy');
    });

    // Plans (full CRUD)
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/',               [PlanController::class, 'index'])->name('index');
        Route::get('/create',         [PlanController::class, 'create'])->name('create');
        Route::post('/',              [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit',    [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}',         [PlanController::class, 'update'])->name('update');
        Route::patch('/{plan}/toggle',       [PlanController::class, 'toggle'])->name('toggle');
        Route::post('/{plan}/sync-stripe',   [PlanController::class, 'syncStripe'])->name('sync-stripe');
        Route::delete('/{plan}',             [PlanController::class, 'destroy'])->name('destroy');
    });

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

    // Support tickets
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/',                  [TicketController::class, 'index'])->name('index');
        Route::get('/{ticket}',          [TicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply',   [TicketController::class, 'reply'])->name('reply');
        Route::patch('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('status');
        Route::patch('/{ticket}/assign', [TicketController::class, 'assign'])->name('assign');
    });

    // Audit logs
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Settings
    Route::prefix('settings')->name('settings')->group(function () {
        Route::get('/',                         [SettingsController::class, 'index']);
        Route::put('/profile',                  [SettingsController::class, 'updateProfile'])->name('.profile');
        Route::put('/password',                 [SettingsController::class, 'updatePassword'])->name('.password');
        Route::post('/agents',                  [SettingsController::class, 'inviteAgent'])->name('.agents.invite');
        Route::patch('/agents/{agent}/toggle',  [SettingsController::class, 'toggleAgent'])->name('.agents.toggle');
        Route::delete('/agents/{agent}',        [SettingsController::class, 'destroyAgent'])->name('.agents.destroy');
    });
});
