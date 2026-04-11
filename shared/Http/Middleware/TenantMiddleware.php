<?php

namespace Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware - Sets the current tenant context for the request
 * 
 * This middleware resolves the tenant ID from:
 *   1. X-Tenant-ID header (for explicit tenant switching)
 *   2. JWT claims (tenant_id in token)
 *   3. User's current_tenant_id field
 * 
 * The resolved tenant ID is stored in the application container
 * for use by TenantScope and BelongsToTenant trait.
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If already bound (e.g. by a test or previous middleware), skip resolution
        if (app()->bound('tenant.id')) {
            return $next($request);
        }

        $tenantId = $this->resolveTenantId($request);

        if ($tenantId) {
            // Bind the tenant ID to the container
            app()->instance('tenant.id', (int) $tenantId);
        }

        return $next($request);
    }

    /**
     * Resolve the tenant ID from the request.
     *
     * @param Request $request
     * @return int|null
     */
    protected function resolveTenantId(Request $request): ?int
    {
        // Priority 1: Explicit header (Fastest & used in tests)
        if ($headerId = $request->header('X-Tenant-ID')) {
            return (int) $headerId;
        }

        // Priority 2: From authenticated user / JWT
        if ($user = $request->user()) {
            return $user->current_tenant_id ?? $user->tenant_id;
        }

        // Priority 3: Extract from JWT token if no user is resolved yet (or for context discovery)
        try {
            // Check for bearer token without triggering guard initialization
            $token = $request->bearerToken();
            if ($token && class_exists(\Tymon\JWTAuth\Facades\JWTAuth::class)) {
                // Use the facade directly with a check to avoid triggering .env scans if not configured
                if (\Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->check(false)) {
                    $payload = \Tymon\JWTAuth\Facades\JWTAuth::getPayload();
                    if ($payload && $payload->get('tenant_id')) {
                        return (int) $payload->get('tenant_id');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fail - avoid crashing or triggering file_get_contents warnings
        }

        return null;
    }

    /**
     * Terminate the request - cleanup tenant context.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        if (app()->bound('tenant.id')) {
            app()->forgetInstance('tenant.id');
        }
    }
}
