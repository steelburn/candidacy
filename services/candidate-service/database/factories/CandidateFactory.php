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
            'summary' => $this->faker->paragraph(),
            'linkedin_url' => $this->faker->url(),
            'github_url' => $this->faker->url(),
            'portfolio_url' => $this->faker->url(),
            'skills' => json_encode([
                $this->faker->randomElement(['PHP', 'Python', 'JavaScript', 'Java', 'C++']),
                $this->faker->randomElement(['Laravel', 'Django', 'React', 'Vue', 'Angular']),
                $this->faker->randomElement(['MySQL', 'PostgreSQL', 'MongoDB', 'Redis']),
            ]),
            'experience' => json_encode([
                [
                    'company' => $this->faker->company(),
                    'position' => $this->faker->jobTitle(),
                    'start_date' => $this->faker->date(),
                    'end_date' => $this->faker->date(),
                    'description' => $this->faker->paragraph(),
                ],
            ]),
            'education' => json_encode([
                [
                    'institution' => $this->faker->company() . ' University',
                    'degree' => $this->faker->randomElement(['Bachelor', 'Master', 'PhD']),
                    'field' => $this->faker->randomElement(['Computer Science', 'Engineering', 'Business']),
                    'start_date' => $this->faker->date(),
                    'end_date' => $this->faker->date(),
                ],
            ]),
            'status' => $this->faker->randomElement(['new', 'screening', 'interview', 'offer', 'hired', 'rejected']),
            'notes' => $this->faker->paragraph(),
        ];
    }
}
