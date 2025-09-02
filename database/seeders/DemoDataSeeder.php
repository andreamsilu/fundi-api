<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\FundiProfile;
use App\Models\ServiceCategory;
use App\Models\Job;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Payment;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Categories
            if (ServiceCategory::count() === 0) {
                $this->call(ServiceCategorySeeder::class);
            }

            // 2) Core roles & configs are already seeded from DatabaseSeeder

            // 3) Create users
            // Admin
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'phone' => '+10000000001',
                'email' => 'admin@example.com',
                'current_role' => 'admin',
                'user_type' => 'individual',
                'is_verified' => true,
            ]);
            $admin->assignRole('admin');

            // Customers & Business Customers
            $customers = User::factory(10)->customer()->create();
            $customers->each(function ($customer) {
                $customer->assignRole('customer');
            });

            $businessCustomers = User::factory(5)->businessCustomer()->create();
            $businessCustomers->each(function ($businessCustomer) {
                $businessCustomer->assignRole('businessCustomer');
            });

            // Fundis & Business Providers
            $fundis = User::factory(15)->fundi()->create();
            $fundis->each(function ($fundi) {
                $fundi->assignRole('fundi');
            });

            $businessProviders = User::factory(5)->businessProvider()->create();
            $businessProviders->each(function ($businessProvider) {
                $businessProvider->assignRole('businessProvider');
            });

            // Multi-role users (can be both customer and fundi)
            $multiRoleUsers = User::factory(8)->create();
            foreach ($multiRoleUsers as $user) {
                if ($user->id % 2 === 0) {
                    // Even IDs: customer + fundi
                    $user->assignRole(['customer', 'fundi']);
                    $user->update(['current_role' => 'customer']); // Set default active role
                } else {
                    // Odd IDs: businessCustomer + businessProvider
                    $user->assignRole(['businessCustomer', 'businessProvider']);
                    $user->update(['current_role' => 'businessCustomer']); // Set default active role
                }
            }

            // 4) Fundi profiles for individual fundis
            $fundis->each(function ($f) {
                FundiProfile::factory()->create([
                    'user_id' => $f->id,
                ]);
            });

            // 5) Jobs (mix of business models)
            $jobs = Job::factory(30)->create();

            // 6) Bookings (some accepted/completed)
            $bookings = collect();
            foreach ($jobs as $job) {
                // Pick a random fundi (individual) for booking
                $fundi = $fundis->random();
                $booking = Booking::factory()->create([
                    'job_id' => $job->id,
                    'fundi_id' => $fundi->id,
                ]);
                $bookings->push($booking);
            }

            // 7) Reviews for completed bookings
            $completed = $bookings->where('status', 'completed');
            foreach ($completed as $booking) {
                Review::factory()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->job->customer->id,
                    'fundi_id' => $booking->fundi_id,
                ]);
            }

            // 8) Payments demo
            foreach ($bookings->take(10) as $booking) {
                Payment::create([
                    'user_id' => $booking->job->customer->id,
                    'amount' => fake()->randomFloat(2, 50, 1000),
                    'currency' => 'USD',
                    'status' => 'completed',
                    'payment_method' => 'mobile_money',
                    'payment_provider' => 'mobile_money',
                    'payment_provider_id' => 'mm_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 16),
                    'payment_provider_status' => 'completed',
                    'payment_provider_response' => ['reference' => 'mm_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 16)],
                    'metadata' => ['booking_id' => $booking->id],
                    'payable_type' => Booking::class,
                    'payable_id' => $booking->id,
                ]);
            }

            // 9) Update last_role_switch for multi-role users
            $multiRoleUsers->each(function ($user) {
                $user->update(['last_role_switch' => now()]);
            });
        });
    }
}
