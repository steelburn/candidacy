<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class VacancyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vacancy;

    public function __construct($vacancy)
    {
        $this->vacancy = $vacancy;
        
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
            
            Redis::publish('vacancy.created', json_encode([
                'event' => 'VacancyCreated',
                'vacancy_id' => $this->vacancy->id,
                'data' => $this->vacancy->toArray(),
                'timestamp' => now()->toIso8601String()
            ]));
            
            \Log::info("Published VacancyCreated event for vacancy {$this->vacancy->id}");
        } catch (\Exception $e) {
            // Don't fail the entire request if Redis publish fails
            \Log::warning('Failed to publish VacancyCreated event: ' . $e->getMessage());
        }
    }
}
