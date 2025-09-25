<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\User;
use App\Models\Category;

class JobsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and categories for relationships
        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->take(10)->get();
        $categories = Category::all();

        if ($customers->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('No customers or categories found. Please run UserSeeder and CategorySeeder first.');
            return;
        }

        $jobs = [
            [
                'title' => 'Kitchen Renovation',
                'description' => 'Complete kitchen renovation including cabinets, countertops, and flooring. Looking for experienced contractor with portfolio.',
                'budget' => 25000.00,
                'deadline' => now()->addDays(30),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Bathroom Remodeling',
                'description' => 'Modern bathroom renovation with new tiles, fixtures, and plumbing. Need professional plumber and tiler.',
                'budget' => 15000.00,
                'deadline' => now()->addDays(21),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Living Room Painting',
                'description' => 'Interior painting for living room and dining area. Need experienced painter with quality work.',
                'budget' => 5000.00,
                'deadline' => now()->addDays(14),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'in_progress',
            ],
            [
                'title' => 'Garden Landscaping',
                'description' => 'Design and implement garden landscaping with plants, pathways, and outdoor lighting.',
                'budget' => 12000.00,
                'deadline' => now()->addDays(45),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Roof Repair',
                'description' => 'Fix leaking roof and replace damaged tiles. Need experienced roofer with safety equipment.',
                'budget' => 8000.00,
                'deadline' => now()->addDays(7),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'urgent',
            ],
            [
                'title' => 'Electrical Installation',
                'description' => 'Install new electrical outlets and lighting fixtures in home office.',
                'budget' => 3500.00,
                'deadline' => now()->addDays(10),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Flooring Installation',
                'description' => 'Install hardwood flooring in 3 bedrooms. Need experienced flooring contractor.',
                'budget' => 18000.00,
                'deadline' => now()->addDays(25),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Fence Installation',
                'description' => 'Install wooden fence around property perimeter for security and privacy.',
                'budget' => 6000.00,
                'deadline' => now()->addDays(18),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'completed',
            ],
            [
                'title' => 'Plumbing Repair',
                'description' => 'Fix multiple plumbing issues including leaky faucets and blocked drains.',
                'budget' => 2500.00,
                'deadline' => now()->addDays(5),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
            [
                'title' => 'Window Installation',
                'description' => 'Replace old windows with energy-efficient double-glazed windows.',
                'budget' => 22000.00,
                'deadline' => now()->addDays(35),
                'location_lat' => -1.2921,
                'location_lng' => 36.8219,
                'status' => 'open',
            ],
        ];

        foreach ($jobs as $jobData) {
            Job::create([
                'customer_id' => $customers->random()->id,
                'category_id' => $categories->random()->id,
                ...$jobData,
            ]);
        }

        $this->command->info('Created ' . count($jobs) . ' jobs successfully.');
    }
}