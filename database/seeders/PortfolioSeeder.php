<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Portfolio;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get fundi users
        $fundis = User::where('role', 'fundi')->where('status', 'active')->get();

        if ($fundis->isEmpty()) {
            return;
        }

        $portfolioTitles = [
            'Modern Kitchen Renovation',
            'Electrical Installation Project',
            'Custom Wooden Furniture',
            'Bathroom Remodeling',
            'Living Room Painting',
            'Roof Repair and Maintenance',
            'Floor Installation Project',
            'HVAC System Installation',
            'Garden Landscaping',
            'Office Cleaning Service',
            'Moving and Relocation',
            'General Home Maintenance',
            'Security System Installation',
            'Plumbing Repair Work',
            'Window Installation',
            'Door Replacement Project',
            'Carpet Installation',
            'Water Heater Installation',
            'Cabinet Refinishing',
            'Shower Installation'
        ];

        $portfolioDescriptions = [
            'Complete kitchen renovation including new cabinets, countertops, and appliances. Modern design with high-quality materials.',
            'Full electrical installation for new home including outlets, switches, and lighting fixtures. All work done to code.',
            'Custom-built wooden dining table and chairs. Handcrafted from solid oak with traditional joinery techniques.',
            'Complete bathroom remodeling including new tiles, fixtures, and vanity. Modern design with excellent craftsmanship.',
            'Professional interior painting service. High-quality paint and meticulous attention to detail for perfect finish.',
            'Comprehensive roof repair including shingle replacement and gutter cleaning. Weatherproof and long-lasting.',
            'Hardwood floor installation throughout the house. Professional installation with proper subfloor preparation.',
            'Complete HVAC system installation including ductwork and thermostat. Energy-efficient and reliable.',
            'Beautiful garden landscaping with native plants and irrigation system. Low maintenance and water-efficient.',
            'Thorough office cleaning service including deep cleaning and sanitization. Professional and reliable.',
            'Complete moving service including packing, loading, and unloading. Careful handling of all items.',
            'General home maintenance including repairs and improvements. Keeping your home in perfect condition.',
            'Security system installation with cameras and monitoring. Professional installation and setup.',
            'Plumbing repair work including pipe replacement and fixture installation. Quality workmanship guaranteed.',
            'Window installation and replacement service. Energy-efficient windows with professional installation.',
            'Door replacement project including new locks and hardware. Security and style combined.',
            'Carpet installation service with padding and professional stretching. Comfortable and durable.',
            'Water heater installation and maintenance. Reliable hot water for your home.',
            'Cabinet refinishing and restoration. Bringing old cabinets back to life with new finish.',
            'Shower installation and tiling work. Modern design with waterproof installation.'
        ];

        $skillsUsed = [
            ['Plumbing', 'Installation', 'Repair'],
            ['Electrical', 'Wiring', 'Installation'],
            ['Carpentry', 'Woodworking', 'Furniture'],
            ['Masonry', 'Tiling', 'Construction'],
            ['Painting', 'Interior', 'Exterior'],
            ['Roofing', 'Repair', 'Maintenance'],
            ['Flooring', 'Installation', 'Hardwood'],
            ['HVAC', 'Installation', 'Maintenance'],
            ['Landscaping', 'Garden', 'Design'],
            ['Cleaning', 'Sanitization', 'Maintenance'],
            ['Moving', 'Packing', 'Transportation'],
            ['Maintenance', 'Repair', 'General'],
            ['Security', 'Installation', 'Electronics'],
            ['Plumbing', 'Repair', 'Installation'],
            ['Installation', 'Windows', 'Construction'],
            ['Carpentry', 'Doors', 'Installation'],
            ['Flooring', 'Carpet', 'Installation'],
            ['Plumbing', 'Water Heater', 'Installation'],
            ['Carpentry', 'Refinishing', 'Restoration'],
            ['Plumbing', 'Tiling', 'Installation']
        ];

        $clientNames = [
            'John Mwalimu', 'Mary Mkono', 'Peter Kipande', 'Grace Mchungaji',
            'Michael Mwalimu', 'Sarah Mkono', 'David Kipande', 'Anna Mchungaji',
            'James Mwalimu', 'Elizabeth Mkono', 'Robert Kipande', 'Susan Mchungaji',
            'William Mwalimu', 'Jennifer Mkono', 'Charles Kipande', 'Linda Mchungaji',
            'Thomas Mwalimu', 'Patricia Mkono', 'Christopher Kipande', 'Barbara Mchungaji'
        ];

        foreach ($fundis as $fundi) {
            // Each fundi gets 3-8 portfolio items
            $numPortfolios = rand(3, 8);
            
            for ($i = 0; $i < $numPortfolios; $i++) {
                $titleIndex = array_rand($portfolioTitles);
                $title = $portfolioTitles[$titleIndex];
                $description = $portfolioDescriptions[$titleIndex];
                $skills = $skillsUsed[$titleIndex];
                
                Portfolio::create([
                    'fundi_id' => $fundi->id,
                    'title' => $title,
                    'description' => $description,
                    'category' => $this->getCategory($skills),
                    'skills_used' => $skills,
                    'images' => $this->getImages($titleIndex),
                    'duration_hours' => rand(8, 120), // 1-15 days of work
                    'budget' => rand(50000, 2000000), // 50,000 - 2,000,000 TZS
                    'client_name' => $clientNames[array_rand($clientNames)],
                    'location' => $this->getLocation(),
                    'created_at' => now()->subDays(rand(0, 180)), // Within last 6 months
                    'updated_at' => now()->subDays(rand(0, 30))
                ]);
            }
        }
    }

    private function getCategory($skills): string
    {
        $skillToCategory = [
            'Plumbing' => 'Plumbing',
            'Electrical' => 'Electrical',
            'Carpentry' => 'Carpentry',
            'Masonry' => 'Masonry',
            'Painting' => 'Painting',
            'Roofing' => 'Roofing',
            'Flooring' => 'Flooring',
            'HVAC' => 'HVAC',
            'Landscaping' => 'Landscaping',
            'Cleaning' => 'Cleaning',
            'Moving' => 'Moving',
            'Maintenance' => 'General Maintenance',
            'Security' => 'General Maintenance'
        ];

        foreach ($skills as $skill) {
            if (isset($skillToCategory[$skill])) {
                return $skillToCategory[$skill];
            }
        }

        return 'General Maintenance';
    }

    private function getImages($titleIndex): array
    {
        // Return placeholder image URLs (in real app, these would be actual image URLs)
        $imageSets = [
            ['https://example.com/images/kitchen1.jpg', 'https://example.com/images/kitchen2.jpg'],
            ['https://example.com/images/electrical1.jpg', 'https://example.com/images/electrical2.jpg'],
            ['https://example.com/images/furniture1.jpg', 'https://example.com/images/furniture2.jpg'],
            ['https://example.com/images/bathroom1.jpg', 'https://example.com/images/bathroom2.jpg'],
            ['https://example.com/images/painting1.jpg', 'https://example.com/images/painting2.jpg'],
            ['https://example.com/images/roofing1.jpg', 'https://example.com/images/roofing2.jpg'],
            ['https://example.com/images/flooring1.jpg', 'https://example.com/images/flooring2.jpg'],
            ['https://example.com/images/hvac1.jpg', 'https://example.com/images/hvac2.jpg'],
            ['https://example.com/images/landscaping1.jpg', 'https://example.com/images/landscaping2.jpg'],
            ['https://example.com/images/cleaning1.jpg', 'https://example.com/images/cleaning2.jpg'],
            ['https://example.com/images/moving1.jpg', 'https://example.com/images/moving2.jpg'],
            ['https://example.com/images/maintenance1.jpg', 'https://example.com/images/maintenance2.jpg'],
            ['https://example.com/images/security1.jpg', 'https://example.com/images/security2.jpg'],
            ['https://example.com/images/plumbing1.jpg', 'https://example.com/images/plumbing2.jpg'],
            ['https://example.com/images/windows1.jpg', 'https://example.com/images/windows2.jpg'],
            ['https://example.com/images/doors1.jpg', 'https://example.com/images/doors2.jpg'],
            ['https://example.com/images/carpet1.jpg', 'https://example.com/images/carpet2.jpg'],
            ['https://example.com/images/waterheater1.jpg', 'https://example.com/images/waterheater2.jpg'],
            ['https://example.com/images/shower1.jpg', 'https://example.com/images/shower2.jpg']
        ];

        return $imageSets[$titleIndex % count($imageSets)];
    }

    private function getLocation(): string
    {
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
        return $locations[array_rand($locations)];
    }
}
