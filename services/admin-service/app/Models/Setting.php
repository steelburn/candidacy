<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'category', 'description'];

    /**
     * Get the setting value with proper type casting
     */
    public function getValueAttribute($value)
    {
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
     * Get setting by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllSettings(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
