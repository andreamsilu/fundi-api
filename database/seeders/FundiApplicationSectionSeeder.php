<?php

namespace Database\Seeders;

use App\Models\FundiApplication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fundi Application Section Seeder
 * Seeds the fundi_application_sections table with multi-step application data
 * Matches the create_fundi_application_sections_table migration
 */
class FundiApplicationSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applications = FundiApplication::all();

        if ($applications->isEmpty()) {
            $this->command->warn('No fundi applications found. Please run FundiApplicationSeeder first.');
            return;
        }

        $sections = [
            'personal_information',
            'professional_details',
            'skills_experience',
            'documents',
            'portfolio',
        ];

        $createdCount = 0;

        foreach ($applications as $application) {
            foreach ($sections as $index => $sectionName) {
                $isComplete = $application->status !== 'pending' || $index < 3; // First 3 always complete
                
                DB::table('fundi_application_sections')->insert([
                    'user_id' => $application->user_id,  // Changed from fundi_application_id to user_id
                    'section_name' => $sectionName,
                    'section_data' => json_encode($this->getSectionData($sectionName, $application)),
                    'is_completed' => $isComplete,  // Changed from is_complete to is_completed
                    'submitted_at' => $isComplete ? $application->created_at->addMinutes(($index + 1) * 5) : null,  // Changed from completed_at to submitted_at
                    'created_at' => $application->created_at->addMinutes($index * 5),
                    'updated_at' => $isComplete ? $application->created_at->addMinutes(($index + 1) * 5) : $application->updated_at,
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} application sections successfully.");
    }

    private function getSectionData(string $sectionName, $application): array
    {
        switch ($sectionName) {
            case 'personal_information':
                return [
                    'full_name' => $application->full_name,
                    'phone_number' => $application->phone_number,
                    'email' => $application->email,
                    'nida_number' => $application->nida_number,
                    'date_of_birth' => '1990-05-15',
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'marital_status' => ['single', 'married', 'divorced'][rand(0, 2)],
                ];

            case 'professional_details':
                return [
                    'veta_certificate' => $application->veta_certificate,
                    'years_of_experience' => rand(2, 20),
                    'specialization' => json_decode($application->skills)[0] ?? 'General',
                    'previous_employers' => [
                        ['company' => 'ABC Construction Ltd', 'duration' => '2018-2021'],
                        ['company' => 'XYZ Services', 'duration' => '2015-2018'],
                    ],
                    'certifications' => [
                        'VETA Level 3 Certificate',
                        'Safety Training Certificate',
                        'First Aid Certificate',
                    ],
                ];

            case 'skills_experience':
                return [
                    'primary_skills' => json_decode($application->skills),
                    'languages' => json_decode($application->languages),
                    'bio' => $application->bio,
                    'location' => $application->location,
                    'willing_to_travel' => rand(0, 1) ? true : false,
                    'available_weekends' => rand(0, 1) ? true : false,
                    'hourly_rate' => rand(5000, 25000),
                    'tools_equipment' => [
                        'own_tools' => true,
                        'transport' => rand(0, 1) ? true : false,
                    ],
                ];

            case 'documents':
                return [
                    'nida_front' => "storage/documents/{$application->id}/nida_front.jpg",
                    'nida_back' => "storage/documents/{$application->id}/nida_back.jpg",
                    'veta_certificate' => "storage/documents/{$application->id}/veta_cert.pdf",
                    'additional_certificates' => [
                        "storage/documents/{$application->id}/cert1.pdf",
                        "storage/documents/{$application->id}/cert2.pdf",
                    ],
                    'profile_photo' => "storage/documents/{$application->id}/photo.jpg",
                ];

            case 'portfolio':
                $portfolioImages = $application->portfolio_images ? 
                    json_decode($application->portfolio_images, true) : [];
                return [
                    'portfolio_images' => $portfolioImages,
                    'project_descriptions' => [
                        'Completed kitchen renovation for residential client',
                        'Commercial office electrical installation',
                        'Bathroom remodeling with modern fixtures',
                    ],
                    'client_references' => [
                        ['name' => 'John Doe', 'phone' => '+255712345678', 'project' => 'Kitchen Renovation'],
                        ['name' => 'Jane Smith', 'phone' => '+255754123456', 'project' => 'Office Wiring'],
                    ],
                ];

            default:
                return [];
        }
    }
}

