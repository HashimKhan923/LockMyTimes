<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Tenant\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-side only — new for the mobile API (no push, poll/pull-to-refresh
 * only, per the plan's explicit push-deferral). Notification::send() etc.
 * (write side) stays untouched; this just queries the polymorphic
 * notifications already produced by that pipeline for the current user.
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
}
