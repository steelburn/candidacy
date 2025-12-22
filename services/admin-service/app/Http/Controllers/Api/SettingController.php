<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
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
            
            // Handle flat key-value format
            foreach ($data as $key => $value) {
                $setting = Setting::where('key', $key)->first();
                
                if ($setting) {
                    // Update existing setting
                    $setting->update(['value' => $value]);
                } else {
                    // Create new setting if it doesn't exist
                    Setting::create([
                        'key' => $key,
                        'value' => $value,
                        'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string'),
                        'category' => 'general'
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => Setting::getAllSettings()
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
        $services = [
            'auth-service' => 'http://auth-service:8080/api/health',
            'candidate-service' => 'http://candidate-service:8080/api/health',
            'vacancy-service' => 'http://vacancy-service:8080/api/health',
            'ai-service' => 'http://ai-service:8080/api/health',
            'matching-service' => 'http://matching-service:8080/api/health',
            'interview-service' => 'http://interview-service:8080/api/health',
            'offer-service' => 'http://offer-service:8080/api/health',
            'onboarding-service' => 'http://onboarding-service:8080/api/health',
            'reporting-service' => 'http://reporting-service:8080/api/health',
            'notification-service' => 'http://notification-service:8080/api/health',
        ];

        $health = [];
        
        foreach ($services as $service => $url) {
            try {
                $start = microtime(true);
                $response = @file_get_contents($url, false, stream_context_create([
                    'http' => ['timeout' => 2]
                ]));
                $time = round((microtime(true) - $start) * 1000);
                
                $health[] = [
                    'service' => $service,
                    'status' => $response !== false ? 'online' : 'offline',
                    'response_time' => $response !== false ? $time : null
                ];
            } catch (\Exception $e) {
                $health[] = [
                    'service' => $service,
                    'status' => 'offline',
                    'response_time' => null
                ];
            }
        }

        return response()->json([
            'services' => $health,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
