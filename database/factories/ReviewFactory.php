<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $booking = Booking::factory();
        return [
            'booking_id' => $booking,
            'user_id' => User::factory()->customer(),
            'fundi_id' => User::factory()->fundi(),
            'rating' => $this->faker->numberBetween(3, 5),
            'comment' => $this->faker->sentence(12),
            'images' => [],
            'is_verified' => $this->faker->boolean(80),
        ];
    }
}
