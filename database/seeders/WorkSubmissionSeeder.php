<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\WorkSubmission;
use Illuminate\Database\Seeder;

class WorkSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get fundi users and job postings (users who have 'fundi' in their roles array)
        $fundis = User::whereJsonContains('roles', 'fundi')->where('status', 'active')->get();
        $jobs = Job::where('status', 'open')->get();

        if ($fundis->isEmpty() || $jobs->isEmpty()) {
            return;
        }

        $submissionDescriptions = [
            'I have completed the electrical installation as requested. All outlets and switches are working properly.',
            'The plumbing work has been finished. All pipes are properly connected and tested.',
            'Kitchen renovation completed successfully. New cabinets and countertops installed.',
            'Bathroom remodeling finished. New tiles and fixtures are in place.',
            'Painting work completed. All rooms have been painted with high-quality paint.',
            'Roof repair work finished. All leaks have been fixed and gutters cleaned.',
            'Floor installation completed. Hardwood floors installed throughout the house.',
            'HVAC system installation finished. All ducts and vents are properly connected.',
            'Garden landscaping completed. Plants and irrigation system installed.',
            'Office cleaning service completed. All areas have been thoroughly cleaned.',
            'Moving service completed. All items safely transported and unpacked.',
            'General maintenance work finished. All repairs and improvements completed.',
            'Security system installation completed. Cameras and monitoring system active.',
            'Window installation finished. All windows properly installed and sealed.',
            'Door replacement completed. New doors and locks installed.',
            'Carpet installation finished. All carpets properly stretched and secured.',
            'Water heater installation completed. System tested and working properly.',
            'Cabinet refinishing finished. All cabinets restored and refinished.',
            'Shower installation completed. New shower and tiles properly installed.',
            'General repair work finished. All requested repairs completed successfully.'
        ];

        $mediaUrls = [
            ['https://example.com/work/electrical1.jpg', 'https://example.com/work/electrical2.jpg'],
            ['https://example.com/work/plumbing1.jpg', 'https://example.com/work/plumbing2.jpg'],
            ['https://example.com/work/kitchen1.jpg', 'https://example.com/work/kitchen2.jpg'],
            ['https://example.com/work/bathroom1.jpg', 'https://example.com/work/bathroom2.jpg'],
            ['https://example.com/work/painting1.jpg', 'https://example.com/work/painting2.jpg'],
            ['https://example.com/work/roofing1.jpg', 'https://example.com/work/roofing2.jpg'],
            ['https://example.com/work/flooring1.jpg', 'https://example.com/work/flooring2.jpg'],
            ['https://example.com/work/hvac1.jpg', 'https://example.com/work/hvac2.jpg'],
            ['https://example.com/work/landscaping1.jpg', 'https://example.com/work/landscaping2.jpg'],
            ['https://example.com/work/cleaning1.jpg', 'https://example.com/work/cleaning2.jpg'],
            ['https://example.com/work/moving1.jpg', 'https://example.com/work/moving2.jpg'],
            ['https://example.com/work/maintenance1.jpg', 'https://example.com/work/maintenance2.jpg'],
            ['https://example.com/work/security1.jpg', 'https://example.com/work/security2.jpg'],
            ['https://example.com/work/windows1.jpg', 'https://example.com/work/windows2.jpg'],
            ['https://example.com/work/doors1.jpg', 'https://example.com/work/doors2.jpg'],
            ['https://example.com/work/carpet1.jpg', 'https://example.com/work/carpet2.jpg'],
            ['https://example.com/work/waterheater1.jpg', 'https://example.com/work/waterheater2.jpg'],
            ['https://example.com/work/shower1.jpg', 'https://example.com/work/shower2.jpg'],
            ['https://example.com/work/repair1.jpg', 'https://example.com/work/repair2.jpg']
        ];

        $statuses = ['submitted', 'approved', 'rejected'];
        $rejectionReasons = [
            'Work quality does not meet standards',
            'Incomplete work submission',
            'Missing required documentation',
            'Work does not match job requirements',
            'Poor workmanship',
            'Incomplete project scope',
            'Missing safety requirements',
            'Work not completed on time'
        ];

        // Create work submissions for each job
        foreach ($jobs as $job) {
            // Each job gets 1-3 work submissions
            $numSubmissions = rand(1, 3);
            
            for ($i = 0; $i < $numSubmissions; $i++) {
                $fundi = $fundis->random();
                $descriptionIndex = array_rand($submissionDescriptions);
                $description = $submissionDescriptions[$descriptionIndex];
                $media = $mediaUrls[$descriptionIndex % count($mediaUrls)];
                $status = $statuses[array_rand($statuses)];
                
                $workSubmission = WorkSubmission::create([
                    'job_posting_id' => $job->id,
                    'fundi_id' => $fundi->id,
                    'title' => 'Work Submission for ' . $job->title,
                    'description' => $description,
                    'work_images' => json_encode($media),
                    'status' => $status,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 7))
                ]);

                // If approved or rejected, set approval details
                if ($status === 'approved') {
                    $workSubmission->update([
                        'reviewed_by' => $job->customer_id,
                        'reviewed_at' => now()->subDays(rand(0, 7))
                    ]);
                } elseif ($status === 'rejected') {
                    $workSubmission->update([
                        'rejection_reason' => $rejectionReasons[array_rand($rejectionReasons)],
                        'reviewed_by' => $job->customer_id,
                        'reviewed_at' => now()->subDays(rand(0, 7))
                    ]);
                }
            }
        }
    }
}