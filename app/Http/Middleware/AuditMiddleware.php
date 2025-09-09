<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuditService;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only audit API requests
        if ($request->is('api/*')) {
            $this->auditRequest($request, $response);
        }

        return $response;
    }

    /**
     * Audit the request
     */
    private function auditRequest(Request $request, Response $response): void
    {
        try {
            $method = $request->method();
            $path = $request->path();
            $statusCode = $response->getStatusCode();

            // Determine action based on HTTP method and path
            $action = $this->determineAction($method, $path);
            $resourceType = $this->determineResourceType($path);

            // Skip certain paths that don't need auditing
            if ($this->shouldSkipAudit($path)) {
                return;
            }

            // Determine status
            $status = $this->determineStatus($statusCode);

            // Log the request
            AuditService::log(
                action: $action,
                resourceType: $resourceType,
                metadata: [
                    'method' => $method,
                    'path' => $path,
                    'status_code' => $statusCode,
                    'endpoint' => $request->url(),
                ],
                status: $status,
                errorMessage: $status === 'failed' ? "HTTP {$statusCode}" : null
            );
        } catch (\Exception $e) {
            // Don't let audit failures break the request
            \Log::error('Audit middleware failed: ' . $e->getMessage());
        }
    }

    /**
     * Determine the action based on HTTP method and path
     */
    private function determineAction(string $method, string $path): string
    {
        $pathSegments = explode('/', $path);
        $lastSegment = end($pathSegments);

        return match ($method) {
            'GET' => 'READ',
            'POST' => match (true) {
                str_contains($path, 'auth/login') => 'LOGIN',
                str_contains($path, 'auth/register') => 'REGISTER',
                str_contains($path, 'auth/logout') => 'LOGOUT',
                str_contains($path, 'upload/') => 'FILE_UPLOAD',
                str_contains($path, 'ratings') => 'RATE',
                default => 'CREATE'
            },
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'UNKNOWN'
        };
    }

    /**
     * Determine the resource type based on the path
     */
    private function determineResourceType(string $path): string
    {
        return match (true) {
            str_contains($path, 'users') => 'User',
            str_contains($path, 'jobs') => 'Job',
            str_contains($path, 'applications') => 'JobApplication',
            str_contains($path, 'portfolio') => 'Portfolio',
            str_contains($path, 'payments') => 'Payment',
            str_contains($path, 'notifications') => 'Notification',
            str_contains($path, 'ratings') => 'Rating',
            str_contains($path, 'categories') => 'Category',
            str_contains($path, 'upload/') => 'File',
            str_contains($path, 'auth/') => 'Authentication',
            str_contains($path, 'admin/') => 'Admin',
            default => 'API'
        };
    }

    /**
     * Determine if the request should be skipped
     */
    private function shouldSkipAudit(string $path): bool
    {
        $skipPaths = [
            'api/v1/notifications',
            'api/v1/categories',
            'api/v1/jobs', // GET requests for listing
            'api/v1/portfolio', // GET requests for viewing
        ];

        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the status based on HTTP status code
     */
    private function determineStatus(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'success',
            $statusCode >= 400 && $statusCode < 500 => 'failed',
            $statusCode >= 500 => 'error',
            default => 'unknown'
        };
    }
}