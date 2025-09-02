<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles', 'permissions');
        
        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }
        
        // Filter by user_type
        if ($request->has('user_type')) {
            $query->where('user_type', $request->user_type);
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

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load('roles', 'permissions', 'fundiProfile')
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,fundi,client,businessClient,businessProvider,moderator,support',
            'user_type' => 'required|in:individual,business,enterprise,government,nonprofit',
            'is_verified' => 'boolean',
            'is_available' => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        
        $user = User::create($data);
        
        // Assign role
        $user->assignRole($data['role']);
        
        return response()->json([
            'message' => 'User created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,fundi,client,businessClient,businessProvider,moderator,support',
            'user_type' => 'sometimes|in:individual,business,enterprise,government,nonprofit',
            'is_verified' => 'sometimes|boolean',
            'is_available' => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Handle role change
        if (isset($data['role']) && !$user->hasRole($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']); // Remove from update data
        }

        $user->update($data);
        
        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update(['is_available' => !$user->is_available]);
        
        return response()->json([
            'message' => 'User status updated',
            'data' => ['is_available' => $user->is_available]
        ]);
    }

    public function verify(User $user): JsonResponse
    {
        $user->update([
            'is_verified' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        
        return response()->json(['message' => 'User verified successfully']);
    }
}
