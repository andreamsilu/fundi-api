<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        // No third-party SDK initialization required for mobile money stub
    }

    /**
     * Create a new payment
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $paymentMethod
     * @param Model $payable
     * @param array $metadata
     * @return Payment
     */
    public function createPayment(
        User $user,
        float $amount,
        string $currency,
        string $paymentMethod,
        Model $payable,
        array $metadata = []
    ): Payment {
        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_provider' => 'mobile_money',
            'metadata' => $metadata,
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
        ]);
    }

    /**
     * Initiate a mobile money payment (stubbed provider integration).
     *
     * @param Payment $payment
     * @param string|null $payerPhone
     * @return array
     */
    public function initiateMobileMoney(Payment $payment, ?string $payerPhone = null): array
    {
        try {
            // Simulate sending a mobile money payment request to a provider
            $reference = 'mm_' . bin2hex(random_bytes(8));

            $providerResponse = [
                'reference' => $reference,
                'status' => 'initiated',
                'phone' => $payerPhone,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ];

            $payment->update([
                'payment_provider_id' => $reference,
                'payment_provider_status' => 'initiated',
                'payment_provider_response' => $providerResponse,
            ]);

            return [
                'payment_reference' => $reference,
                'status' => 'initiated',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to initiate mobile money payment: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Stripe webhook events
     *
     * @param array $payload
     * @return void
     */
    public function handleMobileMoneyCallback(array $payload): void
    {
        $event = $payload['event'] ?? null; // e.g., payment.completed, payment.failed
        $data = $payload['data'] ?? null;

        if (!$event || !$data) {
            return;
        }

        try {
            switch ($event) {
                case 'payment.completed':
                    $this->handleSuccessfulPayment($data);
                    break;
                case 'payment.failed':
                    $this->handleFailedPayment($data);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle Mobile Money callback: ' . $e->getMessage(), [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle successful payment
     *
     * @param array $data
     * @return void
     */
    protected function handleSuccessfulPayment(array $data): void
    {
        $payment = Payment::where('payment_provider_id', $data['id'] ?? ($data['reference'] ?? null))->first();

        if (!$payment) {
            Log::warning('Payment not found for successful payment intent', [
                'payment_intent_id' => $data['id']
            ]);
            return;
        }

        $payment->markAsCompleted($data);

        // Update the payable model status if needed
        $this->updatePayableStatus($payment);
    }

    /**
     * Handle failed payment
     *
     * @param array $data
     * @return void
     */
    protected function handleFailedPayment(array $data): void
    {
        $payment = Payment::where('payment_provider_id', $data['id'])->first();

        if (!$payment) {
            Log::warning('Payment not found for failed payment intent', [
                'payment_intent_id' => $data['id']
            ]);
            return;
        }

        $payment->markAsFailed($data);
    }

    /**
     * Update the status of the payable model
     *
     * @param Payment $payment
     * @return void
     */
    protected function updatePayableStatus(Payment $payment): void
    {
        $payable = $payment->payable;

        if (!$payable) {
            return;
        }

        // Update status based on payable type
        switch (get_class($payable)) {
            case 'App\Models\Booking':
                $payable->update(['status' => 'paid']);
                break;
            case 'App\Models\Job':
                $payable->update(['status' => 'paid']);
                break;
        }
    }

    /**
     * Get payment history for a user
     *
     * @param User $user
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserPaymentHistory(User $user, array $filters = [])
    {
        $query = $user->payments()
            ->with('payable')
            ->latest();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
} 