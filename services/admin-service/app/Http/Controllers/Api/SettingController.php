<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SettingController extends BaseApiController
{
    /**
     * Get all settings
     */
    public function index()
    {
        $defaults = [
            'app_name' => 'Candidacy',
            'company_name' => 'Candidacy Inc.',
            'contact_email' => '',
            'candidate_portal_url' => '',
            'enable_notifications' => true,
            'enable_ai' => true,
            'max_upload_size' => 10,
            'ai_provider' => 'ollama',
            'ollama_url' => 'http://ollama:11434',
            'ollama_model' => 'mistral',
            'ollama_matching_model' => 'llama3.2:3b',
            'openrouter_api_key' => '',
        ];

        $dbSettings = Setting::getAllSettings();
        
        // Merge defaults with DB settings (DB takes precedence)
        $settings = array_merge($defaults, $dbSettings);
        
        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Get settings by category
     */
    public function getByCategory($category)
    {
        $settings = Setting::where('category', $category)->get();
        
        return response()->json([
            'category' => $category,
            'settings' => $settings->pluck('value', 'key')
        ]);
    }

    /**
     * Get single setting
     */
    public function show($key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }
        
        return response()->json($setting);
    }

    /**
     * Update settings (bulk update)
     * Accepts flat key-value pairs: {"app_name": "value", "ai_provider": "ollama"}
     */
    public function update(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $data = $request->all();
            $userId = $request->user()->id ?? null;
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
            $changes = [];
            
            // Handle flat key-value format
            foreach ($data as $key => $value) {
                $setting = Setting::where('key', $key)->first();
                
                if ($setting) {
                    // Validate value if validation rules exist
                    if (!$setting->validate($value)) {
                        DB::rollBack();
                        return response()->json([
                            'error' => "Validation failed for setting: {$key}",
                            'key' => $key
                        ], 422);
                    }
                    
                    // Log the change
                    \App\Models\SettingChangeLog::logChange(
                        $setting->id,
                        $setting->getRawOriginal('value'),
                        $value,
                        $userId,
                        $ipAddress,
                        $userAgent
                    );
                    
                    // Update existing setting
                    $setting->update([
                        'value' => $value,
                        'updated_by' => $userId,
                        'version' => $setting->version + 1
                    ]);
                    
                    $changes[$key] = $value;
                } else {
                    // Create new setting if it doesn't exist
                    $newSetting = Setting::create([
                        'key' => $key,
                        'value' => $value,
                        'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string'),
                        'category' => 'general',
                        'updated_by' => $userId
                    ]);
                    
                    $changes[$key] = $value;
                }
                
                // Invalidate cache for this key
                Setting::invalidateCache($key);
            }
            
            DB::commit();
            
            // Broadcast changes to all services
            if (!empty($changes)) {
                $broadcaster = new \App\Services\ConfigurationBroadcaster();
                $broadcaster->broadcastBulk($changes);
            }
            
            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => Setting::getAllSettings(),
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Settings update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update settings'], 500);
        }
    }

    /**
     * Update single setting
     */
    public function updateSingle(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $setting->update(['value' => $request->value]);
        
        return response()->json([
            'message' => 'Setting updated successfully',
            'setting' => $setting
        ]);
    }

    /**
     * Get system health status
     */
    public function systemHealth()
    {
        $httpServices = [
            'auth-service' => 'http://auth-service:8080/api/health',
            'api-gateway' => 'http://api-gateway:8080/api/health',
            'admin-service' => 'http://admin-service:8080/api/health',
            'candidate-service' => 'http://candidate-service:8080/api/health',
            'vacancy-service' => 'http://vacancy-service:8080/api/health',
            'ai-service' => 'http://ai-service:8080/api/health',
            'matching-service' => 'http://matching-service:8080/api/health',
            'interview-service' => 'http://interview-service:8080/api/health',
            'offer-service' => 'http://offer-service:8080/api/health',
            'onboarding-service' => 'http://onboarding-service:8080/api/health',
            'reporting-service' => 'http://reporting-service:8080/api/health',
            'notification-service' => 'http://notification-service:8080/api/health',
            'document-parser-service' => 'http://document-parser-service:8080/api/health',
            // Frontends
            'candidacy-frontend' => 'http://frontend:3000',
            'candidacy-applicant-frontend' => 'http://applicant-frontend:3000',
        ];

        // Workers - verify queue connectivity
        $workers = [
            'candidate-queue-worker' => 'candidate_queue',
            'document-parser-worker' => 'document_parser_queue',
            'matching-queue-worker' => 'matching_queue',
            'notification-queue-worker' => 'notification_queue',
        ];

        $health = [];
        
        // Check HTTP Services
        foreach ($httpServices as $service => $url) {
            try {
                // Special case for admin-service (self) and api-gateway (parent) to avoid deadlock in single-threaded env
                if ($service === 'admin-service' || $service === 'api-gateway') {
                    $health[] = [
                        'service' => $service,
                        'status' => 'online',
                        'response_time' => '0ms'
                    ];
                    continue;
                }

                $start = microtime(true);
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
                $time = round((microtime(true) - $start) * 1000);
                
                $health[] = [
                    'service' => $service,
                    'status' => $response->successful() ? 'online' : 'offline',
                    'response_time' => $response->successful() ? $time . 'ms' : null
                ];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Health check failed for {$service}: " . $e->getMessage());
                $health[] = [
                    'service' => $service,
                    'status' => 'offline',
                    'response_time' => null,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Check Workers
        foreach ($workers as $worker => $queue) {
            try {
                $start = microtime(true);
                // Check if we can ping Redis as proxy for worker infrastructure
                \Illuminate\Support\Facades\Redis::ping();
                $time = round((microtime(true) - $start) * 1000);
                
                $health[] = [
                    'service' => $worker,
                    'status' => 'online', // Infrastructure is reachable
                    'response_time' => $time . 'ms'
                ];
            } catch (\Exception $e) {
                $health[] = [
                    'service' => $worker,
                    'status' => 'offline',
                    'response_time' => null,
                    'error' => 'Redis unreachable'
                ];
            }
        }

        return response()->json([
            'services' => $health,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Get change history for a setting
     */
    public function history($key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $history = \App\Models\SettingChangeLog::getHistory($setting->id);

        return response()->json([
            'setting' => $setting,
            'history' => $history
        ]);
    }

    /**
     * Get settings by service scope
     */
    public function getByScope($scope)
    {
        $settings = Setting::where('service_scope', 'like', "%{$scope}%")->get();
        
        return response()->json([
            'scope' => $scope,
            'settings' => $settings
        ]);
    }

    /**
     * Get all settings with full details (for admin UI)
     */
    public function getAllDetailed()
    {
        $settings = Setting::all()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->is_sensitive ? $setting->getMaskedValue() : $setting->value,
                'raw_value' => $setting->is_sensitive ? null : $setting->value,
                'type' => $setting->type,
                'category' => $setting->category,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'is_sensitive' => $setting->is_sensitive,
                'service_scope' => $setting->service_scope,
                'requires_restart' => $setting->requires_restart,
                'version' => $setting->version,
                'updated_at' => $setting->updated_at
            ];
        });

        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Export all settings as JSON
     */
    public function export()
    {
        $settings = Setting::all()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->getRawOriginal('value'),
                'type' => $setting->type,
                'category' => $setting->category,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'is_sensitive' => $setting->is_sensitive,
                'service_scope' => $setting->service_scope,
                'requires_restart' => $setting->requires_restart,
                'default_value' => $setting->default_value,
                'validation_rules' => $setting->validation_rules
            ];
        });

        return response()->json([
            'settings' => $settings,
            'exported_at' => now()->toIso8601String(),
            'count' => $settings->count()
        ]);
    }

    /**
     * Import settings from JSON
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $userId = $request->user()->id ?? null;
            $imported = 0;
            $skipped = 0;

            foreach ($request->settings as $settingData) {
                $existing = Setting::where('key', $settingData['key'])->first();

                if ($existing) {
                    // Update existing
                    $existing->update([
                        'value' => $settingData['value'],
                        'type' => $settingData['type'] ?? $existing->type,
                        'category' => $settingData['category'] ?? $existing->category,
                        'description' => $settingData['description'] ?? $existing->description,
                        'updated_by' => $userId,
                        'version' => $existing->version + 1
                    ]);
                    $imported++;
                } else {
                    // Create new
                    Setting::create(array_merge($settingData, [
                        'updated_by' => $userId
                    ]));
                    $imported++;
                }

                Setting::invalidateCache($settingData['key']);
            }

            DB::commit();

            // Broadcast reload signal
            $broadcaster = new \App\Services\ConfigurationBroadcaster();
            $broadcaster->broadcastReload();

            return response()->json([
                'message' => 'Settings imported successfully',
                'imported' => $imported,
                'skipped' => $skipped
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Settings import failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to import settings'], 500);
        }
    }
}
