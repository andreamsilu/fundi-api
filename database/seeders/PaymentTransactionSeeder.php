<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Payment;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Payment Transaction Seeder
 * Seeds the payment_transactions table with detailed transaction records
 * Matches the create_payment_transactions_table migration
 */
class PaymentTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all payments
        $payments = Payment::all();

        if ($payments->isEmpty()) {
            $this->command->warn('No payments found. Please run PaymentSeeder first.');
            return;
        }

        $gateways = ['zenopay', 'mpesa', 'tigo_pesa', 'airtel_money', 'card', 'bank_transfer'];
        $currencies = ['TZS'];

        foreach ($payments as $payment) {
            $gateway = $gateways[array_rand($gateways)];
            $status = $this->mapPaymentStatus($payment->status);
            
            DB::table('payment_transactions')->insert([
                'payment_id' => $payment->id,
                'transaction_id' => $this->generateTransactionId($gateway),
                'gateway' => $gateway,
                'gateway_reference' => $this->generateGatewayReference($gateway),
                'amount' => $payment->amount,
                'currency' => $currencies[0],
                'status' => $status,
                'payment_method' => $payment->payment_type,
                'payer_phone' => $this->generatePhoneNumber(),
                'payer_email' => null,
                'payer_name' => $this->generateName(),
                'metadata' => json_encode([
                    'gateway' => $gateway,
                    'payment_type' => $payment->payment_type,
                    'original_amount' => $payment->amount,
                    'gateway_fee' => $this->calculateGatewayFee($payment->amount, $gateway),
                    'net_amount' => $payment->amount - $this->calculateGatewayFee($payment->amount, $gateway),
                ]),
                'callback_data' => json_encode([
                    'status' => $status,
                    'timestamp' => now()->toISOString(),
                    'gateway' => $gateway,
                ]),
                'error_message' => $status === 'failed' ? $this->getErrorMessage() : null,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ]);
        }

        $this->command->info('Created ' . $payments->count() . ' payment transactions successfully.');
    }

    private function mapPaymentStatus(string $paymentStatus): string
    {
        $statusMap = [
            'pending' => 'pending',
            'completed' => 'completed',
            'failed' => 'failed',
            'refunded' => 'refunded',
        ];

        return $statusMap[$paymentStatus] ?? 'pending';
    }

    private function generateTransactionId(string $gateway): string
    {
        $prefix = strtoupper(substr($gateway, 0, 3));
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }

    private function generateGatewayReference(string $gateway): string
    {
        $prefixes = [
            'zenopay' => 'ZPY',
            'mpesa' => 'MPE',
            'tigo_pesa' => 'TGP',
            'airtel_money' => 'ATM',
            'card' => 'CRD',
            'bank_transfer' => 'BNK',
        ];

        $prefix = $prefixes[$gateway] ?? 'GEN';
        return $prefix . time() . rand(10000, 99999);
    }

    private function generatePhoneNumber(): string
    {
        $prefixes = ['0712', '0754', '0765', '0782', '0688', '0622'];
        $prefix = $prefixes[array_rand($prefixes)];
        return $prefix . rand(100000, 999999);
    }

    private function generateName(): string
    {
        $names = [
            'John Mwangi',
            'Mary Ndunguru',
            'Peter Kileo',
            'Grace Hassan',
            'Michael Mbunda',
            'Sarah Mwakasege',
            'David Komba',
            'Anna Msigwa',
        ];
        return $names[array_rand($names)];
    }

    private function calculateGatewayFee(float $amount, string $gateway): float
    {
        $feeRates = [
            'zenopay' => 0.015,      // 1.5%
            'mpesa' => 0.02,          // 2%
            'tigo_pesa' => 0.02,      // 2%
            'airtel_money' => 0.018,  // 1.8%
            'card' => 0.025,          // 2.5%
            'bank_transfer' => 0.01,  // 1%
        ];

        $rate = $feeRates[$gateway] ?? 0.02;
        return round($amount * $rate, 2);
    }

    private function getErrorMessage(): string
    {
        $errors = [
            'Insufficient funds in account',
            'Transaction declined by gateway',
            'Invalid payment credentials',
            'Network timeout - please try again',
            'Payment gateway temporarily unavailable',
            'Transaction cancelled by user',
            'Maximum transaction limit exceeded',
            'Invalid phone number format',
        ];

        return $errors[array_rand($errors)];
    }
}

