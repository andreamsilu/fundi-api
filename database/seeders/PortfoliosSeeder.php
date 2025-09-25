<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Portfolio;
use App\Models\User;

class PortfoliosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get fundis for relationships
        $fundis = User::whereHas('roles', function($q) {
            $q->where('name', 'fundi');
        })->take(10)->get();

        if ($fundis->isEmpty()) {
            $this->command->warn('No fundis found. Please run UserSeeder first.');
            return;
        }

        $portfolios = [
            [
                'title' => 'Modern Kitchen Renovation',
                'description' => 'Complete kitchen transformation with custom cabinets, quartz countertops, and modern appliances. Project completed in 3 weeks.',
                'skills_used' => 'Carpentry, Plumbing, Electrical, Tile Work',
                'duration_hours' => 120,
                'budget' => 25000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Luxury Bathroom Remodel',
                'description' => 'High-end bathroom renovation featuring marble tiles, custom vanity, and premium fixtures. Client was extremely satisfied.',
                'skills_used' => 'Plumbing, Tiling, Electrical, Design',
                'duration_hours' => 80,
                'budget' => 18000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Commercial Office Painting',
                'description' => 'Large-scale office building painting project. Completed on time and within budget for corporate client.',
                'skills_used' => 'Painting, Surface Preparation, Color Matching',
                'duration_hours' => 200,
                'budget' => 15000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Garden Landscaping Design',
                'description' => 'Complete garden transformation with native plants, water features, and outdoor lighting. Eco-friendly design approach.',
                'skills_used' => 'Landscaping, Design, Plant Selection, Irrigation',
                'duration_hours' => 150,
                'budget' => 12000.00,
                'status' => 'pending',
                'is_visible' => false,
            ],
            [
                'title' => 'Roof Repair and Maintenance',
                'description' => 'Emergency roof repair after storm damage. Replaced damaged tiles and reinforced structure.',
                'skills_used' => 'Roofing, Structural Work, Weatherproofing',
                'duration_hours' => 40,
                'budget' => 8000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Electrical Panel Upgrade',
                'description' => 'Complete electrical panel upgrade for older home. Improved safety and capacity for modern appliances.',
                'skills_used' => 'Electrical Work, Safety Systems, Code Compliance',
                'duration_hours' => 24,
                'budget' => 5000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Hardwood Flooring Installation',
                'description' => 'Premium hardwood flooring installation in 4-bedroom home. Custom staining and finishing.',
                'skills_used' => 'Flooring, Woodworking, Finishing, Installation',
                'duration_hours' => 100,
                'budget' => 20000.00,
                'status' => 'rejected',
                'is_visible' => false,
                'rejection_reason' => 'Incomplete documentation of materials used',
            ],
            [
                'title' => 'Fence Installation Project',
                'description' => 'Privacy fence installation around residential property. Used high-quality materials and professional installation.',
                'skills_used' => 'Fencing, Measurement, Installation, Gate Work',
                'duration_hours' => 60,
                'budget' => 6000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
            [
                'title' => 'Plumbing System Overhaul',
                'description' => 'Complete plumbing system replacement in older home. Modern fixtures and improved water pressure.',
                'skills_used' => 'Plumbing, Pipe Work, Fixture Installation, Testing',
                'duration_hours' => 80,
                'budget' => 12000.00,
                'status' => 'pending',
                'is_visible' => false,
            ],
            [
                'title' => 'Window Replacement Project',
                'description' => 'Energy-efficient window replacement throughout home. Improved insulation and reduced energy costs.',
                'skills_used' => 'Window Installation, Sealing, Insulation, Finishing',
                'duration_hours' => 90,
                'budget' => 25000.00,
                'status' => 'approved',
                'is_visible' => true,
            ],
        ];

        $createdCount = 0;
        foreach ($fundis as $fundi) {
            // Create 2-4 portfolio items per fundi
            $numPortfolios = rand(2, 4);
            $selectedPortfolios = array_slice($portfolios, 0, $numPortfolios);
            
            foreach ($selectedPortfolios as $portfolioData) {
                Portfolio::create([
                    'fundi_id' => $fundi->id,
                    ...$portfolioData,
                ]);
                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} portfolio items successfully.");
    }
}