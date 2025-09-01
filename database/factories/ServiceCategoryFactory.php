<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Plumbing', 'Electrical', 'Carpentry', 'Painting', 'Cleaning', 'Landscaping',
            'Construction', 'IT Support', 'Consulting', 'Marketing', 'Security', 'Logistics'
        ]);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name . '-' . $this->faker->unique()->word()),
            'description' => $this->faker->sentence(12),
        ];
    }
}
