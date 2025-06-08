<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Auth::user()->notifications();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status if provided
        if ($request->has('read')) {
            $query->when($request->boolean('read'), fn($q) => $q->whereNotNull('read_at'))
                  ->when(!$request->boolean('read'), fn($q) => $q->whereNull('read_at'));
        }

        $notifications = $query->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Mark a notification as read
     *
     * @param Notification $notification
     * @return JsonResponse
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     *
     * @param Notification $notification
     * @return JsonResponse
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get unread notification count
     *
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }
} 