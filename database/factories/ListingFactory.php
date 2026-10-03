<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => null,
            'category_id' => Category::factory(),
            'name' => fake()->unique()->company(),
            'summary' => fake()->sentence(10),
            'description' => fake()->paragraphs(2, true),
            'barangay' => fake()->randomElement(['pasil', 'suba', 'callaguip', 'nagbacalan', 'san-agustin']),
            'address' => 'Paoay, Ilocos Norte',
            'latitude' => Geo::PAOAY_CENTER['lat'] + fake()->randomFloat(4, -0.04, 0.04),
            'longitude' => Geo::PAOAY_CENTER['lng'] + fake()->randomFloat(4, -0.04, 0.04),
            'opening_hours' => array_fill_keys(Listing::WEEKDAYS, ['open' => '08:00', 'close' => '17:00']),
            'entrance_fee' => null,
            'price_min' => null,
            'price_max' => null,
            'visit_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'is_bookable' => false,
            'is_accessible' => fake()->boolean(),
            'status' => ListingStatus::Published,
            'published_at' => now(),
        ];
    }

    public function forBusiness(?Business $business = null): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business ?? Business::factory()->approved(),
            'is_bookable' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ListingStatus::Draft, 'published_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ListingStatus::Pending, 'published_at' => null]);
    }

    /**
     * @param  list<string>  $days
     */
    public function openOn(array $days, string $open = '08:00', string $close = '17:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'opening_hours' => array_fill_keys($days, ['open' => $open, 'close' => $close]),
        ]);
    }
}
