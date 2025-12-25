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
     * Load settings using ConfigurationService
     */
    protected function loadSettings()
    {
        // Use the new ConfigurationService
        $this->baseUrl = \Shared\Services\ConfigurationService::get('ai.ollama.url', env('OLLAMA_URL', 'http://ollama:11434'));
        $this->model = \Shared\Services\ConfigurationService::get('ai.ollama.model.default', env('OLLAMA_MODEL', 'mistral'));
        $this->matchingModel = \Shared\Services\ConfigurationService::get('ai.ollama.model.matching', $this->model);
        $this->questionnaireModel = \Shared\Services\ConfigurationService::get('ai.ollama.model.questionnaire', $this->matchingModel);
        
        Log::info('OllamaService settings loaded via ConfigurationService', [
            'url' => $this->baseUrl,
            'model' => $this->model,
            'matching_model' => $this->matchingModel,
            'questionnaire_model' => $this->questionnaireModel
        ]);
    }

    /**
     * Refresh settings (invalidate cache)
     */
    public function refreshSettings()
    {
        \Shared\Services\ConfigurationService::refresh();
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
            
            // Get generation parameters from config
            $timeout = (int) \Shared\Services\ConfigurationService::get('ai.generation.timeout', 300);
            $temperature = (float) \Shared\Services\ConfigurationService::get('ai.generation.temperature', 0.7);
            $contextLength = (int) \Shared\Services\ConfigurationService::get('ai.generation.context_length', 8192);
            
            $response = Http::timeout($timeout)->post("{$this->baseUrl}/api/generate", [
                'model' => $modelName,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_ctx' => $contextLength,
                    'temperature' => $temperature,
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
