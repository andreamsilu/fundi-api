<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\User;

class PaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for relationships
        $users = User::take(20)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $payments = [
            [
                'amount' => 2500.00,
                'payment_type' => 'subscription',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'plan' => 'premium',
                    'duration' => 'monthly',
                    'features' => ['unlimited_jobs', 'priority_support', 'analytics'],
                ],
            ],
            [
                'amount' => 500.00,
                'payment_type' => 'job_posting',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'job_id' => rand(1, 10),
                    'featured' => true,
                    'duration' => '30_days',
                ],
            ],
            [
                'amount' => 1500.00,
                'payment_type' => 'subscription',
                'status' => 'pending',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'plan' => 'basic',
                    'duration' => 'monthly',
                    'features' => ['basic_jobs', 'email_support'],
                ],
            ],
            [
                'amount' => 750.00,
                'payment_type' => 'premium_feature',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'feature' => 'profile_boost',
                    'duration' => '7_days',
                    'visibility' => 'increased',
                ],
            ],
            [
                'amount' => 1000.00,
                'payment_type' => 'subscription',
                'status' => 'failed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'plan' => 'premium',
                    'duration' => 'monthly',
                    'failure_reason' => 'insufficient_funds',
                ],
            ],
            [
                'amount' => 300.00,
                'payment_type' => 'job_posting',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'job_id' => rand(1, 10),
                    'featured' => false,
                    'duration' => '14_days',
                ],
            ],
            [
                'amount' => 2000.00,
                'payment_type' => 'subscription',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'plan' => 'enterprise',
                    'duration' => 'monthly',
                    'features' => ['unlimited_jobs', 'priority_support', 'analytics', 'api_access'],
                ],
            ],
            [
                'amount' => 400.00,
                'payment_type' => 'premium_feature',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'feature' => 'verification_badge',
                    'duration' => 'permanent',
                    'verification_type' => 'identity',
                ],
            ],
            [
                'amount' => 800.00,
                'payment_type' => 'job_posting',
                'status' => 'pending',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'job_id' => rand(1, 10),
                    'featured' => true,
                    'duration' => '60_days',
                ],
            ],
            [
                'amount' => 1200.00,
                'payment_type' => 'subscription',
                'status' => 'completed',
                'pesapal_reference' => 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'metadata' => [
                    'plan' => 'professional',
                    'duration' => 'monthly',
                    'features' => ['advanced_jobs', 'priority_support', 'basic_analytics'],
                ],
            ],
        ];

        $createdCount = 0;
        $usedReferences = [];
        
        foreach ($users as $user) {
            // Create 1-3 payments per user
            $numPayments = rand(1, 3);
            $selectedPayments = array_slice($payments, 0, $numPayments);
            
            foreach ($selectedPayments as $paymentData) {
                // Generate unique pesapal reference
                do {
                    $reference = 'PES' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                } while (in_array($reference, $usedReferences) || 
                        Payment::where('pesapal_reference', $reference)->exists());
                
                $usedReferences[] = $reference;
                $paymentData['pesapal_reference'] = $reference;
                
                Payment::create([
                    'user_id' => $user->id,
                    ...$paymentData,
                ]);
                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} payments successfully.");
    }
}