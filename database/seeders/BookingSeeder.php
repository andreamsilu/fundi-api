<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Job;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users to work with
        $customers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['customer', 'businessClient']);
        })->take(10)->get();

        $fundis = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['fundi', 'businessProvider']);
        })->take(10)->get();

        // Get some service jobs
        $serviceJobs = Job::take(20)->get();

        if ($customers->isEmpty() || $fundis->isEmpty() || $serviceJobs->isEmpty()) {
            $this->command->warn('Not enough users or service jobs found. Please seed users and jobs first.');
            return;
        }

        $this->createSampleBookings($customers, $fundis, $serviceJobs);
    }

    private function createSampleBookings($customers, $fundis, $serviceJobs)
    {
        // Get unique job IDs to avoid constraint violations
        $jobIds = $serviceJobs->pluck('id')->shuffle()->take(5)->toArray();
        
        $bookingData = [
            [
                'job_id' => $jobIds[0],
                'customer_id' => $customers->random()->id,
                'fundi_id' => $fundis->random()->id,
                'service_job_id' => $jobIds[0],
                'description' => 'Need urgent plumbing repair for leaking kitchen faucet. Water is dripping continuously and needs immediate attention.',
                'scheduled_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'scheduled_time' => '09:00:00',
                'location' => 'Dar es Salaam, Kinondoni',
                'notes' => 'Please bring your own tools. Customer will provide materials.',
                'estimated_duration' => 120, // 2 hours
                'estimated_cost' => 25000,
                'payment_status' => 'pending',
                'payment_method' => 'cash',
                'status' => 'pending',
            ],
            [
                'job_id' => $jobIds[1],
                'customer_id' => $customers->random()->id,
                'fundi_id' => $fundis->random()->id,
                'service_job_id' => $jobIds[1],
                'description' => 'Install new electrical outlets in living room and bedroom. Need 4 outlets total.',
                'scheduled_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'scheduled_time' => '14:00:00',
                'location' => 'Arusha, Njiro',
                'notes' => 'Customer has already purchased the outlets. Just need installation.',
                'estimated_duration' => 180, // 3 hours
                'estimated_cost' => 35000,
                'payment_status' => 'pending',
                'payment_method' => 'mobile_money',
                'status' => 'confirmed',
            ],
            [
                'job_id' => $jobIds[2],
                'customer_id' => $customers->random()->id,
                'fundi_id' => $fundis->random()->id,
                'service_job_id' => $jobIds[2],
                'description' => 'Deep cleaning of 3-bedroom apartment. Include kitchen, bathrooms, and all rooms.',
                'scheduled_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'scheduled_time' => '08:00:00',
                'location' => 'Mwanza, Nyamagana',
                'notes' => 'Customer will provide cleaning supplies. Focus on deep cleaning.',
                'estimated_duration' => 240, // 4 hours
                'estimated_cost' => 45000,
                'payment_status' => 'paid',
                'payment_method' => 'bank_transfer',
                'status' => 'in_progress',
            ],
            [
                'job_id' => $jobIds[3],
                'customer_id' => $customers->random()->id,
                'fundi_id' => $fundis->random()->id,
                'service_job_id' => $jobIds[3],
                'description' => 'Build custom kitchen cabinets. Modern design with soft-close doors.',
                'scheduled_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'scheduled_time' => '10:00:00',
                'location' => 'Dodoma, City Center',
                'notes' => 'Customer will provide wood materials. Need experienced carpenter.',
                'estimated_duration' => 480, // 8 hours
                'estimated_cost' => 120000,
                'payment_status' => 'pending',
                'payment_method' => 'cash',
                'status' => 'pending',
            ],
            [
                'job_id' => $jobIds[4],
                'customer_id' => $customers->random()->id,
                'fundi_id' => $fundis->random()->id,
                'service_job_id' => $jobIds[4],
                'description' => 'Garden landscaping and maintenance. Trim hedges, plant flowers, and maintain lawn.',
                'scheduled_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'scheduled_time' => '07:00:00',
                'location' => 'Tanga, Mzizima',
                'notes' => 'Large garden area. Bring all necessary gardening tools.',
                'estimated_duration' => 300, // 5 hours
                'estimated_cost' => 55000,
                'payment_status' => 'pending',
                'payment_method' => 'mobile_money',
                'status' => 'accepted',
            ]
        ];

        // Create bookings
        foreach ($bookingData as $data) {
            Booking::create($data);
        }

        $this->command->info('Created ' . count($bookingData) . ' sample bookings.');
    }
}
