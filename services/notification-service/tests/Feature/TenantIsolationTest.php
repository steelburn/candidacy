<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;

/**
 * Tenant Isolation Tests for Notification Service
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

    public function test_notification_templates_are_scoped_to_current_tenant(): void
    {
        NotificationTemplate::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationTemplate::factory()->definition(), ['tenant_id' => 1])
        );
        NotificationTemplate::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationTemplate::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, NotificationTemplate::all());

        $this->setTenant(2);
        $this->assertCount(1, NotificationTemplate::all());
    }

    public function test_notification_logs_are_scoped_to_current_tenant(): void
    {
        NotificationLog::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationLog::factory()->definition(), ['tenant_id' => 1])
        );
        NotificationLog::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationLog::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertCount(1, NotificationLog::all());

        $this->setTenant(2);
        $this->assertCount(1, NotificationLog::all());
    }

    public function test_template_from_other_tenant_is_not_found_by_id(): void
    {
        $template = NotificationTemplate::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationTemplate::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(NotificationTemplate::find($template->id));
    }

    public function test_log_from_other_tenant_is_not_found_by_id(): void
    {
        $log = NotificationLog::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationLog::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $this->assertNull(NotificationLog::find($log->id));
    }

    public function test_withoutTenant_scope_returns_all_templates(): void
    {
        NotificationTemplate::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationTemplate::factory()->definition(), ['tenant_id' => 1])
        );
        NotificationTemplate::withoutGlobalScopes()->forceCreate(
            array_merge(NotificationTemplate::factory()->definition(), ['tenant_id' => 2])
        );

        $this->setTenant(1);
        $all = NotificationTemplate::withoutTenant()->get();
        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_tenant_id_is_auto_stamped_on_template_create(): void
    {
        $this->setTenant(42);

        $data = NotificationTemplate::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $template = NotificationTemplate::create($data);
        $this->assertEquals(42, $template->tenant_id);
    }

    public function test_tenant_id_is_auto_stamped_on_log_create(): void
    {
        $this->setTenant(42);

        $data = NotificationLog::factory()->make(['tenant_id' => null])->toArray();
        unset($data['tenant_id']);

        $log = NotificationLog::create($data);
        $this->assertEquals(42, $log->tenant_id);
    }

    public function test_factory_defaults_to_tenant_1(): void
    {
        $this->setTenant(1);
        $template = NotificationTemplate::factory()->create();
        $this->assertEquals(1, $template->tenant_id);
    }
}
