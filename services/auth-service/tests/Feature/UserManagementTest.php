<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;



    /**
     * Test listing users
     */
    public function test_can_list_users(): void
    {
        $this->actingAsUser();
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'created_at'],
            ]);
    }

    /**
     * Test creating a user
     */
    public function test_can_create_user(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/users', [
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
        $this->actingAsUser();
        $targetUser = User::factory()->create();

        $response = $this->getJson('/api/users/' . $targetUser->id);

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
        $this->actingAsUser();
        $targetUser = User::factory()->create();

        $response = $this->putJson('/api/users/' . $targetUser->id, [
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
     * Test deleting a user (uses SoftDeletes)
     */
    public function test_can_delete_user(): void
    {
        $this->actingAsUser();
        $targetUser = User::factory()->create();

        $response = $this->deleteJson('/api/users/' . $targetUser->id);

        $response->assertStatus(200);

        // User uses SoftDeletes, so check deleted_at is set
        $this->assertSoftDeleted('users', [
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
