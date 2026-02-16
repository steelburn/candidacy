<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIProvider;
use App\Services\ProviderManager;
use Illuminate\Http\Request;

/**
 * Provider management controller for admin UI.
 */
class ProvidersController extends Controller
{
    protected ProviderManager $manager;

    public function __construct()
    {
        $this->manager = new ProviderManager();
    }

    /**
     * Get all providers and current chains.
     */
    public function index()
    {
        $providers = [];
        foreach ($this->manager->getAllProviders() as $name => $provider) {
            // Get current config from settings to ensure we return persisted values
            $configUrl = $provider->getBaseUrl();
            $configModel = $provider->getDefaultModel();

            $providers[] = [
                'name' => $name,
                'displayName' => $provider->getDisplayName(),
                'available' => $provider->isAvailable(),
                'defaultModel' => $configModel,
                'baseUrl' => $configUrl, // Add baseUrl for editing
                'type' => $provider->getType(),
                'isDefault' => true, // Mark as default provider
                'hasApiKey' => $provider->hasApiKey(),
            ];
        }

        // Get custom instances from database
        $instances = AIProvider::all()->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'displayName' => $i->display_name,
            'type' => $i->type,
            'baseUrl' => $i->base_url,
            'isEnabled' => $i->is_enabled,
            'config' => collect($i->config)->except(['api_key'])->all(),
            'hasApiKey' => !empty($i->config['api_key'] ?? ''),
        ]);

        return response()->json([
            'providers' => $providers,
            'instances' => $instances,
            'providerTypes' => ['ollama', 'openrouter', 'openai', 'gemini', 'azure', 'litellm', 'llamacpp'],
            'chains' => $this->manager->getServiceChains(),
        ]);
    }

    /**
     * Create a new provider instance.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:ai_providers,name',
            'display_name' => 'required|string|max:100',
            'type' => 'required|string|in:ollama,openrouter,openai,gemini,azure,litellm,llamacpp',
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'config' => 'nullable|array',
        ]);

        $config = $validated['config'] ?? [];
        if (!empty($validated['api_key'])) {
            $config['api_key'] = $validated['api_key'];
        }

        $instance = AIProvider::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'type' => $validated['type'],
            'base_url' => $validated['base_url'] ?? null,
            'is_enabled' => true,
            'is_enabled' => true,
            'config' => $config,
        ]);

        return response()->json(['message' => 'Instance created', 'instance' => $instance], 201);
    }

    /**
     * Delete a provider instance.
     */
    public function destroy($id)
    {
        $instance = AIProvider::find($id);
        if (!$instance) {
            return response()->json(['error' => 'Instance not found'], 404);
        }
        
        $instance->delete();
        return response()->json(['message' => 'Instance deleted']);
    }

    /**
     * Update service chains.
     */
    public function updateChains(Request $request)
    {
        $chains = $request->input('chains', []);
        
        try {
            // Save to admin service via HTTP PUT
            $adminUrl = env('ADMIN_SERVICE_URL', 'http://admin-service:8080');
            $response = \Illuminate\Support\Facades\Http::put("{$adminUrl}/api/settings/ai.service_chains", [
                'value' => json_encode($chains),
            ]);
            
            if ($response->successful()) {
                \Shared\Services\ConfigurationService::refresh('ai.service_chains');
                return response()->json(['message' => 'Chains updated successfully']);
            }
            
            // If setting doesn't exist, create it via the general update endpoint
            $response = \Illuminate\Support\Facades\Http::put("{$adminUrl}/api/settings", [
                'ai.service_chains' => json_encode($chains),
            ]);
            
            if ($response->successful()) {
                return response()->json(['message' => 'Chains updated successfully']);
            }
            
            return response()->json(['error' => 'Failed to save configuration'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Update a provider instance (custom or default).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'base_url' => 'nullable|url',
            'model' => 'nullable|string',
            'api_key' => 'nullable|string',
            'config' => 'nullable|array',
            'display_name' => 'nullable|string', // Only for custom
        ]);

        if (is_numeric($id)) {
            $instance = AIProvider::find($id);
        } else {
            // Also check by name, as frontend might send name for custom instances that appear in provider list
            $instance = AIProvider::where('name', $id)->first();
        }

        if ($instance) {
             $updateData = [];
            if ($request->has('display_name')) $updateData['display_name'] = $validated['display_name'];
            if ($request->has('base_url')) $updateData['base_url'] = $validated['base_url'];
            
            // Merge config updates
            if ($request->has('model') || $request->has('config') || $request->has('api_key')) {
                $currentConfig = $instance->config ?? [];
                if ($request->has('model')) $currentConfig['model'] = $validated['model'];
                if ($request->filled('api_key')) $currentConfig['api_key'] = $validated['api_key'];
                if ($request->has('config')) $currentConfig = array_merge($currentConfig, $validated['config']);
                $updateData['config'] = $currentConfig;
            }

            $instance->update($updateData);
            return response()->json(['message' => 'Instance updated', 'instance' => $instance]);
        }

        // Case 2: Default Provider (String ID like 'ollama', 'openai')
        // Map fields to settings keys
        $settingsToUpdate = [];
        $provider = $id;

        if ($request->has('base_url')) {
            // Mapping for base URLs
            $key = match($provider) {
                'ollama' => 'ai.ollama.url',
                'litellm' => 'ai.litellm.base_url',
                'llamacpp' => 'ai.llamacpp.base_url',
                'azure' => 'ai.azure.endpoint',
                'openai' => 'ai.openai.base_url',
                'openrouter' => 'ai.openrouter.base_url',
                'gemini' => 'ai.gemini.base_url',
                default => null
            };
            if ($key) $settingsToUpdate[$key] = $validated['base_url'];
        }

        if ($request->has('model')) {
             // Mapping for default models
             $key = match($provider) {
                'ollama' => 'ai.ollama.model.default',
                'openrouter' => 'ai.openrouter.model',
                'openai' => 'ai.openai.model',
                'gemini' => 'ai.gemini.model',
                default => "ai.{$provider}.model" 
            };
            if ($key) $settingsToUpdate[$key] = $validated['model'];
        }

        if ($request->filled('api_key')) {
             // Mapping for default API keys
             $key = match($provider) {
                'openrouter' => 'ai.openrouter.api_key',
                'openai' => 'ai.openai.api_key',
                'gemini' => 'ai.gemini.api_key',
                'azure' => 'ai.azure.api_key',
                'litellm' => 'ai.litellm.api_key',
                default => null 
            };
            if ($key) $settingsToUpdate[$key] = $validated['api_key'];
        }

        if (!empty($settingsToUpdate)) {
            $adminUrl = env('ADMIN_SERVICE_URL', 'http://admin-service:8080');
            $response = \Illuminate\Support\Facades\Http::put("{$adminUrl}/api/settings", $settingsToUpdate);

            if ($response->successful()) {
                return response()->json(['message' => 'Default provider settings updated']);
            }
            return response()->json(['error' => 'Failed to update settings'], 500);
        }
        
        return response()->json(['message' => 'No settings to update']);
    }

    /**
     * List available models for a provider context.
     */
    public function listModels(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'id' => 'nullable',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|string',
        ]);
        


        $config = [];

        // If ID provided, try to load existing config as base
        if ($request->filled('id')) {
            if (is_numeric($request->id)) {
                $instance = AIProvider::find($request->id);
                if ($instance) {
                    $config = $instance->config ?? []; 
                    if ($instance->base_url) $config['url'] = $instance->base_url;
                }
            } 
            // If default provider (string id), we rely on the Provider class loading its defaults
            // if we don't pass overrides.
        }

        // Apply request overrides (only if provided and not empty placeholder)
        if ($request->filled('api_key')) $config['api_key'] = $request->api_key;
        if ($request->filled('base_url')) $config['url'] = $request->base_url;

        try {
            $tempName = 'temp_check_' . uniqid();
            $provider = $this->manager->registerInstance($tempName, $validated['type'], $config);
            
            if (!$provider) return response()->json(['error' => 'Invalid provider type'], 400);
            
            $models = $provider->listModels();
            return response()->json(['models' => $models]);
        } catch (\Exception $e) {
            return response()->json(['error' => "Failed to fetch models: " . $e->getMessage()], 500);
        }
    }
}