<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User Seeder
 * 
 * Creates comprehensive test users with complete profile information
 * Includes names, emails, locations, skills, languages, and other details
 * All data follows Tanzania market context
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // ADMIN USERS
        // ==========================================
        
        $adminUser = User::updateOrCreate(
            ['phone' => '0754289824'],
            [
                'full_name' => 'James Kikwete',
                'email' => 'admin@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19850615001234567890',
                'bio' => 'Platform administrator with full system access',
                'location' => 'Dar es Salaam, Kinondoni',
                'skills' => json_encode(['Platform Management', 'User Support', 'System Administration']),
                'languages' => json_encode(['English', 'Swahili']),
            ]
        );
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        // ==========================================
        // CUSTOMER USERS (Job Posters)
        // ==========================================
        
        $customer1 = User::updateOrCreate(
            ['phone' => '0654289825'],
            [
                'full_name' => 'Sarah Mwakasege',
                'email' => 'sarah.mwakasege@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19920822098765432101',
                'bio' => 'Homeowner looking for reliable fundis for home improvement projects',
                'location' => 'Dar es Salaam, Ilala',
                'languages' => json_encode(['Swahili', 'English']),
            ]
        );
        if (!$customer1->hasRole('customer')) {
            $customer1->assignRole('customer');
        }

        $customer2 = User::updateOrCreate(
            ['phone' => '0754289826'],
            [
                'full_name' => 'Michael Mtongwe',
                'email' => 'michael.mtongwe@outlook.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19880415011111111111',
                'bio' => 'Property manager seeking skilled craftsmen for multiple rental properties',
                'location' => 'Dar es Salaam, Temeke',
                'languages' => json_encode(['Swahili', 'English']),
            ]
        );
        if (!$customer2->hasRole('customer')) {
            $customer2->assignRole('customer');
        }

        // ==========================================
        // FUNDI USERS (Service Providers)
        // ==========================================
        
        $fundi1 = User::updateOrCreate(
            ['phone' => '0654289827'],
            [
                'full_name' => 'John Mwalimu',
                'email' => 'john.mwalimu@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19871204055555555555',
                'bio' => 'Experienced plumber with over 10 years in residential and commercial plumbing. VETA certified. Available for emergency repairs and installations.',
                'location' => 'Dar es Salaam, Kinondoni',
                'skills' => json_encode(['Plumbing', 'Pipe Installation', 'Water Systems', 'Drainage', 'Fixture Repair']),
                'languages' => json_encode(['Swahili', 'English']),
                'veta_certificate' => 'VETA/NVA/2015/12345',
            ]
        );
        if (!$fundi1->hasRole('fundi')) {
            $fundi1->assignRole('fundi');
        }

        $fundi2 = User::updateOrCreate(
            ['phone' => '0754289828'],
            [
                'full_name' => 'Grace Ndunguru',
                'email' => 'grace.ndunguru@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19901018066666666666',
                'bio' => 'Professional electrician specializing in residential wiring, solar installation, and electrical repairs. Licensed and insured.',
                'location' => 'Dar es Salaam, Ilala',
                'skills' => json_encode(['Electrical Work', 'Wiring', 'Solar Panel Installation', 'Circuit Design', 'Safety Compliance']),
                'languages' => json_encode(['Swahili', 'English', 'French']),
                'veta_certificate' => 'VETA/NVA/2017/23456',
            ]
        );
        if (!$fundi2->hasRole('fundi')) {
            $fundi2->assignRole('fundi');
        }

        $fundi3 = User::updateOrCreate(
            ['phone' => '0654289829'],
            [
                'full_name' => 'Peter Kipande',
                'email' => 'peter.kipande@yahoo.com',
                'password' => Hash::make('password123'),
                'status' => 'inactive',
                'nida_number' => '19930625077777777777',
                'bio' => 'Skilled carpenter with expertise in furniture making, cabinet installation, and wood finishing. Quality craftsmanship guaranteed.',
                'location' => 'Arusha, Arusha City',
                'skills' => json_encode(['Carpentry', 'Furniture Making', 'Cabinet Installation', 'Wood Finishing']),
                'languages' => json_encode(['Swahili']),
                'veta_certificate' => 'VETA/NVA/2018/34567',
            ]
        );
        if (!$fundi3->hasRole('fundi')) {
            $fundi3->assignRole('fundi');
        }

        // ==========================================
        // MORE FUNDI USERS
        // ==========================================
        
        $fundi4 = User::updateOrCreate(
            ['phone' => '0712345678'],
            [
                'full_name' => 'Emmanuel Kileo',
                'email' => 'emmanuel.kileo@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19950318012345678901',
                'bio' => 'Expert mason with 15 years experience in brickwork, stone construction, and foundation work. Specialized in modern and traditional designs.',
                'location' => 'Mwanza, Ilemela',
                'skills' => json_encode(['Masonry', 'Brickwork', 'Stone Construction', 'Foundation Work', 'Plastering']),
                'languages' => json_encode(['Swahili', 'English']),
                'veta_certificate' => 'VETA/NVA/2012/45678',
            ]
        );
        if (!$fundi4->hasRole('fundi')) {
            $fundi4->assignRole('fundi');
        }

        $fundi5 = User::updateOrCreate(
            ['phone' => '0765432109'],
            [
                'full_name' => 'Fatuma Hassan',
                'email' => 'fatuma.hassan@outlook.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19880925023456789012',
                'bio' => 'Professional painter with expertise in interior and exterior painting, wallpaper installation, and decorative finishes. High attention to detail.',
                'location' => 'Dodoma, Dodoma City',
                'skills' => json_encode(['Painting', 'Interior Design', 'Wallpaper Installation', 'Color Consultation', 'Surface Preparation']),
                'languages' => json_encode(['Swahili', 'English', 'Arabic']),
                'veta_certificate' => 'VETA/NVA/2016/56789',
            ]
        );
        if (!$fundi5->hasRole('fundi')) {
            $fundi5->assignRole('fundi');
        }

        $fundi6 = User::updateOrCreate(
            ['phone' => '0782345678'],
            [
                'full_name' => 'Daniel Mbunda',
                'email' => 'daniel.mbunda@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19860512034567890123',
                'bio' => 'Roofing specialist with extensive experience in tile roofing, metal roofing, and waterproofing. Safety certified.',
                'location' => 'Mbeya, Mbeya City',
                'skills' => json_encode(['Roofing', 'Tile Installation', 'Metal Roofing', 'Waterproofing', 'Gutter Systems']),
                'languages' => json_encode(['Swahili', 'English']),
                'veta_certificate' => 'VETA/NVA/2014/67890',
            ]
        );
        if (!$fundi6->hasRole('fundi')) {
            $fundi6->assignRole('fundi');
        }

        // ==========================================
        // INACTIVE/BANNED USERS (For Testing)
        // ==========================================
        
        $inactiveCustomer = User::updateOrCreate(
            ['phone' => '0754289830'],
            [
                'full_name' => 'Anna Msigwa',
                'email' => 'anna.msigwa@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'inactive',
                'nida_number' => '19940820088888888888',
                'bio' => 'Inactive customer account',
                'location' => 'Morogoro, Morogoro City',
                'languages' => json_encode(['Swahili']),
            ]
        );
        if (!$inactiveCustomer->hasRole('customer')) {
            $inactiveCustomer->assignRole('customer');
        }

        $bannedFundi = User::updateOrCreate(
            ['phone' => '0654289831'],
            [
                'full_name' => 'David Komba',
                'email' => 'david.komba@gmail.com',
                'password' => Hash::make('password123'),
                'status' => 'banned',
                'nida_number' => '19891115099999999999',
                'bio' => 'Account banned due to policy violations',
                'location' => 'Tanga, Tanga City',
                'skills' => json_encode(['General Maintenance']),
                'languages' => json_encode(['Swahili']),
                'veta_certificate' => null,
            ]
        );
        if (!$bannedFundi->hasRole('fundi')) {
            $bannedFundi->assignRole('fundi');
        }

        // ==========================================
        // MULTI-ROLE USERS (For Testing)
        // ==========================================
        
        // User with multiple roles (fundi who is also a customer)
        $multiRoleUser = User::updateOrCreate(
            ['phone' => '0754289832'],
            [
                'full_name' => 'Joseph Mwangi',
                'email' => 'joseph.mwangi@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19910707000000000000',
                'bio' => 'Skilled fundi and property owner. Provides flooring services while also posting jobs for other work.',
                'location' => 'Dar es Salaam, Temeke',
                'skills' => json_encode(['Flooring', 'Tile Installation', 'Hardwood Installation', 'Vinyl Flooring']),
                'languages' => json_encode(['Swahili', 'English']),
                'veta_certificate' => 'VETA/NVA/2019/78901',
            ]
        );
        if (!$multiRoleUser->hasRole('fundi')) {
            $multiRoleUser->assignRole('fundi');
        }
        if (!$multiRoleUser->hasRole('customer')) {
            $multiRoleUser->assignRole('customer');
        }

        // ==========================================
        // STAFF USERS (Moderators & Support)
        // ==========================================
        
        $moderatorUser = User::updateOrCreate(
            ['phone' => '0754289834'],
            [
                'full_name' => 'Mary Mkono',
                'email' => 'moderator@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19920310011111111112',
                'bio' => 'Platform moderator responsible for content review and user support',
                'location' => 'Dar es Salaam, Kinondoni',
                'languages' => json_encode(['Swahili', 'English']),
            ]
        );
        if (!$moderatorUser->hasRole('moderator')) {
            $moderatorUser->assignRole('moderator');
        }

        $supportUser = User::updateOrCreate(
            ['phone' => '0654289835'],
            [
                'full_name' => 'Amina Mchungaji',
                'email' => 'support@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19930525011111111113',
                'bio' => 'Customer support specialist providing assistance to platform users',
                'location' => 'Dar es Salaam, Ilala',
                'languages' => json_encode(['Swahili', 'English']),
            ]
        );
        if (!$supportUser->hasRole('support')) {
            $supportUser->assignRole('support');
        }

        // User with admin and fundi roles
        $adminFundi = User::updateOrCreate(
            ['phone' => '0654289836'],
            [
                'full_name' => 'Hassan Mwinyimkuu',
                'email' => 'hassan.admin@fundi.co.tz',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'nida_number' => '19840228011111111114',
                'bio' => 'Platform administrator and certified HVAC specialist. Manages platform while providing professional services.',
                'location' => 'Dar es Salaam, Kinondoni',
                'skills' => json_encode(['HVAC', 'Air Conditioning', 'Heating Systems', 'Ventilation', 'System Maintenance']),
                'languages' => json_encode(['Swahili', 'English']),
                'veta_certificate' => 'VETA/NVA/2013/89012',
            ]
        );
        if (!$adminFundi->hasRole('admin')) {
            $adminFundi->assignRole('admin');
        }
        if (!$adminFundi->hasRole('fundi')) {
            $adminFundi->assignRole('fundi');
        }

        $this->command->info('Created 12 users with complete profile information successfully.');
    }
}
