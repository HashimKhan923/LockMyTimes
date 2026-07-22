<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Employee\AnnouncementController;
use App\Http\Controllers\Api\V1\Employee\AttendanceController;
use App\Http\Controllers\Api\V1\Employee\ExpenseController;
use App\Http\Controllers\Api\V1\Employee\LeaveController;
use App\Http\Controllers\Api\V1\Employee\LoanController;
use App\Http\Controllers\Api\V1\Employee\NotificationController;
use App\Http\Controllers\Api\V1\Employee\PayslipController;
use App\Http\Controllers\Api\V1\Employee\ProfileController;
use App\Http\Controllers\Api\V1\Employee\ProjectController;
use App\Http\Controllers\Api\V1\Employee\SettingsController;
use App\Http\Controllers\Api\V1\Employee\TaskController;
use App\Http\Controllers\Api\V1\Employee\TeamController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Mobile App (Phase 23)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => 'ok', 'time' => now()]));

    // Pre-auth: resolving a company code / logging in, tenant DB only.
    Route::middleware('tenant.api')->group(function () {
        Route::post('/tenant/resolve', [TenantController::class, 'resolve'])->name('tenant.resolve');
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    // Authenticated employee routes.
    Route::middleware(['tenant.api', 'subscription.active', 'auth:sanctum', 'employee.api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('/auth/password', [AuthController::class, 'updatePassword'])->name('auth.password');
        Route::post('/auth/sign-out-other-sessions', [AuthController::class, 'signOutOtherSessions'])->name('auth.signOutOtherSessions');

        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('/status', [AttendanceController::class, 'status'])->name('status');
            Route::get('/export', [AttendanceController::class, 'export'])->name('export');
            Route::get('/{date}', [AttendanceController::class, 'day'])->name('day')->where('date', '\d{4}-\d{2}-\d{2}');
            Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clockIn');
            Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clockOut');
            Route::post('/breaks/start', [AttendanceController::class, 'startBreak'])->name('breaks.start');
            Route::post('/breaks/end', [AttendanceController::class, 'endBreak'])->name('breaks.end');
        });

        Route::prefix('leaves')->name('leaves.')->group(function () {
            Route::get('/', [LeaveController::class, 'index'])->name('index');
            Route::post('/calculate', [LeaveController::class, 'calculate'])->name('calculate');
            Route::post('/', [LeaveController::class, 'store'])->name('store');
            Route::get('/{leave}', [LeaveController::class, 'show'])->name('show')->where('leave', '\d+');
            Route::post('/{leave}/cancel', [LeaveController::class, 'cancel'])->name('cancel')->where('leave', '\d+');
        });

        Route::prefix('payslips')->name('payslips.')->group(function () {
            Route::get('/', [PayslipController::class, 'index'])->name('index');
            Route::get('/{payslip}', [PayslipController::class, 'show'])->name('show')->where('payslip', '\d+');
            Route::get('/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('pdf')->where('payslip', '\d+');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('avatar.upload');
            Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])->name('avatar.remove');
            Route::post('/emergency-contacts', [ProfileController::class, 'storeEmergencyContact'])->name('emergencyContacts.store');
            Route::put('/emergency-contacts/{contact}', [ProfileController::class, 'updateEmergencyContact'])->name('emergencyContacts.update')->where('contact', '\d+');
            Route::delete('/emergency-contacts/{contact}', [ProfileController::class, 'destroyEmergencyContact'])->name('emergencyContacts.destroy')->where('contact', '\d+');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::put('/', [SettingsController::class, 'update'])->name('update');
            Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::put('/privacy', [SettingsController::class, 'updatePrivacy'])->name('privacy.update');
        });

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::patch('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('readAll');
        });

        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [ExpenseController::class, 'index'])->name('index');
            Route::post('/', [ExpenseController::class, 'store'])->name('store');
            Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show')->where('expense', '\d+');
            Route::post('/{expense}/submit', [ExpenseController::class, 'submit'])->name('submit')->where('expense', '\d+');
            Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy')->where('expense', '\d+');
            Route::get('/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('receipt')->where('expense', '\d+');
        });

        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', [LoanController::class, 'index'])->name('index');
            Route::get('/types', [LoanController::class, 'loanTypes'])->name('types');
            Route::post('/calculate-emi', [LoanController::class, 'calculateEmiEndpoint'])->name('calculateEmi');
            Route::post('/', [LoanController::class, 'storeLoan'])->name('store');
            Route::get('/{loan}', [LoanController::class, 'showLoan'])->name('show')->where('loan', '\d+');
            Route::post('/{loan}/cancel', [LoanController::class, 'cancelLoan'])->name('cancel')->where('loan', '\d+');
            Route::post('/advances', [LoanController::class, 'storeAdvance'])->name('advances.store');
            Route::get('/advances/{advance}', [LoanController::class, 'showAdvance'])->name('advances.show')->where('advance', '\d+');
            Route::post('/advances/{advance}/cancel', [LoanController::class, 'cancelAdvance'])->name('advances.cancel')->where('advance', '\d+');
        });

        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show')->where('project', '\d+');
            Route::get('/{project}/board', [ProjectController::class, 'board'])->name('board')->where('project', '\d+');
            Route::post('/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store')->where('project', '\d+');
            Route::post('/{project}/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move')->where(['project' => '\d+', 'task' => '\d+']);
        });

        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::get('/{task}', [TaskController::class, 'show'])->name('show')->where('task', '\d+');
            Route::put('/{task}', [TaskController::class, 'update'])->name('update')->where('task', '\d+');
            Route::put('/{task}/status', [TaskController::class, 'updateStatus'])->name('status')->where('task', '\d+');
            Route::put('/{task}/progress', [TaskController::class, 'updateProgress'])->name('progress')->where('task', '\d+');
            Route::post('/{task}/comments', [TaskController::class, 'storeComment'])->name('comments.store')->where('task', '\d+');
            Route::post('/{task}/checklists', [TaskController::class, 'storeChecklist'])->name('checklists.store')->where('task', '\d+');
            Route::patch('/{task}/checklists/{checklist}/toggle', [TaskController::class, 'toggleChecklist'])->name('checklists.toggle')->where(['task' => '\d+', 'checklist' => '\d+']);
            Route::post('/{task}/attachments', [TaskController::class, 'storeAttachment'])->name('attachments.store')->where('task', '\d+');
            Route::get('/{task}/attachments/{attachment}', [TaskController::class, 'attachment'])->name('attachments.show')->where(['task' => '\d+', 'attachment' => '\d+']);
            Route::delete('/{task}/attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('attachments.destroy')->where(['task' => '\d+', 'attachment' => '\d+']);
        });

        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::get('/{employee}', [TeamController::class, 'show'])->name('show')->where('employee', '\d+');
            Route::get('/leave-approvals', [TeamController::class, 'leaveApprovals'])->name('leaveApprovals');
            Route::post('/leaves/{leave}/approve', [TeamController::class, 'approveLeave'])->name('leaves.approve')->where('leave', '\d+');
            Route::post('/leaves/{leave}/reject', [TeamController::class, 'rejectLeave'])->name('leaves.reject')->where('leave', '\d+');
            Route::get('/expense-approvals', [TeamController::class, 'expenseApprovals'])->name('expenseApprovals');
            Route::post('/expenses/{expense}/approve', [TeamController::class, 'approveExpense'])->name('expenses.approve')->where('expense', '\d+');
            Route::post('/expenses/{expense}/reject', [TeamController::class, 'rejectExpense'])->name('expenses.reject')->where('expense', '\d+');
        });

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/polls', [AnnouncementController::class, 'pollsIndex'])->name('polls.index');
            Route::get('/polls/{poll}', [AnnouncementController::class, 'showPoll'])->name('polls.show')->where('poll', '\d+');
            Route::post('/polls/{poll}/vote', [AnnouncementController::class, 'votePoll'])->name('polls.vote')->where('poll', '\d+');
            Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('show')->where('announcement', '\d+');
            Route::post('/{announcement}/read', [AnnouncementController::class, 'markRead'])->name('read')->where('announcement', '\d+');
            Route::post('/{announcement}/acknowledge', [AnnouncementController::class, 'acknowledge'])->name('acknowledge')->where('announcement', '\d+');
        });
    });
});
