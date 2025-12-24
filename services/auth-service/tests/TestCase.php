<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup Sanctum for testing
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure Sanctum for testing
        config(['sanctum.guard' => ['web']]);
    }

    /**
     * Create an authenticated user and return user with token
     * 
     * @param array $attributes
     * @return array ['user' => User, 'token' => string]
     */
    protected function authenticatedUser(array $attributes = []): array
    {
        $user = User::factory()->create($attributes);
        $token = $user->createToken('test-token')->plainTextToken;
        
        return ['user' => $user, 'token' => $token];
    }

    /**
     * Act as an authenticated user using Sanctum
     * 
     * @param User|null $user
     * @return $this
     */
    protected function actingAsUser(?User $user = null): static
    {
        $user = $user ?? User::factory()->create();
        Sanctum::actingAs($user);
        
        return $this;
    }

    /**
     * Make authenticated request with Bearer token
     * 
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param User|null $user
     * @return \Illuminate\Testing\TestResponse
     */
    protected function authenticatedJson(string $method, string $uri, array $data = [], ?User $user = null)
    {
        $auth = $user ? ['user' => $user, 'token' => $user->createToken('test')->plainTextToken] 
                      : $this->authenticatedUser();
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->json($method, $uri, $data);
    }
}
