<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class CandidateCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $candidate;

    public function __construct($candidate)
    {
        $this->candidate = $candidate;
        
        // Publish to Redis
        $this->publishToRedis();
    }

    protected function publishToRedis()
    {
        try {
            // Check if Redis is available
            if (!extension_loaded('redis') && !extension_loaded('phpredis')) {
                \Log::info('Redis extension not loaded, skipping event publish');
                return;
            }
            
            Redis::publish('candidate.created', json_encode([
                'event' => 'CandidateCreated',
                'candidate_id' => $this->candidate->id,
                'data' => $this->candidate->toArray(),
                'timestamp' => now()->toIso8601String()
            ]));
            
            \Log::info("Published CandidateCreated event for candidate {$this->candidate->id}");
        } catch (\Exception $e) {
            // Don't fail the entire request if Redis publish fails
            \Log::warning('Failed to publish CandidateCreated event: ' . $e->getMessage());
        }
    }
}
