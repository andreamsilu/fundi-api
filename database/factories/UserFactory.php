<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isBusiness = $this->faker->boolean(30);

        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->unique()->e164PhoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'current_role' => $this->faker->randomElement([
                $isBusiness ? 'businessCustomer' : 'customer',
                $isBusiness ? 'businessProvider' : 'fundi',
            ]),
            'user_type' => $isBusiness ? $this->faker->randomElement(['business','enterprise']) : 'individual',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_verified' => $this->faker->boolean(80),
            'is_available' => $this->faker->boolean(90),
        ] + ($isBusiness ? [
            'business_name' => $this->faker->company(),
            'business_type' => $this->faker->randomElement(['LLC','Ltd','PLC','Inc.']),
            'registration_number' => strtoupper(Str::random(10)),
            'tax_id' => strtoupper(Str::random(12)),
            'website' => $this->faker->url(),
            'business_description' => $this->faker->paragraph(),
            'services_offered' => $this->faker->randomElements(['plumbing','electrical','cleaning','construction','consulting','marketing'], rand(2,4)),
            'industries' => $this->faker->randomElements(['residential','commercial','industrial','it','legal','finance'], rand(1,3)),
            'employee_count' => $this->faker->numberBetween(1, 200),
            'year_established' => $this->faker->numberBetween(1980, (int)date('Y')),
            'license_number' => strtoupper(Str::random(8)),
            'certifications' => $this->faker->randomElements(['ISO9001','OSHA','CISA','CISSP','PMP'], rand(0,3)),
            'payment_methods' => $this->faker->randomElements(['cash','bank_transfer','mobile_money','invoice'], rand(2,4)),
            'average_project_value' => $this->faker->randomFloat(2, 1000, 50000),
            'completed_projects' => $this->faker->numberBetween(0, 500),
        ] : [
            'bio' => $this->faker->sentence(12),
            'skills' => $this->faker->randomElements(['plumbing','electrical','carpentry','painting','cleaning','it_support'], rand(2,4)),
            'specializations' => $this->faker->randomElements(['emergency','installation','maintenance','repair'], rand(1,3)),
            'hourly_rate' => $this->faker->randomFloat(2, 5, 150),
            'daily_rate' => $this->faker->randomFloat(2, 50, 800),
            'project_rate' => $this->faker->randomFloat(2, 100, 10000),
            'individual_certifications' => $this->faker->randomElements(['licensed','insured','certified'], rand(0,2)),
            'years_experience' => $this->faker->numberBetween(0, 30),
            'languages' => $this->faker->randomElements(['en','sw','fr','ar'], rand(1,3)),
            'availability' => [
                'mon_fri' => '9:00-17:00',
                'weekends' => $this->faker->boolean(40) ? '10:00-16:00' : 'off'
            ],
            'preferred_job_types' => $this->faker->randomElements(['homeRepair','personalService','eventService','consultation'], rand(1,3)),
            'portfolio' => [['title' => $this->faker->sentence(3), 'url' => $this->faker->url()]],
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Fundi state.
     */
    public function fundi(): static
    {
        return $this->state(fn () => [
            'current_role' => 'fundi',
            'user_type' => 'individual',
        ]);
    }

    /**
     * Customer state.
     */
    public function customer(): static
    {
        return $this->state(fn () => [
            'current_role' => 'customer',
            'user_type' => 'individual',
        ]);
    }

    /**
     * Business Provider state.
     */
    public function businessProvider(): static
    {
        return $this->state(fn () => [
            'current_role' => 'businessProvider',
            'user_type' => 'business',
        ]);
    }

    /**
     * Business Customer state.
     */
    public function businessCustomer(): static
    {
        return $this->state(fn () => [
            'current_role' => 'businessCustomer',
            'user_type' => 'business',
        ]);
    }
}
