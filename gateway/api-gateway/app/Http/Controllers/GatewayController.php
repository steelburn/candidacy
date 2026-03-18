<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        'tenants' => 'tenant-service',      // Tenant management (CRUD, switch, members)
        'invitations' => 'tenant-service',  // Invitation accept/reject flows
    ];

    public function handle(Request $request, $path)
    {
        // $path comes from route /{any} where prefix 'api' is already handled or stripped?
        // RouteServiceProvider maps 'api' prefix to routes/api.php.
        // So if request is /api/candidates/parse-cv
        // Route is /candidates/parse-cv (relative to group) if I define Route::any('{any}').
        
        // Normalize path by removing leading api/ if present
        $path = preg_replace('/^api\//', '', $path);
        
        $segments = explode('/', $path);
        $serviceKey = $segments[0];

        $serviceName = $this->services[$serviceKey] ?? null;

        if (!$serviceName) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        // Target URL
        // We assume all services listen on port 8080 and have 'api' prefix internally? 
        // Based on Step 268 `candidate-service/routes/api.php` has routes like `/candidates/parse-cv`.
        // And RouteServiceProvider usually adds `api` prefix.
        // So target is http://service:8080/api/path
        
        $targetUrl = "http://{$serviceName}:8080/api/{$path}";
        
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
            $headers = collect($request->header())
                ->except(['content-type', 'content-length', 'host'])
                ->all();

            // Extract and verify JWT if present
            $token = $request->bearerToken();
            if ($token) {
                try {
                    $jwtValid = $this->verifyJwtSignature($token);
                    
                    if ($jwtValid) {
                        $segments = explode('.', $token);
                        if (count($segments) === 3) {
                            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $segments[1])), true);
                            
                            if (isset($payload['sub'])) {
                                $headers['X-User-ID'] = $payload['sub'];
                            }
                            
                            if (isset($payload['tenant_id'])) {
                                $headers['X-Tenant-ID'] = $payload['tenant_id'];
                            }
                            
                            Log::info("Injected auth headers from verified JWT", [
                                'user_id' => $payload['sub'] ?? 'none',
                                'tenant_id' => $payload['tenant_id'] ?? 'none'
                            ]);
                        }
                    } else {
                        Log::warning("JWT signature verification failed - not injecting auth headers");
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to process JWT for header injection: " . $e->getMessage());
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
    
    /**
     * Verify JWT signature using the shared JWT_SECRET.
     * 
     * This ensures the gateway only trusts JWT tokens that were signed
     * by the auth-service with the correct secret.
     *
     * @param string $token
     * @return bool
     */
    protected function verifyJwtSignature(string $token): bool
    {
        $segments = explode('.', $token);
        
        if (count($segments) !== 3) {
            return false;
        }
        
        [$header, $payload, $signature] = $segments;
        
        // Get JWT secret from environment
        $secret = env('JWT_SECRET');
        
        if (empty($secret)) {
            Log::warning('JWT_SECRET not configured in gateway - rejecting all tokens');
            return false;
        }
        
        // Compute expected signature using HS256
        $rawSignature = hash_hmac('sha256', "{$header}.{$payload}", $secret, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawSignature));
        
        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }
}
