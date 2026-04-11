<?php

namespace Shared\Http\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;

/**
 * TenantForwarding - Trait to facilitate service-to-service communication
 * 
 * Provides a helper method to create an HTTP client that automatically
 * forwards the current tenant ID and user authentication context.
 */
trait TenantForwarding
{
    /**
     * Create a new HTTP client instance with tenant and user context.
     *
     * @return PendingRequest
     */
    protected function serviceHttp(): PendingRequest
    {
        $client = Http::acceptJson();
        $tenantId = null;

        // Resolve Tenant ID from container
        if (app()->bound('tenant.id')) {
            $tenantId = app('tenant.id');
        }

        // Apply headers
        $headers = [];

        if ($tenantId) {
            $headers['X-Tenant-ID'] = (string) $tenantId;
        }

        // Forward User Identity from current request headers
        if ($userId = request()->header('X-User-ID')) {
            $headers['X-User-ID'] = (string) $userId;
        }

        // Forward Authorization Token if present
        if ($authHeader = request()->header('Authorization')) {
            $headers['Authorization'] = $authHeader;
        }

        return $client->withHeaders($headers);
    }
}
