<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public',
        'is_sensitive',
        'validation_rules',
        'default_value',
        'service_scope',
        'requires_restart',
        'version',
        'updated_by'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_sensitive' => 'boolean',
        'requires_restart' => 'boolean',
        'validation_rules' => 'array',
        'version' => 'integer',
    ];

    /**
     * Relationship to change logs
     */
    public function changeLogs()
    {
        return $this->hasMany(SettingChangeLog::class);
    }

    /**
     * Get the setting value with proper type casting
     */
    public function getValueAttribute($value)
    {
        // Return default if value is null
        if ($value === null && $this->default_value !== null) {
            $value = $this->default_value;
        }

        return match($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value
        };
    }

    /**
     * Set the setting value with proper type encoding
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => $value
        };
    }

    /**
     * Validate value against validation rules
     */
    public function validate($value): bool
    {
        if (empty($this->validation_rules)) {
            return true;
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $this->validation_rules]
        );

        return $validator->passes();
    }

    /**
     * Get setting by key with caching
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "config:{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Get setting without cache (fresh from DB)
     */
    public static function getFresh(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value with cache invalidation
     */
    public static function set(string $key, $value, string $type = 'string', int $userId = null): void
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'updated_by' => $userId,
                'version' => \DB::raw('version + 1')
            ]
        );

        // Invalidate cache
        Cache::forget("config:{$key}");
    }

    /**
     * Get all settings as key-value array with caching
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('config:all', 3600, function () {
            return static::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get settings by category
     */
    public static function getByCategory(string $category): array
    {
        return Cache::remember("config:category:{$category}", 3600, function () use ($category) {
            return static::where('category', $category)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Get settings by service scope
     */
    public static function getByScope(string $scope): array
    {
        return Cache::remember("config:scope:{$scope}", 3600, function () use ($scope) {
            return static::where('service_scope', 'like', "%{$scope}%")
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Invalidate all configuration cache
     */
    public static function invalidateCache(string $key = null): void
    {
        if ($key) {
            Cache::forget("config:{$key}");
        } else {
            Cache::forget('config:all');
            // Also clear category and scope caches
            $categories = static::distinct()->pluck('category');
            foreach ($categories as $category) {
                Cache::forget("config:category:{$category}");
            }
        }
    }

    /**
     * Get masked value for sensitive settings
     */
    public function getMaskedValue(): string
    {
        if (!$this->is_sensitive || empty($this->value)) {
            return $this->value;
        }

        $value = (string) $this->value;
        $length = strlen($value);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }
}
