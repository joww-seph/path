<?php

namespace Tests\Feature\Trips;

use App\Models\Category;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\Trip;
use App\Models\User;
use App\Services\ItineraryPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryPlannerTest extends TestCase
{
    use RefreshDatabase;

    private ItineraryPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouteservice.key' => null]);
        $this->planner = app(ItineraryPlanner::class);
    }

    private function listingAt(float $lat, float $lng, array $attributes = []): Listing
    {
        return Listing::factory()->create(['latitude' => $lat, 'longitude' => $lng, 'opening_hours' => null, 'visit_minutes' => 60, ...$attributes]);
    }

    private function stop(Trip $trip, Listing $listing, int $day = 1, int $position = 0, array $attributes = []): ItineraryItem
    {
        return ItineraryItem::factory()->for($trip)->for($listing)->create(['day_number' => $day, 'position' => $position, ...$attributes]);
    }

    public function test_it_schedules_stops_from_the_day_start_with_travel_between_them(): void
    {
        $trip = Trip::factory()->create(['day_starts_at' => '08:00']);
        $first = $this->stop($trip, $this->listingAt(18.0617, 120.5214));
        $second = $this->stop($trip, $this->listingAt(18.1142, 120.5411), position: 1);

        $this->planner->schedule($trip);

        $first->refresh();
        $second->refresh();
        $this->assertSame('08:00', substr($first->start_time, 0, 5));
        $this->assertSame('09:00', substr($first->end_time, 0, 5));
        $this->assertNull($first->travel_minutes_from_previous);
        $this->assertSame(20, $second->travel_minutes_from_previous);
        $this->assertSame('09:20', substr($second->start_time, 0, 5));
        $this->assertSame('10:20', substr($second->end_time, 0, 5));
    }

    public function test_a_fixed_start_time_holds_a_stop_until_then(): void
    {
        $trip = Trip::factory()->create(['day_starts_at' => '08:00']);
        $this->stop($trip, $this->listingAt(18.0617, 120.5214));
        $fixed = ItineraryItem::factory()->custom('Lunch')->for($trip)->create(['position' => 1, 'fixed_start_time' => '12:00', 'duration_minutes' => 90]);

        $this->planner->schedule($trip);

        $fixed->refresh();
        $this->assertSame('12:00', substr($fixed->start_time, 0, 5));
        $this->assertSame('13:30', substr($fixed->end_time, 0, 5));
    }

    public function test_arranging_a_day_puts_stops_in_the_shortest_order(): void
    {
        $trip = Trip::factory()->create();
        // Start at the church, then the farthest stop was added before the nearest one.
        $church = $this->stop($trip, $this->listingAt(18.0617, 120.5214), position: 0);
        $far = $this->stop($trip, $this->listingAt(18.1400, 120.5600), position: 1);
        $near = $this->stop($trip, $this->listingAt(18.0700, 120.5250), position: 2);
        $middle = $this->stop($trip, $this->listingAt(18.1000, 120.5400), position: 3);

        $this->planner->arrange($trip, 1);

        $this->assertSame(
            [$church->id, $near->id, $middle->id, $far->id],
            $trip->items()->pluck('id')->all(),
        );
    }

    public function test_balancing_spreads_a_full_plan_across_days_and_avoids_closed_days(): void
    {
        // Saturday 10 October 2026 and Sunday 11 October.
        $trip = Trip::factory()->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-11']);
        $weekdayOnly = $this->listingAt(18.0617, 120.5214, ['opening_hours' => ['sun' => ['open' => '08:00', 'close' => '17:00']]]);

        foreach (range(0, 5) as $index) {
            $this->stop($trip, $this->listingAt(18.06 + $index * 0.01, 120.52, ['visit_minutes' => 150]), position: $index);
        }
        $sundayStop = $this->stop($trip, $weekdayOnly, position: 6);

        $this->planner->balance($trip);

        $byDay = $trip->items()->get()->countBy('day_number');
        $this->assertGreaterThan(0, $byDay[1] ?? 0);
        $this->assertGreaterThan(0, $byDay[2] ?? 0);
        $this->assertSame(2, $sundayStop->refresh()->day_number, 'A stop closed on Saturday moves to Sunday.');
    }

    public function test_it_warns_when_a_site_is_closed_or_closes_before_the_visit_ends(): void
    {
        // Monday 12 October 2026.
        $trip = Trip::factory()->create(['start_date' => '2026-10-12', 'end_date' => '2026-10-12', 'day_starts_at' => '15:00']);
        $closedMonday = $this->listingAt(18.06, 120.52, ['name' => 'Museum', 'opening_hours' => ['tue' => ['open' => '09:00', 'close' => '16:00']]]);
        $closesEarly = $this->listingAt(18.06, 120.52, ['name' => 'Garden', 'opening_hours' => ['mon' => ['open' => '09:00', 'close' => '15:30']]]);
        $this->stop($trip, $closedMonday);
        $this->stop($trip, $closesEarly, position: 1);

        $this->planner->schedule($trip);
        $types = collect($this->planner->warnings($trip))->pluck('type', 'message');

        $this->assertContains('closed', $types);
        $this->assertContains('after_closing', $types);
        $this->assertArrayHasKey('Museum is closed on Monday.', $types->all());
    }

    public function test_it_warns_about_overfull_days(): void
    {
        $trip = Trip::factory()->create(['day_starts_at' => '08:00']);

        foreach (range(0, ItineraryPlanner::MAX_STOPS_PER_DAY) as $index) {
            $this->stop($trip, $this->listingAt(18.06, 120.52, ['visit_minutes' => 90]), position: $index);
        }

        $this->planner->schedule($trip);
        $types = collect($this->planner->warnings($trip))->pluck('type');

        $this->assertContains('too_many_stops', $types);
        $this->assertContains('long_day', $types);
    }

    public function test_suggestions_favour_the_travellers_interests_and_skip_added_places(): void
    {
        $food = Category::factory()->create(['slug' => 'food']);
        $attraction = Category::factory()->create(['slug' => 'attraction']);
        $user = User::factory()->create();
        $user->touristProfile()->create(['interests' => ['food']]);
        $trip = Trip::factory()->for($user, 'owner')->create();

        $eatery = Listing::factory()->for($food)->create(['name' => 'Empanada stall']);
        Listing::factory()->for($attraction)->create(['name' => 'Viewpoint']);
        $added = Listing::factory()->for($food)->create(['name' => 'Already added']);
        $this->stop($trip, $added);

        $suggestions = $this->planner->suggestions($trip, $user);

        $this->assertSame($eatery->id, $suggestions->first()->id);
        $this->assertNotContains($added->id, $suggestions->pluck('id'));
    }
}
