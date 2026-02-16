<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * TenantInvitation Model
 * 
 * Represents pending invitations to join a tenant.
 */
class TenantInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'invited_by',
        'message',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate token on creation
        static::creating(function ($invitation) {
            if (!$invitation->token) {
                $invitation->token = Str::random(64);
            }
            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if invitation is valid (not expired and not accepted).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isAccepted();
    }

    /**
     * Mark as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for specific email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', strtolower($email));
    }

    /**
     * Find by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }
}
