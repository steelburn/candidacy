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
 * 
 * Usage in service routes:
 *   Route::middleware('tenant')->group(function () {
 *       // All routes in this group are tenant-scoped
 *   });
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
        // Priority 1: Explicit header (for API clients and tenant switching)
        if ($headerId = $request->header('X-Tenant-ID')) {
            return (int) $headerId;
        }

        // Priority 2: From authenticated user
        if ($user = $request->user()) {
            // First check JWT claims (faster, no DB lookup)
            if (method_exists($user, 'getJWTCustomClaims')) {
                // JWT claims are already validated, so we trust them
                $token = request()->bearerToken();
                if ($token) {
                    try {
                        $payload = auth()->payload();
                        if ($payload && $payload->get('tenant_id')) {
                            return (int) $payload->get('tenant_id');
                        }
                    } catch (\Exception $e) {
                        // Fall through to current_tenant_id
                    }
                }
            }

            // Fallback to user's current_tenant_id
            if (isset($user->current_tenant_id) && $user->current_tenant_id) {
                return (int) $user->current_tenant_id;
            }
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
        // Clear tenant context after request
        if (app()->bound('tenant.id')) {
            app()->forgetInstance('tenant.id');
        }
    }
}
