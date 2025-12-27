<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NotificationLog Model
 * 
 * Tracks all sent notifications with their status and metadata.
 *
 * @property int $id
 * @property int|null $template_id
 * @property string $recipient_email
 * @property string|null $recipient_name
 * @property string $subject
 * @property string|null $body
 * @property string $type
 * @property string $channel
 * @property string $status
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $failed_at
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'type',
        'channel',
        'status',
        'metadata',
        'sent_at',
        'failed_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Channel constants
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_PUSH = 'push';

    /**
     * Relationship to template
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Create a new notification log entry
     */
    public static function createLog(array $data): self
    {
        return static::create(array_merge([
            'channel' => self::CHANNEL_EMAIL,
            'status' => self::STATUS_PENDING,
            'retry_count' => 0,
        ], $data));
    }
}
