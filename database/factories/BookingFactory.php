<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending','accepted','declined','completed','cancelled']);
        return [
            'job_id' => Job::factory(),
            'fundi_id' => User::factory()->fundi(),
            'status' => $status,
            'proposed_price' => $this->faker->randomFloat(2, 50, 2000),
            'proposal' => $this->faker->optional()->paragraph(),
            'accepted_at' => $status === 'accepted' ? now() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ];
    }
}
