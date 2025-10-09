<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving notifications'
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->update(['read_status' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while marking notification as read'
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting notification'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Notification::where('user_id', $user->id)
                ->where('read_status', false)
                ->update(['read_status' => true]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while marking notifications as read'
            ], 500);
        }
    }

    /**
     * Delete notification (alias for destroy)
     */
    public function delete(Request $request, $id): JsonResponse
    {
        return $this->destroy($request, $id);
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Notification::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear all notifications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while clearing notifications'
            ], 500);
        }
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $settings = [
                'email_notifications' => true,
                'push_notifications' => true,
                'job_alerts' => true,
                'message_alerts' => true,
                'marketing_emails' => false,
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while getting notification settings'
            ], 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'email_notifications' => 'boolean',
                'push_notifications' => 'boolean',
                'job_alerts' => 'boolean',
                'message_alerts' => 'boolean',
                'marketing_emails' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, you would save these settings to a user settings table
            
            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating notification settings'
            ], 500);
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Create a test notification
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => 'Test Notification',
                'message' => 'This is a test notification to verify your notification settings.',
                'type' => 'test',
                'read_status' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending test notification'
            ], 500);
        }
    }

    /**
     * Get all notifications (admin only)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $query = Notification::with('user');

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by read status
            if ($request->has('read_status')) {
                $query->where('read_status', $request->read_status);
            }

            $perPage = $request->get('per_page', 15);
            $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Send notification to user(s) (admin only)
     */
    public function sendNotification(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'user_ids' => 'sometimes|array',
                'user_ids.*' => 'exists:users,id',
                'broadcast' => 'sometimes|boolean',
                'title' => 'required|string|max:200',
                'message' => 'required|string|max:1000',
                'type' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notifications = [];

            // Send to specific user
            if ($request->has('user_id')) {
                $notification = Notification::create([
                    'user_id' => $request->user_id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'read_status' => false,
                ]);
                $notifications[] = $notification;
            }
            // Send to multiple users
            elseif ($request->has('user_ids')) {
                foreach ($request->user_ids as $userId) {
                    $notification = Notification::create([
                        'user_id' => $userId,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                        'read_status' => false,
                    ]);
                    $notifications[] = $notification;
                }
            }
            // Broadcast to all users
            elseif ($request->broadcast) {
                $userIds = \App\Models\User::where('status', 'active')->pluck('id');
                foreach ($userIds as $userId) {
                    $notification = Notification::create([
                        'user_id' => $userId,
                        'title' => $request->title,
                        'message' => $request->message,
                        'type' => $request->type,
                        'read_status' => false,
                    ]);
                    $notifications[] = $notification;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => [
                    'count' => count($notifications),
                    'notifications' => $notifications
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update notification (admin only)
     */
    public function adminUpdate(Request $request, $id): JsonResponse
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->update($request->only(['title', 'message', 'type', 'read_status']));

            return response()->json([
                'success' => true,
                'message' => 'Notification updated successfully',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Delete notification (admin only)
     */
    public function adminDelete($id): JsonResponse
    {
        try {
            $notification = Notification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
