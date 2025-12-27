<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NotificationTemplate Model
 * 
 * Stores reusable email templates with variable placeholders.
 * Variables in templates use {{variable_name}} syntax.
 *
 * @property int $id
 * @property string $name
 * @property string $subject
 * @property string $body
 * @property string $type
 * @property array|null $variables
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'type',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Template types
     */
    const TYPE_INTERVIEW_SCHEDULED = 'interview_scheduled';
    const TYPE_INTERVIEW_REMINDER = 'interview_reminder';
    const TYPE_OFFER_SENT = 'offer_sent';
    const TYPE_OFFER_ACCEPTED = 'offer_accepted';
    const TYPE_OFFER_REJECTED = 'offer_rejected';
    const TYPE_APPLICATION_RECEIVED = 'application_received';
    const TYPE_APPLICATION_STATUS = 'application_status';
    const TYPE_REMINDER = 'reminder';

    /**
     * Get all available template types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INTERVIEW_SCHEDULED,
            self::TYPE_INTERVIEW_REMINDER,
            self::TYPE_OFFER_SENT,
            self::TYPE_OFFER_ACCEPTED,
            self::TYPE_OFFER_REJECTED,
            self::TYPE_APPLICATION_RECEIVED,
            self::TYPE_APPLICATION_STATUS,
            self::TYPE_REMINDER,
        ];
    }

    /**
     * Render the template subject with given data
     */
    public function renderSubject(array $data): string
    {
        return $this->replaceVariables($this->subject, $data);
    }

    /**
     * Render the template body with given data
     */
    public function renderBody(array $data): string
    {
        return $this->replaceVariables($this->body, $data);
    }

    /**
     * Replace {{variable}} placeholders with actual values
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
        }
        return $content;
    }

    /**
     * Get active template by type
     */
    public static function findByType(string $type): ?self
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Relationship to notification logs
     */
    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'template_id');
    }
}
