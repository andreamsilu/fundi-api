<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            AdminSettingSeeder::class,
            FundiProfileSeeder::class,
            JobSeeder::class,
            JobApplicationSeeder::class,
            PortfolioSeeder::class,
            WorkSubmissionSeeder::class,
            NotificationSeeder::class,
            PaymentSeeder::class,
            RatingReviewSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
