<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Portfolio Media Seeder
 * Seeds the portfolio_media table with images and videos for portfolio items
 * Matches the create_portfolio_media_table migration
 */
class PortfolioMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $portfolios = Portfolio::all();

        if ($portfolios->isEmpty()) {
            $this->command->warn('No portfolios found. Please run PortfoliosSeeder first.');
            return;
        }

        $createdCount = 0;

        foreach ($portfolios as $portfolio) {
            // Each portfolio gets 3-8 media items
            $numMedia = rand(3, 8);

            for ($i = 0; $i < $numMedia; $i++) {
                // Mostly images, occasional video
                $mediaType = ($i === 0 || rand(1, 100) <= 90) ? 'image' : 'video';
                $fileSize = $this->getFileSize($mediaType);

                DB::table('portfolio_media')->insert([
                    'portfolio_id' => $portfolio->id,
                    'file_path' => $this->getFilePath($portfolio->id, $i, $mediaType),
                    'file_type' => $mediaType,
                    'file_size' => $fileSize,
                    'caption' => $this->getCaption($portfolio->title, $i, $mediaType),
                    'order' => $i,
                    'is_featured' => $i === 0, // First image is featured
                    'created_at' => $portfolio->created_at,
                    'updated_at' => $portfolio->updated_at,
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} portfolio media items successfully.");
    }

    private function getFilePath(int $portfolioId, int $index, string $type): string
    {
        $basePath = "storage/portfolio/{$portfolioId}";
        $extension = $type === 'image' ? 'jpg' : 'mp4';
        $timestamp = time() + $index;
        return "{$basePath}/{$type}_{$timestamp}.{$extension}";
    }

    private function getFileSize(string $type): int
    {
        // File sizes in bytes
        if ($type === 'image') {
            return rand(800000, 6000000); // 800KB - 6MB (high quality photos)
        } else {
            return rand(10000000, 100000000); // 10MB - 100MB (videos)
        }
    }

    private function getCaption(string $portfolioTitle, int $index, string $type): string
    {
        if ($type === 'video') {
            return 'Video walkthrough of completed project';
        }

        $captionTemplates = [
            'Before starting - Initial state',
            'Work in progress - Foundation stage',
            'Mid-project - Primary work completed',
            'Near completion - Finishing touches',
            'Final result - Completed project',
            'Detail shot - Quality craftsmanship',
            'Client satisfaction - Final inspection',
            'Another angle - Overall view',
        ];

        return $captionTemplates[$index % count($captionTemplates)];
    }
}

