<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FundiApplication;
use Illuminate\Database\Seeder;

/**
 * Fundi Application Seeder
 * Seeds the fundi_applications table with realistic application data
 * Matches the create_fundi_applications_table migration
 */
class FundiApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customers who want to become fundis
        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->take(5)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please run UserSeeder first.');
            return;
        }

        $applicationStatuses = ['pending', 'approved', 'rejected'];
        
        $skillSets = [
            ['Plumbing', 'Pipe Installation', 'Water Systems', 'Drainage'],
            ['Electrical', 'Wiring', 'Circuit Design', 'Solar Installation'],
            ['Carpentry', 'Furniture Making', 'Cabinet Installation', 'Wood Finishing'],
            ['Masonry', 'Brickwork', 'Stone Work', 'Foundation Work'],
            ['Painting', 'Interior Painting', 'Exterior Painting', 'Wall Finishing'],
        ];

        $languages = [
            ['Swahili', 'English'],
            ['Swahili', 'English', 'French'],
            ['Swahili'],
            ['Swahili', 'English', 'Arabic'],
            ['Swahili', 'English', 'Italian'],
        ];

        $locations = [
            'Dar es Salaam, Kinondoni',
            'Dar es Salaam, Ilala',
            'Dar es Salaam, Temeke',
            'Arusha, Arusha City',
            'Mwanza, Ilemela',
            'Dodoma, Dodoma City',
            'Mbeya, Mbeya City',
            'Morogoro, Morogoro City',
        ];

        $bios = [
            'Experienced professional with over 10 years in the field. Committed to quality workmanship and customer satisfaction. Licensed and insured. Available for both residential and commercial projects.',
            'Skilled craftsman specializing in modern techniques and traditional methods. VETA certified with excellent track record. Known for attention to detail and timely project completion.',
            'Dedicated professional offering comprehensive services. Focused on delivering exceptional results. Strong communication skills and commitment to safety standards.',
            'Expert in my field with extensive training and hands-on experience. Passionate about creating quality work that lasts. Affordable rates and flexible scheduling available.',
            'Certified professional with proven expertise. Reliable, honest, and hardworking. References available upon request. Free estimates for all projects.',
        ];

        $portfolioImages = [
            [
                'https://example.com/portfolio/work1_1.jpg',
                'https://example.com/portfolio/work1_2.jpg',
                'https://example.com/portfolio/work1_3.jpg',
            ],
            [
                'https://example.com/portfolio/work2_1.jpg',
                'https://example.com/portfolio/work2_2.jpg',
            ],
            [
                'https://example.com/portfolio/work3_1.jpg',
                'https://example.com/portfolio/work3_2.jpg',
                'https://example.com/portfolio/work3_3.jpg',
                'https://example.com/portfolio/work3_4.jpg',
            ],
            null, // Some applications don't have portfolio images yet
            [
                'https://example.com/portfolio/work5_1.jpg',
            ],
        ];

        $rejectionReasons = [
            'Insufficient experience documentation provided',
            'VETA certificate needs verification',
            'Portfolio images do not meet quality standards',
            'Missing required documentation',
            'Application incomplete - please resubmit with all required information',
        ];

        foreach ($customers as $index => $customer) {
            $status = $applicationStatuses[array_rand($applicationStatuses)];
            
            FundiApplication::create([
                'user_id' => $customer->id,
                'full_name' => $this->getFullName($index),
                'phone_number' => $this->getPhoneNumber($index),
                'email' => $this->getEmail($index),
                'nida_number' => $this->getNidaNumber($index),
                'veta_certificate' => $this->getVetaCertificate($index),
                'location' => $locations[$index % count($locations)],
                'bio' => $bios[$index % count($bios)],
                'skills' => json_encode($skillSets[$index % count($skillSets)]),
                'languages' => json_encode($languages[$index % count($languages)]),
                'portfolio_images' => $portfolioImages[$index % count($portfolioImages)] ? 
                    json_encode($portfolioImages[$index % count($portfolioImages)]) : null,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 
                    $rejectionReasons[array_rand($rejectionReasons)] : null,
                'created_at' => now()->subDays(rand(1, 60)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        $this->command->info('Created ' . count($customers) . ' fundi applications successfully.');
    }

    private function getFullName($index): string
    {
        $names = [
            'Joseph Mwangi',
            'Grace Ndunguru',
            'Emmanuel Kileo',
            'Fatuma Hassan',
            'Daniel Mbunda',
        ];
        return $names[$index % count($names)];
    }

    private function getPhoneNumber($index): string
    {
        $phones = [
            '+255712345678',
            '+255754123456',
            '+255765432109',
            '+255782345678',
            '+255734567890',
        ];
        return $phones[$index % count($phones)];
    }

    private function getEmail($index): string
    {
        $emails = [
            'joseph.mwangi@example.com',
            'grace.ndunguru@example.com',
            'emmanuel.kileo@example.com',
            'fatuma.hassan@example.com',
            'daniel.mbunda@example.com',
        ];
        return $emails[$index % count($emails)];
    }

    private function getNidaNumber($index): string
    {
        // Generate realistic 20-digit NIDA numbers
        $baseNumbers = [
            '19850615123456789012',
            '19920822234567890123',
            '19881204345678901234',
            '19950318456789012345',
            '19870925567890123456',
        ];
        return $baseNumbers[$index % count($baseNumbers)];
    }

    private function getVetaCertificate($index): string
    {
        $certificates = [
            'VETA/NVA/2020/12345',
            'VETA/NVA/2019/23456',
            'VETA/NVA/2021/34567',
            'VETA/NVA/2018/45678',
            'VETA/NVA/2022/56789',
        ];
        return $certificates[$index % count($certificates)];
    }
}

