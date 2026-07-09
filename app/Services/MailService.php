<?php

namespace App\Services;

use App\Mail\HrMail;
use App\Models\Main\Tenant;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\Asset;
use App\Models\Tenant\AssetAssignment;
use App\Models\Tenant\Candidate;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Interview;
use App\Models\Tenant\Kudo;
use App\Models\Tenant\LeaveRequest;
use App\Models\Tenant\Loan;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PerformanceReview;
use App\Models\Tenant\Task;
use App\Models\Tenant\Training;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function __construct(protected TenantManager $tenantManager) {}

    /* ─────────────────────────────────────────────────────────────────
     | Tenant Context
     |────────────────────────────────────────────────────────────────*/
    protected function ctx(): array
    {
        $tenant = $this->tenantManager->current();

        return [
            'companyName' => $tenant?->company_name ?? 'Your Company',
            'companyLogo' => $tenant?->logo ? asset('storage/' . $tenant->logo) : null,
            'tenantSlug'  => $tenant?->slug ?? '',
            'portalUrl'   => $tenant ? url('/t/' . $tenant->slug . '/portal') : url('/'),
            'adminUrl'    => $tenant ? url('/t/' . $tenant->slug . '/admin') : url('/'),
        ];
    }

    protected function send(string $to, string $view, array $data): void
    {
        try {
            Mail::to($to)->send(new HrMail($view, $data));
        } catch (\Throwable $e) {
            Log::error("MailService failed sending {$view} to {$to}: " . $e->getMessage());
        }
    }

    protected function sendMany(array $recipients, string $view, array $data): void
    {
        foreach ($recipients as $email) {
            if ($email) $this->send($email, $view, $data);
        }
    }

    /* ─────────────────────────────────────────────────────────────────
     | Admin helper — collect admin/HR email addresses
     |────────────────────────────────────────────────────────────────*/
    protected function adminEmails(): array
    {
        return User::role(['Tenant Admin', 'HR Manager', 'Admin'])
            ->where('is_active', true)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /* ─────────────────────────────────────────────────────────────────
     | 1. EMPLOYEE WELCOME
     |────────────────────────────────────────────────────────────────*/
    public function sendEmployeeWelcome(Employee $employee, string $tempPassword): void
    {
        $ctx = $this->ctx();
        $this->send($employee->email, 'emails.employee-welcome', array_merge($ctx, [
            'subject'      => "Welcome to {$ctx['companyName']} — Your Account is Ready",
            'employee'     => $employee,
            'tempPassword' => $tempPassword,
            'loginUrl'     => $ctx['portalUrl'] . '/login',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 2. LEAVE MANAGEMENT
     |────────────────────────────────────────────────────────────────*/
    public function sendLeaveRequested(LeaveRequest $leave): void
    {
        $ctx    = $this->ctx();
        $admins = $this->adminEmails();
        $this->sendMany($admins, 'emails.leave-requested', array_merge($ctx, [
            'subject'  => "{$leave->employee->full_name} submitted a leave request",
            'leave'    => $leave,
            'adminUrl' => $ctx['adminUrl'] . '/leaves',
        ]));
    }

    public function sendLeaveApproved(LeaveRequest $leave): void
    {
        $ctx   = $this->ctx();
        $email = $leave->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.leave-approved', array_merge($ctx, [
            'subject'   => "Your leave request has been approved",
            'leave'     => $leave,
            'portalUrl' => $ctx['portalUrl'] . '/leaves',
        ]));
    }

    public function sendLeaveRejected(LeaveRequest $leave): void
    {
        $ctx   = $this->ctx();
        $email = $leave->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.leave-rejected', array_merge($ctx, [
            'subject'   => "Your leave request was not approved",
            'leave'     => $leave,
            'portalUrl' => $ctx['portalUrl'] . '/leaves',
        ]));
    }

    public function sendLeaveCancelled(LeaveRequest $leave): void
    {
        $ctx   = $this->ctx();
        $email = $leave->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.leave-cancelled', array_merge($ctx, [
            'subject'   => "Your leave has been cancelled",
            'leave'     => $leave,
            'portalUrl' => $ctx['portalUrl'] . '/leaves',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 3. EXPENSE MANAGEMENT
     |────────────────────────────────────────────────────────────────*/
    public function sendExpenseSubmitted(Expense $expense): void
    {
        $ctx    = $this->ctx();
        $admins = $this->adminEmails();
        $this->sendMany($admins, 'emails.expense-submitted', array_merge($ctx, [
            'subject'  => "{$expense->employee->full_name} submitted an expense",
            'expense'  => $expense,
            'adminUrl' => $ctx['adminUrl'] . '/expenses',
        ]));
    }

    public function sendExpenseApproved(Expense $expense): void
    {
        $ctx   = $this->ctx();
        $email = $expense->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.expense-approved', array_merge($ctx, [
            'subject'   => "Your expense has been approved",
            'expense'   => $expense,
            'portalUrl' => $ctx['portalUrl'] . '/expenses',
        ]));
    }

    public function sendExpenseRejected(Expense $expense): void
    {
        $ctx   = $this->ctx();
        $email = $expense->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.expense-rejected', array_merge($ctx, [
            'subject'   => "Your expense was not approved",
            'expense'   => $expense,
            'portalUrl' => $ctx['portalUrl'] . '/expenses',
        ]));
    }

    public function sendExpensePaid(Expense $expense): void
    {
        $ctx   = $this->ctx();
        $email = $expense->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.expense-paid', array_merge($ctx, [
            'subject'   => "Your expense reimbursement has been processed",
            'expense'   => $expense,
            'portalUrl' => $ctx['portalUrl'] . '/expenses',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 4. PAYROLL — Payslip available
     |────────────────────────────────────────────────────────────────*/
    public function sendPayslipAvailable(Payslip $payslip): void
    {
        $ctx   = $this->ctx();
        $email = $payslip->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.payslip-available', array_merge($ctx, [
            'subject'   => "Your payslip for {$payslip->payrollRun->period_start->format('F Y')} is ready",
            'payslip'   => $payslip,
            'portalUrl' => $ctx['portalUrl'] . '/payslips',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 5. LOANS
     |────────────────────────────────────────────────────────────────*/
    public function sendLoanApproved(Loan $loan): void
    {
        $ctx   = $this->ctx();
        $email = $loan->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.loan-approved', array_merge($ctx, [
            'subject'   => "Your loan request has been approved",
            'loan'      => $loan,
            'portalUrl' => $ctx['portalUrl'] . '/loans',
        ]));
    }

    public function sendLoanRejected(Loan $loan): void
    {
        $ctx   = $this->ctx();
        $email = $loan->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.loan-rejected', array_merge($ctx, [
            'subject'   => "Your loan request was not approved",
            'loan'      => $loan,
            'portalUrl' => $ctx['portalUrl'] . '/loans',
        ]));
    }

    public function sendLoanDisbursed(Loan $loan): void
    {
        $ctx   = $this->ctx();
        $email = $loan->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.loan-disbursed', array_merge($ctx, [
            'subject'   => "Your loan has been disbursed",
            'loan'      => $loan,
            'portalUrl' => $ctx['portalUrl'] . '/loans',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 6. TRAINING
     |────────────────────────────────────────────────────────────────*/
    public function sendTrainingEnrolled(Employee $employee, Training $training): void
    {
        $ctx = $this->ctx();
        if (! $employee->email) return;
        $this->send($employee->email, 'emails.training-enrolled', array_merge($ctx, [
            'subject'   => "You have been enrolled in: {$training->title}",
            'employee'  => $employee,
            'training'  => $training,
            'portalUrl' => $ctx['portalUrl'] . '/trainings',
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 7. RECRUITMENT
     |────────────────────────────────────────────────────────────────*/
    public function sendInterviewScheduled(Interview $interview): void
    {
        $ctx   = $this->ctx();
        $email = $interview->candidate->email ?? null;
        if (! $email) return;
        $jobTitle = $interview->candidate->jobPosting?->title ?? 'Open Position';
        $this->send($email, 'emails.interview-scheduled', array_merge($ctx, [
            'subject'   => "Interview Invitation — {$jobTitle}",
            'interview' => $interview,
            'candidate' => $interview->candidate,
            'portalUrl' => null,
        ]));
    }

    public function sendApplicationReceived(Candidate $candidate): void
    {
        $ctx   = $this->ctx();
        $email = $candidate->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.application-received', array_merge($ctx, [
            'subject'   => "We received your application — {$ctx['companyName']}",
            'candidate' => $candidate,
        ]));
    }

    public function sendCandidateHired(Candidate $candidate): void
    {
        $ctx   = $this->ctx();
        $email = $candidate->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.candidate-hired', array_merge($ctx, [
            'subject'   => "Congratulations! You've been selected — {$ctx['companyName']}",
            'candidate' => $candidate,
        ]));
    }

    public function sendCandidateRejected(Candidate $candidate): void
    {
        $ctx   = $this->ctx();
        $email = $candidate->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.candidate-rejected', array_merge($ctx, [
            'subject'   => "Update on your application — {$ctx['companyName']}",
            'candidate' => $candidate,
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 8. ASSET ASSIGNED
     |────────────────────────────────────────────────────────────────*/
    public function sendAssetAssigned(AssetAssignment $assignment): void
    {
        $ctx   = $this->ctx();
        $email = $assignment->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.asset-assigned', array_merge($ctx, [
            'subject'    => "An asset has been assigned to you",
            'assignment' => $assignment,
            'portalUrl'  => $ctx['portalUrl'],
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 9. ANNOUNCEMENT
     |────────────────────────────────────────────────────────────────*/
    public function sendAnnouncement(Announcement $announcement, array $employeeEmails): void
    {
        $ctx  = $this->ctx();
        $data = array_merge($ctx, [
            'subject'      => "[{$ctx['companyName']}] {$announcement->title}",
            'announcement' => $announcement,
            'portalUrl'    => $ctx['portalUrl'] . '/announcements',
        ]);
        $this->sendMany($employeeEmails, 'emails.announcement', $data);
    }

    /* ─────────────────────────────────────────────────────────────────
     | 10. PERFORMANCE REVIEW ASSIGNED
     |────────────────────────────────────────────────────────────────*/
    public function sendReviewAssigned(PerformanceReview $review): void
    {
        $ctx   = $this->ctx();
        $email = $review->employee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.review-assigned', array_merge($ctx, [
            'subject'   => "A performance review has been assigned to you",
            'review'    => $review,
            'portalUrl' => $ctx['portalUrl'],
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 11. KUDO RECEIVED
     |────────────────────────────────────────────────────────────────*/
    public function sendKudoReceived(Kudo $kudo): void
    {
        $ctx   = $this->ctx();
        $email = $kudo->toEmployee->email ?? null;
        if (! $email) return;
        $this->send($email, 'emails.kudo-received', array_merge($ctx, [
            'subject' => "{$kudo->fromEmployee->first_name} sent you a kudos! ",
            'kudo'      => $kudo,
            'portalUrl' => $ctx['portalUrl'],
        ]));
    }

    /* ─────────────────────────────────────────────────────────────────
     | 12. TASK ASSIGNED
     |────────────────────────────────────────────────────────────────*/
    public function sendTaskAssigned(Employee $employee, Task $task): void
    {
        $ctx = $this->ctx();
        if (! $employee->email) return;
        $this->send($employee->email, 'emails.task-assigned', array_merge($ctx, [
            'subject'   => "New task assigned: {$task->title}",
            'employee'  => $employee,
            'task'      => $task,
            'portalUrl' => $ctx['portalUrl'],
        ]));
    }
}
