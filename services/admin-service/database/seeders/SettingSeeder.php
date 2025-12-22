<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app_name', 'value' => 'Candidacy', 'type' => 'string', 'category' => 'general'],
            ['key' => 'company_name', 'value' => 'Generic Corp', 'type' => 'string', 'category' => 'general'],
            ['key' => 'contact_email', 'value' => 'hr@example.com', 'type' => 'string', 'category' => 'general'],
            ['key' => 'candidate_portal_url', 'value' => 'http://localhost:5173', 'type' => 'string', 'category' => 'general'],
            ['key' => 'login_background_image', 'value' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920', 'type' => 'string', 'category' => 'general'],
            
            // AI
            ['key' => 'enable_ai', 'value' => true, 'type' => 'boolean', 'category' => 'ai'],
            ['key' => 'ai_provider', 'value' => 'ollama', 'type' => 'string', 'category' => 'ai'],
            ['key' => 'match_threshold', 'value' => 70, 'type' => 'integer', 'category' => 'ai'],
            ['key' => 'matching_model', 'value' => 'gemma2:2b', 'type' => 'string', 'category' => 'ai'],
            ['key' => 'questionnaire_model', 'value' => 'gemma2:2b', 'type' => 'string', 'category' => 'ai'],
            
            // Uploads
            ['key' => 'max_upload_size', 'value' => 10, 'type' => 'integer', 'category' => 'system'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
