<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $jobs = Job::where('status', 'completed')->get();

        if ($users->isEmpty()) {
            return;
        }

        $paymentMethods = ['mobile_money', 'bank_transfer', 'cash', 'card'];
        $paymentStatuses = ['pending', 'completed', 'failed', 'refunded'];
        $paymentTypes = ['job_payment', 'subscription', 'application_fee', 'job_posting_fee'];

        // Create payments for completed jobs
        foreach ($jobs as $job) {
            $customer = $job->customer;
            $fundi = User::whereHas('roles', function($q) {
                $q->where('name', 'fundi');
            })->inRandomOrder()->first();
            
            if (!$fundi) continue;

            Payment::create([
                'user_id' => $customer->id,
                'amount' => $job->budget,
                'payment_type' => 'job_posting',
                'status' => $this->getPaymentStatus(),
                'pesapal_reference' => $this->generatePaymentReference(),
                'metadata' => [
                    'job_title' => $job->title,
                    'job_category' => $job->category->name ?? 'General',
                    'payment_notes' => 'Payment processed successfully',
                    'fundi_id' => $fundi->id,
                    'job_id' => $job->id
                ],
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subDays(rand(0, 15))
            ]);
        }

        // Create subscription payments for fundis
        $fundis = User::whereHas('roles', function($q) {
            $q->where('name', 'fundi');
        })->get();
        foreach ($fundis as $fundi) {
            if (rand(1, 100) <= 30) { // 30% of fundis have subscription payments
                Payment::create([
                    'user_id' => $fundi->id,
                    'amount' => 5000.00, // 5000 TZS subscription fee
                    'payment_type' => 'subscription',
                    'status' => $this->getPaymentStatus(),
                    'pesapal_reference' => $this->generatePaymentReference(),
                    'metadata' => [
                        'subscription_period' => 'monthly',
                        'subscription_type' => 'premium',
                        'payment_notes' => 'Monthly subscription payment'
                    ],
                    'created_at' => now()->subDays(rand(0, 60)),
                    'updated_at' => now()->subDays(rand(0, 30))
                ]);
            }
        }

        // Create application fees
        $applications = \App\Models\JobApplication::where('status', 'accepted')->get();
        foreach ($applications as $application) {
            if (rand(1, 100) <= 50) { // 50% of applications have fees
                Payment::create([
                    'user_id' => $application->fundi_id,
                    'amount' => 1000.00, // 1000 TZS application fee
                    'payment_type' => 'application_fee',
                    'status' => $this->getPaymentStatus(),
                    'pesapal_reference' => $this->generatePaymentReference(),
                    'metadata' => [
                        'job_title' => $application->job->title,
                        'application_id' => $application->id,
                        'payment_notes' => 'Application fee payment',
                        'job_id' => $application->job_id
                    ],
                    'created_at' => $application->created_at,
                    'updated_at' => $application->updated_at
                ]);
            }
        }

        // Create job posting fees for customers
        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'customer');
        })->get();
        foreach ($customers as $customer) {
            $customerJobs = Job::where('customer_id', $customer->id)->get();
            foreach ($customerJobs as $job) {
                if (rand(1, 100) <= 40) { // 40% of jobs have posting fees
                    Payment::create([
                        'user_id' => $customer->id,
                        'amount' => 2000.00, // 2000 TZS job posting fee
                        'payment_type' => 'job_posting',
                        'status' => $this->getPaymentStatus(),
                        'pesapal_reference' => $this->generatePaymentReference(),
                        'metadata' => [
                            'job_title' => $job->title,
                            'job_category' => $job->category->name ?? 'General',
                            'payment_notes' => 'Job posting fee payment',
                            'job_id' => $job->id
                        ],
                        'created_at' => $job->created_at,
                        'updated_at' => $job->updated_at
                    ]);
                }
            }
        }
    }

    private function getPaymentStatus(): string
    {
        $statuses = ['pending', 'completed', 'failed'];
        $weights = [10, 85, 5]; // 85% completed, 10% pending, 5% failed
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        for ($i = 0; $i < count($statuses); $i++) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $statuses[$i];
            }
        }
        
        return 'completed';
    }

    private function generateTransactionId(): string
    {
        return 'TXN' . strtoupper(uniqid()) . rand(1000, 9999);
    }

    private function generatePaymentReference(): string
    {
        return 'PAY' . strtoupper(uniqid()) . rand(100, 999);
    }
}
