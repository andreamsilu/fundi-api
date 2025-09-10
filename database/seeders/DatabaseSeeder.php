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
            UserSeeder::class,
            CategorySeeder::class,
            AdminSettingSeeder::class,
            FundiProfileSeeder::class,
            JobSeeder::class,
            JobApplicationSeeder::class,
            PortfolioSeeder::class,
            NotificationSeeder::class,
            PaymentSeeder::class,
            RatingReviewSeeder::class,
            UserSessionSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
