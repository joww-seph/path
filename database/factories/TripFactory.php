<?php

namespace Database\Factories;

use App\Enums\TravelMode;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(7, 60))->startOfDay();

        return [
            'user_id' => User::factory(),
            'title' => 'Paoay '.fake()->randomElement(['weekend', 'getaway', 'family trip', 'barkada trip']),
            'start_date' => $start,
            'end_date' => $start->addDay(),
            'pax' => fake()->numberBetween(1, 5),
            'budget' => fake()->randomElement([null, 5000, 10000, 20000]),
            'day_starts_at' => '08:00',
            'travel_mode' => TravelMode::Car,
        ];
    }

    public function days(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $attributes['start_date']->addDays($days - 1),
        ]);
    }
}
