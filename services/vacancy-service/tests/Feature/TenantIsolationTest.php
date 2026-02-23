<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Vacancy;

/**
 * Tenant Isolation Tests for Vacancy Service
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

    public function test_vacancies_are_scoped_to_current_tenant(): void
    {
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 1])
        );
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, Vacancy::all());

        $this->setTenant(2);
        $this->assertCount(1, Vacancy::all());
    }

    public function test_vacancy_from_other_tenant_is_not_found_by_id(): void
    {
        $vacancy = Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(Vacancy::find($vacancy->id));
    }

    public function test_withoutTenant_bypasses_scope(): void
    {
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 1])
        );
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertGreaterThanOrEqual(2, Vacancy::withoutTenant()->count());
    }

    public function test_tenant_id_is_auto_stamped_on_create(): void
    {
        $this->setTenant(99);

        $data = Vacancy::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $vacancy = Vacancy::create($data);
        $this->assertEquals(99, $vacancy->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $vacancy = Vacancy::factory()->create();
        $this->assertEquals(1, $vacancy->tenant_id);
    }

    public function test_api_list_returns_only_own_tenant_vacancies(): void
    {
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 1])
        );
        Vacancy::withoutGlobalScopes()->forceCreate(
            array_merge(Vacancy::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);

        $response = $this->getJson('/api/vacancies');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        foreach ($ids as $id) {
            $record = Vacancy::withoutTenant()->find($id);
            $this->assertEquals(1, $record->tenant_id, "API returned a vacancy from a different tenant");
        }
    }
}
