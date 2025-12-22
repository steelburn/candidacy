<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected $apiKey;
    protected $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    protected $model;

    public function __construct()
    {
        // Try to get API key from admin settings first
        try {
            $response = Http::timeout(2)->get('http://admin-service:8080/api/settings/openrouter_api_key');
            if ($response->successful()) {
                $setting = $response->json();
                $this->apiKey = $setting['value'] ?? env('OPENROUTER_API_KEY');
            } else {
                $this->apiKey = env('OPENROUTER_API_KEY');
            }
        } catch (\Exception $e) {
            // Fallback to environment variable
            $this->apiKey = env('OPENROUTER_API_KEY');
        }
        
        $this->model = env('OPENROUTER_MODEL', 'mistralai/mistral-7b-instruct');
        
        Log::info('OpenRouterService initialized', [
            'has_api_key' => !empty($this->apiKey),
            'model' => $this->model
        ]);
    }

    /**
     * Get the model name
     */
    public function getModelName(): string
    {
        return $this->model;
    }

    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenRouter API key not configured');
            return '';
        }

        try {
            Log::info("Calling OpenRouter API", [
                'model' => $this->model,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => env('APP_URL', 'http://localhost'),
                'X-Title' => 'Candidacy AI Recruitment'
            ])->timeout(90)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
            ]);

            Log::info("OpenRouter response received", [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';
                
                Log::info("OpenRouter response parsed", [
                    'response_length' => strlen($text),
                    'has_content' => !empty($text)
                ]);
                
                return $text;
            }

            Log::error("OpenRouter API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return '';
        } catch (\Exception $e) {
            Log::error('OpenRouter API Exception: ' . $e->getMessage());
            return '';
        }
    }
}
