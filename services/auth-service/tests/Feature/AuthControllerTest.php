<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_a_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->postJson('/api/validate-token', ['token' => $token]);

        $response->assertStatus(200)
                 ->assertJson(['valid' => true]);
    }

    /** @test */
    public function it_rejects_an_invalid_token()
    {
        $response = $this->postJson('/api/validate-token', ['token' => 'invalid-token']);

        $response->assertStatus(401)
                 ->assertJson(['valid' => false]);
    }

    /** @test */
    public function it_checks_user_role_access()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/role-check', ['required_role' => 'admin']);

        $response->assertStatus(200)
                 ->assertJson(['access' => true]);
    }

    /** @test */
    public function it_denies_access_for_missing_role()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/role-check', ['required_role' => 'admin']);

        $response->assertStatus(200)
                 ->assertJson(['access' => false]);
    }
}