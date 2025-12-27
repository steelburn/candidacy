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
    ];

    public function handle(Request $request, $path)
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            $origin = $request->header('Origin');
            $allowedOrigins = ['http://localhost:3001', 'http://localhost:3002'];
            
            $response = response('', 200);
            
            if (in_array($origin, $allowedOrigins)) {
                $response->header('Access-Control-Allow-Origin', $origin);
                $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
                $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                $response->header('Access-Control-Allow-Credentials', 'true');
                $response->header('Access-Control-Max-Age', '86400');
            }
            
            return $response;
        }
        
        // $path comes from route /{any} where prefix 'api' is already handled or stripped?
        // RouteServiceProvider maps 'api' prefix to routes/api.php.
        // So if request is /api/candidates/parse-cv
        // Route is /candidates/parse-cv (relative to group) if I define Route::any('{any}').
        
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
            // Forward request
            $headers = collect($request->header())
                ->except(['content-type', 'content-length', 'host'])
                ->all();

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
                // Skip headers that might conflict
                if (!in_array(strtolower($key), ['transfer-encoding', 'content-encoding'])) {
                    $proxyResponse->header($key, $values);
                }
            }
            
            // Ensure CORS headers are present (Laravel's HandleCors middleware should add these)
            // But we explicitly add them here as backup
            if ($request->header('Origin')) {
                $allowedOrigins = ['http://localhost:3001', 'http://localhost:3002'];
                $origin = $request->header('Origin');
                
                if (in_array($origin, $allowedOrigins)) {
                    $proxyResponse->header('Access-Control-Allow-Origin', $origin);
                    $proxyResponse->header('Access-Control-Allow-Credentials', 'true');
                }
            }

            return $proxyResponse;

        } catch (\Exception $e) {
            Log::error("Gateway Error: " . $e->getMessage());
            return response()->json(['error' => 'Gateway Error'], 502);
        }
    }
}
