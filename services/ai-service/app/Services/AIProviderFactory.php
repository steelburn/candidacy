<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIProviderFactory
{
    protected $provider;

    public function __construct()
    {
        // Get provider from admin settings via API or env
        $this->provider = $this->getActiveProvider();
    }

    /**
     * Get the active AI provider instance
     */
    public function getProvider()
    {
        return match(strtolower($this->provider)) {
            'openrouter' => new OpenRouterService(),
            'ollama' => new OllamaService(),
            default => new OllamaService()
        };
    }

    /**
     * Generate text using the active provider
     */
    public function generate(string $prompt): string
    {
        $provider = $this->getProvider();
        $providerName = $this->provider;
        
        Log::info("LLM API call initiated", [
            'provider' => $providerName,
            'model' => $provider->getModelName() ?? 'default',
            'prompt_length' => strlen($prompt),
            'operation' => 'generate'
        ]);
        
        return $provider->generate($prompt);
    }

    /**
     * Generate text for matching using faster model (if supported)
     */
    public function generateForMatching(string $prompt): string
    {
        $provider = $this->getProvider();
        $providerName = $this->provider;
        
        // Use the matching-specific method if available (OllamaService has it)
        if (method_exists($provider, 'generateForMatching')) {
            Log::info("LLM API call initiated", [
                'provider' => $providerName,
                'model' => $provider->getMatchingModelName() ?? $provider->getModelName() ?? 'default',
                'prompt_length' => strlen($prompt),
                'operation' => 'matching'
            ]);
            return $provider->generateForMatching($prompt);
        }
        
        Log::info("LLM API call initiated", [
            'provider' => $providerName,
            'model' => $provider->getModelName() ?? 'default',
            'prompt_length' => strlen($prompt),
            'operation' => 'matching_fallback'
        ]);
        
        // Fallback to regular generate
        return $provider->generate($prompt);
    }

    /**
     * Generate text for questionnaires using questionnaire model (if supported)
     */
    public function generateForQuestionnaire(string $prompt): string
    {
        $provider = $this->getProvider();
        $providerName = $this->provider;
        
        // Use the questionnaire-specific method if available (OllamaService has it)
        if (method_exists($provider, 'generateForQuestionnaire')) {
            Log::info("LLM API call initiated", [
                'provider' => $providerName,
                'model' => $provider->getQuestionnaireModelName() ?? $provider->getModelName() ?? 'default',
                'prompt_length' => strlen($prompt),
                'operation' => 'questionnaire'
            ]);
            return $provider->generateForQuestionnaire($prompt);
        }
        
        Log::info("LLM API call initiated", [
            'provider' => $providerName,
            'model' => $provider->getModelName() ?? 'default',
            'prompt_length' => strlen($prompt),
            'operation' => 'questionnaire_fallback'
        ]);
        
        // Fallback to regular generate
        return $provider->generate($prompt);
    }

    /**
     * Get active provider from settings
     */
    protected function getActiveProvider(): string
    {
        try {
            // Try to get from admin service settings
            $response = Http::timeout(2)->get('http://admin-service:8080/api/settings/ai_provider');
            
            if ($response->successful()) {
                $setting = $response->json();
                $provider = $setting['value'] ?? env('AI_PROVIDER', 'ollama');
                Log::info("AI provider from settings: {$provider}");
                return $provider;
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch AI provider setting: ' . $e->getMessage());
        }

        // Fallback to environment variable
        return env('AI_PROVIDER', 'ollama');
    }
}
