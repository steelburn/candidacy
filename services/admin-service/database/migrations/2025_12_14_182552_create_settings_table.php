<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('settings')->insert([
            [
                'key' => 'app_name',
                'value' => 'Candidacy - AI Recruitment',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Application name',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'company_name',
                'value' => 'Your Company',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Company name',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'contact_email',
                'value' => 'hr@company.com',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Contact email',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enable_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'features',
                'description' => 'Enable email notifications',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'enable_ai',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'features',
                'description' => 'Enable AI features',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_upload_size',
                'value' => '10',
                'type' => 'integer',
                'category' => 'files',
                'description' => 'Max upload size in MB',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'ai_provider',
                'value' => 'ollama',
                'type' => 'string',
                'category' => 'ai',
                'description' => 'AI provider (ollama or openrouter)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'openrouter_api_key',
                'value' => '',
                'type' => 'string',
                'category' => 'ai',
                'description' => 'OpenRouter API key (optional)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
