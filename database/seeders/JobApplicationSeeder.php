<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Database\Seeder;

class JobApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get fundi users and open jobs
        $fundis = User::where('role', 'fundi')->where('status', 'active')->get();
        $openJobs = Job::where('status', 'open')->get();

        if ($fundis->isEmpty() || $openJobs->isEmpty()) {
            return;
        }

        $applicationMessages = [
            'I have extensive experience in this type of work and would love to help you with this project.',
            'I am very interested in this job and believe I have the skills needed to complete it successfully.',
            'I have been working in this field for many years and can provide quality work at a competitive price.',
            'I am available to start immediately and can complete this project within your timeline.',
            'I have excellent references and a strong track record of customer satisfaction.',
            'I specialize in this type of work and can provide a detailed quote upon request.',
            'I am reliable, punctual, and committed to delivering high-quality results.',
            'I have all the necessary tools and equipment to complete this job efficiently.',
            'I am passionate about my work and always strive to exceed customer expectations.',
            'I can provide a free estimate and discuss the project details with you.',
            'I have insurance coverage and all required certifications for this type of work.',
            'I am flexible with scheduling and can work around your availability.',
            'I have completed similar projects recently and can show you examples of my work.',
            'I am detail-oriented and will ensure the job is done right the first time.',
            'I offer a satisfaction guarantee and will address any concerns promptly.'
        ];

        $budgetTypes = ['fixed', 'hourly', 'negotiable'];

        // Create applications for open jobs
        foreach ($openJobs as $job) {
            // Each job gets 1-4 applications
            $numApplications = min(rand(1, 4), $fundis->count());
            $selectedFundis = $fundis->random($numApplications);

            foreach ($selectedFundis as $fundi) {
                $budgetType = $budgetTypes[array_rand($budgetTypes)];
                
                JobApplication::updateOrCreate(
                    ['job_id' => $job->id, 'fundi_id' => $fundi->id],
                    [
                        'requirements' => $applicationMessages[array_rand($applicationMessages)],
                        'budget_breakdown' => json_encode([
                            'labor' => $this->getProposedBudget($job->budget, $budgetType) * 0.7,
                            'materials' => $this->getProposedBudget($job->budget, $budgetType) * 0.3,
                            'total' => $this->getProposedBudget($job->budget, $budgetType)
                        ]),
                        'total_budget' => $this->getProposedBudget($job->budget, $budgetType),
                        'estimated_time' => rand(1, 14),
                        'status' => $this->getApplicationStatus(),
                        'created_at' => now()->subDays(rand(0, 30)),
                        'updated_at' => now()->subDays(rand(0, 15))
                    ]
                );
            }
        }

        // Create some applications for in_progress jobs (accepted applications)
        $inProgressJobs = Job::where('status', 'in_progress')->get();
        foreach ($inProgressJobs as $job) {
            $fundi = $fundis->random();
            $budgetType = $budgetTypes[array_rand($budgetTypes)];
            
            JobApplication::updateOrCreate(
                ['job_id' => $job->id, 'fundi_id' => $fundi->id],
                [
                    'requirements' => $applicationMessages[array_rand($applicationMessages)],
                    'budget_breakdown' => json_encode([
                        'labor' => $this->getProposedBudget($job->budget, $budgetType) * 0.7,
                        'materials' => $this->getProposedBudget($job->budget, $budgetType) * 0.3,
                        'total' => $this->getProposedBudget($job->budget, $budgetType)
                    ]),
                    'total_budget' => $this->getProposedBudget($job->budget, $budgetType),
                    'estimated_time' => rand(1, 14),
                    'status' => 'accepted',
                    'created_at' => now()->subDays(rand(5, 20)),
                    'updated_at' => now()->subDays(rand(0, 10))
                ]
            );
        }
    }

    private function getProposedBudget($jobBudget, $budgetType): float
    {
        // Propose budget within 80-120% of job budget
        $variation = rand(80, 120) / 100;
        return $jobBudget * $variation;
    }

    private function getApplicationStatus(): string
    {
        $statuses = ['pending', 'accepted', 'rejected'];
        $weights = [60, 30, 10]; // 60% pending, 30% accepted, 10% rejected
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($statuses); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $statuses[$i];
            }
        }
        
        return 'pending';
    }
}
