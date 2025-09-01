<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    public function definition(): array
    {
        $businessModel = $this->faker->randomElement(['c2c','b2c','c2b','b2b']);
        $jobTypeMap = [
            'c2c' => ['homeRepair','personalService','eventService','consultation'],
            'b2c' => ['homeRepair','personalService','commercialRepair','installation','cleaning'],
            'c2b' => ['consultation','training','consulting','digitalService','marketing'],
            'b2b' => ['construction','maintenance','installation','consulting','legal','accounting','hr','logistics','security','cleaning','catering','transportation','equipment','emergency'],
        ];
        $jobType = $this->faker->randomElement($jobTypeMap[$businessModel]);
        $paymentType = $this->faker->randomElement(['fixed','hourly','daily','milestone','negotiable']);

        return [
            'user_id' => User::factory()->client(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'detailed_description' => $this->faker->paragraphs(2, true),
            'location' => $this->faker->streetAddress(),
            'category_id' => ServiceCategory::inRandomOrder()->value('id') ?? ServiceCategory::factory(),
            'status' => 'open',
            'business_model' => $businessModel,
            'job_type' => $jobType,
            'requirements' => $this->faker->randomElements(['licensed','insured','experience','tools'], rand(1,3)),
            'skills_required' => $this->faker->randomElements(['plumbing','electrical','carpentry','painting','cleaning','it_support'], rand(1,3)),
            'experience_required' => $this->faker->numberBetween(0, 5),
            'insurance_required' => $this->faker->boolean(30),
            'license_required' => $this->faker->boolean(30),
            'start_date' => $this->faker->optional()->dateTimeBetween('+1 days', '+30 days'),
            'end_date' => $this->faker->optional()->dateTimeBetween('+31 days', '+90 days'),
            'onsite_required' => $this->faker->boolean(70),
            'payment_type' => $paymentType,
            'budget_min' => $this->faker->randomFloat(2, 50, 500),
            'budget_max' => $this->faker->randomFloat(2, 600, 5000),
            'fixed_amount' => $paymentType === 'fixed' ? $this->faker->randomFloat(2, 50, 5000) : null,
            'hourly_rate' => $paymentType === 'hourly' ? $this->faker->randomFloat(2, 5, 150) : null,
            'daily_rate' => $paymentType === 'daily' ? $this->faker->randomFloat(2, 50, 800) : null,
            'accepted_payment_methods' => $this->faker->randomElements(['cash','bank_transfer','credit_card','mobile_money','invoice'], rand(1,3)),
            'payment_schedule' => $this->faker->randomElement(['immediate','net7','net15','net30','net60','milestone','completion']),
            'requires_contract' => $this->faker->boolean(40),
            'requires_invoice' => $this->faker->boolean(40),
            'requires_insurance' => $this->faker->boolean(30),
            'requires_license' => $this->faker->boolean(30),
            'requires_background_check' => $this->faker->boolean(30),
            'tags' => $this->faker->randomElements(['urgent','featured','premium','remote'], rand(0,2)),
            'urgency' => $this->faker->randomElement(['low','medium','high','urgent']),
            'view_count' => $this->faker->numberBetween(0, 500),
            'proposal_count' => $this->faker->numberBetween(0, 50),
            'deadline' => $this->faker->optional()->dateTimeBetween('+7 days', '+60 days'),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
        ];
    }
}
