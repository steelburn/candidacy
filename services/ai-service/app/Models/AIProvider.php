<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI Provider model.
 */
class AIProvider extends Model
{
    protected $table = 'ai_providers';
    
    protected $fillable = [
        'name', 'display_name', 'type', 'base_url', 'is_enabled', 'config'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
    ];

    public function models() { return $this->hasMany(AIModel::class, 'provider_id'); }
    public function mappings() { return $this->hasMany(AIServiceMapping::class, 'provider_id'); }
    public function requestLogs() { return $this->hasMany(AIRequestLog::class, 'provider_id'); }
}
