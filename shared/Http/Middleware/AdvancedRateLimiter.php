<?php

namespace Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Advanced Rate Limiting Middleware
 * 
 * Provides tiered rate limiting based on authentication status and endpoint type
 */
class AdvancedRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $tier  Rate limit tier: 'public', 'authenticated', 'admin'
     */
    public function handle(Request $request, Closure $next, string $tier = 'authenticated'): Response
    {
        $key = $this->resolveRequestSignature($request, $tier);
        $limits = $this->getLimitsForTier($tier);

        $executed = RateLimiter::attempt(
            $key,
            $limits['maxAttempts'],
            function() {},
            $limits['decaySeconds']
        );

        if (!$executed) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        $response = $next($request);

        // Add rate limit headers
        $remaining = RateLimiter::remaining($key, $limits['maxAttempts']);
        $response->headers->add([
            'X-RateLimit-Limit' => $limits['maxAttempts'],
            'X-RateLimit-Remaining' => max(0, $remaining),
        ]);

        return $response;
    }

    /**
     * Resolve the request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request, string $tier): string
    {
        $user = $request->user();
        
        if ($user) {
            return "rate-limit:{$tier}:user:{$user->id}";
        }

        return "rate-limit:{$tier}:ip:{$request->ip()}";
    }

    /**
     * Get rate limits for the specified tier
     */
    protected function getLimitsForTier(string $tier): array
    {
        $limits = [
            'public' => [
                'maxAttempts' => 60,    // 60 requests
                'decaySeconds' => 60,   // per minute
            ],
            'authenticated' => [
                'maxAttempts' => 120,   // 120 requests
                'decaySeconds' => 60,   // per minute
            ],
            'admin' => [
                'maxAttempts' => 300,   // 300 requests
                'decaySeconds' => 60,   // per minute
            ],
        ];

        return $limits[$tier] ?? $limits['authenticated'];
    }
}
