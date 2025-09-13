<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FundiProfile;
use Illuminate\Database\Seeder;

class FundiProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get fundi users (users who have 'fundi' in their roles array)
        $fundiUsers = User::whereJsonContains('roles', 'fundi')->get();

        foreach ($fundiUsers as $index => $user) {
            FundiProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->getFirstName($index) . ' ' . $this->getLastName($index),
                    'bio' => $this->getBio($index),
                    'skills' => json_encode($this->getSkills($index)),
                    'experience_years' => rand(1, 15),
                    'location_lat' => $this->getLatitude($index),
                    'location_lng' => $this->getLongitude($index),
                    'verification_status' => $user->status === 'active' ? 'approved' : 'pending',
                    'veta_certificate' => $this->getVetaCertificate($index),
                ]
            );
        }
    }

    private function getFirstName($index): string
    {
        $names = ['John', 'Mary', 'Peter', 'Grace', 'Michael', 'Sarah', 'David', 'Anna'];
        return $names[$index % count($names)];
    }

    private function getLastName($index): string
    {
        $names = ['Mwalimu', 'Mkono', 'Kipande', 'Mchungaji', 'Mwalimu', 'Mkono', 'Kipande', 'Mchungaji'];
        return $names[$index % count($names)];
    }

    private function getBio($index): string
    {
        $bios = [
            'Experienced craftsman with over 10 years in the field. Specializing in quality work and customer satisfaction.',
            'Professional service provider with excellent attention to detail. Committed to delivering outstanding results.',
            'Skilled artisan with a passion for creating beautiful and functional spaces. Always ready to help.',
            'Reliable and experienced professional. Known for timely completion and quality craftsmanship.',
            'Expert in my field with years of experience. Dedicated to providing the best service possible.',
            'Professional and trustworthy. Committed to exceeding customer expectations with every project.',
            'Experienced craftsman who takes pride in quality work. Always available for new challenges.',
            'Skilled professional with a reputation for excellence. Ready to tackle any project with confidence.'
        ];
        return $bios[$index % count($bios)];
    }

    private function getSkills($index): array
    {
        $skillSets = [
            ['Plumbing', 'Pipe Repair', 'Installation'],
            ['Electrical', 'Wiring', 'Maintenance'],
            ['Carpentry', 'Furniture', 'Repair'],
            ['Masonry', 'Brickwork', 'Construction'],
            ['Painting', 'Interior', 'Exterior'],
            ['Roofing', 'Installation', 'Repair'],
            ['Flooring', 'Installation', 'Maintenance'],
            ['General', 'Maintenance', 'Repair']
        ];
        return $skillSets[$index % count($skillSets)];
    }

    private function getLatitude($index): float
    {
        // Tanzania latitude range: -11.7 to -1.0
        return -6.0 + (rand(-50, 50) / 100);
    }

    private function getLongitude($index): float
    {
        // Tanzania longitude range: 29.3 to 40.3
        return 35.0 + (rand(-50, 50) / 100);
    }

    private function getVetaCertificate($index): ?string
    {
        $certificates = [
            'VETA-CERT-001',
            'VETA-CERT-002',
            'VETA-CERT-003',
            'VETA-CERT-004',
            'VETA-CERT-005',
            null, // Some fundis don't have certificates
            null,
            null
        ];
        return $certificates[$index % count($certificates)];
    }
}
