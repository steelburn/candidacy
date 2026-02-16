<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Tenant Model
 * 
 * Represents an organization using the Candidacy platform.
 * Each tenant has complete data isolation.
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'domain',
        'logo_url',
        'settings',
        'subscription_plan',
        'subscription_status',
        'subscription_ends_at',
        'max_users',
        'max_candidates',
        'max_vacancies',
        'owner_id',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_ends_at' => 'datetime',
        'max_users' => 'integer',
        'max_candidates' => 'integer',
        'max_vacancies' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID on creation
        static::creating(function ($tenant) {
            if (!$tenant->uuid) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (!$tenant->slug) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /**
     * Get the tenant users (members).
     */
    public function users()
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Get pending invitations for this tenant.
     */
    public function invitations()
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * Get API keys for this tenant.
     */
    public function apiKeys()
    {
        return $this->hasMany(TenantApiKey::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function hasActiveSubscription(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->subscription_status !== 'active') {
            return false;
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Check if user limit is reached.
     */
    public function hasReachedUserLimit(): bool
    {
        return $this->users()->where('is_active', true)->count() >= $this->max_users;
    }

    /**
     * Scope for active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for tenants by subscription plan.
     */
    public function scopePlan($query, string $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
