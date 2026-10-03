<?php

namespace Tests\Feature\Trips;

use App\Models\ItineraryItem;
use App\Models\ItineraryTemplate;
use App\Models\Listing;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TripPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouteservice.key' => null]);
        $this->travelTo('2026-10-03 09:00');
    }

    public function test_a_tourist_creates_a_trip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tourist.trips.store'), [
            'title' => 'Barkada weekend',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'pax' => 4,
            'budget' => 12000,
            'travel_mode' => 'tricycle',
        ])->assertRedirect();

        $trip = $user->trips()->sole();
        $this->assertSame(2, $trip->dayCount());
        $this->assertSame('tricycle', $trip->travel_mode->value);
    }

    public function test_a_trip_can_start_from_a_template(): void
    {
        $church = Listing::factory()->create();
        $dunes = Listing::factory()->create();
        $template = ItineraryTemplate::create(['name' => 'Paoay in One Day', 'slug' => 'paoay-in-one-day', 'days' => 1]);
        $template->items()->create(['listing_id' => $church->id, 'day_number' => 1, 'position' => 0, 'duration_minutes' => 60]);
        $template->items()->create(['listing_id' => $dunes->id, 'day_number' => 1, 'position' => 1]);
        $template->items()->create(['custom_title' => 'Ilocano lunch', 'day_number' => 1, 'position' => 2]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tourist.trips.store'), [
            'title' => 'Day trip',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'pax' => 2,
            'template' => 'paoay-in-one-day',
        ]);

        $items = $user->trips()->sole()->items;
        $this->assertSame([$church->id, $dunes->id, null], $items->pluck('listing_id')->all());
        $this->assertSame('Ilocano lunch', $items[2]->custom_title);
        $this->assertNotNull($items[0]->start_time, 'Template stops are scheduled straight away.');
    }

    public function test_trips_cannot_start_in_the_past_or_run_longer_than_two_weeks(): void
    {
        $this->actingAs(User::factory()->create())->post(route('tourist.trips.store'), [
            'title' => 'Too long',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-30',
            'pax' => 1,
        ])->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_the_planner_shows_days_stops_and_warnings(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->days(2)->create();
        ItineraryItem::factory()->for($trip)->create(['day_number' => 2]);

        $this->actingAs($user)->get(route('tourist.trips.show', $trip))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tourist/trips/Show')
                ->has('days', 2)
                ->has('days.0.items', 0)
                ->has('days.1.items', 1)
                ->where('trip.role', 'owner')
                ->where('can.update', true)
                ->has('warnings')
                ->has('suggestions'));
    }

    public function test_strangers_cannot_open_a_trip(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('tourist.trips.show', $trip))->assertForbidden();
    }

    public function test_a_viewer_companion_can_see_but_not_change_the_trip(): void
    {
        $trip = Trip::factory()->create();
        $viewer = User::factory()->create();
        $trip->members()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->get(route('tourist.trips.show', $trip))
            ->assertInertia(fn (Assert $page) => $page->where('can.update', false)->where('trip.role', 'viewer'));

        $this->actingAs($viewer)->post(route('tourist.trips.items.store', $trip), [
            'custom_title' => 'Sneaky stop',
            'day_number' => 1,
        ])->assertForbidden();
    }

    public function test_an_editor_companion_can_add_stops(): void
    {
        $trip = Trip::factory()->create();
        $editor = User::factory()->create();
        $trip->members()->attach($editor, ['role' => 'editor']);
        $listing = Listing::factory()->create();

        $this->actingAs($editor)->post(route('tourist.trips.items.store', $trip), [
            'listing_id' => $listing->id,
            'day_number' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $trip->items()->sole()->day_number);
    }

    public function test_draft_listings_cannot_be_added_and_days_must_be_inside_the_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        $draft = Listing::factory()->draft()->create();

        $this->actingAs($user)->post(route('tourist.trips.items.store', $trip), [
            'listing_id' => $draft->id,
            'day_number' => 5,
        ])->assertSessionHasErrors(['listing_id', 'day_number']);
    }

    public function test_a_repeated_offline_submission_is_saved_once(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        $payload = ['custom_title' => 'Merienda', 'day_number' => 1, 'client_uuid' => '6f1c7a3e-4b8b-4a63-9c38-0d0f0f9c1a11'];

        $this->actingAs($user)->post(route('tourist.trips.items.store', $trip), $payload);
        $this->actingAs($user)->post(route('tourist.trips.items.store', $trip), $payload);

        $this->assertSame(1, $trip->items()->count());
    }

    public function test_reordering_moves_stops_within_and_between_days(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->days(2)->create();
        $a = ItineraryItem::factory()->for($trip)->create(['position' => 0]);
        $b = ItineraryItem::factory()->for($trip)->create(['position' => 1]);
        $c = ItineraryItem::factory()->for($trip)->create(['position' => 2]);

        $this->actingAs($user)->post(route('tourist.trips.reorder', $trip), [
            'days' => [1 => [$c->id, $a->id], 2 => [$b->id]],
        ])->assertSessionHasNoErrors();

        $this->assertSame([1, 1], [$c->refresh()->day_number, $a->refresh()->day_number]);
        $this->assertSame([0, 1], [$c->position, $a->position]);
        $this->assertSame(2, $b->refresh()->day_number);
    }

    public function test_reordering_rejects_stops_from_another_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        $mine = ItineraryItem::factory()->for($trip)->create();
        $theirs = ItineraryItem::factory()->create();

        $this->actingAs($user)->post(route('tourist.trips.reorder', $trip), [
            'days' => [1 => [$mine->id, $theirs->id]],
        ])->assertSessionHasErrors('days');

        $this->assertNotSame($trip->id, $theirs->refresh()->trip_id);
    }

    public function test_a_stop_from_another_trip_cannot_be_edited_through_this_one(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        $theirs = ItineraryItem::factory()->create();

        $this->actingAs($user)
            ->delete(route('tourist.trips.items.destroy', ['trip' => $trip, 'item' => $theirs]))
            ->assertNotFound();
    }

    public function test_editing_a_stop_updates_notes_duration_and_done_state(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        $item = ItineraryItem::factory()->for($trip)->create();

        $this->actingAs($user)->patch(route('tourist.trips.items.update', [$trip, $item]), [
            'notes' => 'Bring water',
            'duration_minutes' => 45,
            'is_done' => true,
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Bring water', $item->notes);
        $this->assertSame(45, $item->visitMinutes());
        $this->assertTrue($item->is_done);
    }

    public function test_shortening_a_trip_moves_later_stops_to_the_last_day(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-12']);
        $dayThree = ItineraryItem::factory()->for($trip)->create(['day_number' => 3]);

        $this->actingAs($user)->put(route('tourist.trips.update', $trip), [
            'title' => $trip->title,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'pax' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $dayThree->refresh()->day_number);
    }

    public function test_only_the_owner_can_delete_a_trip(): void
    {
        $trip = Trip::factory()->create();
        $editor = User::factory()->create();
        $trip->members()->attach($editor, ['role' => 'editor']);

        $this->actingAs($editor)->delete(route('tourist.trips.destroy', $trip))->assertForbidden();
        $this->actingAs($trip->owner)->delete(route('tourist.trips.destroy', $trip))->assertRedirect(route('tourist.trips.index'));

        $this->assertModelMissing($trip);
    }

    public function test_arranging_the_whole_trip_runs_the_planner(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create();
        ItineraryItem::factory()->count(3)->for($trip)->create();

        $this->actingAs($user)->post(route('tourist.trips.arrange', $trip), ['mode' => 'all'])->assertSessionHasNoErrors();

        $this->assertSame(0, $trip->items()->whereNull('start_time')->count());
    }

    public function test_the_itinerary_downloads_as_a_pdf(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user, 'owner')->create(['title' => 'Family Trip']);
        ItineraryItem::factory()->for($trip)->create();

        $this->actingAs($user)->get(route('tourist.trips.pdf', $trip))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('family-trip-itinerary.pdf');
    }

    public function test_the_listing_page_offers_the_tourists_upcoming_trips(): void
    {
        $user = User::factory()->create();
        Trip::factory()->for($user, 'owner')->create(['title' => 'Next week']);
        Trip::factory()->for($user, 'owner')->create(['start_date' => '2026-09-01', 'end_date' => '2026-09-02']);
        $listing = Listing::factory()->create();

        $this->actingAs($user)->get(route('listings.show', $listing))
            ->assertInertia(fn (Assert $page) => $page->has('myTrips', 1)->where('myTrips.0.title', 'Next week'));

        $this->app['auth']->guard('web')->logout();
        $this->get(route('listings.show', $listing))->assertInertia(fn (Assert $page) => $page->where('myTrips', null));
    }
}
