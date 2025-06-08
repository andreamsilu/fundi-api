<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Fundi permissions
            'view own profile',
            'edit own profile',
            'manage service categories',
            'view bookings',
            'accept bookings',
            'reject bookings',
            'complete bookings',
            'cancel bookings',
            
            // Customer permissions
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'create reviews',
            'edit own reviews',
            'delete own reviews',
            
            // Admin permissions
            'manage users',
            'manage fundis',
            'manage categories',
            'manage bookings',
            'manage reviews',
            'view statistics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $fundiRole = Role::create(['name' => 'fundi']);
        $fundiRole->givePermissionTo([
            'view own profile',
            'edit own profile',
            'manage service categories',
            'view bookings',
            'accept bookings',
            'reject bookings',
            'complete bookings',
            'cancel bookings',
        ]);

        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'create reviews',
            'edit own reviews',
            'delete own reviews',
        ]);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
    }
} 