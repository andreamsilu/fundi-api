<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ErrorController extends Controller
{
    /**
     * Handle 404 errors for API routes
     */
    public function notFound(Request $request): JsonResponse
    {
        Log::warning('API endpoint not found', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Endpoint not found',
            'error' => 'The requested API endpoint does not exist',
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 404);
    }

    /**
     * Handle 405 Method Not Allowed errors
     */
    public function methodNotAllowed(Request $request): JsonResponse
    {
        Log::warning('API method not allowed', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Method not allowed',
            'error' => 'The HTTP method is not allowed for this endpoint',
            'path' => $request->path(),
            'method' => $request->method(),
            'allowed_methods' => $this->getAllowedMethods($request->path()),
            'timestamp' => now()->toISOString()
        ], 405);
    }

    /**
     * Handle 500 Internal Server Error
     */
    public function internalServerError(Request $request, \Exception $exception = null): JsonResponse
    {
        Log::error('API internal server error', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'exception' => $exception ? $exception->getMessage() : 'Unknown error',
            'trace' => $exception ? $exception->getTraceAsString() : null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
            'error' => config('app.debug') && $exception 
                ? $exception->getMessage() 
                : 'An unexpected error occurred',
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 500);
    }

    /**
     * Handle 503 Service Unavailable
     */
    public function serviceUnavailable(Request $request): JsonResponse
    {
        Log::warning('API service unavailable', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Service temporarily unavailable',
            'error' => 'The service is currently undergoing maintenance',
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
            'retry_after' => 300 // 5 minutes
        ], 503);
    }

    /**
     * Get allowed methods for a given path
     */
    private function getAllowedMethods(string $path): array
    {
        $routes = collect(\Route::getRoutes());
        $allowedMethods = [];

        foreach ($routes as $route) {
            if ($route->uri() === $path || $this->matchesRoute($route->uri(), $path)) {
                $allowedMethods = array_merge($allowedMethods, $route->methods());
            }
        }

        return array_unique(array_filter($allowedMethods, function($method) {
            return !in_array($method, ['HEAD', 'OPTIONS']);
        }));
    }

    /**
     * Check if route pattern matches the given path
     */
    private function matchesRoute(string $pattern, string $path): bool
    {
        // Simple pattern matching - in production, you might want more sophisticated matching
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }

    /**
     * Handle validation errors
     */
    public function validationError(Request $request, array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 422);
    }

    /**
     * Handle authentication errors
     */
    public function unauthorized(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
            'error' => 'Authentication required to access this resource',
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 401);
    }

    /**
     * Handle authorization errors
     */
    public function forbidden(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden',
            'error' => 'You do not have permission to access this resource',
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ], 403);
    }
}
