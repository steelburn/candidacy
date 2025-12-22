<?php

namespace Shared\Common;

use Illuminate\Support\Facades\Redis;
use Shared\Contracts\EventInterface;

/**
 * Event publisher for Redis Pub/Sub
 */
class EventPublisher
{
    protected string $channel = 'candidacy.events';

    /**
     * Publish event to Redis
     */
    public function publish(EventInterface $event): void
    {
        Redis::publish($this->channel, $event->toJson());
    }

    /**
     * Publish raw event
     */
    public function publishRaw(string $eventName, array $payload): void
    {
        $event = [
            'name' => $eventName,
            'payload' => $payload,
            'timestamp' => time()
        ];

        Redis::publish($this->channel, json_encode($event));
    }
}
