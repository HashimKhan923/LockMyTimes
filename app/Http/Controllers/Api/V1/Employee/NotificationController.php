<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Tenant\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read side of the in-app notification feed, plus push-token registration.
 * Writing notifications still goes through NotificationService::send(),
 * which also fires the OS-level push via ExpoPushService.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::forUser($user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'notifications' => NotificationResource::collection($notifications->items()),
            'unread_count' => Notification::unreadFor($user->id)->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        $notification->markRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::unreadFor($request->user()->id)->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Register (or refresh) this device's Expo push token, so future
     * NotificationService::send() calls can also fire an OS-level push.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'push_token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[.+\]$/'],
        ]);

        $request->user()->update(['device_token' => $data['push_token']]);

        return response()->json(['success' => true]);
    }
}
