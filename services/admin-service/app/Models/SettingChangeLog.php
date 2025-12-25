<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingChangeLog extends Model
{
    protected $table = 'setting_change_logs';
    
    public $timestamps = false;

    protected $fillable = [
        'setting_id',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Relationship to setting
     */
    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }

    /**
     * Log a configuration change
     */
    public static function logChange(
        int $settingId,
        $oldValue,
        $newValue,
        ?int $changedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        static::create([
            'setting_id' => $settingId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $changedBy,
            'changed_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * Get change history for a setting
     */
    public static function getHistory(int $settingId, int $limit = 50): array
    {
        return static::where('setting_id', $settingId)
            ->orderBy('changed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
