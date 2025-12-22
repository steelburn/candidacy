<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'hired', 'rejected']),
            'summary' => $this->faker->paragraph(),
            'linkedin_url' => $this->faker->url(),
            'github_url' => $this->faker->url(),
            'portfolio_url' => $this->faker->url(),
            'years_of_experience' => $this->faker->numberBetween(0, 20),
            'notice_period' => $this->faker->randomElement(['immediate', '2 weeks', '1 month', '2 months', '3 months']),
            'notes' => $this->faker->paragraph(),
        ];
    }
}
