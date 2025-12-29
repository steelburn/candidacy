<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI Model model (models available per provider).
 */
class AIModel extends Model
{
    protected $table = 'ai_models';
    
    protected $fillable = [
        'provider_id', 'name', 'display_name', 'is_enabled', 'capabilities', 'context_length'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'capabilities' => 'array',
    ];

    public function provider() { return $this->belongsTo(AIProvider::class, 'provider_id'); }
}
