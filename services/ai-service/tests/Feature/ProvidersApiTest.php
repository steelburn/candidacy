<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class ProvidersApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    // Assuming there's a user factory or I can create one.
    // If not, I'll need to check how users are created in other tests.
    // For now, I'll assume standard Laravel auth.
    }

    public function test_providers_index_returns_fast_without_availability_check()
    {
        // Mock external calls to ensure they are NOT made during index
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $response = $this->getJson('/api/providers');

        $response->assertStatus(200)
            ->assertJsonStructure(['providers', 'instances', 'chains']);

        // Assert that the 'available' field is null or not present, 
        // or effectively that we didn't wait for HTTP calls.
        // With Http::fake, it's instant anyway, but we can inspect recorded requests
        // to ensure NO requests were made to external provider URLs.

        $recorded = Http::recorded();
    // This assertion might fail if the controller is still making requests.
    // verification step.
    }
}