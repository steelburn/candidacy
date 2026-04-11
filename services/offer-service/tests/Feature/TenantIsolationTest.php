<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Offer;

/**
 * Tenant Isolation Tests for Offer Service
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

    public function test_offers_are_scoped_to_current_tenant(): void
    {
        Offer::withoutGlobalScopes()->forceCreate(
            array_merge(Offer::factory()->definition(), ['tenant_id' => 1])
        );
        Offer::withoutGlobalScopes()->forceCreate(
            array_merge(Offer::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, Offer::all());

        $this->setTenant(2);
        $this->assertCount(1, Offer::all());
    }

    public function test_offer_from_other_tenant_is_not_found_by_id(): void
    {
        $offer = Offer::withoutGlobalScopes()->forceCreate(
            array_merge(Offer::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(Offer::find($offer->id));
    }

    public function test_withoutTenant_scope_returns_all_records(): void
    {
        Offer::withoutGlobalScopes()->forceCreate(
            array_merge(Offer::factory()->definition(), ['tenant_id' => 1])
        );
        Offer::withoutGlobalScopes()->forceCreate(
            array_merge(Offer::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $all = Offer::withoutTenant()->get();
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $this->setTenant(42);

        $data = Offer::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $offer = Offer::create($data);
        $this->assertEquals(42, $offer->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $offer = Offer::factory()->create();
        $this->assertEquals(1, $offer->tenant_id);
    }
}
