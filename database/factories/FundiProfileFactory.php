<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FundiProfile>
 */
class FundiProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->fundi(),
            'category_id' => ServiceCategory::inRandomOrder()->value('id') ?? ServiceCategory::factory(),
            'skills' => implode(',', $this->faker->randomElements(['plumbing','electrical','carpentry','painting','cleaning','it_support'], rand(2,4))),
            'rating' => $this->faker->randomFloat(2, 3, 5),
            'location' => $this->faker->city(),
            'bio' => $this->faker->sentence(15),
            'availability' => [
                'mon_fri' => '9:00-17:00',
                'weekends' => $this->faker->boolean(40) ? '10:00-16:00' : 'off'
            ],
            'is_verified' => $this->faker->boolean(70),
            'is_available' => $this->faker->boolean(85),
        ];
    }
}
