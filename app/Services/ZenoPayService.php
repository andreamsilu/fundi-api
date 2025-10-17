<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\SmsService;

/**
 * ZenoPay Service
 * Handles integration with ZenoPay Mobile Money payment gateway for Tanzania
 * Supports M-Pesa, Tigo Pesa, and Airtel Money
 * 
 * @author Isaiah Nyalali
 * @see https://zenopay-docs.netlify.app
 */
class ZenoPayService
{
    private ?Client $client;
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.zenopay.api_key');
        $this->baseUrl = config('services.zenopay.base_url', 'https://zenoapi.com');
        
        // Only initialize client if API key is configured
        if ($this->apiKey) {
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 60, // Mobile money payments can take time
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
        }
    }

    /**
     * Check if ZenoPay is properly configured
     * 
     * @return bool
     */
    private function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->client);
    }

    /**
     * Initiate mobile money payment
     * 
     * @param string $orderId Unique transaction ID from your system
     * @param string $buyerEmail Customer's email
     * @param string $buyerName Customer's full name
     * @param string $buyerPhone Tanzanian mobile number (format: 07XXXXXXXX)
     * @param float $amount Amount in TZS
     * @param string|null $webhookUrl Optional webhook URL for status updates
     * @return array Response from ZenoPay
     */
    public function initiatePayment(
        string $orderId,
        string $buyerEmail,
        string $buyerName,
        string $buyerPhone,
        float $amount,
        ?string $webhookUrl = null
    ): array {
        if (!$this->isConfigured()) {
            Log::warning('ZenoPay: Service not configured - API key missing');
            return [
                'success' => false,
                'message' => 'Payment service not configured. Please contact support.',
                'error' => 'ZENOPAY_NOT_CONFIGURED',
            ];
        }

        try {
            $payload = [
                'order_id' => $orderId,
                'buyer_email' => $buyerEmail,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'amount' => $amount,
                'webhook_url' => $webhookUrl ?? config('services.zenopay.webhook_url'),
            ];

            Log::info('ZenoPay: Initiating payment', [
                'order_id' => $orderId,
                'amount' => $amount,
                'phone' => $buyerPhone
            ]);

            $response = $this->client->request('POST', '/api/payments/mobile_money_tanzania', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('ZenoPay: Payment initiated', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'response' => $responseBody
            ]);

            return [
                'success' => $statusCode === 200 && isset($responseBody['status']) && $responseBody['status'] === 'success',
                'data' => $responseBody,
                'message' => $responseBody['message'] ?? 'Payment initiated',
                'order_id' => $responseBody['order_id'] ?? $orderId,
                'resultcode' => $responseBody['resultcode'] ?? null,
            ];

        } catch (RequestException $e) {
            Log::error('ZenoPay: Payment initiation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check payment order status
     * 
     * @param string $orderId The order ID to check
     * @return array Order status details
     */
    public function checkOrderStatus(string $orderId): array
    {
        if (!$this->isConfigured()) {
            Log::warning('ZenoPay: Service not configured - API key missing');
            return [
                'success' => false,
                'message' => 'Payment service not configured. Please contact support.',
                'error' => 'ZENOPAY_NOT_CONFIGURED',
            ];
        }

        try {
            Log::info('ZenoPay: Checking order status', ['order_id' => $orderId]);

            $response = $this->client->request('GET', '/api/payments/order-status', [
                'query' => [
                    'order_id' => $orderId,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('ZenoPay: Order status retrieved', [
                'order_id' => $orderId,
                'status' => $responseBody['data'][0]['payment_status'] ?? 'UNKNOWN'
            ]);

            $orderData = $responseBody['data'][0] ?? null;

            return [
                'success' => $statusCode === 200 && isset($responseBody['result']) && $responseBody['result'] === 'SUCCESS',
                'data' => $orderData,
                'payment_status' => $orderData['payment_status'] ?? 'UNKNOWN',
                'amount' => $orderData['amount'] ?? null,
                'channel' => $orderData['channel'] ?? null,
                'reference' => $orderData['reference'] ?? null,
                'transid' => $orderData['transid'] ?? null,
                'message' => $responseBody['message'] ?? 'Status retrieved',
            ];

        } catch (RequestException $e) {
            Log::error('ZenoPay: Status check failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process webhook callback from ZenoPay
     * Called when payment status changes to COMPLETED
     * 
     * @param array $payload Webhook payload from ZenoPay
     * @param string $apiKeyFromHeader API key from request header
     * @return array Processing result
     */
    public function processWebhook(array $payload, string $apiKeyFromHeader): array
    {
        if (!$this->isConfigured()) {
            Log::warning('ZenoPay: Service not configured - cannot process webhook');
            return [
                'success' => false,
                'message' => 'Payment service not configured',
                'error' => 'ZENOPAY_NOT_CONFIGURED',
            ];
        }

        // Verify API key from webhook request
        if ($apiKeyFromHeader !== $this->apiKey) {
            Log::warning('ZenoPay: Webhook authentication failed', [
                'provided_key' => substr($apiKeyFromHeader, 0, 10) . '...',
            ]);

            return [
                'success' => false,
                'message' => 'Invalid API key',
            ];
        }

        try {
            $orderId = $payload['order_id'] ?? null;
            $paymentStatus = $payload['payment_status'] ?? null;
            $reference = $payload['reference'] ?? null;
            $metadata = $payload['metadata'] ?? [];

            Log::info('ZenoPay: Processing webhook', [
                'order_id' => $orderId,
                'payment_status' => $paymentStatus,
                'reference' => $reference
            ]);

            if (!$orderId || !$paymentStatus) {
                return [
                    'success' => false,
                    'message' => 'Invalid webhook payload',
                ];
            }

            // Find payment transaction
            $transaction = PaymentTransaction::where('transaction_id', $orderId)->first();

            if (!$transaction) {
                Log::warning('ZenoPay: Transaction not found', ['order_id' => $orderId]);
                return [
                    'success' => false,
                    'message' => 'Transaction not found',
                ];
            }

            // Update transaction status
            if ($paymentStatus === 'COMPLETED') {
                $transaction->update([
                    'status' => 'completed',
                    'gateway_reference' => $reference,
                    'completed_at' => now(),
                    'paid_at' => now(),
                    'metadata' => array_merge($transaction->metadata ?? [], $metadata),
                ]);

                // Update related payment
                if ($transaction->payment) {
                    $transaction->payment->update(['status' => 'completed']);
                }

                Log::info('ZenoPay: Payment completed', [
                    'order_id' => $orderId,
                    'reference' => $reference
                ]);

                // Send SMS notification for subscription payments
                $this->sendPaymentConfirmationSms($transaction);

                return [
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'transaction' => $transaction,
                ];
            }

            return [
                'success' => true,
                'message' => 'Webhook processed',
            ];

        } catch (\Exception $e) {
            Log::error('ZenoPay: Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate Tanzanian phone number format
     * 
     * @param string $phone Phone number to validate
     * @return bool
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Tanzanian mobile numbers: 07XXXXXXXX or 255XXXXXXXXX
        $pattern = '/^(07[0-9]{8}|2557[0-9]{8})$/';
        return preg_match($pattern, $phone) === 1;
    }

    /**
     * Format phone number to ZenoPay format (07XXXXXXXX)
     * 
     * @param string $phone Phone number to format
     * @return string|null Formatted phone number or null if invalid
     */
    public function formatPhoneNumber(string $phone): ?string
    {
        // Remove spaces, dashes, and plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);

        // If starts with 255, convert to 0
        if (str_starts_with($phone, '255')) {
            $phone = '0' . substr($phone, 3);
        }

        // Validate
        if ($this->validatePhoneNumber($phone)) {
            return $phone;
        }

        return null;
    }

    /**
     * Get supported payment channels
     * 
     * @return array List of supported channels
     */
    public function getSupportedChannels(): array
    {
        return [
            'MPESA-TZ' => 'M-Pesa Tanzania',
            'TIGO-TZ' => 'Tigo Pesa',
            'AIRTEL-TZ' => 'Airtel Money',
        ];
    }

    /**
     * Send payment confirmation SMS for subscription payments
     * 
     * @param PaymentTransaction $transaction
     * @return void
     */
    private function sendPaymentConfirmationSms(PaymentTransaction $transaction): void
    {
        try {
            // Only send SMS for subscription payments
            if ($transaction->transaction_type !== 'subscription') {
                Log::info('ZenoPay: Skipping SMS - not a subscription payment', [
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->transaction_type
                ]);
                return;
            }

            // Load user relationship
            $user = $transaction->user;
            if (!$user || !$user->phone) {
                Log::warning('ZenoPay: Cannot send SMS - user or phone not found', [
                    'transaction_id' => $transaction->transaction_id,
                    'user_id' => $transaction->user_id
                ]);
                return;
            }

            // Load payment plan relationship
            $paymentPlan = $transaction->paymentPlan;
            if (!$paymentPlan) {
                Log::warning('ZenoPay: Cannot send SMS - payment plan not found', [
                    'transaction_id' => $transaction->transaction_id,
                    'payment_plan_id' => $transaction->payment_plan_id
                ]);
                return;
            }

            // Create SMS message
            $amount = number_format($transaction->amount, 0);
            $planName = $paymentPlan->name ?? 'Subscription';
            $reference = $transaction->gateway_reference ?? $transaction->transaction_id;
            
            $message = "Payment Successful! Your {$planName} subscription of TZS {$amount} has been confirmed. Reference: {$reference}. Thank you for using Fundi App!";

            // Send SMS
            $smsService = new SmsService();
            $result = $smsService->sendSms($user->phone, $message);

            if ($result['success']) {
                Log::info('ZenoPay: Subscription SMS sent successfully', [
                    'transaction_id' => $transaction->transaction_id,
                    'phone' => $user->phone,
                    'plan' => $planName
                ]);
            } else {
                Log::warning('ZenoPay: Failed to send subscription SMS', [
                    'transaction_id' => $transaction->transaction_id,
                    'phone' => $user->phone,
                    'error' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ZenoPay: Exception while sending subscription SMS', [
                'transaction_id' => $transaction->transaction_id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}

