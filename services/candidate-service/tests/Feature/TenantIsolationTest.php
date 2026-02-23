<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Candidate;

/**
 * Tenant Isolation Tests for Candidate Service
 *
 * Verifies that:
 *  - Records created in one tenant are not visible to another tenant
 *  - BelongsToTenant global scope auto-applies
 *  - withoutTenant() escape hatch works for admin queries
 *  - tenant_id is auto-stamped on creation when context is set
 */
class TenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    /** Set tenant context for the current request lifecycle */
    private function setTenant(int $tenantId): void
    {
        app()->instance('tenant.id', $tenantId);
    }

    /** Clear tenant context so global scope is not applied */
    private function clearTenant(): void
    {
        app()->forgetInstance('tenant.id');
    }

    protected function tearDown(): void
    {
        $this->clearTenant();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // SCOPING TESTS
    // -------------------------------------------------------------------------

    public function test_candidates_are_scoped_to_current_tenant(): void
    {
        // Create candidates in two different tenants (bypass scope using forceFill)
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 1])
        );
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 2])
        );

        // Switch to tenant 1
        $this->setTenant(1);
        $this->assertCount(1, Candidate::all());

        // Switch to tenant 2
        $this->setTenant(2);
        $this->assertCount(1, Candidate::all());
    }

    public function test_candidate_from_other_tenant_is_not_found_by_id(): void
    {
        $candidate = Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 2])
        );

        // Set a different tenant — record should be invisible
        $this->setTenant(1);
        $this->assertNull(Candidate::find($candidate->id));
    }

    public function test_withoutTenant_scope_returns_all_records(): void
    {
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 1])
        );
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        // withoutTenant() bypasses the global scope
        $all = Candidate::withoutTenant()->get();
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    // -------------------------------------------------------------------------
    // AUTO-STAMP TESTS
    // -------------------------------------------------------------------------

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $this->setTenant(42);

        // Directly insert so the creating hook in BelongsToTenant sets tenant_id
        $data = Candidate::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']); // ensure it is missing so trait stamps it

        $candidate = Candidate::create($data);

        $this->assertEquals(42, $candidate->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $candidate = Candidate::factory()->create();
        $this->assertEquals(1, $candidate->tenant_id);
    }

    // -------------------------------------------------------------------------
    // BELONGS-TO HELPERS
    // -------------------------------------------------------------------------

    public function test_belongsToTenant_returns_true_for_correct_tenant(): void
    {
        $this->setTenant(1);
        $candidate = Candidate::factory()->create(['tenant_id' => 1]);
        $this->assertTrue($candidate->belongsToTenant(1));
        $this->assertFalse($candidate->belongsToTenant(2));
    }

    public function test_belongsToCurrentTenant_returns_true_when_context_matches(): void
    {
        $this->setTenant(5);
        $candidate = Candidate::factory()->create(['tenant_id' => 5]);
        $this->assertTrue($candidate->belongsToCurrentTenant());
    }

    public function test_belongsToCurrentTenant_returns_false_when_context_differs(): void
    {
        $this->setTenant(1);
        $candidateInTenant2 = Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 2])
        );
        $this->assertFalse($candidateInTenant2->belongsToCurrentTenant());
    }

    // -------------------------------------------------------------------------
    // API LAYER TESTS (HTTP-level scoping via X-Tenant-ID header)
    // -------------------------------------------------------------------------

    public function test_api_list_returns_only_own_tenant_candidates(): void
    {
        // Seed both tenants via forceCreate
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 1])
        );
        Candidate::withoutGlobalScopes()->forceCreate(
            array_merge(Candidate::factory()->definition(), ['tenant_id' => 2])
        );

        // Set tenant context (simulates TenantMiddleware having resolved tenant 1)
        $this->setTenant(1);

        $response = $this->getJson('/api/candidates');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        foreach ($ids as $id) {
            $record = Candidate::withoutTenant()->find($id);
            $this->assertEquals(1, $record->tenant_id, "API returned a candidate from a different tenant");
        }
    }
}
