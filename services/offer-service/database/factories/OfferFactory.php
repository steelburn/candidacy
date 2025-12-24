<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        $salaryOffered = $this->faker->numberBetween(50000, 150000);
        $offerDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $expiryDate = (clone $offerDate)->modify('+2 weeks');
        
        return [
            'candidate_id' => $this->faker->numberBetween(1, 100),
            'vacancy_id' => $this->faker->numberBetween(1, 50),
            'salary_offered' => $salaryOffered,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'SGD']),
            'benefits' => [
                'Health Insurance',
                'Dental Insurance',
                '401k Matching',
                'Paid Time Off',
                'Remote Work Options',
                'Professional Development Budget',
            ],
            'start_date' => $this->faker->dateTimeBetween('+1 month', '+3 months'),
            'offer_date' => $offerDate,
            'expiry_date' => $expiryDate,
            'status' => $this->faker->randomElement(['pending', 'accepted', 'rejected', 'withdrawn', 'expired']),
            'terms' => $this->faker->paragraphs(3, true),
            'candidate_response' => $this->faker->optional()->paragraph(),
            'responded_at' => $this->faker->optional()->dateTimeBetween($offerDate, 'now'),
        ];
    }

    /**
     * Indicate that the offer is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'candidate_response' => null,
            'responded_at' => null,
            'expiry_date' => now()->addWeeks(2),
        ]);
    }

    /**
     * Indicate that the offer was accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'candidate_response' => 'I am pleased to accept this offer.',
            'responded_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the offer was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'candidate_response' => 'Thank you for the offer, but I have decided to pursue other opportunities.',
            'responded_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
