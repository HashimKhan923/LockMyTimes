<?php

namespace App\Services;

use App\Models\Main\SuperAdmin;
use App\Models\Main\SuperAdminNotification;

class SuperAdminNotificationService
{
    /**
     * Send a notification to a specific super admin.
     */
    public static function send(
        SuperAdmin $superAdmin,
        string     $title,
        string     $type       = 'general',
        string     $icon       = 'bell',
        string     $color      = '#6C7DF7',
        ?string    $actionUrl  = null,
        array      $data       = []
    ): SuperAdminNotification {
        return SuperAdminNotification::create([
            'super_admin_id' => $superAdmin->id,
            'type'           => $type,
            'title'          => $title,
            'icon'           => $icon,
            'color'          => $color,
            'action_url'     => $actionUrl,
            'data'           => $data,
        ]);
    }

    /**
     * Broadcast a notification to every active super admin.
     */
    public static function notifyAll(
        string $title,
        string $type       = 'general',
        string $icon       = 'bell',
        string $color      = '#6C7DF7',
        ?string $actionUrl = null,
        array  $data       = []
    ): void {
        $superAdmins = SuperAdmin::where('is_active', true)->get();
        foreach ($superAdmins as $superAdmin) {
            static::send($superAdmin, $title, $type, $icon, $color, $actionUrl, $data);
        }
    }

    /**
     * Shorthand helpers for common system events.
     */
    public static function tenantSignedUp(string $tenantName, string $url): void
    {
        static::notifyAll("New organization signed up: {$tenantName}",
            'tenant.signup', 'building-2', '#10B981', $url);
    }

    public static function paymentFailed(string $tenantName, string $amount, string $url): void
    {
        static::notifyAll("Payment failed for {$tenantName} ({$amount})",
            'payment.failed', 'alert-triangle', '#EF4444', $url);
    }

    public static function trialExpiringSoon(string $tenantName, int $daysLeft, string $url): void
    {
        static::notifyAll("{$tenantName}'s trial ends in {$daysLeft} day(s)",
            'tenant.trial_expiring', 'clock', '#F59E0B', $url);
    }

    public static function supportTicketOpened(string $tenantName, string $subject, string $url): void
    {
        static::notifyAll("New support ticket from {$tenantName}: {$subject}",
            'ticket.opened', 'life-buoy', '#3B82F6', $url);
    }
}
