<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'title' => fake()->unique()->words(3, true).' festival',
            'description' => fake()->paragraph(),
            'venue_name' => 'Paoay town plaza',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+6 hours'),
            'is_featured' => false,
        ];
    }
}
