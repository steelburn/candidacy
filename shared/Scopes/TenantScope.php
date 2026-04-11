<?php

namespace Shared\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope - Global scope for automatic tenant filtering
 * 
 * This scope automatically filters all queries to only return records
 * belonging to the current tenant. It reads the tenant ID from the
 * application container.
 * 
 * Usage:
 *   - Applied automatically via BelongsToTenant trait
 *   - Use ->withoutGlobalScope(TenantScope::class) to bypass
 */
class TenantScope implements Scope
{
    /**
     * Apply the tenant scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->getTenantId();

        // If a tenant context is resolved, scope the query to that tenant.
        // If NO tenant context is available, we filter by an impossible ID (-1)
        // to ensure no records are leaked across tenants (Fail-Closed security).
        $builder->where($model->getTable() . '.tenant_id', $tenantId ?? -1);
    }

    /**
     * Get the current tenant ID from the application container.
     *
     * @return int|null
     */
    protected function getTenantId(): ?int
    {
        if (app()->bound('tenant.id')) {
            return app('tenant.id');
        }

        return null;
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder $builder
     * @return void
     */
    public function extend(Builder $builder): void
    {
        // Add a withoutTenant scope to explicitly bypass tenant filtering
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });

        // Add a forTenant scope to query a specific tenant
        $builder->macro('forTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope(static::class)
                ->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
        });
    }
}
