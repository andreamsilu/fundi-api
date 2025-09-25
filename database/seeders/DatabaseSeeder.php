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
            JobsSeeder::class,
            JobApplicationsSeeder::class,
            PortfoliosSeeder::class,
            PaymentSeeder::class,
            WorkSubmissionSeeder::class,
            NotificationSeeder::class,
            RatingReviewSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
