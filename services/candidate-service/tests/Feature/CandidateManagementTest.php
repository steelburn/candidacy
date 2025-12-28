<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Candidate;

class CandidateManagementTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test listing candidates
     */
    public function test_can_list_candidates(): void
    {
        Candidate::factory()->count(5)->create();

        $response = $this->getJson('/api/candidates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    /**
     * Test creating a candidate
     */
    public function test_can_create_candidate(): void
    {
        $response = $this->postJson('/api/candidates', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'name', 'email',
            ]);

        $this->assertDatabaseHas('candidates', [
            'email' => 'john.doe@example.com',
        ]);
    }

    /**
     * Test viewing a specific candidate
     */
    public function test_can_view_candidate(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->getJson('/api/candidates/' . $candidate->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $candidate->id,
                'email' => $candidate->email,
            ]);
    }

    /**
     * Test updating a candidate
     */
    public function test_can_update_candidate(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->putJson('/api/candidates/' . $candidate->id, [
            'name' => 'Updated Name',
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'status' => 'new',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test deleting a candidate
     */
    public function test_can_delete_candidate(): void
    {
        $candidate = Candidate::factory()->create();

        $response = $this->deleteJson('/api/candidates/' . $candidate->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('candidates', [
            'id' => $candidate->id,
        ]);
    }

    /**
     * Test candidate validation
     */
    public function test_candidate_validation_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/candidates', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /**
     * Test candidate search/filtering
     */
    public function test_can_filter_candidates(): void
    {
        Candidate::factory()->create(['name' => 'John Doe', 'status' => 'active']);
        Candidate::factory()->create(['name' => 'Jane Smith', 'status' => 'inactive']);

        $response = $this->getJson('/api/candidates?status=active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['status']);
    }

    /**
     * Test getting candidate metrics
     */
    public function test_can_get_candidate_metrics(): void
    {
        Candidate::factory()->count(10)->create(['status' => 'active']);
        Candidate::factory()->count(5)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/candidates/metrics/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_candidates',
                'by_status',
                'this_month',
                'this_week',
            ]);
    }
}
