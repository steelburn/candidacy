<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to validate JWT tokens from the API Gateway.
 * 
 * This middleware validates JWT tokens by calling the auth-service.
 * It does NOT trust X-User-ID headers directly as they could be spoofed
 * if the gateway doesn't properly verify JWT signatures.
 */
class ValidateJwtFromGateway
{
    /**
     * Auth service URL - should be configured via environment
     */
    protected string $authServiceUrl;

    public function __construct()
    {
        $this->authServiceUrl = env('AUTH_SERVICE_URL', 'http://auth-service:8080');
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // SECURITY: Always validate JWT tokens - never trust X-User-ID headers
        // directly as they could be spoofed if gateway doesn't verify signatures.
        
        // Check if this is a public route that doesn't require authentication
        // Skip validation for public endpoints if configured
        $token = $request->bearerToken();
        
        if (!$token && !$request->hasHeader('Authorization')) {
            // Check if authentication is required for this route
            // Default to requiring auth unless explicitly disabled
            if ($this->isAuthDisabledForRoute($request)) {
                return $next($request);
            }
            
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        // Extract token from Authorization header if present
        $token = $request->bearerToken() 
            ?? ($request->hasHeader('Authorization') 
                ? substr($request->header('Authorization'), 7) 
                : null);

        if (empty($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token'
            ], 401);
        }

        // Validate token by calling auth-service
        try {
            $response = Http::timeout(10)->post("{$this->authServiceUrl}/api/auth/validate", [
                'token' => $token
            ]);

            if ($response->successful()) {
                $userData = $response->json();
                
                // Validate response structure before accessing data
                if (!isset($userData['valid']) || $userData['valid'] !== true) {
                    Log::warning('JWT validation response invalid', ['response' => $userData]);
                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'Invalid token response'
                    ], 401);
                }
                
                $userId = $userData['id'] ?? $userData['sub'] ?? null;

                if ($userId) {
                    // Set the user ID header for downstream use
                    $request->headers->set('X-User-ID', (string) $userId);
                    Log::debug('JWT validated via auth-service', ['user_id' => $userId]);
                    return $next($request);
                }
            }

            Log::warning('JWT validation failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token'
            ], 401);

        } catch (\Exception $e) {
            Log::error('Failed to validate JWT with auth-service', [
                'error' => $e->getMessage()
            ]);

            // Only allow fallback in development with explicit config
            if (app()->isLocal() && env('AUTH_SERVICE_SKIP_VALIDATION', false)) {
                Log::warning('Auth service unavailable - allowing request in dev mode');
                return $next($request);
            }

            return response()->json([
                'error' => 'Service unavailable',
                'message' => 'Could not validate authentication'
            ], 503);
        }
    }
    
    /**
     * Check if authentication is disabled for specific routes.
     * 
     * @param Request $request
     * @return bool
     */
    protected function isAuthDisabledForRoute(Request $request): bool
    {
        // Allow health check endpoints without authentication
        $path = $request->path();
        $publicPaths = [
            'health',
            'api/health',
            'api/status',
        ];
        
        foreach ($publicPaths as $publicPath) {
            if (str_starts_with($path, $publicPath)) {
                return true;
            }
        }
        
        // Check for explicit disable in config
        return env('AUTH_REQUIRED', true) === false;
    }
}
