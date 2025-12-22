<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    protected $baseUrl;
    protected $model;
    protected $matchingModel;
    protected $questionnaireModel;
    protected $settingsCacheKey = 'ollama_service_settings';
    protected $settingsCacheTTL = 300; // 5 minutes

    public function __construct()
    {
        // Load settings on initialization
        $this->loadSettings();
    }

    /**
     * Load settings from admin service or cache
     */
    protected function loadSettings()
    {
        // Try to get from cache first
        $cached = Cache::get($this->settingsCacheKey);
        if ($cached) {
            $this->baseUrl = $cached['url'];
            $this->model = $cached['model'];
            $this->matchingModel = $cached['matching_model'] ?? $cached['model'];
            $this->questionnaireModel = $cached['questionnaire_model'] ?? $cached['matching_model'] ?? $cached['model'];
            return;
        }

        // Fetch from admin service
        try {
            $response = Http::timeout(2)->get('http://admin-service:8080/api/settings');
            
            if ($response->successful()) {
                $settings = $response->json();
                
                // Handle nested settings response
                $settingsData = $settings['settings'] ?? $settings;
                
                $this->baseUrl = $settingsData['ollama_url'] ?? env('OLLAMA_URL', 'http://ollama:11434');
                $this->model = $settingsData['ollama_model'] ?? env('OLLAMA_MODEL', 'mistral');
                $this->matchingModel = $settingsData['ollama_matching_model'] ?? $this->model;
                $this->questionnaireModel = $settingsData['ollama_questionnaire_model'] ?? $this->matchingModel;
                
                // Cache the settings
                Cache::put($this->settingsCacheKey, [
                    'url' => $this->baseUrl,
                    'model' => $this->model,
                    'matching_model' => $this->matchingModel,
                    'questionnaire_model' => $this->questionnaireModel
                ], $this->settingsCacheTTL);
                
                Log::info('OllamaService settings loaded', [
                    'url' => $this->baseUrl,
                    'model' => $this->model,
                    'matching_model' => $this->matchingModel,
                    'questionnaire_model' => $this->questionnaireModel,
                    'source' => 'admin-service'
                ]);
            } else {
                throw new \Exception('Failed to fetch settings: ' . $response->status());
            }
        } catch (\Exception $e) {
            // Fallback to environment variables
            $this->baseUrl = env('OLLAMA_URL', 'http://ollama:11434');
            $this->model = env('OLLAMA_MODEL', 'mistral');
            $this->matchingModel = env('OLLAMA_MATCHING_MODEL', $this->model);
            $this->questionnaireModel = env('OLLAMA_QUESTIONNAIRE_MODEL', $this->matchingModel);
            
            Log::warning('Failed to fetch Ollama settings: ' . $e->getMessage() . '. Using env defaults.');
        }
    }

    /**
     * Refresh settings from admin service (clears cache)
     */
    public function refreshSettings()
    {
        Cache::forget($this->settingsCacheKey);
        $this->loadSettings();
    }

    /**
     * Get current configuration
     */
    public function getConfig()
    {
        // Reload settings to ensure we have fresh config
        $this->loadSettings();
        
        return [
            'url' => $this->baseUrl,
            'model' => $this->model,
            'matching_model' => $this->matchingModel,
            'questionnaire_model' => $this->questionnaireModel
        ];
    }

    /**
     * Get the primary model name
     */
    public function getModelName(): string
    {
        return $this->model;
    }

    /**
     * Get the matching model name
     */
    public function getMatchingModelName(): string
    {
        return $this->matchingModel;
    }

    /**
     * Generate using the primary model (for CV parsing, JD generation)
     */
    public function generate(string $prompt): string
    {
        $this->loadSettings();
        return $this->generateWithModel($prompt, $this->model);
    }

    /**
     * Generate using the matching model (faster for candidate matching)
     */
    public function generateForMatching(string $prompt): string
    {
        $this->loadSettings();
        return $this->generateWithModel($prompt, $this->matchingModel);
    }

    /**
     * Generate using the questionnaire model (for interview questions)
     */
    public function generateForQuestionnaire(string $prompt): string
    {
        $this->loadSettings();
        return $this->generateWithModel($prompt, $this->questionnaireModel);
    }

    /**
     * Get the questionnaire model name
     */
    public function getQuestionnaireModelName(): string
    {
        return $this->questionnaireModel;
    }

    /**
     * Generate with a specific model
     */
    protected function generateWithModel(string $prompt, string $modelName): string
    {
        // Reload settings before each request to pick up changes
        $this->loadSettings();
        
        try {
            Log::info("Calling Ollama API", [
                'url' => "{$this->baseUrl}/api/generate",
                'model' => $modelName,
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);
            
            $response = Http::timeout(90)->post("{$this->baseUrl}/api/generate", [
                'model' => $modelName,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_ctx' => 8192,
                    'temperature' => 0.7,
                    'repeat_penalty' => 1.5,
                    'top_k' => 40,
                    'top_p' => 0.9,
                ]
            ]);

            Log::info("Ollama response received", [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'model' => $modelName
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['response'] ?? '';
                
                Log::info("Ollama response parsed", [
                    'response_length' => strlen($text),
                    'has_content' => !empty($text),
                    'response_preview' => substr($text, 0, 100) . '...',
                    'model' => $modelName
                ]);
                
                return $text;
            }

            Log::error("Ollama API error", [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $modelName
            ]);
            
            return '';
        } catch (\Exception $e) {
            Log::error('Ollama API Exception: ' . $e->getMessage() . ' (model: ' . $modelName . ')');
            return '';
        }
    }
}
