<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class GatewayController extends Controller
{
    protected $services = [
        'auth' => 'auth-service',
        'candidates' => 'candidate-service',
        'vacancies' => 'vacancy-service',
        'matches' => 'matching-service',
        'interviews' => 'interview-service',
        'offers' => 'offer-service',
        'onboarding' => 'onboarding-service',
        'admin' => 'admin-service',
        'settings' => 'admin-service', // Admin service handles settings
        'system-health' => 'admin-service', // Admin service handles system health
        'portal' => 'candidate-service', // Candidate service handles portal logic
        'notifications' => 'notification-service',
        'notification' => 'notification-service', // Singular for template routes
        'reports' => 'reporting-service',
        'users' => 'auth-service', // Auth service handles user management
        'roles' => 'auth-service', // Auth service handles role management
        'generate-jd' => 'ai-service', // AI service handles job description generation
        'parse-cv' => 'ai-service', // AI service handles CV parsing
        'match' => 'ai-service', // AI service handles matching
        'discuss-question' => 'ai-service', // AI service handles question discussions
        'generate-questions-screening' => 'ai-service',
        'providers' => 'ai-service', // AI provider management
        'ai' => 'ai-service',
        'setup' => 'auth-service', // Auth service handles first-time setup
        'tenants' => 'tenant-service', // Tenant management (CRUD, switch, members)
        'invitations' => 'tenant-service', // Invitation accept/reject flows
    ];

    /**
     * Verify JWT token signature using Tymon JWT
     *
     * @param string $token
     * @return bool
     */
    protected function verifyJwtSignature(string $token): bool
    {
        try {
            // Attempt to parse and validate the token
            $payload = JWTAuth::parseToken()->getPayload();

            // Check if token is not expired ( JWTAuth handles this automatically)
            // If we get here, the token is valid
            return true;
        } catch (TokenExpiredException $e) {
            Log::warning("JWT token expired", ['error' => $e->getMessage()]);
            return false;
        } catch (TokenInvalidException $e) {
            Log::warning("JWT token invalid", ['error' => $e->getMessage()]);
            return false;
        } catch (JWTException $e) {
            Log::warning("JWT token error", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Extract user info from JWT payload
     *
     * @param string $token
     * @return array|null
     */
    protected function getUserFromToken(string $token): ?array
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            return [
                'sub' => $payload->get('sub'),
                'tenant_id' => $payload->get('tenant_id'),
                'email' => $payload->get('email'),
            ];
        } catch (JWTException $e) {
            Log::warning("Failed to extract user from token", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function handle(Request $request, $path)
    {
        // $path comes from route /{any} where prefix 'api' is already handled or stripped?
        // RouteServiceProvider maps 'api' prefix to routes/api.php.
        // So if request is /api/candidates/parse-cv
        // Route is /candidates/parse-cv (relative to group) if I define Route::any('{any}').

        $segments = explode('/', $path);

        // Skip 'api' prefix if present
        if (isset($segments[0]) && $segments[0] === 'api') {
            array_shift($segments);
        }

        $serviceKey = $segments[0] ?? null;

        $serviceName = $this->services[$serviceKey] ?? null;

        if (!$serviceName) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        // Determine correct port for the target service.
        // All services run on port 8080 internally in the docker network.
        $port = 8080;
        $targetUrl = "http://{$serviceName}:{$port}/api/{$path}";

        // Append query string
        if ($request->getQueryString()) {
            $targetUrl .= '?' . $request->getQueryString();
        }

        Log::info("Proxying request", [
            'method' => $request->method(),
            'url' => $targetUrl,
            'ip' => $request->ip()
        ]);

        try {
            // Forward request
            $headers = collect($request->header())
                ->except(['content-type', 'content-length', 'host'])
                ->all();

            // Extract and verify JWT, then inject X-User-ID / X-Tenant-ID for downstream services
            $token = $request->bearerToken();
            if ($token) {
                $jwtValid = $this->verifyJwtSignature($token);
                if ($jwtValid) {
                    // Use Tymon JWT's built-in method to get user info from token
                    $userInfo = $this->getUserFromToken($token);
                    if ($userInfo && isset($userInfo['sub'])) {
                        $headers['X-User-ID'] = (string) $userInfo['sub'];
                    }
                    if ($userInfo && !empty($userInfo['tenant_id'])) {
                        $headers['X-Tenant-ID'] = (string) $userInfo['tenant_id'];
                    }
                    Log::info("Injected auth headers from verified JWT", [
                        'user_id' => $userInfo['sub'] ?? 'none',
                        'tenant_id' => $userInfo['tenant_id'] ?? 'none',
                    ]);
                } else {
                    Log::warning("JWT signature verification failed - not injecting auth headers");
                }
            }

            $httpClient = Http::timeout(300)->withHeaders($headers);

            // Handle Multipart Requests (Files)
            if (str_contains($request->header('Content-Type'), 'multipart')) {
                $httpClient = $httpClient->asMultipart();

                // Attach all files
                foreach ($request->allFiles() as $name => $file) {
                    if (is_array($file)) {
                        foreach ($file as $subFile) {
                            $httpClient->attach($name . '[]', file_get_contents($subFile->getPathname()), $subFile->getClientOriginalName());
                        }
                    } else {
                        $httpClient->attach($name, file_get_contents($file->getPathname()), $file->getClientOriginalName());
                    }
                }

                // Attach other inputs
                foreach ($request->except($request->keys()) as $name => $value) {
                    // Note: http client attach also handles fields as 'contents' key map if needed, 
                    // but asMultipart() usually implies using post fields for non-file data.
                    // A safer way in Laravel Http client with multipart is just passing the data array as second arg to post()
                }

                // For multipart, we pass data in the send/post method
                $response = $httpClient->post($targetUrl, $request->except(array_keys($request->allFiles())));

            } else {
                // Handle JSON/Raw Requests
                $contentType = $request->header('Content-Type');
                $content = $request->getContent();

                if ($content && $contentType) {
                    $httpClient = $httpClient->withBody($content, $contentType);
                }

                $response = $httpClient->send($request->method(), $targetUrl);
            }

            // Build response with proper CORS headers
            $proxyResponse = response($response->body(), $response->status());

            // Add response headers from service
            foreach ($response->headers() as $key => $values) {
                // Skip headers that might conflict or duplicate CORS
                if (!in_array(strtolower($key), ['transfer-encoding', 'content-encoding', 'access-control-allow-origin', 'access-control-allow-methods', 'access-control-allow-headers', 'access-control-allow-credentials'])) {
                    $proxyResponse->header($key, $values);
                }
            }

            return $proxyResponse;

        } catch (\Exception $e) {
            Log::error("Gateway Error: " . $e->getMessage());
            return response()->json(['error' => 'Gateway Error'], 502);
        }
    }
}