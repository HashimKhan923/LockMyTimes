<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SuperAdminNotification;

class NotificationController extends Controller
{
    /** GET /notifications — paginated list (for full page) */
    public function index()
    {
        $notifications = SuperAdminNotification::forAdmin(auth('superadmin')->id())
            ->latest()
            ->paginate(20);

        return view('superadmin.notifications.index', compact('notifications'));
    }

    /** GET /notifications/feed — JSON feed for the bell dropdown */
    public function feed()
    {
        $items = SuperAdminNotification::forAdmin(auth('superadmin')->id())
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'icon'       => $n->icon ?? 'bell',
                'color'      => $n->color ?? '#6C7DF7',
                'action_url' => $n->action_url,
                'unread'     => $n->isUnread(),
                'time'       => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = SuperAdminNotification::unreadFor(auth('superadmin')->id())->count();

        return response()->json([
            'items'        => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    /** PATCH /notifications/{id}/read */
    public function markRead(string $id)
    {
        $notif = SuperAdminNotification::forAdmin(auth('superadmin')->id())->findOrFail($id);
        $notif->markRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        if ($notif->action_url) {
            return redirect($notif->action_url);
        }

        return back();
    }

    /** POST /notifications/read-all */
    public function markAllRead()
    {
        SuperAdminNotification::unreadFor(auth('superadmin')->id())
            ->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /** DELETE /notifications/{id} */
    public function destroy(string $id)
    {
        SuperAdminNotification::forAdmin(auth('superadmin')->id())->findOrFail($id)->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /** DELETE /notifications — clear all */
    public function destroyAll()
    {
        SuperAdminNotification::forAdmin(auth('superadmin')->id())->delete();

        return back()->with('success', 'All notifications cleared.');
    }
}
