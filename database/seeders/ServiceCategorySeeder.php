<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plumbing', 'description' => 'All plumbing related services'],
            ['name' => 'Electrical', 'description' => 'Electrical installation and repair'],
            ['name' => 'Carpentry', 'description' => 'Woodwork and carpentry services'],
            ['name' => 'Painting', 'description' => 'Interior and exterior painting'],
            ['name' => 'Cleaning', 'description' => 'Home and commercial cleaning services'],
            ['name' => 'Landscaping', 'description' => 'Garden and landscaping services'],
            ['name' => 'Construction', 'description' => 'Construction and renovation projects'],
            ['name' => 'IT Support', 'description' => 'Technical support and IT services'],
            ['name' => 'Consulting', 'description' => 'Business and professional consulting'],
            ['name' => 'Marketing', 'description' => 'Marketing and advertising services'],
            ['name' => 'Security', 'description' => 'Security and surveillance'],
            ['name' => 'Logistics', 'description' => 'Logistics and supply chain'],
        ];

        foreach ($categories as $data) {
            ServiceCategory::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['name'])],
                $data
            );
        }
    }
}
