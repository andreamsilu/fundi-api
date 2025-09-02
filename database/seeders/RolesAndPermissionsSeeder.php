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
            
            // UAC permissions (enhanced)
            'manage roles',
            'manage permissions',
            'assign roles',
            'revoke roles',
            'grant permissions',
            'revoke permissions',
            
            // Additional permissions for business models
            'manage business profiles',
            'view business statistics',
            'manage enterprise accounts',
            'view enterprise reports',
            
            // Job management permissions
            'view all jobs',
            'manage job categories',
            'feature jobs',
            'moderate jobs',
            
            // Payment permissions
            'manage payments',
            'view payment history',
            'process refunds',
            'manage invoices',
            
            // Notification permissions
            'manage notifications',
            'send system notifications',
            'manage notification templates',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $fundiRole = Role::firstOrCreate(['name' => 'fundi']);
        $fundiRole->givePermissionTo([
            'view own profile',
            'edit own profile',
            'view bookings',
            'accept bookings',
            'reject bookings',
            'complete bookings',
            'cancel bookings',
        ]);

        // Customer role
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
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
        
        $businessCustomerRole = Role::firstOrCreate(['name' => 'businessCustomer']);
        $businessCustomerRole->givePermissionTo([
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'create reviews',
            'edit own reviews',
            'delete own reviews',
            'manage business profiles',
            'view business statistics',
        ]);
        
        $businessProviderRole = Role::firstOrCreate(['name' => 'businessProvider']);
        $businessProviderRole->givePermissionTo([
            'view own profile',
            'edit own profile',
            'view bookings',
            'accept bookings',
            'reject bookings',
            'complete bookings',
            'cancel bookings',
            'manage business profiles',
            'view business statistics',
        ]);
        
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'manage users',
            'manage fundis',
            'manage categories',
            'manage bookings',
            'manage reviews',
            'view bookings',
            'moderate jobs',
            'manage notifications',
        ]);
        
        $supportRole = Role::firstOrCreate(['name' => 'support']);
        $supportRole->givePermissionTo([
            'view bookings',
            'manage bookings',
            'manage reviews',
            'view payment history',
            'manage notifications',
        ]);

        // Admin role gets all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // Create enterprise and government roles for B2B models
        $enterpriseRole = Role::firstOrCreate(['name' => 'enterprise']);
        $enterpriseRole->givePermissionTo([
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'manage business profiles',
            'view business statistics',
            'view enterprise reports',
            'manage enterprise accounts',
        ]);
        
        $governmentRole = Role::firstOrCreate(['name' => 'government']);
        $governmentRole->givePermissionTo([
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'manage business profiles',
            'view business statistics',
            'view enterprise reports',
        ]);
        
        $nonprofitRole = Role::firstOrCreate(['name' => 'nonprofit']);
        $nonprofitRole->givePermissionTo([
            'create jobs',
            'edit own jobs',
            'delete own jobs',
            'view own jobs',
            'create bookings',
            'cancel own bookings',
            'manage business profiles',
            'view business statistics',
        ]);
    }
} 