<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        $adminUser = User::updateOrCreate(
            ['phone' => '0754289824'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '12345678901234567890'
            ]
        );
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        // Customer Users
        $customer1 = User::updateOrCreate(
            ['phone' => '0654289825'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '98765432109876543210'
            ]
        );
        if (!$customer1->hasRole('customer')) {
            $customer1->assignRole('customer');
        }

        $customer2 = User::updateOrCreate(
            ['phone' => '0754289826'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '11111111111111111111'
            ]
        );
        if (!$customer2->hasRole('customer')) {
            $customer2->assignRole('customer');
        }

        // Fundi Users
        $fundi1 = User::updateOrCreate(
            ['phone' => '0654289827'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '55555555555555555555'
            ]
        );
        if (!$fundi1->hasRole('fundi')) {
            $fundi1->assignRole('fundi');
        }

        $fundi2 = User::updateOrCreate(
            ['phone' => '0754289828'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '66666666666666666666'
            ]
        );
        if (!$fundi2->hasRole('fundi')) {
            $fundi2->assignRole('fundi');
        }

        $fundi3 = User::updateOrCreate(
            ['phone' => '0654289829'],
            [
                'password' => Hash::make('password123'),
                'status' => 'inactive',
                'nida_number' => '77777777777777777777'
            ]
        );
        if (!$fundi3->hasRole('fundi')) {
            $fundi3->assignRole('fundi');
        }

        // Additional test users for different scenarios
        $inactiveCustomer = User::updateOrCreate(
            ['phone' => '0754289830'],
            [
                'password' => Hash::make('password123'),
                'status' => 'inactive',
                'nida_number' => '88888888888888888888'
            ]
        );
        if (!$inactiveCustomer->hasRole('customer')) {
            $inactiveCustomer->assignRole('customer');
        }

        $bannedFundi = User::updateOrCreate(
            ['phone' => '0654289831'],
            [
                'password' => Hash::make('password123'),
                'status' => 'banned',
                'nida_number' => '99999999999999999999'
            ]
        );
        if (!$bannedFundi->hasRole('fundi')) {
            $bannedFundi->assignRole('fundi');
        }

        // User with multiple roles (fundi who is also a customer)
        $multiRoleUser = User::updateOrCreate(
            ['phone' => '0754289832'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '00000000000000000000'
            ]
        );
        if (!$multiRoleUser->hasRole('fundi')) {
            $multiRoleUser->assignRole('fundi');
        }
        if (!$multiRoleUser->hasRole('customer')) {
            $multiRoleUser->assignRole('customer');
        }

        // Additional test users for different role combinations
        $moderatorUser = User::updateOrCreate(
            ['phone' => '0754289834'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '11111111111111111112'
            ]
        );
        if (!$moderatorUser->hasRole('moderator')) {
            $moderatorUser->assignRole('moderator');
        }

        $supportUser = User::updateOrCreate(
            ['phone' => '0654289835'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '11111111111111111113'
            ]
        );
        if (!$supportUser->hasRole('support')) {
            $supportUser->assignRole('support');
        }

        // User with admin and fundi roles
        $adminFundi = User::updateOrCreate(
            ['phone' => '0654289836'],
            [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '11111111111111111114'
            ]
        );
        if (!$adminFundi->hasRole('admin')) {
            $adminFundi->assignRole('admin');
        }
        if (!$adminFundi->hasRole('fundi')) {
            $adminFundi->assignRole('fundi');
        }
    }
}
