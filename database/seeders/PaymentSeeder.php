<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Job;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get existing users, jobs, and bookings
            $users = User::all();
            $jobs = Job::all();
            $bookings = Booking::all();

            if ($users->isEmpty() || $jobs->isEmpty()) {
                $this->command->warn('No users or jobs found. Please run other seeders first.');
                return;
            }

            // Clear existing payments
            Payment::truncate();

            // Payment providers for Tanzania
            $paymentProviders = [
                'vodacom_mpesa' => 'Vodacom M-Pesa',
                'airtel_money' => 'Airtel Money',
                'tigo_pesa' => 'Tigo Pesa',
                'halopesa' => 'HaloPesa',
                'tpesa' => 'TPesa',
            ];

            // Payment methods
            $paymentMethods = [
                'mobile_money',
                'bank_transfer',
                'card',
            ];

            // Payment statuses with realistic distribution
            $statuses = [
                'completed' => 70, // 70% completed
                'pending' => 15,   // 15% pending
                'failed' => 10,    // 10% failed
                'cancelled' => 3,  // 3% cancelled
                'refunded' => 2,   // 2% refunded
            ];

            $payments = [];

            // Generate payments for jobs (direct payments)
            foreach ($jobs->take(50) as $job) {
                // Skip jobs without customer_id
                if (!$job->customer_id) {
                    continue;
                }
                
                $status = $this->getWeightedStatus($statuses);
                $provider = array_rand($paymentProviders);
                $method = $paymentMethods[array_rand($paymentMethods)];
                
                $payments[] = [
                    'user_id' => $job->customer_id,
                    'amount' => $this->generateRealisticAmount($job->budget_min, $job->budget_max),
                    'currency' => 'TZS',
                    'status' => $status,
                    'payment_method' => $method,
                    'payment_provider' => $provider,
                    'payment_provider_id' => $this->generateProviderId($provider, $status),
                    'payment_provider_status' => $this->getProviderStatus($status),
                    'payment_provider_response' => $this->generateProviderResponse($provider, $status),
                    'metadata' => json_encode([
                        'job_id' => $job->id,
                        'job_title' => $job->title,
                        'customer_phone' => $job->customer->phone ?? '+255700000000',
                        'created_via' => 'admin_seeder',
                    ]),
                    'payable_type' => Job::class,
                    'payable_id' => $job->id,
                    'created_at' => $this->generateRealisticDate(),
                    'updated_at' => $this->generateRealisticDate(),
                ];
            }

            // Generate payments for bookings
            foreach ($bookings->take(30) as $booking) {
                // Skip bookings without customer_id
                if (!$booking->job->customer_id) {
                    continue;
                }
                
                $status = $this->getWeightedStatus($statuses);
                $provider = array_rand($paymentProviders);
                $method = $paymentMethods[array_rand($paymentMethods)];
                
                $payments[] = [
                    'user_id' => $booking->job->customer_id,
                    'amount' => $this->generateRealisticAmount($booking->job->budget_min, $booking->job->budget_max),
                    'currency' => 'TZS',
                    'status' => $status,
                    'payment_method' => $method,
                    'payment_provider' => $provider,
                    'payment_provider_id' => $this->generateProviderId($provider, $status),
                    'payment_provider_status' => $this->getProviderStatus($status),
                    'payment_provider_response' => $this->generateProviderResponse($provider, $status),
                    'metadata' => json_encode([
                        'booking_id' => $booking->id,
                        'job_id' => $booking->job_id,
                        'fundi_id' => $booking->fundi_id,
                        'customer_phone' => $booking->job->customer->phone ?? '+255700000000',
                        'created_via' => 'admin_seeder',
                    ]),
                    'payable_type' => Booking::class,
                    'payable_id' => $booking->id,
                    'created_at' => $this->generateRealisticDate(),
                    'updated_at' => $this->generateRealisticDate(),
                ];
            }

            // Generate some additional standalone payments
            for ($i = 0; $i < 20; $i++) {
                $user = $users->random();
                $status = $this->getWeightedStatus($statuses);
                $provider = array_rand($paymentProviders);
                $method = $paymentMethods[array_rand($paymentMethods)];
                
                $payments[] = [
                    'user_id' => $user->id,
                    'amount' => fake()->randomFloat(2, 10000, 500000), // 10,000 - 500,000 TZS
                    'currency' => 'TZS',
                    'status' => $status,
                    'payment_method' => $method,
                    'payment_provider' => $provider,
                    'payment_provider_id' => $this->generateProviderId($provider, $status),
                    'payment_provider_status' => $this->getProviderStatus($status),
                    'payment_provider_response' => $this->generateProviderResponse($provider, $status),
                    'metadata' => json_encode([
                        'description' => fake()->sentence(),
                        'customer_phone' => $user->phone ?? '+255700000000',
                        'created_via' => 'admin_seeder',
                        'standalone_payment' => true,
                    ]),
                    'payable_type' => 'App\\Models\\Payment',
                    'payable_id' => 0,
                    'created_at' => $this->generateRealisticDate(),
                    'updated_at' => $this->generateRealisticDate(),
                ];
            }

            // Insert all payments
            Payment::insert($payments);

            $this->command->info('Created ' . count($payments) . ' sample payments with TZS currency');
        });
    }

    /**
     * Get weighted random status
     */
    private function getWeightedStatus(array $statuses): string
    {
        $total = array_sum($statuses);
        $random = mt_rand(1, $total);
        
        foreach ($statuses as $status => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $status;
            }
        }
        
        return 'completed'; // fallback
    }

    /**
     * Generate realistic amount based on job budget
     */
    private function generateRealisticAmount(?float $min, ?float $max): float
    {
        if ($min && $max) {
            return fake()->randomFloat(2, $min, $max);
        }
        
        // Default range for TZS
        return fake()->randomFloat(2, 10000, 500000);
    }

    /**
     * Generate provider ID based on provider and status
     */
    private function generateProviderId(string $provider, string $status): ?string
    {
        if (in_array($status, ['failed', 'cancelled'])) {
            return null;
        }

        $prefixes = [
            'vodacom_mpesa' => 'VMP',
            'airtel_money' => 'ATM',
            'tigo_pesa' => 'TGP',
            'halopesa' => 'HLP',
            'tpesa' => 'TPS',
        ];

        $prefix = $prefixes[$provider] ?? 'PAY';
        return $prefix . fake()->numerify('##########');
    }

    /**
     * Get provider status based on payment status
     */
    private function getProviderStatus(string $status): ?string
    {
        return match ($status) {
            'completed' => 'succeeded',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => null,
        };
    }

    /**
     * Generate provider response
     */
    private function generateProviderResponse(string $provider, string $status): ?string
    {
        if (in_array($status, ['failed', 'cancelled'])) {
            return json_encode([
                'error_code' => fake()->randomElement(['INSUFFICIENT_FUNDS', 'NETWORK_ERROR', 'INVALID_ACCOUNT', 'TIMEOUT']),
                'error_message' => fake()->sentence(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        if ($status === 'completed') {
            return json_encode([
                'transaction_id' => fake()->uuid(),
                'reference' => fake()->numerify('REF########'),
                'timestamp' => now()->toISOString(),
                'provider_response' => 'SUCCESS',
            ]);
        }

        return null;
    }

    /**
     * Generate realistic date within last 6 months
     */
    private function generateRealisticDate(): string
    {
        $startDate = Carbon::now()->subMonths(6);
        $endDate = Carbon::now();
        
        return fake()->dateTimeBetween($startDate, $endDate)->format('Y-m-d H:i:s');
    }
}
