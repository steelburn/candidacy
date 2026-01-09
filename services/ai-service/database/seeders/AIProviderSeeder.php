<?php

namespace Database\Seeders;

use App\Models\AIProvider;
use Illuminate\Database\Seeder;

class AIProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $providers = [
            [
                'name' => 'ollama',
                'display_name' => 'Ollama (Local)',
                'type' => 'ollama',
                'base_url' => env('OLLAMA_URL', 'http://ollama:11434'),
                'is_enabled' => true,
                'config' => [
                    'model' => env('OLLAMA_MODEL', 'mistral'),
                    'timeout' => 300,
                ]
            ],
            [
                'name' => 'openai',
                'display_name' => 'OpenAI',
                'type' => 'openai',
                'base_url' => 'https://api.openai.com/v1',
                'is_enabled' => false,
                'config' => [
                    // API Key should be set via UI for security, not seeded ideally
                    // but we seed structure
                    'model' => 'gpt-4o-mini',
                ]
            ],
            [
                'name' => 'gemini',
                'display_name' => 'Google Gemini',
                'type' => 'gemini',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'is_enabled' => false,
                'config' => [
                    'model' => 'gemini-1.5-flash',
                ]
            ],
            [
                'name' => 'azure',
                'display_name' => 'Azure OpenAI',
                'type' => 'azure',
                'base_url' => '',
                'is_enabled' => false,
                'config' => [
                    'deployment' => 'gpt-4',
                ]
            ],
            [
                'name' => 'openrouter',
                'display_name' => 'OpenRouter',
                'type' => 'openrouter',
                'base_url' => 'https://openrouter.ai/api/v1',
                'is_enabled' => false,
                'config' => [
                    'model' => 'mistralai/mistral-7b-instruct',
                ]
            ],
            [
                'name' => 'litellm',
                'display_name' => 'LiteLLM Proxy',
                'type' => 'litellm',
                'base_url' => 'http://localhost:4000',
                'is_enabled' => false,
                'config' => []
            ],
            [
                'name' => 'llamacpp',
                'display_name' => 'Llama.cpp Server',
                'type' => 'llamacpp',
                'base_url' => 'http://localhost:8080',
                'is_enabled' => false,
                'config' => []
            ]
        ];

        foreach ($providers as $provider) {
            AIProvider::updateOrCreate(
                ['name' => $provider['name']],
                $provider
            );
        }
    }
}
