<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Vacancy;

class VacancyManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing vacancies
     */
    public function test_can_list_vacancies(): void
    {
        Vacancy::factory()->count(5)->create();

        $response = $this->getJson('/api/vacancies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'department', 'status'],
                ],
            ]);
    }

    /**
     * Test creating a vacancy
     */
    public function test_can_create_vacancy(): void
    {
        $response = $this->postJson('/api/vacancies', [
            'title' => 'Senior Software Engineer',
            'department' => 'Engineering',
            'location' => 'Remote',
            'employment_type' => 'full-time',
            'experience_level' => 'senior',
            'description' => 'We are looking for a senior software engineer',
            'requirements' => '5+ years of experience',
            'status' => 'open',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'title', 'department',
            ]);

        $this->assertDatabaseHas('vacancies', [
            'title' => 'Senior Software Engineer',
        ]);
    }

    /**
     * Test viewing a specific vacancy
     */
    public function test_can_view_vacancy(): void
    {
        $vacancy = Vacancy::factory()->create();

        $response = $this->getJson('/api/vacancies/' . $vacancy->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $vacancy->id,
                'title' => $vacancy->title,
            ]);
    }

    /**
     * Test updating a vacancy
     */
    public function test_can_update_vacancy(): void
    {
        $vacancy = Vacancy::factory()->create();

        $response = $this->putJson('/api/vacancies/' . $vacancy->id, [
            'title' => 'Updated Title',
            'department' => $vacancy->department,
            'location' => $vacancy->location,
            'employment_type' => $vacancy->employment_type,
            'experience_level' => $vacancy->experience_level,
            'description' => $vacancy->description,
            'requirements' => $vacancy->requirements,
            'status' => $vacancy->status,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vacancies', [
            'id' => $vacancy->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * Test deleting a vacancy
     */
    public function test_can_delete_vacancy(): void
    {
        $vacancy = Vacancy::factory()->create();

        $response = $this->deleteJson('/api/vacancies/' . $vacancy->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('vacancies', [
            'id' => $vacancy->id,
        ]);
    }

    /**
     * Test vacancy validation
     */
    public function test_vacancy_validation_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/vacancies', [
            'title' => '',
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test filtering vacancies by status
     */
    public function test_can_filter_vacancies_by_status(): void
    {
        Vacancy::factory()->create(['status' => 'open']);
        Vacancy::factory()->create(['status' => 'closed']);

        $response = $this->getJson('/api/vacancies?status=open');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $vacancy) {
            $this->assertEquals('open', $vacancy['status']);
        }
    }

    /**
     * Test getting vacancy metrics
     */
    public function test_can_get_vacancy_metrics(): void
    {
        Vacancy::factory()->count(10)->create(['status' => 'open']);
        Vacancy::factory()->count(5)->create(['status' => 'closed']);

        $response = $this->getJson('/api/vacancies/metrics/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'open',
                'closed',
            ]);
    }
}
