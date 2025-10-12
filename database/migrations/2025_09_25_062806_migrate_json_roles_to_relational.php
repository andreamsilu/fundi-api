<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if roles table doesn't exist yet (will be created later by Laravel Permission migration)
        if (!Schema::hasTable('roles')) {
            return;
        }

        // First, ensure we have the basic roles in the roles table
        $roles = [
            ['name' => 'customer', 'display_name' => 'Customer', 'description' => 'Can post jobs and hire fundis'],
            ['name' => 'fundi', 'display_name' => 'Fundi', 'description' => 'Can apply for jobs and provide services'],
            ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Can manage the platform and users'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                array_merge($role, [
                    'is_system_role' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Get all users and migrate their JSON roles to relational structure
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            $rolesJson = $user->roles;
            $roles = [];
            
            if ($rolesJson) {
                // Handle different JSON formats
                if (is_string($rolesJson)) {
                    $decoded = json_decode($rolesJson, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $roles = $decoded;
                    } else {
                        // Try to handle escaped JSON
                        $cleaned = stripslashes($rolesJson);
                        $decoded = json_decode($cleaned, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $roles = $decoded;
                        }
                    }
                } elseif (is_array($rolesJson)) {
                    $roles = $rolesJson;
                }
            }
            
            // Ensure roles is an array
            if (!is_array($roles)) {
                $roles = [];
            }
            
            foreach ($roles as $roleName) {
                $role = DB::table('roles')->where('name', $roleName)->first();
                
                if ($role) {
                    // Insert user-role relationship
                    DB::table('user_roles')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => $role->id],
                        [
                            'assigned_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert relational roles back to JSON
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            $userRoles = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_roles.user_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
            
            // Update user with JSON roles
            DB::table('users')
                ->where('id', $user->id)
                ->update(['roles' => json_encode($userRoles)]);
        }
    }
};
