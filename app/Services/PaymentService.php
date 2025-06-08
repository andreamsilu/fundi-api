<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService
{
    protected $stripeSecretKey;

    public function __construct()
    {
        $this->stripeSecretKey = config('services.stripe.secret');
        Stripe::setApiKey($this->stripeSecretKey);
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
            'payment_provider' => 'stripe',
            'metadata' => $metadata,
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
        ]);
    }

    /**
     * Create a payment intent with Stripe
     *
     * @param Payment $payment
     * @return array
     */
    public function createStripePaymentIntent(Payment $payment): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $payment->getAmountInSmallestUnit(),
                'currency' => strtolower($payment->currency),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'payable_type' => $payment->payable_type,
                    'payable_id' => $payment->payable_id,
                ],
            ]);

            $payment->update([
                'payment_provider_id' => $paymentIntent->id,
                'payment_provider_status' => $paymentIntent->status,
                'payment_provider_response' => $paymentIntent->toArray(),
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe payment intent: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
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
    public function handleStripeWebhook(array $payload): void
    {
        $event = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? null;

        if (!$event || !$data) {
            return;
        }

        try {
            switch ($event) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($data);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($data);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle Stripe webhook: ' . $e->getMessage(), [
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
        $payment = Payment::where('payment_provider_id', $data['id'])->first();

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