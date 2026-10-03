<?php

namespace Tests\Feature\Reviews;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\Review;
use App\Models\SiteVisit;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');
        $this->listing = Listing::factory()->forBusiness(Business::factory()->approved()->create())->create();
    }

    public function test_tourists_cannot_review_places_they_have_not_visited(): void
    {
        $tourist = User::factory()->create();

        $this->actingAs($tourist)->post(route('tourist.reviews.store', $this->listing), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_completed_booking_unlocks_a_review_linked_to_it(): void
    {
        $tourist = User::factory()->create();
        $booking = Booking::factory()->for($tourist, 'tourist')->for($this->listing)->create(['status' => BookingStatus::Completed, 'checked_in_at' => now()]);

        $this->actingAs($tourist)
            ->post(route('tourist.reviews.store', $this->listing), ['rating' => 4, 'comment' => 'Great ride over the dunes.'])
            ->assertRedirect();

        $review = Review::sole();
        $this->assertSame($booking->id, $review->booking_id);
        $this->assertSame(4.0, (float) $this->listing->fresh()->rating_average);
        $this->assertSame(1, $this->listing->fresh()->reviews_count);

        // One review per place.
        $this->actingAs($tourist)->post(route('tourist.reviews.store', $this->listing), ['rating' => 1])->assertForbidden();
    }

    public function test_ticking_off_a_stop_records_visits_for_the_whole_trip_and_unlocks_reviews(): void
    {
        $trip = Trip::factory()->create(['start_date' => now()->startOfDay(), 'end_date' => now()->startOfDay()->addDay()]);
        $friend = User::factory()->create();
        $trip->members()->attach($friend, ['role' => 'editor']);
        $item = ItineraryItem::factory()->for($trip)->create(['listing_id' => $this->listing->id]);

        $this->actingAs($trip->owner)
            ->patch(route('tourist.trips.items.update', [$trip, $item]), ['is_done' => true])
            ->assertRedirect();

        $this->assertSame(2, SiteVisit::where('listing_id', $this->listing->id)->where('visited_on', '2026-10-03')->count());

        $this->actingAs($friend)->post(route('tourist.reviews.store', $this->listing), ['rating' => 5])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['user_id' => $friend->id, 'rating' => 5]);
    }

    public function test_ticking_off_a_stop_on_a_future_day_is_not_a_visit(): void
    {
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->for($trip)->create(['listing_id' => $this->listing->id]);

        $item->update(['is_done' => true]);

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_hidden_reviews_drop_out_of_the_rating_and_the_listing_page(): void
    {
        $visible = Review::factory()->for($this->listing)->create(['rating' => 5]);
        $abusive = Review::factory()->for($this->listing)->create(['rating' => 1, 'comment' => 'Spam spam spam']);
        $this->assertSame(3.0, (float) $this->listing->fresh()->rating_average);

        $officer = User::factory()->officer()->create();
        $this->actingAs($officer)
            ->put(route('office.reviews.update', $abusive), ['status' => 'hidden'])
            ->assertSessionHasErrors('moderation_note');
        $this->actingAs($officer)
            ->put(route('office.reviews.update', $abusive), ['status' => 'hidden', 'moderation_note' => 'Spam'])
            ->assertRedirect();

        $this->assertSame(ReviewStatus::Hidden, $abusive->fresh()->status);
        $this->assertSame(5.0, (float) $this->listing->fresh()->rating_average);
        $this->assertDatabaseHas('activity_logs', ['action' => 'review.hidden']);

        $this->get(route('listings.show', $this->listing->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reviews', 1)
                ->where('reviews.0.id', $visible->id));
    }

    public function test_tourists_and_partners_cannot_moderate(): void
    {
        $review = Review::factory()->for($this->listing)->create();

        $this->actingAs(User::factory()->create())->get(route('office.reviews.index'))->assertForbidden();
        $this->actingAs($this->listing->business->owner)->put(route('office.reviews.update', $review), ['status' => 'hidden', 'moderation_note' => 'x'])->assertForbidden();
    }

    public function test_partners_reply_only_to_reviews_of_their_own_listings(): void
    {
        $review = Review::factory()->for($this->listing)->create();
        $owner = $this->listing->business->owner;

        $this->actingAs($owner)->get(route('partner.reviews.index'))
            ->assertInertia(fn (Assert $page) => $page->component('partner/Reviews')->has('reviews.data', 1));

        $this->actingAs($owner)
            ->put(route('partner.reviews.reply', $review), ['partner_reply' => 'Salamat po! Come back soon.'])
            ->assertRedirect();
        $this->assertSame('Salamat po! Come back soon.', $review->fresh()->partner_reply);
        $this->assertNotNull($review->fresh()->partner_replied_at);

        $otherPartner = Business::factory()->approved()->create()->owner;
        $this->actingAs($otherPartner)->put(route('partner.reviews.reply', $review), ['partner_reply' => 'Hijack'])->assertForbidden();
    }

    public function test_authors_can_edit_and_delete_their_review(): void
    {
        $review = Review::factory()->for($this->listing)->create(['rating' => 2]);

        $this->actingAs($review->user)->put(route('tourist.reviews.update', $review), ['rating' => 4, 'comment' => 'Better on a second look'])->assertRedirect();
        $this->assertSame(4.0, (float) $this->listing->fresh()->rating_average);

        $this->actingAs(User::factory()->create())->delete(route('tourist.reviews.destroy', $review))->assertForbidden();

        $this->actingAs($review->user)->delete(route('tourist.reviews.destroy', $review))->assertRedirect();
        $this->assertModelMissing($review);
        $this->assertSame(0, $this->listing->fresh()->reviews_count);
    }
}
