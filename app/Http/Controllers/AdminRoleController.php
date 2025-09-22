<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminRoleController extends Controller
{
    /**
     * Get all users with their roles
     */
    public function getUsersWithRoles(Request $request): JsonResponse
    {
        try {
            $users = User::select('id', 'phone', 'roles', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Transform the data to include role information
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'roles' => $user->roles,
                    'primary_role' => $user->primary_role,
                    'role_display_name' => $user->role_display_name,
                    'has_multiple_roles' => $user->hasMultipleRoles(),
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get users with roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific user's roles
     */
    public function getUserRoles(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'roles' => $user->roles,
                    'primary_role' => $user->primary_role,
                    'role_display_name' => $user->role_display_name,
                    'has_multiple_roles' => $user->hasMultipleRoles(),
                    'can_become_fundi' => $user->canBecomeFundi(),
                    'can_become_admin' => $user->canBecomeAdmin(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a role to a user
     */
    public function addRole(Request $request, $userId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:customer,fundi,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($userId);
            $role = $request->input('role');

            if ($user->addRole($role)) {
                return response()->json([
                    'success' => true,
                    'message' => "Role '{$role}' added successfully",
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->roles,
                        'role_display_name' => $user->role_display_name,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "User already has the '{$role}' role"
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a role from a user
     */
    public function removeRole(Request $request, $userId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|in:customer,fundi,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($userId);
            $role = $request->input('role');

            // Prevent removing the last role
            if (count($user->roles) <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the last role. User must have at least one role.'
                ], 400);
            }

            if ($user->removeRole($role)) {
                return response()->json([
                    'success' => true,
                    'message' => "Role '{$role}' removed successfully",
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->roles,
                        'role_display_name' => $user->role_display_name,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "User does not have the '{$role}' role"
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set user roles (replace all existing roles)
     */
    public function setRoles(Request $request, $userId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'roles' => 'required|array|min:1',
                'roles.*' => 'in:customer,fundi,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($userId);
            $roles = array_unique($request->input('roles')); // Remove duplicates

            $user->update(['roles' => $roles]);

            return response()->json([
                'success' => true,
                'message' => 'User roles updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'roles' => $user->roles,
                    'role_display_name' => $user->role_display_name,
                    'has_multiple_roles' => $user->hasMultipleRoles(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Promote user to fundi
     */
    public function promoteToFundi(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            if ($user->promoteToFundi()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User promoted to fundi successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->roles,
                        'role_display_name' => $user->role_display_name,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User cannot be promoted to fundi or already has fundi role'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user to fundi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Promote user to admin
     */
    public function promoteToAdmin(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            if ($user->promoteToAdmin()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User promoted to admin successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->roles,
                        'role_display_name' => $user->role_display_name,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User cannot be promoted to admin or already has admin role'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user to admin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Demote user to customer only
     */
    public function demoteToCustomer(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $user->demoteToCustomer();

            return response()->json([
                'success' => true,
                'message' => 'User demoted to customer successfully',
                'data' => [
                    'user_id' => $user->id,
                    'roles' => $user->roles,
                    'role_display_name' => $user->role_display_name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to demote user to customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(Request $request): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $customers = User::whereJsonContains('roles', 'customer')->count();
            $fundis = User::whereJsonContains('roles', 'fundi')->count();
            $admins = User::whereJsonContains('roles', 'admin')->count();
            $multiRoleUsers = User::whereRaw('JSON_LENGTH(roles) > 1')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'customers' => $customers,
                    'fundis' => $fundis,
                    'admins' => $admins,
                    'multi_role_users' => $multiRoleUsers,
                    'customer_fundi_combinations' => User::whereJsonContains('roles', 'customer')
                        ->whereJsonContains('roles', 'fundi')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get role statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available roles
     */
    public function getAvailableRoles(Request $request): JsonResponse
    {
        try {
            $roles = [
                [
                    'key' => 'customer',
                    'name' => 'Customer',
                    'description' => 'Can post jobs and hire fundis',
                    'permissions' => [
                        'post_jobs',
                        'hire_fundis',
                        'manage_jobs',
                        'approve_work',
                        'rate_fundis'
                    ]
                ],
                [
                    'key' => 'fundi',
                    'name' => 'Fundi',
                    'description' => 'Can apply for jobs and provide services',
                    'permissions' => [
                        'apply_jobs',
                        'manage_portfolio',
                        'submit_work',
                        'view_job_feeds',
                        'manage_applications'
                    ]
                ],
                [
                    'key' => 'admin',
                    'name' => 'Admin',
                    'description' => 'Can manage the platform and users',
                    'permissions' => [
                        'manage_users',
                        'manage_roles',
                        'manage_categories',
                        'view_analytics',
                        'manage_system',
                        'moderate_content'
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new custom role
     */
    public function createRole(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = Role::create([
                'name' => $request->input('name'),
                'display_name' => $request->input('display_name'),
                'description' => $request->input('description'),
                'is_system_role' => false,
                'is_active' => true,
                'sort_order' => $request->input('sort_order', 0),
            ]);

            // Assign permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->input('permissions'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => [
                    'role' => $role->load('permissions'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a role
     */
    public function updateRole(Request $request, $roleId): JsonResponse
    {
        try {
            $role = Role::findOrFail($roleId);

            // Prevent editing system roles
            if ($role->is_system_role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit system roles'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:roles,name,' . $roleId,
                'display_name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role->update($request->only([
                'name', 'display_name', 'description', 'sort_order', 'is_active'
            ]));

            // Update permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->input('permissions'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => [
                    'role' => $role->load('permissions'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(Request $request, $roleId): JsonResponse
    {
        try {
            $role = Role::findOrFail($roleId);

            // Prevent deleting system roles
            if ($role->is_system_role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system roles'
                ], 400);
            }

            // Check if any users have this role
            $usersWithRole = User::whereJsonContains('roles', $role->name)->count();
            if ($usersWithRole > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete role. {$usersWithRole} user(s) currently have this role."
                ], 400);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all roles
     */
    public function getAllRoles(Request $request): JsonResponse
    {
        try {
            $query = Role::with('permissions')
                ->orderBy('sort_order')
                ->orderBy('display_name');

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $roles = $query->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(Request $request): JsonResponse
    {
        try {
            $permissions = Permission::active()
                ->orderBy('category')
                ->orderBy('display_name')
                ->get()
                ->groupBy('category');

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new permission
     */
    public function createPermission(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:permissions,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = Permission::create([
                'name' => $request->input('name'),
                'display_name' => $request->input('display_name'),
                'description' => $request->input('description'),
                'category' => $request->input('category'),
                'is_system_permission' => false,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role details with permissions
     */
    public function getRoleDetails(Request $request, $roleId): JsonResponse
    {
        try {
            $role = Role::with('permissions')->findOrFail($roleId);

            return response()->json([
                'success' => true,
                'data' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get role details: ' . $e->getMessage()
            ], 500);
        }
    }
}