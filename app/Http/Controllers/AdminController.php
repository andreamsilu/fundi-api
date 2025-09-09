<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get all users
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $users = User::with('fundiProfile')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving users'
            ], 500);
        }
    }

    /**
     * Get specific user
     */
    public function getUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::with('fundiProfile')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving user'
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:active,inactive,banned',
                'role' => 'sometimes|in:customer,fundi,admin',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update($request->only(['status', 'role']));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating user'
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting user'
            ], 500);
        }
    }

    // Additional admin methods will be implemented as needed
}
