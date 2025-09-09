<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Plumbing', 'description' => 'Plumbing services and repairs'],
            ['name' => 'Electrical', 'description' => 'Electrical installation and repairs'],
            ['name' => 'Carpentry', 'description' => 'Woodwork and furniture making'],
            ['name' => 'Masonry', 'description' => 'Brickwork and stone construction'],
            ['name' => 'Painting', 'description' => 'Interior and exterior painting'],
            ['name' => 'Roofing', 'description' => 'Roof installation and repairs'],
            ['name' => 'Flooring', 'description' => 'Floor installation and repairs'],
            ['name' => 'HVAC', 'description' => 'Heating, ventilation, and air conditioning'],
            ['name' => 'Landscaping', 'description' => 'Garden and outdoor space design'],
            ['name' => 'Cleaning', 'description' => 'House and office cleaning services'],
            ['name' => 'Moving', 'description' => 'Moving and relocation services'],
            ['name' => 'General Maintenance', 'description' => 'General repair and maintenance work'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
