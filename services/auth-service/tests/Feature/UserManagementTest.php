<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        return ['user' => $user, 'token' => $token];
    }

    /**
     * Test listing users
     */
    public function test_can_list_users(): void
    {
        $auth = $this->authenticatedUser();
        User::factory()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'created_at'],
                ],
            ]);
    }

    /**
     * Test creating a user
     */
    public function test_can_create_user(): void
    {
        $auth = $this->authenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'email',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    /**
     * Test viewing a specific user
     */
    public function test_can_view_user(): void
    {
        $auth = $this->authenticatedUser();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/users/' . $targetUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $targetUser->id,
                'email' => $targetUser->email,
            ]);
    }

    /**
     * Test updating a user
     */
    public function test_can_update_user(): void
    {
        $auth = $this->authenticatedUser();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson('/api/users/' . $targetUser->id, [
            'name' => 'Updated Name',
            'email' => $targetUser->email,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test deleting a user
     */
    public function test_can_delete_user(): void
    {
        $auth = $this->authenticatedUser();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson('/api/users/' . $targetUser->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    /**
     * Test unauthenticated user cannot access user management
     */
    public function test_unauthenticated_user_cannot_access_user_management(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }
}
