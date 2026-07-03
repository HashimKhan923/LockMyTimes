<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\UpgradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes
| Prefix: /t/{tenant}/admin  |  Name prefix: admin.
|--------------------------------------------------------------------------
*/

/* ===== Auth (public — no auth required) ===== */
Route::middleware('tenant')->group(function () {
    Route::get('/login',            [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',           [AuthController::class, 'login'])->name('login.post');
    Route::get('/password/change',  [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.post');
});

/* ===== Protected (tenant + subscription + auth) ===== */
Route::middleware(['tenant', 'subscription.active', 'admin.auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /* Dashboard */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* Upgrade / Plan page */
    Route::get('/upgrade', [UpgradeController::class, 'index'])->name('upgrade');

    /* Billing & Subscription */
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/',        [BillingController::class, 'index'])->name('index');
        Route::post('/checkout', [BillingController::class, 'checkout'])->name('checkout');
        Route::get('/portal',  [BillingController::class, 'portal'])->name('portal');
        Route::post('/cancel', [BillingController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [BillingController::class, 'resume'])->name('resume');
    });

    /* ---- Employees ---- */
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/',                       [EmployeeController::class, 'index'])->name('index')->middleware('permission:employees.view');
        Route::get('/create',                 [EmployeeController::class, 'create'])->name('create')->middleware('permission:employees.create');
        Route::post('/',                      [EmployeeController::class, 'store'])->name('store')->middleware('permission:employees.create');
        Route::get('/org-chart',              [EmployeeController::class, 'orgChart'])->name('org-chart')->middleware('permission:employees.view');
        Route::post('/import',                [EmployeeController::class, 'import'])->name('import')->middleware('permission:employees.create');
        Route::get('/export',                 [EmployeeController::class, 'export'])->name('export')->middleware('permission:employees.export');
        Route::get('/{employee}',             [EmployeeController::class, 'show'])->name('show')->middleware('permission:employees.view');
        Route::get('/{employee}/edit',        [EmployeeController::class, 'edit'])->name('edit')->middleware('permission:employees.edit');
        Route::put('/{employee}',             [EmployeeController::class, 'update'])->name('update')->middleware('permission:employees.edit');
        Route::patch('/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('terminate')->middleware('permission:employees.delete');
    });

    /* ---- Departments ---- */
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/',               [DepartmentController::class, 'index'])->name('index')->middleware('permission:departments.view');
        Route::post('/',              [DepartmentController::class, 'store'])->name('store')->middleware('permission:departments.create');
        Route::put('/{department}',   [DepartmentController::class, 'update'])->name('update')->middleware('permission:departments.edit');
        Route::delete('/{department}',[DepartmentController::class, 'destroy'])->name('destroy')->middleware('permission:departments.delete');
    });

    /* ---- Positions ---- */
    Route::prefix('positions')->name('positions.')->group(function () {
        Route::get('/',              [PositionController::class, 'index'])->name('index')->middleware('permission:positions.view');
        Route::post('/',             [PositionController::class, 'store'])->name('store')->middleware('permission:positions.create');
        Route::put('/{position}',    [PositionController::class, 'update'])->name('update')->middleware('permission:positions.edit');
        Route::delete('/{position}', [PositionController::class, 'destroy'])->name('destroy')->middleware('permission:positions.delete');
    });

    /* ---- Locations ---- */
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/',              [LocationController::class, 'index'])->name('index')->middleware('permission:locations.view');
        Route::post('/',             [LocationController::class, 'store'])->name('store')->middleware('permission:locations.create');
        Route::put('/{location}',    [LocationController::class, 'update'])->name('update')->middleware('permission:locations.edit');
        Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy')->middleware('permission:locations.delete');
    });

    /* ---- Attendance ---- */
    Route::prefix('attendance')->name('attendance.')->middleware('module:attendance')->group(function () {
        Route::get('/',                          [AttendanceController::class, 'index'])->name('index')->middleware('permission:attendance.view');
        Route::post('/manual',                   [AttendanceController::class, 'manualEntry'])->name('manual')->middleware('permission:attendance.create');
        Route::get('/export',                    [AttendanceController::class, 'export'])->name('export')->middleware('permission:attendance.export');
        Route::get('/employee/{employee}/sheet', [AttendanceController::class, 'employeeSheet'])->name('employee-sheet')->middleware('permission:attendance.view');
    });

    /* ---- Leave exports ---- */
    Route::get('/leaves/export', [\App\Http\Controllers\Admin\LeaveController::class, 'export'])->name('leaves.export')->middleware('permission:leaves.view');

    /* ---- Payroll exports ---- */
    Route::get('/payroll/export', [\App\Http\Controllers\Admin\PayrollController::class, 'export'])->name('payroll.export')->middleware('permission:payroll.view');

    /* ---- Expense exports ---- */
    Route::get('/expenses/export', [\App\Http\Controllers\Admin\ExpenseController::class, 'export'])->name('expenses.export')->middleware('permission:expenses.view');

    /* ---- Asset exports ---- */
    Route::get('/assets/export', [\App\Http\Controllers\Admin\AssetController::class, 'export'])->name('assets.export')->middleware('permission:assets.view');

    /* ---- Loan exports ---- */
    Route::get('/loans/export', [\App\Http\Controllers\Admin\LoanController::class, 'export'])->name('loans.export')->middleware('permission:loans.view');

    /* ---- Training exports ---- */
    Route::get('/training/export', [\App\Http\Controllers\Admin\TrainingController::class, 'export'])->name('training.export')->middleware('permission:training.view');

    /* ---- Recruitment exports ---- */
    Route::get('/recruitment/export', [\App\Http\Controllers\Admin\RecruitmentController::class, 'export'])->name('recruitment.export')->middleware('permission:recruitment.view');

    /* ---- Performance exports ---- */
    Route::get('/performance/export', [\App\Http\Controllers\Admin\PerformanceController::class, 'export'])->name('performance.export')->middleware('permission:performance.view');

    /* ---- QR Codes ---- */
    Route::prefix('qrcodes')->name('qrcodes.')->group(function () {
        Route::get('/',                  [QrCodeController::class, 'index'])->name('index')->middleware('permission:qr_codes.view');
        Route::post('/',                 [QrCodeController::class, 'store'])->name('store')->middleware('permission:qr_codes.create');
        Route::get('/{qrCode}',          [QrCodeController::class, 'show'])->name('show')->middleware('permission:qr_codes.view');
        Route::patch('/{qrCode}/rotate', [QrCodeController::class, 'rotate'])->name('rotate')->middleware('permission:qr_codes.edit');
        Route::patch('/{qrCode}/toggle', [QrCodeController::class, 'toggle'])->name('toggle')->middleware('permission:qr_codes.edit');
        Route::delete('/{qrCode}',       [QrCodeController::class, 'destroy'])->name('destroy')->middleware('permission:qr_codes.delete');
    });

    /* ---- Placeholder routes (built in upcoming phases) ---- */
    $ph = fn($title) => fn(string $tenant) =>
        view('admin.placeholder', ['title' => $title, 'tenantSlug' => $tenant]);

    /* ---- Shifts ---- */
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/',                        [\App\Http\Controllers\Admin\ShiftController::class, 'index'])->name('index')->middleware('permission:shifts.view');
        Route::post('/',                       [\App\Http\Controllers\Admin\ShiftController::class, 'store'])->name('store')->middleware('permission:shifts.create');
        Route::put('/{shift}',                 [\App\Http\Controllers\Admin\ShiftController::class, 'update'])->name('update')->middleware('permission:shifts.edit');
        Route::delete('/{shift}',              [\App\Http\Controllers\Admin\ShiftController::class, 'destroy'])->name('destroy')->middleware('permission:shifts.delete');
        Route::post('/assign',                 [\App\Http\Controllers\Admin\ShiftController::class, 'assign'])->name('assign')->middleware('permission:shifts.edit');
        Route::patch('/unassign/{assignment}', [\App\Http\Controllers\Admin\ShiftController::class, 'unassign'])->name('unassign')->middleware('permission:shifts.edit');
        Route::patch('/swap/{swap}/approve',   [\App\Http\Controllers\Admin\ShiftController::class, 'approveSwap'])->name('swap.approve')->middleware('permission:shifts.approve');
        Route::patch('/swap/{swap}/reject',    [\App\Http\Controllers\Admin\ShiftController::class, 'rejectSwap'])->name('swap.reject')->middleware('permission:shifts.approve');
        Route::get('/employee/{employee}',     [\App\Http\Controllers\Admin\ShiftController::class, 'employeeSchedule'])->name('employee-schedule')->middleware('permission:shifts.view');
    });

    /* ---- Leave Management ---- */
    Route::prefix('leaves')->name('leaves.')->middleware('module:attendance')->group(function () {
        Route::get('/',                      [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('index')->middleware('permission:leaves.view');
        Route::post('/',                     [\App\Http\Controllers\Admin\LeaveController::class, 'store'])->name('store')->middleware('permission:leaves.create');
        Route::patch('/{leave}/approve',     [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('approve')->middleware('permission:leaves.approve');
        Route::patch('/{leave}/reject',      [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('reject')->middleware('permission:leaves.approve');
        Route::patch('/{leave}/cancel',      [\App\Http\Controllers\Admin\LeaveController::class, 'cancel'])->name('cancel')->middleware('permission:leaves.edit');
        Route::post('/auto-approve',         [\App\Http\Controllers\Admin\LeaveController::class, 'autoApprovePending'])->name('auto-approve')->middleware('permission:leaves.approve');
        Route::get('/balances',              [\App\Http\Controllers\Admin\LeaveController::class, 'balances'])->name('balances')->middleware('permission:leaves.view');
        Route::post('/balances',             [\App\Http\Controllers\Admin\LeaveController::class, 'updateBalance'])->name('balance.update')->middleware('permission:leaves.edit');
        Route::post('/balances/sync',        [\App\Http\Controllers\Admin\LeaveController::class, 'syncBalances'])->name('balances.sync')->middleware('permission:leaves.edit');
        Route::get('/types',                 [\App\Http\Controllers\Admin\LeaveController::class, 'types'])->name('types')->middleware('permission:leave_types.view');
        Route::post('/types',                [\App\Http\Controllers\Admin\LeaveController::class, 'storeType'])->name('types.store')->middleware('permission:leave_types.create');
        Route::put('/types/{leaveType}',     [\App\Http\Controllers\Admin\LeaveController::class, 'updateType'])->name('types.update')->middleware('permission:leave_types.edit');
        Route::get('/holidays',              [\App\Http\Controllers\Admin\LeaveController::class, 'holidays'])->name('holidays')->middleware('permission:holidays.view');
        Route::post('/holidays',             [\App\Http\Controllers\Admin\LeaveController::class, 'storeHoliday'])->name('holidays.store')->middleware('permission:holidays.create');
        Route::delete('/holidays/{holiday}', [\App\Http\Controllers\Admin\LeaveController::class, 'destroyHoliday'])->name('holidays.destroy')->middleware('permission:holidays.delete');
    });

    /* ---- Payroll ---- */
    Route::prefix('payroll')->name('payroll.')->middleware('module:payroll')->group(function () {
        Route::get('/',                          [\App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('index')->middleware('permission:payroll.view');
        Route::post('/run',                      [\App\Http\Controllers\Admin\PayrollController::class, 'createRun'])->name('run')->middleware('permission:payroll.create');
        Route::get('/{payrollRun}',              [\App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('show')->middleware('permission:payroll.view');
        Route::patch('/{payrollRun}/approve',    [\App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve')->middleware('permission:payroll.approve');
        Route::patch('/{payrollRun}/paid',       [\App\Http\Controllers\Admin\PayrollController::class, 'markPaid'])->name('paid')->middleware('permission:payroll.approve');
        Route::patch('/{payrollRun}/regenerate', [\App\Http\Controllers\Admin\PayrollController::class, 'regenerate'])->name('regenerate')->middleware('permission:payroll.edit');
        Route::get('/payslip/{payslip}',         [\App\Http\Controllers\Admin\PayrollController::class, 'payslip'])->name('payslip')->middleware('permission:payslips.view');
        Route::get('/settings/components',       [\App\Http\Controllers\Admin\PayrollController::class, 'components'])->name('components')->middleware('permission:salary_components.view');
        Route::post('/settings/components',      [\App\Http\Controllers\Admin\PayrollController::class, 'storeComponent'])->name('components.store')->middleware('permission:salary_components.create');
    });

    /* ---- Expenses ---- */
    Route::prefix('expenses')->name('expenses.')->middleware('module:expenses')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('index')->middleware('permission:expenses.view');
        Route::post('/',                            [\App\Http\Controllers\Admin\ExpenseController::class, 'store'])->name('store')->middleware('permission:expenses.create');
        Route::patch('/{expense}/approve',          [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->name('approve')->middleware('permission:expenses.approve');
        Route::patch('/{expense}/reject',           [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->name('reject')->middleware('permission:expenses.approve');
        Route::patch('/{expense}/paid',             [\App\Http\Controllers\Admin\ExpenseController::class, 'markPaid'])->name('paid')->middleware('permission:expenses.approve');
        Route::delete('/{expense}',                 [\App\Http\Controllers\Admin\ExpenseController::class, 'destroy'])->name('destroy')->middleware('permission:expenses.delete');
        Route::get('/categories',                   [\App\Http\Controllers\Admin\ExpenseController::class, 'categories'])->name('categories')->middleware('permission:expenses.view');
        Route::post('/categories',                  [\App\Http\Controllers\Admin\ExpenseController::class, 'storeCategory'])->name('categories.store')->middleware('permission:expenses.create');
        Route::put('/categories/{expenseCategory}', [\App\Http\Controllers\Admin\ExpenseController::class, 'updateCategory'])->name('categories.update')->middleware('permission:expenses.edit');
    });

    /* ---- Loans & Advances ---- */
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\LoanController::class, 'index'])->name('index')->middleware('permission:loans.view');
        Route::post('/',                            [\App\Http\Controllers\Admin\LoanController::class, 'store'])->name('store')->middleware('permission:loans.create');
        Route::get('/{loan}',                       [\App\Http\Controllers\Admin\LoanController::class, 'show'])->name('show')->middleware('permission:loans.view');
        Route::patch('/{loan}/approve',             [\App\Http\Controllers\Admin\LoanController::class, 'approve'])->name('approve')->middleware('permission:loans.approve');
        Route::patch('/{loan}/reject',              [\App\Http\Controllers\Admin\LoanController::class, 'reject'])->name('reject')->middleware('permission:loans.approve');
        Route::patch('/{loan}/disburse',            [\App\Http\Controllers\Admin\LoanController::class, 'disburse'])->name('disburse')->middleware('permission:loans.approve');
        Route::patch('/{loan}/repayment',           [\App\Http\Controllers\Admin\LoanController::class, 'recordRepayment'])->name('repayment')->middleware('permission:loans.edit');
        Route::post('/advances',                    [\App\Http\Controllers\Admin\LoanController::class, 'storeAdvance'])->name('advance.store')->middleware('permission:salary_advances.create');
        Route::patch('/advances/{advance}/approve', [\App\Http\Controllers\Admin\LoanController::class, 'approveAdvance'])->name('advance.approve')->middleware('permission:salary_advances.approve');
        Route::patch('/advances/{advance}/reject',  [\App\Http\Controllers\Admin\LoanController::class, 'rejectAdvance'])->name('advance.reject')->middleware('permission:salary_advances.approve');
        Route::get('/settings/types',               [\App\Http\Controllers\Admin\LoanController::class, 'types'])->name('types')->middleware('permission:loans.view');
        Route::post('/settings/types',              [\App\Http\Controllers\Admin\LoanController::class, 'storeType'])->name('types.store')->middleware('permission:loans.create');
    });

    /* ---- Performance Management ---- */
    Route::prefix('performance')->name('performance.')->middleware('module:performance')->group(function () {
        Route::get('/',                             [\App\Http\Controllers\Admin\PerformanceController::class, 'index'])->name('index')->middleware('permission:performance.view');
        Route::post('/cycles',                      [\App\Http\Controllers\Admin\PerformanceController::class, 'storeCycle'])->name('cycles.store')->middleware('permission:reviews.create');
        Route::post('/reviews',                     [\App\Http\Controllers\Admin\PerformanceController::class, 'storeReview'])->name('reviews.store')->middleware('permission:reviews.create');
        Route::get('/reviews/{review}',             [\App\Http\Controllers\Admin\PerformanceController::class, 'showReview'])->name('review')->middleware('permission:reviews.view');
        Route::patch('/reviews/{review}/submit',    [\App\Http\Controllers\Admin\PerformanceController::class, 'submitReview'])->name('reviews.submit')->middleware('permission:reviews.edit');
        Route::post('/goals',                       [\App\Http\Controllers\Admin\PerformanceController::class, 'storeGoal'])->name('goals.store')->middleware('permission:goals.create');
        Route::patch('/goals/{goal}',               [\App\Http\Controllers\Admin\PerformanceController::class, 'updateGoal'])->name('goals.update')->middleware('permission:goals.edit');
        Route::delete('/goals/{goal}',              [\App\Http\Controllers\Admin\PerformanceController::class, 'destroyGoal'])->name('goals.destroy')->middleware('permission:goals.delete');
        Route::post('/kudos',                       [\App\Http\Controllers\Admin\PerformanceController::class, 'storeKudo'])->name('kudos.store')->middleware('permission:kudos.create');
        Route::delete('/kudos/{kudo}',              [\App\Http\Controllers\Admin\PerformanceController::class, 'destroyKudo'])->name('kudos.destroy')->middleware('permission:kudos.delete');
    });

    /* ---- Projects & Tasks ---- */
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/',                                                     [\App\Http\Controllers\Admin\ProjectController::class, 'index'])->name('index')->middleware('permission:projects.view');
        Route::post('/',                                                    [\App\Http\Controllers\Admin\ProjectController::class, 'store'])->name('store')->middleware('permission:projects.create');
        Route::get('/{project}',                                            [\App\Http\Controllers\Admin\ProjectController::class, 'show'])->name('show')->middleware('permission:projects.view');
        Route::put('/{project}',                                            [\App\Http\Controllers\Admin\ProjectController::class, 'update'])->name('update')->middleware('permission:projects.edit');
        Route::delete('/{project}',                                         [\App\Http\Controllers\Admin\ProjectController::class, 'destroy'])->name('destroy')->middleware('permission:projects.delete');
        Route::get('/{project}/board',                                      [\App\Http\Controllers\Admin\TaskController::class, 'board'])->name('board')->middleware('permission:tasks.view');
        Route::post('/{project}/members',                                   [\App\Http\Controllers\Admin\ProjectController::class, 'addMember'])->name('members.add')->middleware('permission:projects.edit');
        Route::delete('/{project}/members/{member}',                        [\App\Http\Controllers\Admin\ProjectController::class, 'removeMember'])->name('members.remove')->middleware('permission:projects.edit');
        Route::post('/{project}/lists',                                     [\App\Http\Controllers\Admin\TaskController::class, 'storeList'])->name('lists.store')->middleware('permission:tasks.create');
        Route::put('/{project}/lists/{taskList}',                           [\App\Http\Controllers\Admin\TaskController::class, 'updateList'])->name('lists.update')->middleware('permission:tasks.edit');
        Route::delete('/{project}/lists/{taskList}',                        [\App\Http\Controllers\Admin\TaskController::class, 'destroyList'])->name('lists.destroy')->middleware('permission:tasks.delete');
        Route::post('/{project}/tasks',                                     [\App\Http\Controllers\Admin\TaskController::class, 'store'])->name('tasks.store')->middleware('permission:tasks.create');
        Route::get('/{project}/tasks/{task}',                               [\App\Http\Controllers\Admin\TaskController::class, 'show'])->name('tasks.show')->middleware('permission:tasks.view');
        Route::put('/{project}/tasks/{task}',                               [\App\Http\Controllers\Admin\TaskController::class, 'update'])->name('tasks.update')->middleware('permission:tasks.edit');
        Route::patch('/{project}/tasks/{task}/move',                        [\App\Http\Controllers\Admin\TaskController::class, 'move'])->name('tasks.move')->middleware('permission:tasks.edit');
        Route::delete('/{project}/tasks/{task}',                            [\App\Http\Controllers\Admin\TaskController::class, 'destroy'])->name('tasks.destroy')->middleware('permission:tasks.delete');
        Route::post('/{project}/tasks/{task}/checklist',                    [\App\Http\Controllers\Admin\TaskController::class, 'storeChecklist'])->name('tasks.checklist.store')->middleware('permission:tasks.edit');
        Route::patch('/{project}/tasks/{task}/checklist/{checklist}/toggle', [\App\Http\Controllers\Admin\TaskController::class, 'toggleChecklist'])->name('tasks.checklist.toggle')->middleware('permission:tasks.edit');
        Route::post('/{project}/tasks/{task}/comments',                     [\App\Http\Controllers\Admin\TaskController::class, 'storeComment'])->name('tasks.comments.store')->middleware('permission:tasks.edit');
        Route::post('/{project}/tasks/{task}/attachments',                  [\App\Http\Controllers\Admin\TaskController::class, 'storeAttachment'])->name('tasks.attachments.store')->middleware('permission:tasks.edit');
        Route::delete('/{project}/tasks/{task}/attachments/{attachment}',   [\App\Http\Controllers\Admin\TaskController::class, 'destroyAttachment'])->name('tasks.attachments.destroy')->middleware('permission:tasks.delete');
    });

    /* ---- Assets ---- */
    Route::prefix('assets')->name('assets.')->middleware('module:assets')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\Admin\AssetController::class, 'index'])->name('index')->middleware('permission:assets.view');
        Route::post('/',                   [\App\Http\Controllers\Admin\AssetController::class, 'store'])->name('store')->middleware('permission:assets.create');
        Route::get('/{asset}',             [\App\Http\Controllers\Admin\AssetController::class, 'show'])->name('show')->middleware('permission:assets.view');
        Route::put('/{asset}',             [\App\Http\Controllers\Admin\AssetController::class, 'update'])->name('update')->middleware('permission:assets.edit');
        Route::delete('/{asset}',          [\App\Http\Controllers\Admin\AssetController::class, 'destroy'])->name('destroy')->middleware('permission:assets.delete');
        Route::post('/{asset}/assign',     [\App\Http\Controllers\Admin\AssetController::class, 'assign'])->name('assign')->middleware('permission:assets.edit');
        Route::post('/{asset}/return',     [\App\Http\Controllers\Admin\AssetController::class, 'return'])->name('return')->middleware('permission:assets.edit');
        Route::get('/settings/categories', [\App\Http\Controllers\Admin\AssetController::class, 'categories'])->name('categories')->middleware('permission:asset_categories.view');
        Route::post('/settings/categories',[\App\Http\Controllers\Admin\AssetController::class, 'storeCategory'])->name('categories.store')->middleware('permission:asset_categories.create');
    });

    /* ---- Training & LMS ---- */
    Route::prefix('training')->name('training.')->middleware('module:training')->group(function () {
        Route::get('/',                              [\App\Http\Controllers\Admin\TrainingController::class, 'index'])->name('index')->middleware('permission:training.view');
        Route::post('/',                             [\App\Http\Controllers\Admin\TrainingController::class, 'store'])->name('store')->middleware('permission:training.create');
        Route::get('/{training}',                    [\App\Http\Controllers\Admin\TrainingController::class, 'show'])->name('show')->middleware('permission:training.view');
        Route::put('/{training}',                    [\App\Http\Controllers\Admin\TrainingController::class, 'update'])->name('update')->middleware('permission:training.edit');
        Route::delete('/{training}',                 [\App\Http\Controllers\Admin\TrainingController::class, 'destroy'])->name('destroy')->middleware('permission:training.delete');
        Route::post('/{training}/enroll',            [\App\Http\Controllers\Admin\TrainingController::class, 'enroll'])->name('enroll')->middleware('permission:training.edit');
        Route::patch('/enrollments/{enrollment}',    [\App\Http\Controllers\Admin\TrainingController::class, 'updateEnrollment'])->name('enrollments.update')->middleware('permission:training.edit');
        Route::post('/certifications',               [\App\Http\Controllers\Admin\TrainingController::class, 'storeCertification'])->name('certifications.store')->middleware('permission:certifications.create');
        Route::delete('/certifications/{certification}', [\App\Http\Controllers\Admin\TrainingController::class, 'destroyCertification'])->name('certifications.destroy')->middleware('permission:certifications.delete');
    });

    /* ---- Documents ---- */
    Route::prefix('documents')->name('documents.')->middleware('module:documents')->group(function () {
        Route::get('/',                        [\App\Http\Controllers\Admin\DocumentController::class, 'index'])->name('index')->middleware('permission:documents.view');
        Route::post('/',                       [\App\Http\Controllers\Admin\DocumentController::class, 'store'])->name('store')->middleware('permission:documents.create');
        Route::get('/{document}/download',     [\App\Http\Controllers\Admin\DocumentController::class, 'download'])->name('download')->middleware('permission:documents.view');
        Route::delete('/{document}',           [\App\Http\Controllers\Admin\DocumentController::class, 'destroy'])->name('destroy')->middleware('permission:documents.delete');
        Route::post('/{document}/send',        [\App\Http\Controllers\Admin\DocumentController::class, 'sendForSignature'])->name('send')->middleware('permission:documents.edit');
        Route::post('/{document}/acknowledge', [\App\Http\Controllers\Admin\DocumentController::class, 'acknowledge'])->name('acknowledge')->middleware('permission:documents.view');
        Route::post('/folders',                [\App\Http\Controllers\Admin\DocumentController::class, 'storeFolder'])->name('folders.store')->middleware('permission:documents.create');
        Route::delete('/folders/{folder}',     [\App\Http\Controllers\Admin\DocumentController::class, 'destroyFolder'])->name('folders.destroy')->middleware('permission:documents.delete');
    });

    /* ---- Recruitment & ATS ---- */
    Route::prefix('recruitment')->name('recruitment.')->middleware('module:recruitment')->group(function () {
        Route::get('/',                                    [\App\Http\Controllers\Admin\RecruitmentController::class, 'index'])->name('index')->middleware('permission:recruitment.view');
        Route::post('/',                                   [\App\Http\Controllers\Admin\RecruitmentController::class, 'store'])->name('store')->middleware('permission:recruitment.create');
        Route::get('/{job}',                               [\App\Http\Controllers\Admin\RecruitmentController::class, 'show'])->name('show')->middleware('permission:recruitment.view');
        Route::put('/{job}',                               [\App\Http\Controllers\Admin\RecruitmentController::class, 'update'])->name('update')->middleware('permission:recruitment.edit');
        Route::delete('/{job}',                            [\App\Http\Controllers\Admin\RecruitmentController::class, 'destroy'])->name('destroy')->middleware('permission:recruitment.delete');
        Route::post('/{job}/candidates',                   [\App\Http\Controllers\Admin\RecruitmentController::class, 'storeCandidate'])->name('candidates.store')->middleware('permission:candidates.create');
        Route::get('/candidates/{candidate}',              [\App\Http\Controllers\Admin\RecruitmentController::class, 'candidate'])->name('candidate')->middleware('permission:candidates.view');
        Route::patch('/candidates/{candidate}',            [\App\Http\Controllers\Admin\RecruitmentController::class, 'updateCandidate'])->name('candidates.update')->middleware('permission:candidates.edit');
        Route::patch('/candidates/{candidate}/stage',      [\App\Http\Controllers\Admin\RecruitmentController::class, 'moveStage'])->name('candidates.stage')->middleware('permission:candidates.edit');
        Route::post('/candidates/{candidate}/interview',   [\App\Http\Controllers\Admin\RecruitmentController::class, 'scheduleInterview'])->name('candidates.interview')->middleware('permission:candidates.create');
        Route::patch('/interviews/{interview}/feedback',   [\App\Http\Controllers\Admin\RecruitmentController::class, 'interviewFeedback'])->name('interviews.feedback')->middleware('permission:candidates.edit');
    });
    /* ---- Profile ---- */
    Route::get('/profile',           [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile',         [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password',[\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');

    /* ---- Global Search ---- */
    Route::get('/search', [\App\Http\Controllers\Admin\SearchController::class, 'search'])->name('search');

    /* ---- Notifications ---- */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/feed',          [\App\Http\Controllers\Admin\NotificationController::class, 'feed'])->name('feed');
        Route::patch('/{id}/read',   [\App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all',     [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/clear-all',  [\App\Http\Controllers\Admin\NotificationController::class, 'destroyAll'])->name('clear-all');
        Route::delete('/{id}',       [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('destroy');
    });

    /* ---- Announcements & Polls ---- */
Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::get('/',                      [\App\Http\Controllers\Admin\AnnouncementController::class, 'index'])->name('index')->middleware('permission:announcements.view');
    Route::post('/',                     [\App\Http\Controllers\Admin\AnnouncementController::class, 'store'])->name('store')->middleware('permission:announcements.create');
    Route::put('/{announcement}',        [\App\Http\Controllers\Admin\AnnouncementController::class, 'update'])->name('update')->middleware('permission:announcements.edit');
    Route::delete('/{announcement}',     [\App\Http\Controllers\Admin\AnnouncementController::class, 'destroy'])->name('destroy')->middleware('permission:announcements.delete');
    Route::post('/{announcement}/read',  [\App\Http\Controllers\Admin\AnnouncementController::class, 'markRead'])->name('read')->middleware('permission:announcements.view');
    Route::post('/polls',                [\App\Http\Controllers\Admin\AnnouncementController::class, 'storePoll'])->name('polls.store')->middleware('permission:announcements.create');
    Route::post('/polls/{poll}/vote',    [\App\Http\Controllers\Admin\AnnouncementController::class, 'vote'])->name('polls.vote')->middleware('permission:announcements.view');
    Route::patch('/polls/{poll}/close',  [\App\Http\Controllers\Admin\AnnouncementController::class, 'closePoll'])->name('polls.close')->middleware('permission:announcements.edit');
    Route::delete('/polls/{poll}',       [\App\Http\Controllers\Admin\AnnouncementController::class, 'destroyPoll'])->name('polls.destroy')->middleware('permission:announcements.delete');
});
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports.view');

    /* ---- Roles & Permissions ---- */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',                     [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('index')->middleware('permission:roles.view');
        Route::post('/',                    [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('store')->middleware('permission:roles.create');
        Route::get('/{role}',               [\App\Http\Controllers\Admin\RoleController::class, 'show'])->name('show')->middleware('permission:roles.view');
        Route::put('/{role}',               [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('update')->middleware('permission:roles.edit');
        Route::patch('/{role}/permissions', [\App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('permissions.update')->middleware('permission:roles.edit');
        Route::delete('/{role}',            [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('destroy')->middleware('permission:roles.delete');
        Route::post('/assign-user',         [\App\Http\Controllers\Admin\RoleController::class, 'assignRole'])->name('assign')->middleware('permission:users.edit');
        Route::post('/remove-user',         [\App\Http\Controllers\Admin\RoleController::class, 'removeRole'])->name('remove')->middleware('permission:users.edit');
    });

    /* ---- Settings ---- */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',                        [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('index')->middleware('permission:settings.view');
        Route::patch('/group/{group}',         [\App\Http\Controllers\Admin\SettingController::class, 'updateGroup'])->name('update')->middleware('permission:settings.edit');
        Route::post('/branding',               [\App\Http\Controllers\Admin\SettingController::class, 'updateBranding'])->name('branding.update')->middleware('permission:settings.edit');
        Route::post('/tax',                    [\App\Http\Controllers\Admin\SettingController::class, 'storeTax'])->name('tax.store')->middleware('permission:settings.edit');
        Route::patch('/tax/{taxSetting}',      [\App\Http\Controllers\Admin\SettingController::class, 'updateTax'])->name('tax.update')->middleware('permission:settings.edit');
        Route::delete('/tax/{taxSetting}',     [\App\Http\Controllers\Admin\SettingController::class, 'destroyTax'])->name('tax.destroy')->middleware('permission:settings.delete');
        Route::patch('/email/{emailTemplate}', [\App\Http\Controllers\Admin\SettingController::class, 'updateEmailTemplate'])->name('email.update')->middleware('permission:settings.edit');
    });
});