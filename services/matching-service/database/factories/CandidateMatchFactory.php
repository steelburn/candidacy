<?php

namespace Database\Factories;

use App\Models\CandidateMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateMatchFactory extends Factory
{
    protected $model = CandidateMatch::class;

    public function definition(): array
    {
        $matchScore = $this->faker->numberBetween(50, 100);
        
        return [
            'candidate_id' => $this->faker->numberBetween(1, 100),
            'vacancy_id' => $this->faker->numberBetween(1, 50),
            'match_score' => $matchScore,
            'analysis' => [
                'strengths' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'gaps' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'recommendation' => $this->faker->paragraph(),
            ],
            'interview_questions' => [
                [
                    'question' => $this->faker->sentence() . '?',
                    'type' => $this->faker->randomElement(['technical', 'behavioral', 'situational']),
                    'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                ],
                [
                    'question' => $this->faker->sentence() . '?',
                    'type' => $this->faker->randomElement(['technical', 'behavioral', 'situational']),
                    'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                ],
            ],
            'questions_generated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'questions_model' => 'gemma2:2b',
            'status' => $this->faker->randomElement(['pending', 'reviewed', 'accepted', 'rejected']),
        ];
    }

    /**
     * Indicate that the match has a high score.
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'match_score' => $this->faker->numberBetween(85, 100),
            'status' => 'reviewed',
        ]);
    }

    /**
     * Indicate that the match is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
