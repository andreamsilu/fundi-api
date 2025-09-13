<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\RatingReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RatingReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::whereJsonContains('roles', 'customer')->get();
        $fundis = User::whereJsonContains('roles', 'fundi')->get();
        $completedJobs = Job::where('status', 'completed')->get();

        if ($customers->isEmpty() || $fundis->isEmpty() || $completedJobs->isEmpty()) {
            return;
        }

        $reviewTemplates = [
            'Excellent work! Very professional and completed on time. Highly recommended.',
            'Great service and quality work. Will definitely hire again for future projects.',
            'Good work overall. Some minor issues but they were resolved quickly.',
            'Satisfied with the work done. Professional and reliable service.',
            'Outstanding craftsmanship and attention to detail. Exceeded expectations.',
            'Very good work. Completed the project as promised and on schedule.',
            'Good quality work but took longer than expected. Still satisfied with the result.',
            'Professional service and good communication throughout the project.',
            'Excellent workmanship and very clean work area. Highly recommended.',
            'Good work overall. Some communication issues but the final result was good.',
            'Very satisfied with the work. Professional and trustworthy.',
            'Good service and fair pricing. Would consider hiring again.',
            'Excellent work! Very detailed and completed ahead of schedule.',
            'Good quality work and good value for money. Recommended.',
            'Satisfied with the work. Professional and completed on time.',
            'Very good work. Clean and professional. Will hire again.',
            'Good service overall. Some delays but the work quality was good.',
            'Excellent craftsmanship and very professional. Highly recommended.',
            'Good work and good communication. Satisfied with the result.',
            'Very professional and completed the work as promised.'
        ];

        $negativeReviewTemplates = [
            'Work was not completed as agreed. Some issues with quality.',
            'Took much longer than expected and communication was poor.',
            'Work quality was below expectations. Not satisfied with the result.',
            'Had some issues with the work done. Not as described.',
            'Work was completed but not to the standard expected.',
            'Some problems with the work. Not fully satisfied.',
            'Work took longer than promised and quality was average.',
            'Had some issues but they were partially resolved.',
            'Work was okay but not exceptional. Average quality.',
            'Some problems with communication and work quality.'
        ];

        foreach ($completedJobs as $job) {
            $customer = $job->customer;
            $fundi = $fundis->random();
            
            // 80% chance of getting a rating
            if (rand(1, 100) <= 80) {
                $rating = $this->getRating();
                $isPositive = $rating >= 4;
                
                $reviewText = $isPositive 
                    ? $reviewTemplates[array_rand($reviewTemplates)]
                    : $negativeReviewTemplates[array_rand($negativeReviewTemplates)];

                DB::table('ratings_reviews')->updateOrInsert(
                    ['customer_id' => $customer->id, 'fundi_id' => $fundi->id, 'job_id' => $job->id],
                    [
                        'rating' => $rating,
                        'review' => $reviewText,
                        'created_at' => $job->updated_at->addDays(rand(0, 7)), // Within a week of job completion
                        'updated_at' => $job->updated_at->addDays(rand(0, 7))
                    ]
                );
            }
        }

        // Create some additional ratings for fundis without specific jobs
        foreach ($fundis as $fundi) {
            if (rand(1, 100) <= 30) { // 30% chance of additional rating
                $customer = $customers->random();
                $rating = $this->getRating();
                $isPositive = $rating >= 4;
                
                $reviewText = $isPositive 
                    ? $reviewTemplates[array_rand($reviewTemplates)]
                    : $negativeReviewTemplates[array_rand($negativeReviewTemplates)];

                $randomJob = $completedJobs->random();
                DB::table('ratings_reviews')->updateOrInsert(
                    ['customer_id' => $customer->id, 'fundi_id' => $fundi->id, 'job_id' => $randomJob->id],
                    [
                        'rating' => $rating,
                        'review' => $reviewText,
                        'created_at' => now()->subDays(rand(0, 90)),
                        'updated_at' => now()->subDays(rand(0, 30))
                    ]
                );
            }
        }
    }

    private function getRating(): int
    {
        // Weighted distribution: 5 stars most common, 1 star least common
        $ratings = [1, 2, 3, 4, 5];
        $weights = [5, 10, 20, 35, 30]; // 30% 5-star, 35% 4-star, 20% 3-star, 10% 2-star, 5% 1-star
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($ratings); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $ratings[$i];
            }
        }
        
        return 5; // Default to 5 stars
    }
}
