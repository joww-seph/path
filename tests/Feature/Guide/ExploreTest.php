<?php

namespace Tests\Feature\Guide;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExploreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_only_published_listings(): void
    {
        Listing::factory()->create(['name' => 'Published Place']);
        Listing::factory()->draft()->create(['name' => 'Draft Place']);
        Listing::factory()->pending()->create(['name' => 'Pending Place']);

        $this->get(route('explore'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('guide/Explore')
                ->has('listings.data', 1)
                ->where('listings.data.0.name', 'Published Place'));
    }

    public function test_listings_can_be_filtered_by_category_and_searched(): void
    {
        $food = Category::factory()->create(['slug' => 'food']);
        Listing::factory()->for($food)->create(['name' => 'Bagnet House']);
        Listing::factory()->for($food)->create(['name' => 'Empanada Stall']);
        Listing::factory()->create(['name' => 'Bagnet Museum']);

        $this->get(route('explore', ['category' => 'food']))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 2));

        $this->get(route('explore', ['category' => 'food', 'q' => 'bagnet']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', 'Bagnet House'));
    }

    public function test_the_price_filter_uses_fees_starting_prices_and_rates(): void
    {
        Listing::factory()->create(['name' => 'Cheap fee', 'entrance_fee' => 50]);
        Listing::factory()->create(['name' => 'Pricey fee', 'entrance_fee' => 900]);
        $withRate = Listing::factory()->create(['name' => 'Cheap rate', 'price_min' => 3000]);
        ListingRate::factory()->for($withRate)->create(['price' => 100]);

        $this->get(route('explore', ['max_price' => 200, 'sort' => 'name']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data.0.name', 'Cheap fee')
                ->where('listings.data.1.name', 'Cheap rate'));
    }

    public function test_listings_can_be_limited_to_accessible_places(): void
    {
        Listing::factory()->create(['is_accessible' => true]);
        Listing::factory()->create(['is_accessible' => false]);

        $this->get(route('explore', ['accessible' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1)->where('listings.data.0.is_accessible', true));
    }

    public function test_the_open_on_filter_hides_places_closed_that_day(): void
    {
        Listing::factory()->openOn(['sat', 'sun'])->create(['name' => 'Weekend only']);
        Listing::factory()->openOn(['mon', 'tue', 'wed', 'thu', 'fri'])->create(['name' => 'Weekdays only']);
        Listing::factory()->create(['name' => 'Unknown hours', 'opening_hours' => null]);

        // 2026-10-10 is a Saturday.
        $this->get(route('explore', ['open_on' => '2026-10-10', 'sort' => 'name']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data.0.name', 'Unknown hours')
                ->where('listings.data.1.name', 'Weekend only'));
    }

    public function test_listings_can_be_sorted_by_distance_from_the_visitor(): void
    {
        Listing::factory()->create(['name' => 'Far', 'latitude' => 18.12, 'longitude' => 120.56]);
        Listing::factory()->create(['name' => 'Near', 'latitude' => 18.0618, 'longitude' => 120.5215]);
        Listing::factory()->create(['name' => 'No pin', 'latitude' => null, 'longitude' => null]);

        $this->get(route('explore', ['sort' => 'distance', 'lat' => 18.0617, 'lng' => 120.5214]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data.0.name', 'Near')
                ->where('listings.data.1.name', 'Far')
                ->where('listings.data.0.distance_km', 0));
    }

    public function test_sorting_by_distance_needs_a_location(): void
    {
        $this->get(route('explore', ['sort' => 'distance']))->assertSessionHasErrors(['lat', 'lng']);
    }
}
