<?php

namespace Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Validation Middleware
 * 
 * Validates common request parameters and sanitizes input
 */
class ValidateRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate pagination parameters if present
        if ($request->has(['page', 'per_page'])) {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid pagination parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        // Validate sorting parameters if present
        if ($request->has('sort_by')) {
            $validator = Validator::make($request->all(), [
                'sort_by' => 'string|max:50',
                'sort_order' => 'in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid sorting parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        // Validate search parameters if present
        if ($request->has('search')) {
            $validator = Validator::make($request->all(), [
                'search' => 'string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid search parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        // Sanitize string inputs to prevent XSS
        $this->sanitizeInputs($request);

        return $next($request);
    }

    /**
     * Sanitize request inputs
     */
    protected function sanitizeInputs(Request $request): void
    {
        $inputs = $request->all();

        array_walk_recursive($inputs, function (&$input) {
            if (is_string($input)) {
                // Remove null bytes
                $input = str_replace("\0", '', $input);
                
                // Trim whitespace
                $input = trim($input);
            }
        });

        $request->merge($inputs);
    }
}
