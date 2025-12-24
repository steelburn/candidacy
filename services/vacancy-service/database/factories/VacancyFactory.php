<?php

namespace Database\Factories;

use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    public function definition(): array
    {
        $minSalary = $this->faker->numberBetween(40000, 100000);
        $maxSalary = $minSalary + $this->faker->numberBetween(20000, 50000);
        
        return [
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'responsibilities' => $this->faker->paragraphs(2, true),
            'department' => $this->faker->randomElement(['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations']),
            'location' => $this->faker->randomElement(['Remote', 'New York', 'San Francisco', 'London', 'Singapore']),
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'experience_level' => $this->faker->randomElement(['entry', 'mid', 'senior', 'lead', 'executive']),
            'min_experience_years' => $this->faker->numberBetween(0, 5),
            'max_experience_years' => $this->faker->numberBetween(5, 15),
            'min_salary' => $minSalary,
            'max_salary' => $maxSalary,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'SGD']),
            'required_skills' => json_encode([
                $this->faker->randomElement(['PHP', 'Python', 'JavaScript', 'Java']),
                $this->faker->randomElement(['Laravel', 'Django', 'React', 'Spring']),
            ]),
            'preferred_skills' => json_encode([
                $this->faker->randomElement(['Docker', 'Kubernetes', 'AWS', 'Azure']),
            ]),
            'benefits' => json_encode([
                'Health Insurance',
                'Dental Insurance',
                '401k',
                'Remote Work',
            ]),
            'status' => $this->faker->randomElement(['draft', 'open', 'closed', 'on_hold']),
            'closing_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'positions_available' => $this->faker->numberBetween(1, 5),
        ];
    }
}
