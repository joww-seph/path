<?php

namespace Tests\Feature\Partner;

use App\Enums\ListingStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Dune Buggy Rides',
            'summary' => 'Fast rides on the dunes.',
            'barangay' => 'suba',
            'latitude' => 18.09,
            'longitude' => 120.50,
            'hours_known' => 1,
            'hours' => [
                'mon' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
                'tue' => ['closed' => 1],
                'wed' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
                'thu' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
                'fri' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
                'sat' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
                'sun' => ['open' => '06:00', 'close' => '18:00', 'closed' => 0],
            ],
            'price_min' => 2000,
            'visit_minutes' => 90,
            'contact_phone' => '0918 123 4567',
            'is_bookable' => 1,
            'is_featured' => 1,
            ...$overrides,
        ];
    }

    public function test_a_partner_creates_a_draft_listing_for_their_business(): void
    {
        $business = Business::factory()->approved()->create();

        $this->actingAs($business->owner)->post(route('partner.listings.store'), $this->payload())
            ->assertRedirect(route('partner.listings.edit', 'dune-buggy-rides'));

        $listing = Listing::sole();
        $this->assertSame($business->id, $listing->business_id);
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertArrayNotHasKey('tue', $listing->opening_hours);
        $this->assertSame(['open' => '06:00', 'close' => '18:00'], $listing->opening_hours['mon']);
        $this->assertSame('09181234567', $listing->contact_phone);
        $this->assertTrue($listing->is_bookable);
        $this->assertFalse($listing->is_featured, 'Partners cannot feature their own listings.');
    }

    public function test_unknown_hours_are_saved_as_null(): void
    {
        $business = Business::factory()->approved()->create();

        $this->actingAs($business->owner)->post(route('partner.listings.store'), $this->payload(['hours_known' => 0]));

        $this->assertNull(Listing::sole()->opening_hours);
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        $business = Business::factory()->approved()->create();
        $payload = $this->payload();
        $payload['hours']['mon'] = ['open' => '18:00', 'close' => '06:00', 'closed' => 0];

        $this->actingAs($business->owner)->post(route('partner.listings.store'), $payload)
            ->assertSessionHasErrors(['hours.mon' => 'Closing time must be after opening time.']);
    }

    public function test_the_map_pin_must_be_inside_paoay(): void
    {
        $business = Business::factory()->approved()->create();

        $this->actingAs($business->owner)->post(route('partner.listings.store'), $this->payload(['latitude' => 14.6, 'longitude' => 121.0]))
            ->assertSessionHasErrors(['latitude' => 'The map pin must be inside Paoay.']);
    }

    public function test_a_verified_partner_submits_a_draft_for_approval(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->draft()->create();

        $this->actingAs($business->owner)->post(route('partner.listings.submit', $listing))->assertRedirect();

        $this->assertSame(ListingStatus::Pending, $listing->refresh()->status);
    }

    public function test_an_unverified_partner_cannot_submit_a_listing(): void
    {
        $business = Business::factory()->create();
        $listing = Listing::factory()->forBusiness($business)->draft()->create();

        $this->actingAs($business->owner)->post(route('partner.listings.submit', $listing))->assertForbidden();

        $this->assertSame(ListingStatus::Draft, $listing->refresh()->status);
    }

    public function test_a_partner_cannot_edit_another_partners_listing(): void
    {
        $listing = Listing::factory()->forBusiness()->create();
        $otherBusiness = Business::factory()->approved()->create();

        $this->actingAs($otherBusiness->owner)->get(route('partner.listings.edit', $listing))->assertForbidden();
        $this->actingAs($otherBusiness->owner)->put(route('partner.listings.update', $listing), $this->payload())->assertForbidden();
    }

    public function test_editing_a_published_listing_keeps_it_published_and_updates_the_slug(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create(['name' => 'Old Name']);

        $this->actingAs($business->owner)->put(route('partner.listings.update', $listing), $this->payload(['name' => 'New Name']))
            ->assertRedirect(route('partner.listings.edit', 'new-name'));

        $listing->refresh();
        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertSame('new-name', $listing->slug);
    }

    public function test_partners_can_delete_drafts_but_not_published_listings(): void
    {
        $business = Business::factory()->approved()->create();
        $draft = Listing::factory()->forBusiness($business)->draft()->create();
        $published = Listing::factory()->forBusiness($business)->create();

        $this->actingAs($business->owner)->delete(route('partner.listings.destroy', $draft))->assertRedirect();
        $this->actingAs($business->owner)->delete(route('partner.listings.destroy', $published))->assertForbidden();

        $this->assertModelMissing($draft);
        $this->assertModelExists($published);
    }
}
