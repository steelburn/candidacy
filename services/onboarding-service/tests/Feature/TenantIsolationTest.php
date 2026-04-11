<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\OnboardingChecklist;

/**
 * Tenant Isolation Tests for Onboarding Service
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

    public function test_onboarding_checklists_are_scoped_to_current_tenant(): void
    {
        OnboardingChecklist::withoutGlobalScopes()->forceCreate(
            array_merge(OnboardingChecklist::factory()->definition(), ['tenant_id' => 1])
        );
        OnboardingChecklist::withoutGlobalScopes()->forceCreate(
            array_merge(OnboardingChecklist::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, OnboardingChecklist::all());

        $this->setTenant(2);
        $this->assertCount(1, OnboardingChecklist::all());
    }

    public function test_onboarding_checklist_from_other_tenant_is_not_found_by_id(): void
    {
        $checklist = OnboardingChecklist::withoutGlobalScopes()->forceCreate(
            array_merge(OnboardingChecklist::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(OnboardingChecklist::find($checklist->id));
    }

    public function test_withoutTenant_scope_returns_all_records(): void
    {
        OnboardingChecklist::withoutGlobalScopes()->forceCreate(
            array_merge(OnboardingChecklist::factory()->definition(), ['tenant_id' => 1])
        );
        OnboardingChecklist::withoutGlobalScopes()->forceCreate(
            array_merge(OnboardingChecklist::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $all = OnboardingChecklist::withoutTenant()->get();
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $this->setTenant(42);

        $data = OnboardingChecklist::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $checklist = OnboardingChecklist::create($data);
        $this->assertEquals(42, $checklist->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $checklist = OnboardingChecklist::factory()->create();
        $this->assertEquals(1, $checklist->tenant_id);
    }
}
