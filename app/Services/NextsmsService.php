<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NextsmsService
{
    /**
     * Send OTP via Nextsms
     *
     * @param array $payload
     * @return array
     * @throws \Exception
     */
    public function sendOtp(array $payload): array
    {
        try {
            // Validate required environment variables
            $this->validateEnvironmentVariables();

            // Prepare the request payload
            $requestPayload = [
                'from' => $payload['from'] ?? config('services.nextsms.sender_id'),
                'to' => $payload['to'],
                'text' => $payload['text'],
                'reference' => $payload['reference']
            ];

            // Get base URL from config
            $baseUrl = config('services.nextsms.base_url');
            if (empty($baseUrl)) {
                throw new \Exception('Nextsms base URL is not configured');
            }

            // Make the API request
            $response = Http::withBasicAuth(
                config('services.nextsms.username'),
                config('services.nextsms.password')
            )->post($baseUrl . '/text/single', $requestPayload);

            // Log the response for debugging
            Log::info('Nextsms API Response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'reference' => $payload['reference'],
                'url' => $baseUrl . '/text/single'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to send SMS: ' . ($response->json()['message'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'reference' => $payload['reference'],
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Nextsms API Error', [
                'message' => $e->getMessage(),
                'payload' => $payload,
                'url' => config('services.nextsms.base_url') . '/text/single'
            ]);

            throw new \Exception('Failed to send SMS: ' . $e->getMessage());
        }
    }

    /**
     * Validate that all required environment variables are set
     *
     * @throws \Exception
     */
    private function validateEnvironmentVariables(): void
    {
        $requiredVariables = [
            'NEXTSMS_USERNAME' => config('services.nextsms.username'),
            'NEXTSMS_PASSWORD' => config('services.nextsms.password'),
            'NEXTSMS_SENDER_ID' => config('services.nextsms.sender_id'),
            'NEXTSMS_BASE_URL' => config('services.nextsms.base_url')
        ];

        $missingVariables = array_filter($requiredVariables, function ($value) {
            return empty($value);
        });

        if (!empty($missingVariables)) {
            $missingKeys = array_keys($missingVariables);
            throw new \Exception('Missing required environment variables: ' . implode(', ', $missingKeys));
        }
    }
} 