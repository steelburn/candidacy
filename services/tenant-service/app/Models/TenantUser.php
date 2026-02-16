<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TenantUser Model
 * 
 * Represents user membership in a tenant with role assignment.
 * A user can belong to multiple tenants with different roles.
 */
class TenantUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    /**
     * Role hierarchy - higher values mean more permissions.
     */
    public const ROLE_HIERARCHY = [
        'member' => 1,
        'interviewer' => 2,
        'recruiter' => 3,
        'manager' => 4,
        'admin' => 5,
        'owner' => 6,
    ];

    /**
     * Get the tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has at least a certain role level.
     */
    public function hasMinimumRole(string $requiredRole): bool
    {
        $userLevel = self::ROLE_HIERARCHY[$this->role] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$requiredRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if user is owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is admin or owner.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner']);
    }

    /**
     * Check if user has a custom permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Scope for active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific role.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
