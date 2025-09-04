<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Communications Controller
 * Handles all communication-related operations including notifications and messages
 */
class CommunicationsController extends Controller
{
    /**
     * Get all notifications with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $query = Notification::with('user')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('read')) {
                if ($request->read === 'true') {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $notifications = $query->paginate($perPage);

            return response()->json([
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch notifications'], 500);
        }
    }

    /**
     * Get unread notifications count
     *
     * @return JsonResponse
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $count = Notification::whereNull('read_at')->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Error fetching unread count: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch unread count'], 500);
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @return JsonResponse
     */
    public function markNotificationAsRead(int $notificationId): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->update(['read_at' => now()]);

            return response()->json(['message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark notification as read'], 500);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @return JsonResponse
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        try {
            Notification::whereNull('read_at')->update(['read_at' => now()]);

            return response()->json(['message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark all notifications as read'], 500);
        }
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @return JsonResponse
     */
    public function deleteNotification(int $notificationId): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->delete();

            return response()->json(['message' => 'Notification deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete notification'], 500);
        }
    }

    /**
     * Get communication statistics
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_notifications' => Notification::count(),
                'unread_notifications' => Notification::whereNull('read_at')->count(),
                'total_messages' => 0, // Placeholder for messages when implemented
                'unread_messages' => 0, // Placeholder for messages when implemented
                'notifications_by_type' => Notification::select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'messages_by_month' => [], // Placeholder for messages when implemented
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error fetching communication stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch communication stats'], 500);
        }
    }

    /**
     * Bulk delete notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDeleteNotifications(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $deletedCount = Notification::whereIn('id', $request->notification_ids)->delete();

            return response()->json([
                'message' => "Successfully deleted {$deletedCount} notifications"
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting notifications: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to bulk delete notifications'], 500);
        }
    }

    /**
     * Get communication history for a specific user
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserCommunicationHistory(int $userId, Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $query = Notification::where('user_id', $userId)
                ->with('user')
                ->orderBy('created_at', 'desc');

            // Apply type filter
            if ($request->filled('type') && $request->type !== 'all') {
                if ($request->type === 'notifications') {
                    // Only notifications (current implementation)
                    // When messages are implemented, this will filter appropriately
                }
            }

            $perPage = $request->get('per_page', 15);
            $notifications = $query->paginate($perPage);

            return response()->json([
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user communication history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch user communication history'], 500);
        }
    }
}
