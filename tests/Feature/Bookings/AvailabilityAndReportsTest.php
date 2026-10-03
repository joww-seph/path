<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AvailabilityAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');
    }

    public function test_a_partner_sets_default_slots_and_closes_dates(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();

        $this->actingAs($business->owner)->put(route('partner.listings.default-slots', $listing), ['default_daily_slots' => 3])->assertRedirect();
        $this->assertSame(3, $listing->refresh()->default_daily_slots);

        $this->actingAs($business->owner)->put(route('partner.listings.availability.update', $listing), [
            'dates' => ['2026-10-10', '2026-10-11'],
            'is_closed' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, AvailabilityBlock::where('is_closed', true)->count());

        $this->actingAs($business->owner)->get(route('partner.listings.availability', [$listing, 'month' => '2026-10']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('partner/listings/Availability')
                ->where('blocks.2026-10-10.is_closed', true));
    }

    public function test_slots_cannot_drop_below_what_is_already_booked(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();
        AvailabilityBlock::create(['listing_id' => $listing->id, 'date' => '2026-10-10', 'slots_booked' => 3]);

        $this->actingAs($business->owner)->put(route('partner.listings.availability.update', $listing), [
            'dates' => ['2026-10-10'],
            'slots_total' => 1,
        ])->assertSessionHasErrors('slots_total');
    }

    public function test_another_partner_cannot_change_availability(): void
    {
        $listing = Listing::factory()->forBusiness()->create();
        $intruder = Business::factory()->approved()->create()->owner;

        $this->actingAs($intruder)->put(route('partner.listings.default-slots', $listing), ['default_daily_slots' => 0])->assertForbidden();
    }

    public function test_reports_sum_completed_earnings_and_rank_rates(): void
    {
        $business = Business::factory()->approved()->create();
        $listing = Listing::factory()->forBusiness($business)->create();
        Booking::factory()->for($listing)->create(['status' => BookingStatus::Completed, 'total_amount' => 2500, 'rate_name' => 'Ride', 'date' => '2026-09-20']);
        Booking::factory()->for($listing)->create(['status' => BookingStatus::Completed, 'total_amount' => 3500, 'rate_name' => 'Ride + board', 'date' => '2026-10-02']);
        Booking::factory()->for($listing)->create(['status' => BookingStatus::Confirmed, 'total_amount' => 2500, 'rate_name' => 'Ride', 'date' => '2026-10-10']);
        Booking::factory()->for($listing)->create(['status' => BookingStatus::Declined, 'total_amount' => 2500, 'rate_name' => 'Ride', 'date' => '2026-10-11']);

        $this->actingAs($business->owner)->get(route('partner.reports'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('partner/Reports')
                ->where('totals.earnings', 6000)
                ->where('totals.completed', 2)
                ->where('totals.acceptance_rate', 75)
                ->where('topRates.0.name', 'Ride')
                ->where('topRates.0.bookings', 2)
                ->has('byMonth', 12));
    }
}
