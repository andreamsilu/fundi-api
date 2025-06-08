<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        // Sanitize all string inputs
        $input = $this->sanitizeArray($input);

        // Replace the request input with sanitized data
        $request->merge($input);

        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs.
     *
     * @param  array  $input
     * @return array
     */
    protected function sanitizeArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $input[$key] = $this->sanitizeString($value);
            }
        }

        return $input;
    }

    /**
     * Sanitize a string input.
     *
     * @param  string  $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove any potential SQL injection attempts
        $value = $this->removeSqlInjection($value);

        // Remove any potential XSS attempts
        $value = $this->removeXss($value);

        // Remove any potential command injection attempts
        $value = $this->removeCommandInjection($value);

        return $value;
    }

    /**
     * Remove potential SQL injection attempts.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeSqlInjection(string $value): string
    {
        // Remove common SQL injection patterns
        $patterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|TRUNCATE)\b/i',
            '/[\'";]/',
            '/--/',
            '/\/\*.*?\*\//',
            '/\b(OR|AND)\s+\d+=\d+/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
            '/\b(OR|AND)\s+\w+\s*=\s*\w+/i',
        ];

        return preg_replace($patterns, '', $value);
    }

    /**
     * Remove potential XSS attempts.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeXss(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);

        // Remove JavaScript events
        $value = preg_replace('/on\w+="[^"]*"/', '', $value);
        $value = preg_replace('/on\w+=\'[^\']*\'/', '', $value);

        // Remove JavaScript protocol
        $value = preg_replace('/javascript:/i', '', $value);

        // Remove data URLs
        $value = preg_replace('/data:/i', '', $value);

        // Remove vbscript protocol
        $value = preg_replace('/vbscript:/i', '', $value);

        // Remove any remaining script tags
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);

        return $value;
    }

    /**
     * Remove potential command injection attempts.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeCommandInjection(string $value): string
    {
        // Remove common command injection patterns
        $patterns = [
            '/[;&|`$]/',
            '/\b(cat|chmod|curl|wget|nc|netcat|bash|sh|rm|mkdir|touch|echo|printf)\b/i',
            '/\$\([^)]*\)/',
            '/`[^`]*`/',
        ];

        return preg_replace($patterns, '', $value);
    }
} 