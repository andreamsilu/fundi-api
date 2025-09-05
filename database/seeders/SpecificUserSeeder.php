<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SpecificUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if user already exists
        $existingUser = User::where('phone', '+255769289824')->first();
        
        if ($existingUser) {
            $this->command->warn('User with phone +255769289824 already exists.');
            return;
        }

        // Create the specific user
        $user = User::create([
            'name' => 'Specific User',
            'phone' => '+255769289824',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'current_role' => 'customer',
            'user_type' => 'individual',
            'is_verified' => true,
            'is_available' => true,
        ]);

        // Assign customer role
        $user->assignRole('customer');

        $this->command->info('Created user with phone +255769289824 and password: password');
    }
}