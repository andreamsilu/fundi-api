<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PaymentPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Payment Transaction Seeder
 * Seeds the payment_transactions table with subscription and plan payment records
 * Matches the create_payment_transactions_table migration
 */
class PaymentTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $paymentPlans = PaymentPlan::all();

        if ($users->isEmpty() || $paymentPlans->isEmpty()) {
            $this->command->warn('No users or payment plans found.');
            return;
        }

        $createdCount = 0;
        $transactionTypes = ['subscription', 'pay_per_use', 'job_posting', 'fundi_application'];
        $paymentMethods = ['mpesa', 'tigo_pesa', 'airtel_money', 'card', 'bank_transfer'];
        $statuses = ['completed', 'pending', 'failed', 'refunded'];
        $statusWeights = [75, 15, 8, 2]; // 75% completed

        // Create 2-3 transactions per user
        foreach ($users as $user) {
            $numTransactions = rand(2, 3);
            
            for ($i = 0; $i < $numTransactions; $i++) {
                $plan = $paymentPlans->random();
                $status = $this->getWeightedStatus($statuses, $statusWeights);
                
                DB::table('payment_transactions')->insert([
                    'user_id' => $user->id,
                    'payment_plan_id' => $plan->id,
                    'transaction_type' => $transactionTypes[array_rand($transactionTypes)],
                    'reference_id' => rand(1, 10), // Job or Application ID
                    'amount' => $plan->price,
                    'currency' => 'TZS',
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'payment_reference' => $this->generatePaymentReference(),
                    'status' => $status,
                    'description' => $this->getTransactionDescription($plan->name),
                    'metadata' => json_encode([
                        'plan_type' => $plan->type,
                        'billing_cycle' => $plan->billing_cycle ?? 'one_time',
                        'gateway_fee' => round($plan->price * 0.02, 2),
                    ]),
                    'paid_at' => $status === 'completed' ? now()->subDays(rand(0, 30)) : null,
                    'created_at' => now()->subDays(rand(0, 60)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} payment transactions successfully.");
    }

    private function getWeightedStatus(array $statuses, array $weights): string
    {
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($statuses); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $statuses[$i];
            }
        }
        
        return $statuses[0];
    }

    private function generatePaymentReference(): string
    {
        return 'TXN-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }

    private function getTransactionDescription(string $planName): string
    {
        return "Payment for {$planName}";
    }
}

