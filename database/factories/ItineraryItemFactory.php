<?php

namespace Database\Factories;

use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryItem>
 */
class ItineraryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'listing_id' => Listing::factory(),
            'day_number' => 1,
            'position' => 0,
        ];
    }

    public function custom(string $title = 'Lunch at home'): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_id' => null,
            'custom_title' => $title,
        ]);
    }
}
