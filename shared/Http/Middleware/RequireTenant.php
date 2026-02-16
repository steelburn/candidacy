<?php

namespace Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireTenant - Ensures a tenant context is set before proceeding
 * 
 * Use this middleware on routes that require tenant context.
 * It will return a 400 error if no tenant is set.
 * 
 * Usage:
 *   Route::middleware(['tenant', 'require-tenant'])->group(function () {
 *       // Routes that require a tenant
 *   });
 */
class RequireTenant
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
        if (!app()->bound('tenant.id') || !app('tenant.id')) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context is required. Please provide X-Tenant-ID header or ensure you have a current tenant set.',
                'error' => 'TENANT_REQUIRED'
            ], 400);
        }

        return $next($request);
    }
}
