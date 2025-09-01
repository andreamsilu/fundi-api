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
            User::factory()->create([
                'name' => 'Admin User',
                'phone' => '+10000000001',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'user_type' => 'individual',
                'is_verified' => true,
            ])->assignRole('admin');

            // Clients & Business Clients
            $clients = User::factory(10)->client()->create();
            $businessClients = User::factory(5)->businessClient()->create();

            // Fundis & Business Providers
            $fundis = User::factory(15)->fundi()->create();
            $businessProviders = User::factory(5)->businessProvider()->create();

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
                    'payment_method' => 'credit_card',
                    'payment_provider' => 'stripe',
                    'payment_provider_id' => 'pi_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 16),
                    'payment_provider_status' => 'succeeded',
                    'payment_provider_response' => ['id' => 'ch_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 16)],
                    'metadata' => ['booking_id' => $booking->id],
                    'payable_type' => Booking::class,
                    'payable_id' => $booking->id,
                ]);
            }
        });
    }
}
