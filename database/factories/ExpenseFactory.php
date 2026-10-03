<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomElement([150, 450, 1200, 2500]),
            'spent_on' => now()->toDateString(),
            'note' => fake()->optional()->words(3, true),
        ];
    }
}
