<?php

namespace App\Http\Controllers\Uac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    /**
     * Display a listing of users with their permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('permissions');
        
        // Filter by permission
        if ($request->has('permission')) {
            $query->permission($request->permission);
        }
        
        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $users = $query->paginate($request->get('per_page', 15));
        
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Display the specified user with permissions.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load('permissions', 'roles.permissions'),
            'meta' => [
                'message' => 'User permissions retrieved successfully'
            ]
        ]);
    }

    /**
     * Give a permission to a user.
     */
    public function givePermission(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        // Check if user already has this permission
        if ($user->hasPermissionTo($data['permission'])) {
            return response()->json([
                'message' => 'User already has this permission',
                'errors' => ['permission' => ['User already has the specified permission.']]
            ], 400);
        }

        $user->givePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission granted successfully',
            'data' => [
                'user' => $user->load('permissions'),
                'granted_permission' => $data['permission']
            ]
        ]);
    }

    /**
     * Revoke a permission from a user.
     */
    public function revokePermission(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        // Check if user has this permission
        if (!$user->hasPermissionTo($data['permission'])) {
            return response()->json([
                'message' => 'User does not have this permission',
                'errors' => ['permission' => ['User does not have the specified permission.']]
            ], 400);
        }

        $user->revokePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission revoked successfully',
            'data' => [
                'user' => $user->load('permissions'),
                'revoked_permission' => $data['permission']
            ]
        ]);
    }

    /**
     * Sync multiple permissions for a user.
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $user->syncPermissions($data['permissions']);

        return response()->json([
            'message' => 'Permissions synchronized successfully',
            'data' => [
                'user' => $user->load('permissions'),
                'synced_permissions' => $data['permissions']
            ]
        ]);
    }

    /**
     * Get user's effective permissions (direct + from roles).
     */
    public function getEffectivePermissions(User $user): JsonResponse
    {
        $directPermissions = $user->permissions;
        $rolePermissions = $user->getPermissionsViaRoles();
        
        // Combine and deduplicate permissions
        $allPermissions = $directPermissions->merge($rolePermissions)->unique('id');
        
        // Group by source
        $permissionsBySource = [
            'direct' => $directPermissions->pluck('name')->toArray(),
            'via_roles' => $rolePermissions->pluck('name')->toArray(),
            'effective' => $allPermissions->pluck('name')->toArray()
        ];

        return response()->json([
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'permissions_by_source' => $permissionsBySource,
                'total_effective_permissions' => $allPermissions->count()
            ]
        ]);
    }

    /**
     * Get users by permission.
     */
    public function getUsersByPermission(string $permission): JsonResponse
    {
        $users = User::permission($permission)->with('permissions')->get();

        return response()->json([
            'data' => $users,
            'meta' => [
                'permission' => $permission,
                'total_users' => $users->count()
            ]
        ]);
    }

    /**
     * Get permission assignment statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();
        $usersWithPermissions = User::has('permissions')->count();
        $usersWithoutPermissions = $totalUsers - $usersWithPermissions;

        // Get permission distribution
        $permissionDistribution = Permission::withCount('users')->get()->map(function ($permission) {
            return [
                'permission_name' => $permission->name,
                'user_count' => $permission->users_count
            ];
        });

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'users_with_permissions' => $usersWithPermissions,
                'users_without_permissions' => $usersWithoutPermissions,
                'permission_distribution' => $permissionDistribution
            ]
        ]);
    }

    /**
     * Check if user has specific permission.
     */
    public function checkPermission(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string'
        ]);

        $hasPermission = $user->hasPermissionTo($data['permission']);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'permission' => $data['permission'],
                'has_permission' => $hasPermission,
                'check_result' => $hasPermission ? 'GRANTED' : 'DENIED'
            ]
        ]);
    }
}
