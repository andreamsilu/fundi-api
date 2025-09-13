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
        User::updateOrCreate(
            ['phone' => '+255123456789'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['admin']),
                'status' => 'active',
                'nida_number' => '12345678901234567890'
            ]
        );

        // Customer Users
        User::updateOrCreate(
            ['phone' => '+255987654321'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['customer']),
                'status' => 'active',
                'nida_number' => '98765432109876543210'
            ]
        );

        User::updateOrCreate(
            ['phone' => '+255111111111'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['customer']),
                'status' => 'active',
                'nida_number' => '11111111111111111111'
            ]
        );

        // Fundi Users
        User::updateOrCreate(
            ['phone' => '+255555555555'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['fundi']),
                'status' => 'active',
                'nida_number' => '55555555555555555555'
            ]
        );

        User::updateOrCreate(
            ['phone' => '+255666666666'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['fundi']),
                'status' => 'active',
                'nida_number' => '66666666666666666666'
            ]
        );

        User::updateOrCreate(
            ['phone' => '+255777777777'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['fundi']),
                'status' => 'inactive',
                'nida_number' => '77777777777777777777'
            ]
        );

        // Additional test users for different scenarios
        User::updateOrCreate(
            ['phone' => '+255888888888'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['customer']),
                'status' => 'inactive',
                'nida_number' => '88888888888888888888'
            ]
        );

        User::updateOrCreate(
            ['phone' => '+255999999999'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['fundi']),
                'status' => 'banned',
                'nida_number' => '99999999999999999999'
            ]
        );

        // User with multiple roles (fundi who is also a customer)
        User::updateOrCreate(
            ['phone' => '+255000000000'],
            [
                'password' => Hash::make('password123'),
                'roles' => json_encode(['fundi', 'customer']),
                'status' => 'active',
                'nida_number' => '00000000000000000000'
            ]
        );
    }
}
