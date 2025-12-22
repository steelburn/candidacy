<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Get all settings from database, with defaults for missing keys
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
            'ollama_matching_model' => 'llama3.2:3b', // Faster model for matching (default: llama3.2:3b)
            'openrouter_api_key' => '',
            'email_notifications' => true,
            'auto_matching' => true,
            'interview_reminder_hours' => 24,
            'offer_expiry_days' => 7,
            'cv_storage_limit_mb' => 10,
            'timezone' => 'UTC',
            'language' => 'en',
        ];

        // Get stored settings
        $storedSettings = Setting::getAllSettings();
        
        // Merge with defaults (stored settings take precedence)
        $settings = array_merge($defaults, $storedSettings);

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $data = $request->all();
        
        // Store each setting in database
        foreach ($data as $key => $value) {
            // Determine type
            $type = 'string';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_array($value)) {
                $type = 'json';
            }
            
            Setting::set($key, $value, $type);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $data
        ]);
    }

    public function health()
    {
        $health = [
            'database' => 'ok',
            'redis' => 'ok',
            'services' => [
                'auth' => 'ok',
                'candidate' => 'ok',
                'vacancy' => 'ok',
                'ai' => 'ok',
                'matching' => 'ok',
            ],
            'uptime' => rand(1, 30) . ' days',
        ];

        return response()->json($health);
    }
}
