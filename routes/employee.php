<?php

use App\Http\Controllers\Employee\AnnouncementController;
use App\Http\Controllers\Employee\AssetController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Employee\AttendanceCorrectionController;
use App\Http\Controllers\Employee\AuthController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\ExpenseController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\LoanController;
use App\Http\Controllers\Employee\NotificationController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\ProjectController;
use App\Http\Controllers\Employee\TaskController;
use App\Http\Controllers\Employee\SettingsController;
use App\Http\Controllers\Employee\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Portal Routes
| Prefix: /t/{tenant}/portal  |  Name prefix: employee.
|--------------------------------------------------------------------------
*/

/* ===== Auth (public) ===== */
Route::middleware('tenant')->group(function () {
    Route::get('/login',           [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',          [AuthController::class, 'login'])->name('login.post');
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change',[AuthController::class, 'changePassword'])->name('password.change.post');
    Route::get('/password/forgot',        [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/password/forgot',       [AuthController::class, 'forgotPassword'])->name('password.forgot.post')->middleware('throttle:6,1');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset')->where('token', '\d{6}');
    Route::post('/password/reset',        [AuthController::class, 'resetPassword'])->name('password.reset.post')->middleware('throttle:6,1');
});

/* ===== Protected ===== */
Route::middleware(['tenant', 'subscription.active', 'employee.auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /* ─── Phase 2 — Dashboard ─── */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* ─── Phase 3 — Attendance ─── */
    Route::prefix('attendance')->name('attendance.')->middleware('permission:attendance.view')->group(function () {
        Route::get('/',             [AttendanceController::class, 'index'])->name('index');
        Route::get('/status',       [AttendanceController::class, 'status'])->name('status');
        Route::get('/day/{date}',   [AttendanceController::class, 'day'])->name('day');
        Route::get('/export',       [AttendanceController::class, 'export'])->name('export');
        Route::post('/clock-in',    [AttendanceController::class, 'clockIn'])->name('clock-in')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
        Route::post('/clock-out',   [AttendanceController::class, 'clockOut'])->name('clock-out')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
        Route::post('/break/start', [AttendanceController::class, 'startBreak'])->name('break.start')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
        Route::post('/break/end',   [AttendanceController::class, 'endBreak'])->name('break.end')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
    });

    /* ─── Attendance correction requests ─── */
    Route::prefix('attendance-corrections')->name('attendance-corrections.')->middleware('permission:attendance.view')->group(function () {
        Route::get('/',                    [AttendanceCorrectionController::class, 'index'])->name('index');
        Route::get('/new',                 [AttendanceCorrectionController::class, 'create'])->name('create')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
        Route::post('/',                   [AttendanceCorrectionController::class, 'store'])->name('store')->withoutMiddleware('permission:attendance.view')->middleware('permission:attendance.create');
        Route::post('/{correction}/cancel',[AttendanceCorrectionController::class, 'cancel'])->name('cancel');
    });

    /* ─── Phase 4 — Leaves (LIVE) ─── */
    Route::prefix('leaves')->name('leaves.')->middleware('permission:leaves.view')->group(function () {
        Route::get('/',               [LeaveController::class, 'index'])->name('index');
        Route::get('/apply',          [LeaveController::class, 'create'])->name('create')->withoutMiddleware('permission:leaves.view')->middleware('permission:leaves.create');
        Route::get('/calculate',      [LeaveController::class, 'calculate'])->name('calculate');
        Route::post('/',              [LeaveController::class, 'store'])->name('store')->withoutMiddleware('permission:leaves.view')->middleware('permission:leaves.create');
        Route::get('/{leave}',        [LeaveController::class, 'show'])->name('show');
        Route::post('/{leave}/cancel',[LeaveController::class, 'cancel'])->name('cancel');
    });

    /* ─── Phase 5 — Payslips (LIVE) ─── */
    Route::prefix('payslips')->name('payslips.')->middleware('permission:payslips.view')->group(function () {
        Route::get('/',              [PayslipController::class, 'index'])->name('index');
        Route::get('/{payslip}',     [PayslipController::class, 'show'])->name('show');
        Route::get('/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('pdf');
    });

    /* ─── Phase 6 — Profile (LIVE) ─── */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',                                       [ProfileController::class, 'index'])->name('index');
        Route::put('/',                                       [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar',                                [ProfileController::class, 'uploadAvatar'])->name('avatar');
        Route::delete('/avatar',                              [ProfileController::class, 'removeAvatar'])->name('avatar.remove');
        Route::put('/password',                               [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/emergency-contacts',                    [ProfileController::class, 'storeEmergencyContact'])->name('emergency.store');
        Route::put('/emergency-contacts/{contact}',           [ProfileController::class, 'updateEmergencyContact'])->name('emergency.update');
        Route::delete('/emergency-contacts/{contact}',        [ProfileController::class, 'destroyEmergencyContact'])->name('emergency.destroy');
    });

    /* ─── Phase 7 — Documents (REMOVED) ─── */

    /* ─── Phase 8 — Tasks & Projects (LIVE) ─── */
    Route::prefix('tasks')->name('tasks.')->middleware('permission:tasks.view')->group(function () {
        Route::get('/',                                  [TaskController::class, 'index'])->name('index');
        Route::get('/{task}',                            [TaskController::class, 'show'])->name('show');
        Route::patch('/{task}/status',                   [TaskController::class, 'updateStatus'])->name('status');
        Route::patch('/{task}/progress',                 [TaskController::class, 'updateProgress'])->name('progress');
        Route::post('/{task}/comments',                  [TaskController::class, 'storeComment'])->name('comment');
        Route::patch('/{task}/checklists/{checklist}',   [TaskController::class, 'toggleChecklist'])->name('checklist.toggle');
        Route::get('/{task}/attachments/{attachment}',   [TaskController::class, 'attachment'])->name('attachment');
    });
    Route::prefix('projects')->name('projects.')->middleware('permission:projects.view')->group(function () {
        Route::get('/',                   [ProjectController::class, 'index'])->name('index');
        Route::get('/{project}',          [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/board',    [ProjectController::class, 'board'])->name('board');
    });

    /* ─── Phase 9 — Performance ─── */
    Route::prefix('performance')->name('performance.')->middleware('permission:performance.view')->group(function () {
        Route::get('/',                 [DashboardController::class, 'comingSoon'])->name('index');
        Route::get('/goals',            [DashboardController::class, 'comingSoon'])->name('goals');
        Route::post('/goals',           [DashboardController::class, 'comingSoon'])->name('goals.store');
        Route::get('/reviews',          [DashboardController::class, 'comingSoon'])->name('reviews');
        Route::get('/reviews/{review}', [DashboardController::class, 'comingSoon'])->name('reviews.show');
        Route::post('/kudos',           [DashboardController::class, 'comingSoon'])->name('kudos.send');
        Route::get('/one-on-ones',      [DashboardController::class, 'comingSoon'])->name('one-on-ones');
    });

    /* ─── My Assets (LIVE) ─── */
    Route::prefix('assets')->name('assets.')->middleware('permission:assets.view')->group(function () {
        Route::get('/',                               [AssetController::class, 'index'])->name('index');
        Route::post('/{assignment}/return-request',   [DashboardController::class, 'comingSoon'])->name('return');
    });

    /* ─── Phase 11 — Training & Certifications ─── */
    Route::prefix('training')->name('training.')->middleware('permission:training.view')->group(function () {
        Route::get('/',                  [DashboardController::class, 'comingSoon'])->name('index');
        Route::get('/{enrollment}',      [DashboardController::class, 'comingSoon'])->name('show');
        Route::post('/{training}/enrol', [DashboardController::class, 'comingSoon'])->name('enrol');
        Route::get('/certifications',    [DashboardController::class, 'comingSoon'])->name('certifications');
    });

    /* ─── Phase 12 — Expenses (LIVE) ─── */
    Route::prefix('expenses')->name('expenses.')->middleware('permission:expenses.view')->group(function () {
        Route::get('/',                       [ExpenseController::class, 'index'])->name('index');
        Route::get('/create',                 [ExpenseController::class, 'create'])->name('create')->withoutMiddleware('permission:expenses.view')->middleware('permission:expenses.create');
        Route::post('/',                      [ExpenseController::class, 'store'])->name('store')->withoutMiddleware('permission:expenses.view')->middleware('permission:expenses.create');
        Route::get('/{expense}',              [ExpenseController::class, 'show'])->name('show');
        Route::get('/{expense}/receipt',      [ExpenseController::class, 'receipt'])->name('receipt');
        Route::post('/{expense}/submit',      [ExpenseController::class, 'submit'])->name('submit');
        Route::delete('/{expense}',           [ExpenseController::class, 'destroy'])->name('destroy');
    });

    /* ─── Phase 13 — Loans & Advances (LIVE) ─── */
    Route::prefix('loans')->name('loans.')->middleware('permission:loans.view')->group(function () {
        Route::get('/',                 [LoanController::class, 'index'])->name('index');
        Route::get('/apply',            [LoanController::class, 'createLoan'])->name('create')->withoutMiddleware('permission:loans.view')->middleware('permission:loans.create');
        Route::post('/',                [LoanController::class, 'storeLoan'])->name('store')->withoutMiddleware('permission:loans.view')->middleware('permission:loans.create');
        Route::get('/calculate-emi',    [LoanController::class, 'calculateEmiEndpoint'])->name('calculate-emi');

        Route::prefix('advances')->name('advance.')->group(function () {
            Route::get('/apply',          [LoanController::class, 'createAdvance'])->name('create')->withoutMiddleware('permission:loans.view')->middleware('permission:loans.create');
            Route::post('/',              [LoanController::class, 'storeAdvance'])->name('store')->withoutMiddleware('permission:loans.view')->middleware('permission:loans.create');
            Route::get('/{advance}',      [LoanController::class, 'showAdvance'])->name('show');
            Route::post('/{advance}/cancel',[LoanController::class, 'cancelAdvance'])->name('cancel');
        });

        Route::get('/{loan}',           [LoanController::class, 'showLoan'])->name('show');
        Route::post('/{loan}/cancel',   [LoanController::class, 'cancelLoan'])->name('cancel');
    });

    /* ─── Phase 14 — My Team (Manager mode) (LIVE) ─── */
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/',                              [TeamController::class, 'index'])->name('index');
        Route::get('/approvals/leaves',              [TeamController::class, 'leaveApprovals'])->name('approvals.leaves');
        Route::get('/approvals/expenses',            [TeamController::class, 'expenseApprovals'])->name('approvals.expenses');
        Route::get('/approvals/corrections',         [TeamController::class, 'correctionApprovals'])->name('approvals.corrections');
        Route::patch('/leaves/{leave}/approve',      [TeamController::class, 'approveLeave'])->name('leaves.approve');
        Route::patch('/leaves/{leave}/reject',       [TeamController::class, 'rejectLeave'])->name('leaves.reject');
        Route::patch('/corrections/{correction}/approve', [TeamController::class, 'approveCorrection'])->name('corrections.approve');
        Route::patch('/corrections/{correction}/reject',  [TeamController::class, 'rejectCorrection'])->name('corrections.reject');
        Route::patch('/expenses/{expense}/approve',  [TeamController::class, 'approveExpense'])->name('expenses.approve');
        Route::patch('/expenses/{expense}/reject',   [TeamController::class, 'rejectExpense'])->name('expenses.reject');
        Route::get('/{employee}',                    [TeamController::class, 'show'])->name('show');
    });

    /* ─── Phase 15 — Announcements & Polls (LIVE) ─── */
    Route::prefix('announcements')->name('announcements.')->middleware('permission:announcements.view')->group(function () {
        Route::get('/',                              [AnnouncementController::class, 'index'])->name('index');
        Route::get('/polls/{poll}',                  [AnnouncementController::class, 'showPoll'])->name('polls.show');
        Route::post('/polls/{poll}/vote',            [AnnouncementController::class, 'votePoll'])->name('polls.vote');
        Route::get('/{announcement}',                [AnnouncementController::class, 'show'])->name('show');
        Route::post('/{announcement}/read',          [AnnouncementController::class, 'markRead'])->name('read');
        Route::post('/{announcement}/acknowledge',   [AnnouncementController::class, 'acknowledge'])->name('acknowledge');
    });

    /* ─── Phase 16 — Notifications ─── */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                  [NotificationController::class, 'index'])->name('index');
        Route::get('/feed',              [NotificationController::class, 'feed'])->name('feed');
        Route::patch('/{id}/read',       [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all',         [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/clear-all',      [NotificationController::class, 'destroyAll'])->name('clear-all');
        Route::delete('/{id}',           [NotificationController::class, 'destroy'])->name('destroy');
    });

    /* ─── Phase 17 — Helpdesk / Support (REMOVED) ─── */

    /* ─── Phase 18 — Org Directory ─── */
    Route::prefix('directory')->name('directory.')->group(function () {
        Route::get('/',                  [DashboardController::class, 'comingSoon'])->name('index');
        Route::get('/{employee}',        [DashboardController::class, 'comingSoon'])->name('show');
        Route::get('/org-chart',         [DashboardController::class, 'comingSoon'])->name('org-chart');
    });

    /* ─── Phase 19 — Settings ─── */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                      [SettingsController::class, 'index'])->name('index');
        Route::put('/',                      [SettingsController::class, 'update'])->name('update');
        Route::put('/notifications',         [SettingsController::class, 'updateNotifications'])->name('notifications');
        Route::put('/privacy',               [SettingsController::class, 'updatePrivacy'])->name('privacy');
        Route::post('/signout-other',        [SettingsController::class, 'signOutOtherSessions'])->name('signout-other');
    });

});