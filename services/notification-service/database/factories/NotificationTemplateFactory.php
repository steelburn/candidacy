<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(2),
            'subject' => $this->faker->sentence(5),
            'body' => $this->faker->paragraphs(3, true),
            'type' => $this->faker->randomElement(['interview_scheduled', 'offer_sent', 'application_received']),
            'variables' => json_encode(['candidate_name', 'vacancy_title']),
            'is_active' => true,
            'tenant_id' => 1,
        ];
    }
}
