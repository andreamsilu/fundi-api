<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Job;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customer users
        $customers = User::where('role', 'customer')->get();
        $categories = Category::all();

        if ($customers->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $jobTitles = [
            'Fix leaking kitchen faucet',
            'Install new electrical outlets',
            'Build custom bookshelf',
            'Repair broken door handle',
            'Paint living room walls',
            'Install ceiling fan',
            'Fix running toilet',
            'Replace broken window',
            'Install new light fixtures',
            'Repair squeaky floorboards',
            'Install security system',
            'Fix garage door opener',
            'Replace bathroom tiles',
            'Install new door locks',
            'Fix air conditioning unit',
            'Repair broken fence',
            'Install new carpet',
            'Fix water heater',
            'Replace kitchen cabinet doors',
            'Install new shower head'
        ];

        $jobDescriptions = [
            'The kitchen faucet has been leaking for a few days. Need someone to diagnose and fix the issue.',
            'Need to install 3 new electrical outlets in the living room. Existing wiring is available.',
            'Looking for a skilled carpenter to build a custom bookshelf to fit specific dimensions.',
            'The door handle is loose and needs to be tightened or replaced.',
            'Want to repaint the living room with a new color. Walls are in good condition.',
            'Need to install a ceiling fan in the bedroom. Electrical box is already installed.',
            'Toilet keeps running after flushing. Need to fix the internal mechanism.',
            'Window glass is cracked and needs to be replaced with new glass.',
            'Want to upgrade the lighting in the dining room with new fixtures.',
            'Some floorboards are squeaky and need to be fixed.',
            'Looking to install a basic security system for the house.',
            'Garage door opener stopped working. Need diagnosis and repair.',
            'Bathroom tiles are coming loose and need to be replaced.',
            'Want to upgrade all door locks for better security.',
            'Air conditioning unit is not cooling properly. Need maintenance.',
            'Fence has some broken sections that need repair.',
            'Want to install new carpet in the bedroom.',
            'Water heater is not heating water properly. Need repair.',
            'Kitchen cabinet doors are damaged and need replacement.',
            'Want to install a new shower head with better water pressure.'
        ];

        $locations = [
            'Dar es Salaam, Kinondoni',
            'Arusha, Arusha City',
            'Mwanza, Ilemela',
            'Dodoma, Dodoma City',
            'Tanga, Tanga City',
            'Morogoro, Morogoro Urban',
            'Moshi, Moshi Urban',
            'Zanzibar, Stone Town'
        ];

        $budgetTypes = ['fixed', 'hourly', 'negotiable'];

        for ($i = 0; $i < 30; $i++) {
            $customer = $customers->random();
            $category = $categories->random();
            $title = $jobTitles[array_rand($jobTitles)];
            $description = $jobDescriptions[array_rand($jobDescriptions)];
            $location = $locations[array_rand($locations)];
            $budgetType = $budgetTypes[array_rand($budgetTypes)];

            Job::create([
                'customer_id' => $customer->id,
                'category_id' => $category->id,
                'title' => $title,
                'description' => $description,
                'budget' => $this->getBudget($budgetType),
                'budget_type' => $budgetType,
                'deadline' => now()->addDays(rand(1, 30)),
                'location_lat' => $this->getLatitude(),
                'location_lng' => $this->getLongitude(),
                'status' => $this->getJobStatus(),
                'created_at' => now()->subDays(rand(0, 60)),
                'updated_at' => now()->subDays(rand(0, 30))
            ]);
        }
    }

    private function getBudget($budgetType): float
    {
        switch ($budgetType) {
            case 'fixed':
                return rand(10000, 500000); // 10,000 - 500,000 TZS
            case 'hourly':
                return rand(2000, 15000); // 2,000 - 15,000 TZS per hour
            case 'negotiable':
                return rand(5000, 100000); // 5,000 - 100,000 TZS starting point
            default:
                return rand(10000, 100000);
        }
    }

    private function getJobStatus(): string
    {
        $statuses = ['open', 'in_progress', 'completed', 'cancelled'];
        $weights = [50, 20, 25, 5]; // 50% open, 20% in_progress, 25% completed, 5% cancelled
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($statuses); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $statuses[$i];
            }
        }
        
        return 'open';
    }

    private function getLatitude(): float
    {
        // Tanzania latitude range: -11.7 to -1.0
        return -6.0 + (rand(-50, 50) / 100);
    }

    private function getLongitude(): float
    {
        // Tanzania longitude range: 29.3 to 40.3
        return 35.0 + (rand(-50, 50) / 100);
    }
}
