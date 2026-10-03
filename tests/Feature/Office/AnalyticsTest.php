<?php

namespace Tests\Feature\Office;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\SiteVisit;
use App\Models\TouristProfile;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Listing $church;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');

        $this->church = Listing::factory()->create(['name' => 'Paoay Church']);
        $dunes = Listing::factory()->create(['name' => 'Paoay Sand Dunes']);

        $local = User::factory()->create();
        TouristProfile::factory()->for($local)->create(['home_country' => 'PH', 'home_province' => 'Metro Manila']);
        $foreign = User::factory()->create();
        TouristProfile::factory()->for($foreign)->create(['home_country' => 'JP', 'home_province' => null]);

        $trip = Trip::factory()->for($local, 'owner')->create(['start_date' => '2026-10-01', 'end_date' => '2026-10-02', 'pax' => 3]);
        ItineraryItem::factory()->for($trip)->create(['listing_id' => $this->church->id]);
        ItineraryItem::factory()->for($trip)->create(['listing_id' => $dunes->id]);
        Trip::factory()->for($foreign, 'owner')->create(['start_date' => '2026-10-02', 'end_date' => '2026-10-02', 'pax' => 2]);

        SiteVisit::record($this->church->id, $local->id, '2026-10-01', 'itinerary');
        SiteVisit::record($this->church->id, $foreign->id, '2026-10-02', 'itinerary');
        SiteVisit::record($dunes->id, $local->id, '2026-10-02', 'booking');

        Booking::factory()->for($dunes)->create(['date' => '2026-10-02', 'status' => BookingStatus::Completed, 'total_amount' => 2500]);
        Booking::factory()->for($dunes)->create(['date' => '2026-10-02', 'status' => BookingStatus::Declined]);
    }

    public function test_officers_see_visitor_figures_for_a_date_range(): void
    {
        $this->actingAs(User::factory()->officer()->create())
            ->get(route('office.analytics', ['from' => '2026-10-01', 'to' => '2026-10-03']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('office/Analytics')
                ->where('totals.trips', 2)
                ->where('totals.travellers', 5)
                ->where('totals.visits', 3)
                ->where('totals.bookings', 1)
                ->where('totals.booking_value', 2500)
                ->has('daily', 3)
                ->where('daily.1', ['date' => '2026-10-02', 'tourists' => 5, 'visits' => 2, 'bookings' => 1])
                ->where('peakDates.0.date', '2026-10-02')
                ->where('topSites.0.name', 'Paoay Church')
                ->where('topSites.0.visits', 2)
                ->where('topSites.0.planned', 1)
                ->has('origins', 2)
                ->has('bookingsByStatus', 2));
    }

    public function test_the_report_downloads_as_csv(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('office.analytics.export', ['from' => '2026-10-01', 'to' => '2026-10-03']));

        $response->assertOk()->assertDownload('path-analytics-2026-10-01-to-2026-10-03.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('2026-10-02,5,2,1', $csv);
        $this->assertStringContainsString('"Paoay Church",2,1', $csv);
        $this->assertStringContainsString('"Metro Manila",1', $csv);
    }

    public function test_partners_and_tourists_cannot_see_analytics(): void
    {
        $this->actingAs(User::factory()->create())->get(route('office.analytics'))->assertForbidden();
        $this->actingAs(User::factory()->partner()->create())->get(route('office.analytics.export'))->assertForbidden();
    }

    public function test_a_tourist_sees_a_recap_of_their_trip(): void
    {
        $trip = Trip::first();
        $trip->items()->where('listing_id', $this->church->id)->update(['is_done' => true, 'distance_km_from_previous' => 4.2]);

        $this->actingAs($trip->owner)->get(route('tourist.trips.recap', $trip))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tourist/trips/Recap')
                ->where('stats.stops_planned', 2)
                ->where('stats.stops_visited', 1)
                ->where('stats.distance_km', 4.2)
                ->where('visited.0.title', 'Paoay Church')
                ->where('visited.0.my_rating', null));

        $this->actingAs(User::factory()->create())->get(route('tourist.trips.recap', $trip))->assertForbidden();
    }
}
