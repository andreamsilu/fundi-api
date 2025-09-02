<?php

namespace App\Http\Controllers\Uac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles', 'permissions');
        
        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
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
     * Display the specified user with roles.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load('roles', 'permissions'),
            'meta' => [
                'message' => 'User roles retrieved successfully'
            ]
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        // Check if user already has this role
        if ($user->hasRole($data['role'])) {
            return response()->json([
                'message' => 'User already has this role',
                'errors' => ['role' => ['User already has the specified role.']]
            ], 400);
        }

        $user->assignRole($data['role']);

        return response()->json([
            'message' => 'Role assigned successfully',
            'data' => [
                'user' => $user->load('roles'),
                'assigned_role' => $data['role']
            ]
        ]);
    }

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        // Prevent revoking admin role from last admin
        if ($data['role'] === 'admin' && $this->isLastAdmin($user)) {
            return response()->json([
                'message' => 'Cannot revoke admin role from last admin',
                'errors' => ['role' => ['Cannot revoke admin role from the last admin user.']]
            ], 400);
        }

        // Check if user has this role
        if (!$user->hasRole($data['role'])) {
            return response()->json([
                'message' => 'User does not have this role',
                'errors' => ['role' => ['User does not have the specified role.']]
            ], 400);
        }

        $user->removeRole($data['role']);

        return response()->json([
            'message' => 'Role revoked successfully',
            'data' => [
                'user' => $user->load('roles'),
                'revoked_role' => $data['role']
            ]
        ]);
    }

    /**
     * Sync multiple roles for a user.
     */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        // Prevent removing admin role from last admin
        if ($user->hasRole('admin') && !in_array('admin', $data['roles'])) {
            if ($this->isLastAdmin($user)) {
                return response()->json([
                    'message' => 'Cannot remove admin role from last admin',
                    'errors' => ['roles' => ['Cannot remove admin role from the last admin user.']]
                ], 400);
            }
        }

        $user->syncRoles($data['roles']);

        return response()->json([
            'message' => 'Roles synchronized successfully',
            'data' => [
                'user' => $user->load('roles'),
                'synced_roles' => $data['roles']
            ]
        ]);
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole(string $role): JsonResponse
    {
        $users = User::role($role)->with('roles')->get();

        return response()->json([
            'data' => $users,
            'meta' => [
                'role' => $role,
                'total_users' => $users->count()
            ]
        ]);
    }

    /**
     * Get role assignment statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();
        $usersWithRoles = User::has('roles')->count();
        $usersWithoutRoles = $totalUsers - $usersWithRoles;

        // Get role distribution
        $roleDistribution = Role::withCount('users')->get()->map(function ($role) {
            return [
                'role_name' => $role->name,
                'user_count' => $role->users_count
            ];
        });

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'users_with_roles' => $usersWithRoles,
                'users_without_roles' => $usersWithoutRoles,
                'role_distribution' => $roleDistribution
            ]
        ]);
    }

    /**
     * Check if user is the last admin.
     */
    private function isLastAdmin(User $user): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        $adminCount = User::role('admin')->count();
        return $adminCount === 1;
    }
}
