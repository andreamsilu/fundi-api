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
        // First, add the new roles column
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('phone');
        });

        // Migrate existing data from single role to roles array
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $roles = [$user->role]; // Convert single role to array
            DB::table('users')
                ->where('id', $user->id)
                ->update(['roles' => json_encode($roles)]);
        }

        // Drop the old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the single role column
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['customer', 'fundi', 'admin'])->default('customer')->after('phone');
        });

        // Migrate data back from roles array to single role
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $roles = json_decode($user->roles, true);
            $primaryRole = $roles[0] ?? 'customer'; // Use first role as primary
            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $primaryRole]);
        }

        // Drop the roles column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};