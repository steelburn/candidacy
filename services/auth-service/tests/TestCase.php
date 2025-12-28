<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Create an authenticated user and return user with JWT token
     * 
     * @param array $attributes
     * @return array ['user' => User, 'token' => string]
     */
    protected function authenticatedUser(array $attributes = []): array
    {
        $user = User::factory()->create($attributes);
        $token = JWTAuth::fromUser($user);
        
        return ['user' => $user, 'token' => $token];
    }

    /**
     * Act as an authenticated user using JWT
     * Sets up the request to use JWT authentication
     * 
     * @param User|null $user
     * @return $this
     */
    protected function actingAsUser(?User $user = null): static
    {
        $user = $user ?? User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
        
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
        $auth = $user ? ['user' => $user, 'token' => JWTAuth::fromUser($user)] 
                      : $this->authenticatedUser();
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->json($method, $uri, $data);
    }
}

