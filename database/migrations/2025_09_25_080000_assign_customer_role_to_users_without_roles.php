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
        // Get all users who don't have any roles assigned (using Laravel-permission tables)
        $usersWithoutRoles = DB::table('users')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->whereRaw('model_has_roles.model_id = users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User');
            })
            ->get();

        // Get the customer role ID from Laravel-permission
        $customerRole = DB::table('roles')->where('name', 'customer')->first();
        
        if ($customerRole) {
            // Assign customer role to all users without roles
            foreach ($usersWithoutRoles as $user) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $customerRole->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }

            echo "Assigned customer role to " . $usersWithoutRoles->count() . " users without roles.\n";
        } else {
            echo "Customer role not found. Skipping role assignment.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the customer role ID from Laravel-permission
        $customerRole = DB::table('roles')->where('name', 'customer')->first();
        
        if ($customerRole) {
            // Remove customer role from users who only have customer role
            $usersWithOnlyCustomerRole = DB::table('model_has_roles')
                ->where('role_id', $customerRole->id)
                ->where('model_type', 'App\\Models\\User')
                ->whereNotExists(function ($query) use ($customerRole) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles as mhr2')
                        ->whereRaw('mhr2.model_id = model_has_roles.model_id')
                        ->where('mhr2.model_type', 'App\\Models\\User')
                        ->where('mhr2.role_id', '!=', $customerRole->id);
                })
                ->get();

            foreach ($usersWithOnlyCustomerRole as $userRole) {
                DB::table('model_has_roles')
                    ->where('model_id', $userRole->model_id)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('role_id', $customerRole->id)
                    ->delete();
            }

            echo "Removed customer role from " . $usersWithOnlyCustomerRole->count() . " users.\n";
        } else {
            echo "Customer role not found. Skipping role removal.\n";
        }
    }
};
