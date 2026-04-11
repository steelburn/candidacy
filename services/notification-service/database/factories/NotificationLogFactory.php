<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'template_id' => null,
            'recipient_email' => $this->faker->unique()->safeEmail(),
            'recipient_name' => $this->faker->name(),
            'subject' => $this->faker->sentence(5),
            'body' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['interview_scheduled', 'offer_sent', 'application_received']),
            'channel' => $this->faker->randomElement(['email', 'sms', 'push']),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed']),
            'metadata' => json_encode(['priority' => $this->faker->randomElement(['low', 'medium', 'high'])]),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'failed_at' => null,
            'error_message' => null,
            'retry_count' => 0,
            'tenant_id' => 1,
        ];
    }
}
