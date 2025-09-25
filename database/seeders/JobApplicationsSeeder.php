<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobApplication;
use App\Models\Job;
use App\Models\User;

class JobApplicationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get jobs and fundis for relationships
        $jobs = Job::all();
        $fundis = User::whereHas('roles', function($q) {
            $q->where('name', 'fundi');
        })->take(15)->get();

        if ($jobs->isEmpty() || $fundis->isEmpty()) {
            $this->command->warn('No jobs or fundis found. Please run JobsSeeder and UserSeeder first.');
            return;
        }

        $applications = [
            [
                'requirements' => 'I have 5+ years experience in kitchen renovations. I can provide references and portfolio.',
                'budget_breakdown' => [
                    'materials' => 15000,
                    'labor' => 8000,
                    'permits' => 2000,
                ],
                'estimated_time' => 14, // days
                'status' => 'pending',
            ],
            [
                'requirements' => 'Licensed contractor with insurance. Specialized in modern bathroom designs.',
                'budget_breakdown' => [
                    'materials' => 8000,
                    'labor' => 5000,
                    'plumbing' => 2000,
                ],
                'estimated_time' => 10,
                'status' => 'accepted',
            ],
            [
                'requirements' => 'Professional painter with high-quality finishes. Can start immediately.',
                'budget_breakdown' => [
                    'materials' => 1500,
                    'labor' => 3000,
                    'preparation' => 500,
                ],
                'estimated_time' => 5,
                'status' => 'accepted',
            ],
            [
                'requirements' => 'Landscape architect with 10+ years experience. Specialized in sustainable designs.',
                'budget_breakdown' => [
                    'design' => 2000,
                    'plants' => 4000,
                    'labor' => 5000,
                    'materials' => 1000,
                ],
                'estimated_time' => 20,
                'status' => 'pending',
            ],
            [
                'requirements' => 'Certified roofer with safety training. Can provide warranty on work.',
                'budget_breakdown' => [
                    'materials' => 4000,
                    'labor' => 3000,
                    'safety_equipment' => 1000,
                ],
                'estimated_time' => 3,
                'status' => 'accepted',
            ],
            [
                'requirements' => 'Licensed electrician with commercial and residential experience.',
                'budget_breakdown' => [
                    'materials' => 1500,
                    'labor' => 1500,
                    'permits' => 500,
                ],
                'estimated_time' => 2,
                'status' => 'pending',
            ],
            [
                'requirements' => 'Flooring specialist with hardwood expertise. Can provide samples.',
                'budget_breakdown' => [
                    'materials' => 12000,
                    'labor' => 5000,
                    'finishing' => 1000,
                ],
                'estimated_time' => 12,
                'status' => 'rejected',
            ],
            [
                'requirements' => 'Fence contractor with quality materials. Can complete in 1 week.',
                'budget_breakdown' => [
                    'materials' => 3500,
                    'labor' => 2000,
                    'hardware' => 500,
                ],
                'estimated_time' => 7,
                'status' => 'accepted',
            ],
            [
                'requirements' => 'Master plumber with 24/7 availability. Licensed and insured.',
                'budget_breakdown' => [
                    'materials' => 800,
                    'labor' => 1200,
                    'emergency_fee' => 500,
                ],
                'estimated_time' => 1,
                'status' => 'accepted',
            ],
            [
                'requirements' => 'Window installation specialist with energy efficiency focus.',
                'budget_breakdown' => [
                    'materials' => 15000,
                    'labor' => 5000,
                    'installation' => 2000,
                ],
                'estimated_time' => 15,
                'status' => 'pending',
            ],
        ];

        $createdCount = 0;
        $usedCombinations = [];
        
        foreach ($jobs as $job) {
            // Create 1-3 applications per job
            $numApplications = rand(1, 3);
            $selectedFundis = $fundis->random($numApplications);
            
            foreach ($selectedFundis as $fundi) {
                $combination = $job->id . '-' . $fundi->id;
                
                // Skip if this combination already exists in our array or in database
                if (in_array($combination, $usedCombinations) || 
                    JobApplication::where('job_id', $job->id)->where('fundi_id', $fundi->id)->exists()) {
                    continue;
                }
                
                $usedCombinations[] = $combination;
                $applicationData = $applications[array_rand($applications)];
                
                JobApplication::create([
                    'job_id' => $job->id,
                    'fundi_id' => $fundi->id,
                    ...$applicationData,
                ]);
                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} job applications successfully.");
    }
}