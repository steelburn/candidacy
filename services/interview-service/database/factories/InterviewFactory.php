<?php

namespace Database\Factories;

use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        $scheduledAt = $this->faker->dateTimeBetween('now', '+2 months');
        
        return [
            'candidate_id' => $this->faker->numberBetween(1, 100),
            'vacancy_id' => $this->faker->numberBetween(1, 50),
            'interviewer_id' => $this->faker->numberBetween(1, 20),
            'interviewer_ids' => [
                $this->faker->numberBetween(1, 20),
                $this->faker->numberBetween(1, 20),
            ],
            'stage' => $this->faker->randomElement(['screening', 'technical', 'behavioral', 'final']),
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            'location' => $this->faker->randomElement([
                'https://meet.google.com/' . $this->faker->uuid,
                'https://zoom.us/j/' . $this->faker->numerify('##########'),
                $this->faker->address,
            ]),
            'type' => $this->faker->randomElement(['in_person', 'video', 'phone']),
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled', 'rescheduled']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the interview is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the interview is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the interview is a video call.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'location' => 'https://meet.google.com/' . $this->faker->uuid,
        ]);
    }
}
