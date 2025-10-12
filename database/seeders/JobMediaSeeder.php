<?php

namespace Database\Seeders;

use App\Models\Job;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Job Media Seeder
 * Seeds the job_media table with images and documents for job postings
 * Matches the create_job_media_table migration
 */
class JobMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = Job::all();

        if ($jobs->isEmpty()) {
            $this->command->warn('No jobs found. Please run JobsSeeder first.');
            return;
        }

        $mediaTypes = ['image', 'document', 'video'];
        $imageCategories = [
            'site_photo',
            'reference_image',
            'floor_plan',
            'design_sketch',
            'current_state',
        ];

        $createdCount = 0;

        foreach ($jobs as $job) {
            // Each job gets 2-5 media items
            $numMedia = rand(2, 5);

            for ($i = 0; $i < $numMedia; $i++) {
                $mediaType = $i === 0 ? 'image' : $mediaTypes[array_rand($mediaTypes)];
                $fileSize = $this->getFileSize($mediaType);

                DB::table('job_media')->insert([
                    'job_posting_id' => $job->id,
                    'media_type' => $mediaType,
                    'file_path' => $this->getFilePath($job->id, $i, $mediaType),
                    'file_name' => $this->getFileName($job->id, $i, $mediaType),
                    'file_size' => $fileSize,
                    'mime_type' => $this->getMimeType($mediaType),
                    'description' => $this->getDescription($job->title, $i, $mediaType),
                    'order' => $i,
                    'is_primary' => $i === 0,
                    'created_at' => $job->created_at,
                    'updated_at' => $job->updated_at,
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} job media items successfully.");
    }

    private function getFilePath(int $jobId, int $index, string $type): string
    {
        $basePath = "storage/jobs/{$jobId}";
        $extension = $this->getExtension($type);
        return "{$basePath}/media_{$index}.{$extension}";
    }

    private function getFileName(int $jobId, int $index, string $type): string
    {
        $names = [
            'image' => [
                'site_overview.jpg',
                'detail_view.jpg',
                'reference_design.jpg',
                'current_condition.jpg',
                'requirement_photo.jpg',
            ],
            'document' => [
                'requirements.pdf',
                'specifications.pdf',
                'contract_draft.pdf',
                'terms_conditions.pdf',
            ],
            'video' => [
                'site_walkthrough.mp4',
                'requirements_video.mp4',
            ],
        ];

        $typeNames = $names[$type] ?? ['file.dat'];
        return $typeNames[$index % count($typeNames)];
    }

    private function getFileSize(string $type): int
    {
        // File sizes in bytes
        $sizes = [
            'image' => rand(500000, 5000000),      // 500KB - 5MB
            'document' => rand(100000, 2000000),   // 100KB - 2MB
            'video' => rand(5000000, 50000000),    // 5MB - 50MB
        ];

        return $sizes[$type] ?? 1000000;
    }

    private function getExtension(string $type): string
    {
        $extensions = [
            'image' => 'jpg',
            'document' => 'pdf',
            'video' => 'mp4',
        ];

        return $extensions[$type] ?? 'dat';
    }

    private function getMimeType(string $type): string
    {
        $mimeTypes = [
            'image' => 'image/jpeg',
            'document' => 'application/pdf',
            'video' => 'video/mp4',
        ];

        return $mimeTypes[$type] ?? 'application/octet-stream';
    }

    private function getDescription(string $jobTitle, int $index, string $type): ?string
    {
        $descriptions = [
            'image' => [
                'Current site overview photo',
                'Detailed view of work area',
                'Reference design or inspiration',
                'Before photo showing current condition',
                'Additional requirement photo',
            ],
            'document' => [
                'Detailed project requirements',
                'Technical specifications',
                'Contract draft for review',
                'Terms and conditions',
            ],
            'video' => [
                'Site walkthrough video',
                'Video explaining requirements',
            ],
        ];

        $typeDescriptions = $descriptions[$type] ?? ['Attachment'];
        return $typeDescriptions[$index % count($typeDescriptions)];
    }
}

