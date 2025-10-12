<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PaymentPlan;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * User Subscription Seeder
 * Seeds the user_subscriptions table with active and expired subscriptions
 * Matches the create_user_subscriptions_table migration
 */
class UserSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $subscriptionPlans = PaymentPlan::where('type', 'subscription')
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty() || $subscriptionPlans->isEmpty()) {
            $this->command->warn('No users or subscription plans found. Please run UserSeeder and PaymentPlanSeeder first.');
            return;
        }

        $createdCount = 0;

        // 30% of users get subscriptions
        foreach ($users->random(min(ceil($users->count() * 0.3), $users->count())) as $user) {
            $plan = $subscriptionPlans->random();
            $isActive = rand(1, 100) <= 80; // 80% active, 20% expired
            
            $startDate = now()->subDays(rand(1, 90));
            $endDate = $startDate->copy()->addDays($plan->duration_days ?? 30);
            
            // If subscription should be expired, make end date in the past
            if (!$isActive) {
                $endDate = $startDate->copy()->subDays(rand(1, 30));
            }

            // Determine status
            $status = 'active';
            if ($endDate->isPast()) {
                $status = 'expired';
            } elseif (rand(1, 100) <= 5) { // 5% cancelled
                $status = 'cancelled';
            }

            UserSubscription::create([
                'user_id' => $user->id,
                'payment_plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'auto_renew' => $status === 'active' ? (rand(1, 100) <= 70) : false, // 70% auto-renew
                'payment_method' => $this->getPaymentMethod(),
                'amount_paid' => $plan->price,
                'transaction_id' => $this->generateTransactionId(),
                'created_at' => $startDate,
                'updated_at' => $status === 'cancelled' ? 
                    $endDate->copy()->subDays(rand(1, 7)) : now(),
            ]);

            $createdCount++;
        }

        $this->command->info("Created {$createdCount} user subscriptions successfully.");
    }

    private function getPaymentMethod(): string
    {
        $methods = [
            'mobile_money',
            'card',
            'bank_transfer',
            'mpesa',
            'tigo_pesa',
            'airtel_money',
        ];
        $weights = [30, 20, 10, 25, 10, 5]; // Weighted distribution
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($methods); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $methods[$i];
            }
        }
        
        return 'mobile_money';
    }

    private function generateTransactionId(): string
    {
        return 'SUB-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }
}

