<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * TenantApiKey Model
 * 
 * API keys for programmatic access to tenant data.
 * Keys are hashed and never stored in plain text.
 */
class TenantApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'key_prefix',
        'key_hash',
        'scopes',
        'last_used_at',
        'expires_at',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'key_hash',
    ];

    /**
     * Get the tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a new API key.
     * 
     * @return array{key: string, model: TenantApiKey}
     */
    public static function generateKey(int $tenantId, string $name, int $createdBy, ?array $scopes = null, ?\DateTimeInterface $expiresAt = null): array
    {
        // Generate a random key with prefix
        $prefix = 'ck_' . Str::random(5);
        $secret = Str::random(32);
        $fullKey = $prefix . '_' . $secret;

        $model = new static([
            'tenant_id' => $tenantId,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', $fullKey),
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
            'is_active' => true,
        ]);

        $model->save();

        // Return both the plain key (only shown once) and the model
        return [
            'key' => $fullKey,
            'model' => $model,
        ];
    }

    /**
     * Verify an API key.
     */
    public static function verify(string $key): ?self
    {
        $hash = hash('sha256', $key);
        
        $model = static::where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        if (!$model) {
            return null;
        }

        // Check expiration
        if ($model->expires_at && $model->expires_at->isPast()) {
            return null;
        }

        // Update last used
        $model->last_used_at = now();
        $model->save();

        return $model;
    }

    /**
     * Check if key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        // Empty scopes means all scopes allowed
        if (empty($this->scopes)) {
            return true;
        }

        return in_array($scope, $this->scopes ?? []);
    }

    /**
     * Check if key is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Revoke the key.
     */
    public function revoke(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Scope for active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
