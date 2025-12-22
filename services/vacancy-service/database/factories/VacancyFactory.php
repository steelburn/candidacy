<?php

namespace Database\Factories;

use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations']),
            'location' => $this->faker->randomElement(['Remote', 'New York', 'San Francisco', 'London', 'Singapore']),
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract', 'internship']),
            'experience_level' => $this->faker->randomElement(['entry', 'mid', 'senior', 'lead', 'executive']),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(['draft', 'open', 'closed', 'on-hold']),
        ];
    }
}
