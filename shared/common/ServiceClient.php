<?php

namespace Shared\Common;

use Illuminate\Support\Facades\Http;

/**
 * HTTP Client for inter-service communication
 */
class ServiceClient
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(string $baseUrl, int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Make GET request to service
     */
    public function get(string $path, array $params = []): array
    {
        $response = Http::timeout($this->timeout)
            ->get($this->baseUrl . '/' . ltrim($path, '/'), $params);

        if ($response->failed()) {
            throw new \Exception("Service request failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Make POST request to service
     */
    public function post(string $path, array $data = []): array
    {
        $response = Http::timeout($this->timeout)
            ->post($this->baseUrl . '/' . ltrim($path, '/'), $data);

        if ($response->failed()) {
            throw new \Exception("Service request failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Make PUT request to service
     */
    public function put(string $path, array $data = []): array
    {
        $response = Http::timeout($this->timeout)
            ->put($this->baseUrl . '/' . ltrim($path, '/'), $data);

        if ($response->failed()) {
            throw new \Exception("Service request failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Make DELETE request to service
     */
    public function delete(string $path): array
    {
        $response = Http::timeout($this->timeout)
            ->delete($this->baseUrl . '/' . ltrim($path, '/'));

        if ($response->failed()) {
            throw new \Exception("Service request failed: " . $response->body());
        }

        return $response->json();
    }
}
