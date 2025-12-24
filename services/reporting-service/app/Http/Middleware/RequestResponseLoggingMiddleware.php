<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to log detailed HTTP request and response information
 */
class RequestResponseLoggingMiddleware
{
    /**
     * List of sensitive fields to redact from logs
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'authorization',
        'credit_card',
        'cvv',
        'ssn',
    ];

    /**
     * Maximum body size to log (in bytes)
     */
    private const MAX_BODY_SIZE = 10000;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log request
        $this->logRequest($request);
        
        // Process request
        $response = $next($request);
        
        // Calculate duration
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log response
        $this->logResponse($request, $response, $duration);
        
        return $response;
    }

    /**
     * Log incoming request details
     */
    private function logRequest(Request $request): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ];

        // Add authenticated user info if available
        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
            $data['user_email'] = $request->user()->email ?? null;
        }

        // Add request body for non-GET requests
        if (!$request->isMethod('GET')) {
            $body = $request->all();
            $data['body'] = $this->sanitizeData($body);
            
            // Truncate if too large
            $bodyJson = json_encode($data['body']);
            if (strlen($bodyJson) > self::MAX_BODY_SIZE) {
                $data['body'] = '[Body too large - ' . strlen($bodyJson) . ' bytes]';
            }
        }

        // Add query parameters
        if ($request->query()) {
            $data['query'] = $request->query();
        }

        Log::info('HTTP Request', $data);
    }

    /**
     * Log response details
     */
    private function logResponse(Request $request, Response $response, float $duration): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ];

        // Add response body for non-binary responses
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/')) {
            $content = $response->getContent();
            
            if (strlen($content) > self::MAX_BODY_SIZE) {
                $data['response'] = '[Response too large - ' . strlen($content) . ' bytes]';
            } else {
                // Try to decode JSON for better logging
                $decoded = json_decode($content, true);
                $data['response'] = $decoded ?? $content;
            }
        }

        // Log at different levels based on status code
        if ($response->getStatusCode() >= 500) {
            Log::error('HTTP Response', $data);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('HTTP Response', $data);
        } else {
            Log::info('HTTP Response', $data);
        }
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, self::SENSITIVE_FIELDS)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize data to remove sensitive fields
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, self::SENSITIVE_FIELDS)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
