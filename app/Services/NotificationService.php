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
     * Broadcast a notification to all admin users.
     */
    public static function notifyAdmins(
        string $title,
        string $type       = 'general',
        string $icon       = 'bell',
        string $color      = '#6C7DF7',
        ?string $actionUrl = null,
        array  $data       = []
    ): void {
        $admins = User::role(['Tenant Admin', 'HR Manager', 'Admin'])->get();
        foreach ($admins as $admin) {
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

    public static function payrollReady(User $admin, string $period, string $url): void
    {
        static::send($admin, "Payroll run for {$period} is ready for review",
            'payroll.ready', 'dollar-sign', '#F59E0B', $url);
    }

    public static function payrollApproved(User $admin, string $period, string $url): void
    {
        static::send($admin, "Payroll for {$period} has been approved",
            'payroll.approved', 'check-circle', '#10B981', $url);
    }

    public static function expenseSubmitted(User $admin, string $employeeName, string $amount, string $url): void
    {
        static::send($admin, "{$employeeName} submitted an expense of {$amount}",
            'expense.submitted', 'receipt', '#8B5CF6', $url);
    }

    public static function newEmployee(User $admin, string $name, string $url): void
    {
        static::send($admin, "New employee {$name} has been added",
            'employee.created', 'user-plus', '#6C7DF7', $url);
    }
}
