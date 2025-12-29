<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI Request Log for tracking and metrics.
 */
class AIRequestLog extends Model
{
    protected $table = 'ai_request_logs';
    public $timestamps = false;
    
    protected $fillable = [
        'service_type', 'provider_id', 'model_id', 'input_tokens', 'output_tokens',
        'duration_ms', 'success', 'failover_attempt', 'total_attempts', 'error_message', 'created_at'
    ];

    protected $casts = ['success' => 'boolean', 'created_at' => 'datetime'];

    public function provider() { return $this->belongsTo(AIProvider::class, 'provider_id'); }
    public function model() { return $this->belongsTo(AIModel::class, 'model_id'); }

    public static function log(array $data): self
    {
        $data['created_at'] = now();
        return self::create($data);
    }
}
