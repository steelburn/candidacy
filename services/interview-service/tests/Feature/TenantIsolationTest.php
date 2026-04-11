<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Interview;

/**
 * Tenant Isolation Tests for Interview Service
 */
class TenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private function setTenant(int $tenantId): void
    {
        app()->instance('tenant.id', $tenantId);
    }

    private function clearTenant(): void
    {
        app()->forgetInstance('tenant.id');
    }

    protected function tearDown(): void
    {
        $this->clearTenant();
        parent::tearDown();
    }

    public function test_interviews_are_scoped_to_current_tenant(): void
    {
        Interview::withoutGlobalScopes()->forceCreate(
            array_merge(Interview::factory()->definition(), ['tenant_id' => 1])
        );
        Interview::withoutGlobalScopes()->forceCreate(
            array_merge(Interview::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, Interview::all());

        $this->setTenant(2);
        $this->assertCount(1, Interview::all());
    }

    public function test_interview_from_other_tenant_is_not_found_by_id(): void
    {
        $interview = Interview::withoutGlobalScopes()->forceCreate(
            array_merge(Interview::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(Interview::find($interview->id));
    }

    public function test_withoutTenant_scope_returns_all_records(): void
    {
        Interview::withoutGlobalScopes()->forceCreate(
            array_merge(Interview::factory()->definition(), ['tenant_id' => 1])
        );
        Interview::withoutGlobalScopes()->forceCreate(
            array_merge(Interview::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $all = Interview::withoutTenant()->get();
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $this->setTenant(42);

        $data = Interview::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $interview = Interview::create($data);
        $this->assertEquals(42, $interview->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $interview = Interview::factory()->create();
        $this->assertEquals(1, $interview->tenant_id);
    }
}
