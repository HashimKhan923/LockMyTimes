<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /notifications — paginated list (for full page) */
    public function index(string $tenant)
    {
        $notifications = Notification::forUser(auth()->id())
            ->latest()
            ->paginate(20);

        return view('employee.notifications.index', compact('notifications', 'tenant'));
    }

    /** GET /notifications/feed — JSON feed for the bell dropdown */
    public function feed(string $tenant)
    {
        $items = Notification::forUser(auth()->id())
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

        $unreadCount = Notification::unreadFor(auth()->id())->count();

        return response()->json([
            'items'        => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    /** PATCH /notifications/{id}/read */
    public function markRead(string $tenant, string $id)
    {
        $notif = Notification::forUser(auth()->id())->findOrFail($id);
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
    public function markAllRead(string $tenant)
    {
        Notification::unreadFor(auth()->id())
            ->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /** DELETE /notifications/{id} */
    public function destroy(string $tenant, string $id)
    {
        Notification::forUser(auth()->id())->findOrFail($id)->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /** DELETE /notifications — clear all */
    public function destroyAll(string $tenant)
    {
        Notification::forUser(auth()->id())->delete();

        return back()->with('success', 'All notifications cleared.');
    }
}
