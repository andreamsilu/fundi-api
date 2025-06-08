<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter as LaravelRateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Lang;

class ApiRateLimiter
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new rate limiter middleware.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(LaravelRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $key, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $limiterKey = $this->resolveRequestSignature($request, $key);

        if ($this->limiter->tooManyAttempts($limiterKey, $maxAttempts)) {
            return $this->buildResponse($limiterKey, $maxAttempts);
        }

        $this->limiter->hit($limiterKey, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($limiterKey, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $key): string
    {
        if ($request->user()) {
            return sha1($key . '|' . $request->user()->id);
        }

        return sha1($key . '|' . $request->ip());
    }

    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'message' => 'Too Many Attempts.',
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts,
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => $this->getTimeUntilNextAttempt($key),
        ]);
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key);
    }

    /**
     * Get the number of seconds until the next attempt.
     *
     * @param  string  $key
     * @return int
     */
    protected function getTimeUntilNextAttempt(string $key): int
    {
        return $this->limiter->availableIn($key);
    }
} 