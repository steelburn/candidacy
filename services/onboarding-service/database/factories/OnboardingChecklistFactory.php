<?php

namespace Database\Factories;

use App\Models\OnboardingChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingChecklistFactory extends Factory
{
    protected $model = OnboardingChecklist::class;

    public function definition(): array
    {
        return [
            'candidate_id' => $this->faker->numberBetween(1, 100),
            'task_name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'completed']),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'completed_at' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'order' => $this->faker->numberBetween(1, 10),
            'tenant_id' => 1,
        ];
    }
}
