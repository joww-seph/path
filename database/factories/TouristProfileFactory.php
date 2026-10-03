<?php

namespace Database\Factories;

use App\Models\TouristProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TouristProfile>
 */
class TouristProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $budgetMin = fake()->numberBetween(1, 10) * 1000;

        return [
            'user_id' => User::factory(),
            'interests' => fake()->randomElements(TouristProfile::INTERESTS, 3),
            'group_size' => fake()->numberBetween(1, 6),
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMin + fake()->numberBetween(1, 10) * 1000,
            'accessibility_needs' => [],
            'home_province' => fake()->randomElement(['Metro Manila', 'Ilocos Norte', 'Pangasinan', 'Cebu', 'Benguet']),
            'home_country' => 'PH',
        ];
    }
}
