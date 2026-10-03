<?php

namespace Database\Factories;

use App\Enums\RateUnit;
use App\Models\Listing;
use App\Models\ListingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingRate>
 */
class ListingRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->forBusiness(),
            'name' => fake()->randomElement(['Standard', '4x4 ride, up to 5 people', 'Family room', 'Guided tour']),
            'description' => null,
            'price' => fake()->randomElement([500, 1500, 2500, 3500]),
            'unit' => RateUnit::PerPerson,
            'capacity' => fake()->numberBetween(1, 6),
            'is_active' => true,
            'position' => 0,
        ];
    }
}
