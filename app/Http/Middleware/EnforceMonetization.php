<?php

namespace App\Http\Middleware;

use App\Services\MonetizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EnforceMonetization
{
    protected MonetizationService $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to job application routes
        if (!$this->isJobApplicationRoute($request)) {
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

        // Check if fundi can apply to this job
        $canApply = $this->monetizationService->canFundiApplyToJob($user, $job);
        
        if (!$canApply['can_apply']) {
            return response()->json([
                'success' => false,
                'message' => $canApply['reason'],
                'error_code' => 'MONETIZATION_REQUIRED',
                'data' => [
                    'required_payment' => $canApply['required_payment'],
                    'payment_type' => $canApply['payment_type'] ?? null,
                    'subscription_required' => $canApply['payment_type'] === 'subscription',
                    'credits_required' => $canApply['payment_type'] === 'credits'
                ]
            ], 402); // Payment Required
        }

        // Add monetization data to request for use in controller
        $request->merge([
            'monetization_check' => $canApply
        ]);

        return $next($request);
    }

    /**
     * Check if the request is for a job application route.
     */
    private function isJobApplicationRoute(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'jobs/') && str_contains($path, '/apply');
    }
}
