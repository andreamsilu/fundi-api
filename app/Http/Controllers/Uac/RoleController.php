<?php

namespace App\Http\Controllers\Uac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with(['permissions'])->orderBy('name')->get();
        
        // Add permissions_count to each role
        $roles->each(function ($role) {
            $role->permissions_count = $role->permissions->count();
        });
        
        return response()->json([
            'data' => $roles,
            'meta' => [
                'total' => $roles->count(),
                'message' => 'Roles retrieved successfully'
            ]
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role = $role->load(['permissions']);
        $role->permissions_count = $role->permissions->count();
        
        return response()->json([
            'data' => $role,
            'meta' => [
                'message' => 'Role retrieved successfully'
            ]
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);

        // Sync permissions if provided
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $role = $role->load(['permissions']);
        $role->permissions_count = $role->permissions->count();
        
        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Prevent deletion of system roles
        if (in_array($role->name, ['admin', 'super_admin'])) {
            return response()->json([
                'message' => 'Cannot delete system roles',
                'errors' => ['role' => ['System roles cannot be deleted.']]
            ], 400);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get role statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalRoles = Role::count();
        $rolesWithPermissions = Role::has('permissions')->count();
        $rolesWithoutPermissions = $totalRoles - $rolesWithPermissions;

        return response()->json([
            'data' => [
                'total_roles' => $totalRoles,
                'roles_with_permissions' => $rolesWithPermissions,
                'roles_without_permissions' => $rolesWithoutPermissions,
                'most_used_permissions' => $this->getMostUsedPermissions()
            ]
        ]);
    }

    /**
     * Get most used permissions across roles.
     */
    private function getMostUsedPermissions(): array
    {
        return \DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.name', \DB::raw('count(*) as usage_count'))
            ->groupBy('permissions.id', 'permissions.name')
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Attach a permission to a role.
     */
    public function attachPermission(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $role->givePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission attached to role successfully',
            'data' => [
                'role' => $role->load('permissions'),
                'attached_permission' => $data['permission']
            ]
        ]);
    }

    /**
     * Detach a permission from a role.
     */
    public function detachPermission(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permission' => 'required|string|exists:permissions,name'
        ]);

        $role->revokePermissionTo($data['permission']);

        return response()->json([
            'message' => 'Permission detached from role successfully',
            'data' => [
                'role' => $role->load('permissions'),
                'detached_permission' => $data['permission']
            ]
        ]);
    }
}
