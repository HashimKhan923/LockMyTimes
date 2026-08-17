<?php

namespace App\Services;

use App\Models\Tenant\Notification;
use App\Models\Tenant\User;

class NotificationService
{
    /**
     * Send a notification to a specific user.
     */
    public static function send(
        User   $user,
        string $title,
        string $type        = 'general',
        string $icon        = 'bell',
        string $color       = '#6C7DF7',
        ?string $actionUrl  = null,
        array  $data        = []
    ): Notification {
        return Notification::create([
            'type'            => $type,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'title'           => $title,
            'icon'            => $icon,
            'color'           => $color,
            'action_url'      => $actionUrl,
            'data'            => $data,
        ]);
    }

    /**
     * Broadcast a notification to all admin users. Pass $excludeUserId (typically the acting
     * admin) to avoid notifying someone about the action they just took themselves.
     */
    public static function notifyAdmins(
        string $title,
        string $type         = 'general',
        string $icon         = 'bell',
        string $color        = '#6C7DF7',
        ?string $actionUrl   = null,
        array  $data         = [],
        ?int   $excludeUserId = null
    ): void {
        $admins = User::role(['Tenant Admin', 'HR Manager'], 'web')->get();
        foreach ($admins as $admin) {
            if ($excludeUserId !== null && $admin->id === $excludeUserId) {
                continue;
            }
            static::send($admin, $title, $type, $icon, $color, $actionUrl, $data);
        }
    }

    /**
     * Shorthand helpers for common notification types.
     */
    public static function leaveRequested(User $admin, string $employeeName, int $days, string $url): void
    {
        static::send($admin, "{$employeeName} requested {$days} day(s) of leave",
            'leave.requested', 'calendar', '#3B82F6', $url);
    }

    public static function leaveApproved(User $employee, string $approverName, string $url): void
    {
        static::send($employee, "Your leave was approved by {$approverName}",
            'leave.approved', 'check-circle', '#10B981', $url);
    }

    public static function leaveRejected(User $employee, string $approverName, string $url): void
    {
        static::send($employee, "Your leave was rejected by {$approverName}",
            'leave.rejected', 'x-circle', '#EF4444', $url);
    }

    public static function loanApproved(User $employee, string $amount, string $url): void
    {
        static::send($employee, "Your loan request of {$amount} was approved",
            'loan.approved', 'check-circle', '#10B981', $url);
    }

    public static function loanRejected(User $employee, string $amount, string $url): void
    {
        static::send($employee, "Your loan request of {$amount} was rejected",
            'loan.rejected', 'x-circle', '#EF4444', $url);
    }

    public static function loanDisbursed(User $employee, string $amount, string $url): void
    {
        static::send($employee, "Your loan of {$amount} has been disbursed",
            'loan.disbursed', 'banknote', '#10B981', $url);
    }

    public static function payslipAvailable(User $employee, string $period, string $url): void
    {
        static::send($employee, "Your payslip for {$period} is available",
            'payslip.available', 'file-text', '#6C7DF7', $url);
    }

    public static function assetAssigned(User $employee, string $assetName, string $url): void
    {
        static::send($employee, "{$assetName} has been assigned to you",
            'asset.assigned', 'package', '#3B82F6', $url);
    }

    public static function trainingEnrolled(User $employee, string $courseName, string $url): void
    {
        static::send($employee, "You've been enrolled in {$courseName}",
            'training.enrolled', 'graduation-cap', '#3B82F6', $url);
    }

    public static function taskAssigned(User $employee, string $taskTitle, string $url): void
    {
        static::send($employee, "You've been assigned a task: {$taskTitle}",
            'task.assigned', 'check-square', '#6C7DF7', $url);
    }

    public static function reviewAssigned(User $employee, string $revieweeName, string $url): void
    {
        static::send($employee, "You've been assigned to review {$revieweeName}",
            'review.assigned', 'clipboard-list', '#8B5CF6', $url);
    }

    public static function kudoReceived(User $employee, string $fromName, string $url): void
    {
        static::send($employee, "{$fromName} gave you a kudo",
            'kudo.received', 'award', '#F59E0B', $url);
    }
}
