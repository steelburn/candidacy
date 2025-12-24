<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For API-only services, always return null to trigger JSON response.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
