<?php

namespace Tests\Feature\Guide;

use App\Models\Business;
use App\Models\Event;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_listing_shows_its_details_rates_stories_and_nearby_places(): void
    {
        $listing = Listing::factory()->forBusiness()->create(['latitude' => 18.06, 'longitude' => 120.52]);
        $listing->rates()->create(['name' => '4x4 ride', 'price' => 2500, 'unit' => 'ride']);
        $listing->rates()->create(['name' => 'Hidden rate', 'price' => 10, 'unit' => 'ride', 'is_active' => false]);
        $listing->heritageStories()->create(['title' => 'Old story', 'body' => 'Long ago…']);
        Listing::factory()->create(['latitude' => 18.061, 'longitude' => 120.521]);
        Event::factory()->create(['venue_listing_id' => $listing->id]);

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('guide/Listing')
                ->where('listing.name', $listing->name)
                ->has('listing.rates', 1)
                ->where('listing.rates.0.name', '4x4 ride')
                ->where('listing.heritage_stories.0.title', 'Old story')
                ->has('nearby', 1)
                ->has('events', 1));
    }

    public function test_guests_cannot_see_a_draft_listing(): void
    {
        $listing = Listing::factory()->forBusiness()->draft()->create();

        $this->get(route('listings.show', $listing))->assertForbidden();
    }

    public function test_the_owning_partner_and_the_office_can_preview_a_draft(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->draft()->create();

        $this->actingAs($business->owner)->get(route('listings.show', $listing))->assertOk();
        $this->actingAs(User::factory()->officer()->create())->get(route('listings.show', $listing))->assertOk();
        $this->actingAs(User::factory()->partner()->create())->get(route('listings.show', $listing))->assertForbidden();
    }

    public function test_the_map_lists_published_listings_that_have_a_pin(): void
    {
        Listing::factory()->create(['name' => 'Pinned']);
        Listing::factory()->create(['latitude' => null, 'longitude' => null]);
        Listing::factory()->draft()->create();

        $this->get(route('map'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('guide/Map')
                ->has('listings', 1)
                ->where('listings.0.name', 'Pinned'));
    }

    public function test_the_sitemap_lists_published_listings_only(): void
    {
        $published = Listing::factory()->create();
        $draft = Listing::factory()->draft()->create();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('listings.show', $published))
            ->assertDontSee(route('listings.show', $draft));
    }
}
