<?php

namespace App\Http\Middleware;

use App\Models\JobApplicationFee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProtectCustomerContact
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to job detail routes
        if (!$this->isJobDetailRoute($request)) {
            return $next($request);
        }

        $user = $request->user();
        
        // Only apply to fundis
        if (!$user || $user->role !== 'fundi') {
            return $next($request);
        }

        // Get job from route parameter
        $job = $request->route('job');
        if (!$job) {
            return $next($request);
        }

        // Check if fundi has paid to apply to this job
        $hasPaid = JobApplicationFee::where('job_id', $job->id)
            ->where('fundi_id', $user->id)
            ->where('status', 'paid')
            ->exists();

        if (!$hasPaid) {
            // Remove sensitive customer information from response
            $response = $next($request);
            
            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                
                if (isset($data['data']['customer'])) {
                    // Hide customer contact information
                    $data['data']['customer'] = $this->hideCustomerContact($data['data']['customer']);
                    $data['data']['contact_protected'] = true;
                    $data['data']['contact_unlock_message'] = 'Apply to this job to unlock customer contact information';
                }
                
                $response->setData($data);
            }
            
            return $response;
        }

        return $next($request);
    }

    /**
     * Check if the request is for a job detail route.
     */
    private function isJobDetailRoute(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'jobs/') && !str_contains($path, '/apply');
    }

    /**
     * Hide customer contact information.
     */
    private function hideCustomerContact(array $customer): array
    {
        // Hide sensitive contact information
        if (isset($customer['phone'])) {
            $customer['phone'] = $this->maskPhone($customer['phone']);
        }
        
        if (isset($customer['email'])) {
            $customer['email'] = $this->maskEmail($customer['email']);
        }
        
        // Add message about unlocking contact
        $customer['contact_locked'] = true;
        
        return $customer;
    }

    /**
     * Mask phone number.
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }
        
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    /**
     * Mask email address.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return str_repeat('*', strlen($email));
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . $username[-1];
        }
        
        return $maskedUsername . '@' . $domain;
    }
}
