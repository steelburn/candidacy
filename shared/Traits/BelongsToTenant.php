<?php

namespace Shared\Traits;

use Shared\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToTenant - Trait for tenant-scoped Eloquent models
 * 
 * This trait should be used on any model that belongs to a tenant.
 * It automatically:
 *   - Applies TenantScope for query filtering
 *   - Sets tenant_id on new records
 *   - Provides a tenant() relationship
 * 
 * Usage:
 *   class Candidate extends Model
 *   {
 *       use BelongsToTenant;
 *   }
 * 
 * Important:
 *   - Ensure the model's table has a tenant_id column
 *   - Tenant context must be set before creating records
 */
trait BelongsToTenant
{
    /**
     * Boot the trait - register the global scope and auto-fill tenant_id.
     *
     * @return void
     */
    public static function bootBelongsToTenant(): void
    {
        // Apply the tenant scope to all queries
        static::addGlobalScope(new TenantScope());

        // Auto-assign tenant_id when creating new records
        static::creating(function (Model $model) {
            if (!$model->tenant_id && $tenantId = static::getCurrentTenantId()) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    /**
     * Get the current tenant ID from the application container.
     *
     * @return int|null
     */
    protected static function getCurrentTenantId(): ?int
    {
        if (app()->bound('tenant.id')) {
            return app('tenant.id');
        }

        return null;
    }

    /**
     * Initialize the trait - add tenant_id to fillable if mass assignment is used.
     *
     * @return void
     */
    public function initializeBelongsToTenant(): void
    {
        // Ensure tenant_id is not mass-assignable (security)
        $this->guarded = array_merge($this->guarded ?? [], ['tenant_id']);
    }

    /**
     * Scope to query without tenant restriction.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope to query a specific tenant.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable() . '.tenant_id', $tenantId);
    }

    /**
     * Check if this model belongs to the given tenant.
     *
     * @param int $tenantId
     * @return bool
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Check if this model belongs to the current tenant.
     *
     * @return bool
     */
    public function belongsToCurrentTenant(): bool
    {
        $currentTenantId = static::getCurrentTenantId();
        
        if (!$currentTenantId) {
            return false;
        }

        return $this->belongsToTenant($currentTenantId);
    }
}
