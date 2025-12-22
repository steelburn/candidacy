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
        'portal' => 'candidate-service', // Candidate service handles portal logic
        'notifications' => 'notification-service',
    ];

    public function handle(Request $request, $path)
    {
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
            $response = Http::timeout(300)->withHeaders($request->header())
                ->withBody($request->getContent(), $request->header('Content-Type'))
                ->send($request->method(), $targetUrl);

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
