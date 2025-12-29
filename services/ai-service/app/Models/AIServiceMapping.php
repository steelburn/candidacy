<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI Service Mapping - maps services to provider/model with priority.
 */
class AIServiceMapping extends Model
{
    protected $table = 'ai_service_mappings';
    
    protected $fillable = [
        'service_type', 'provider_id', 'model_id', 'priority', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function provider() { return $this->belongsTo(AIProvider::class, 'provider_id'); }
    public function model() { return $this->belongsTo(AIModel::class, 'model_id'); }

    public static function getChainFor(string $serviceType): array
    {
        return self::where('service_type', $serviceType)
            ->where('is_active', true)
            ->orderBy('priority')
            ->with(['provider', 'model'])
            ->get()
            ->toArray();
    }
}
