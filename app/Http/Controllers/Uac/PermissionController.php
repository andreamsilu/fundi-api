<?php

namespace App\Http\Controllers\Uac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::with('roles')->orderBy('name')->get();
        
        return response()->json([
            'data' => $permissions,
            'meta' => [
                'total' => $permissions->count(),
                'message' => 'Permissions retrieved successfully'
            ]
        ]);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
            'description' => 'nullable|string|max:255',
            'guard_name' => 'nullable|string|max:100'
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => $data['guard_name'] ?? 'web'
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'data' => $permission
        ], 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json([
            'data' => $permission->load('roles'),
            'meta' => [
                'message' => 'Permission retrieved successfully'
            ]
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255',
            'guard_name' => 'nullable|string|max:100'
        ]);

        $permission->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => $data['guard_name'] ?? 'web'
        ]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'data' => $permission
        ]);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        // Prevent deletion of system permissions
        if (in_array($permission->name, ['manage roles', 'manage permissions'])) {
            return response()->json([
                'message' => 'Cannot delete system permissions',
                'errors' => ['permission' => ['System permissions cannot be deleted.']]
            ], 400);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }

    /**
     * Get permission statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalPermissions = Permission::count();
        $permissionsWithRoles = Permission::has('roles')->count();
        $permissionsWithoutRoles = $totalPermissions - $permissionsWithRoles;

        return response()->json([
            'data' => [
                'total_permissions' => $totalPermissions,
                'permissions_with_roles' => $permissionsWithRoles,
                'permissions_without_roles' => $permissionsWithoutRoles,
                'most_assigned_permissions' => $this->getMostAssignedPermissions()
            ]
        ]);
    }

    /**
     * Get most assigned permissions to roles.
     */
    private function getMostAssignedPermissions(): array
    {
        return \DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.name', \DB::raw('count(*) as assignment_count'))
            ->groupBy('permissions.id', 'permissions.name')
            ->orderBy('assignment_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Search permissions by name.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $permissions = Permission::where('name', 'like', '%' . $request->query . '%')
            ->orWhere('description', 'like', '%' . $request->query . '%')
            ->with('roles')
            ->get();

        return response()->json([
            'data' => $permissions,
            'meta' => [
                'query' => $request->query,
                'total' => $permissions->count()
            ]
        ]);
    }
}
