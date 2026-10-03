<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Enums\RateUnit;
use App\Jobs\ExpirePendingBookings;
use App\Jobs\MarkNoShowBookings;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Listing;
use App\Models\ListingRate;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BookingRequested;
use App\Notifications\BookingStatusChanged;
use App\Services\BookingService;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Listing $listing;

    private ListingRate $ride;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');
        Notification::fake();

        $this->business = Business::factory()->approved()->create(['payment_instructions' => 'GCash 0918 123 4567']);
        $this->listing = Listing::factory()->forBusiness($this->business)->create(['default_daily_slots' => 2, 'opening_hours' => null]);
        $this->ride = ListingRate::factory()->for($this->listing)->create([
            'name' => '4x4 ride, up to 5 people',
            'price' => 2500,
            'unit' => RateUnit::PerRide,
            'capacity' => 5,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function requestBooking(User $tourist, array $overrides = []): TestResponse
    {
        return $this->actingAs($tourist)->post(route('tourist.bookings.store'), [
            'listing_id' => $this->listing->id,
            'listing_rate_id' => $this->ride->id,
            'date' => '2026-10-10',
            'time' => '07:00',
            'pax' => 4,
            ...$overrides,
        ]);
    }

    public function test_quotes_depend_on_how_the_rate_is_charged(): void
    {
        $service = app(BookingService::class);

        $this->assertSame(['quantity' => 2, 'total' => 5000.0], $service->quote($this->ride, 7));
        $perPerson = new ListingRate(['price' => 300, 'unit' => RateUnit::PerPerson]);
        $this->assertSame(['quantity' => 4, 'total' => 1200.0], $service->quote($perPerson, 4));
        $room = new ListingRate(['price' => 1800, 'unit' => RateUnit::PerNight, 'capacity' => 2]);
        $this->assertSame(['quantity' => 2, 'total' => 10800.0], $service->quote($room, 3, 3));
    }

    public function test_a_tourist_requests_a_booking_and_the_partner_is_notified(): void
    {
        $tourist = User::factory()->create();

        $this->requestBooking($tourist)->assertRedirect();

        $booking = Booking::sole();
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('2500.00', $booking->total_amount);
        $this->assertSame('4x4 ride, up to 5 people', $booking->rate_name);
        $this->assertMatchesRegularExpression('/^PTH-[A-HJ-NP-Z2-9]{6}$/', $booking->code);
        $this->assertSame('2026-10-05 09:00:00', $booking->expires_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, AvailabilityBlock::sole()->slots_booked);
        Notification::assertSentTo($this->business->owner, BookingRequested::class);
    }

    public function test_the_last_slot_cannot_be_taken_twice(): void
    {
        $this->requestBooking(User::factory()->create())->assertSessionHasNoErrors();
        $this->requestBooking(User::factory()->create())->assertSessionHasNoErrors();

        $this->requestBooking(User::factory()->create())
            ->assertSessionHasErrors(['date' => 'Not enough slots left on October 10. Only 0 available.']);

        $this->assertSame(2, Booking::count());
        $this->assertSame(2, AvailabilityBlock::sole()->slots_booked);
    }

    public function test_a_date_the_partner_closed_cannot_be_booked(): void
    {
        AvailabilityBlock::create(['listing_id' => $this->listing->id, 'date' => '2026-10-10', 'is_closed' => true]);

        $this->requestBooking(User::factory()->create())->assertSessionHasErrors('date');

        $this->assertSame(0, Booking::count());
    }

    public function test_only_this_listings_active_rates_on_bookable_listings_can_be_booked(): void
    {
        $tourist = User::factory()->create();
        $otherRate = ListingRate::factory()->create();

        $this->requestBooking($tourist, ['listing_rate_id' => $otherRate->id])->assertSessionHasErrors('listing_rate_id');

        $this->ride->update(['is_active' => false]);
        $this->requestBooking($tourist)->assertSessionHasErrors('listing_rate_id');

        $this->assertSame(0, Booking::count());
    }

    public function test_past_dates_cannot_be_booked(): void
    {
        $this->requestBooking(User::factory()->create(), ['date' => '2026-10-01'])->assertSessionHasErrors('date');
    }

    public function test_confirming_creates_a_voucher_and_adds_the_booking_to_the_trip(): void
    {
        $tourist = User::factory()->create();
        $trip = Trip::factory()->for($tourist, 'owner')->create(['start_date' => '2026-10-09', 'end_date' => '2026-10-11']);
        $this->requestBooking($tourist, ['trip_id' => $trip->id]);
        $booking = Booking::sole();

        $this->actingAs($this->business->owner)->post(route('partner.bookings.confirm', $booking))->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->qr_token);
        Notification::assertSentTo($tourist, BookingStatusChanged::class);

        $item = $trip->items()->sole();
        $this->assertSame(2, $item->day_number);
        $this->assertSame($booking->id, $item->booking_id);
        $this->assertSame('07:00', substr($item->fixed_start_time, 0, 5));

        $this->actingAs($tourist)->get(route('tourist.bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tourist/bookings/Show')
                ->where('booking.status', 'confirmed')
                ->where('paymentInstructions', 'GCash 0918 123 4567')
                ->where('qr', fn ($qr) => str_starts_with($qr, '<svg')));
    }

    public function test_declining_needs_a_reason_and_frees_the_slot(): void
    {
        $tourist = User::factory()->create();
        $this->requestBooking($tourist);
        $booking = Booking::sole();

        $this->actingAs($this->business->owner)->post(route('partner.bookings.decline', $booking))->assertSessionHasErrors('reason');
        $this->actingAs($this->business->owner)->post(route('partner.bookings.decline', $booking), ['reason' => 'Vehicle under repair'])->assertRedirect();

        $booking->refresh();
        $this->assertSame(BookingStatus::Declined, $booking->status);
        $this->assertSame('Vehicle under repair', $booking->partner_note);
        $this->assertSame(0, AvailabilityBlock::sole()->slots_booked);
    }

    public function test_a_declined_booking_cannot_then_be_confirmed(): void
    {
        $booking = Booking::factory()->for($this->listing)->create(['status' => BookingStatus::Declined]);

        $this->actingAs($this->business->owner)->post(route('partner.bookings.confirm', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertSame(BookingStatus::Declined, $booking->refresh()->status);
    }

    public function test_a_tourist_cancels_and_the_trip_stop_is_removed(): void
    {
        $tourist = User::factory()->create();
        $trip = Trip::factory()->for($tourist, 'owner')->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-10']);
        $this->requestBooking($tourist, ['trip_id' => $trip->id]);
        $booking = Booking::sole();
        app(BookingService::class)->confirm($booking, $this->business->owner);

        $this->actingAs($tourist)->post(route('tourist.bookings.cancel', $booking), ['reason' => 'Change of plans'])->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $booking->refresh()->status);
        $this->assertSame(0, $trip->items()->count());
        $this->assertSame(0, AvailabilityBlock::sole()->slots_booked);
        Notification::assertSentTo($this->business->owner, BookingStatusChanged::class);
    }

    public function test_a_confirmed_booking_cannot_be_cancelled_after_its_date(): void
    {
        $booking = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-01']);

        $this->actingAs($booking->tourist)->post(route('tourist.bookings.cancel', $booking))->assertSessionHasErrors('booking');
    }

    public function test_the_partner_checks_in_a_guest_by_code_on_the_day(): void
    {
        $booking = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-03']);

        $this->actingAs($this->business->owner)->post(route('partner.check-in.store'), ['code' => strtolower($booking->code)])
            ->assertRedirect(route('partner.bookings.index', ['status' => 'completed']));

        $booking->refresh();
        $this->assertSame(BookingStatus::Completed, $booking->status);
        $this->assertNotNull($booking->checked_in_at);

        // The visit counts for analytics and lets the guest review the place.
        $this->assertDatabaseHas('site_visits', ['listing_id' => $this->listing->id, 'user_id' => $booking->user_id, 'visited_on' => '2026-10-03', 'source' => 'booking']);
    }

    public function test_scanning_a_voucher_opens_its_check_in_page(): void
    {
        $booking = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-03']);

        $this->actingAs($this->business->owner)->get(route('partner.check-in.show', $booking->qr_token))
            ->assertInertia(fn (Assert $page) => $page->component('partner/bookings/CheckIn')->where('booking.code', $booking->code));

        $this->actingAs($this->business->owner)->post(route('partner.check-in.store'), ['token' => $booking->qr_token])->assertRedirect();
        $this->assertSame(BookingStatus::Completed, $booking->refresh()->status);
    }

    public function test_check_in_refuses_other_days_other_partners_and_unconfirmed_bookings(): void
    {
        $future = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-05']);
        $this->actingAs($this->business->owner)->post(route('partner.check-in.store'), ['code' => $future->code])
            ->assertSessionHasErrors(['code' => "Booking {$future->code} is for October 5, 2026, not today."]);

        $today = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-03']);
        $intruder = Business::factory()->approved()->create()->owner;
        $this->actingAs($intruder)->post(route('partner.check-in.store'), ['code' => $today->code])
            ->assertSessionHasErrors('code');
        $this->actingAs($intruder)->get(route('partner.check-in.show', $today->qr_token))->assertForbidden();

        $pending = Booking::factory()->for($this->listing)->create(['date' => '2026-10-03']);
        $this->actingAs($this->business->owner)->post(route('partner.check-in.store'), ['code' => $pending->code])
            ->assertSessionHasErrors('code');
        $this->assertSame(BookingStatus::Pending, $pending->refresh()->status);
    }

    public function test_partners_cannot_answer_other_partners_bookings(): void
    {
        $booking = Booking::factory()->for($this->listing)->create();
        $intruder = Business::factory()->approved()->create()->owner;

        $this->actingAs($intruder)->post(route('partner.bookings.confirm', $booking))->assertForbidden();
        $this->assertSame(BookingStatus::Pending, $booking->refresh()->status);
    }

    public function test_other_tourists_cannot_see_a_voucher(): void
    {
        $booking = Booking::factory()->for($this->listing)->create();

        $this->actingAs(User::factory()->create())->get(route('tourist.bookings.show', $booking))->assertForbidden();
    }

    public function test_unanswered_requests_expire_after_48_hours_and_free_their_slots(): void
    {
        $tourist = User::factory()->create();
        $this->requestBooking($tourist);
        $booking = Booking::sole();

        $this->travelTo('2026-10-05 09:01:00');
        (new ExpirePendingBookings)->handle(app(BookingService::class));

        $this->assertSame(BookingStatus::Expired, $booking->refresh()->status);
        $this->assertSame(0, AvailabilityBlock::sole()->slots_booked);
        Notification::assertSentTo($tourist, BookingStatusChanged::class);
    }

    public function test_confirmed_bookings_not_checked_in_become_no_shows(): void
    {
        $missed = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-02']);
        $todays = Booking::factory()->confirmed()->for($this->listing)->create(['date' => '2026-10-03']);

        (new MarkNoShowBookings)->handle(app(BookingService::class));

        $this->assertSame(BookingStatus::NoShow, $missed->refresh()->status);
        $this->assertSame(BookingStatus::Confirmed, $todays->refresh()->status);
    }

    public function test_the_voucher_downloads_as_a_pdf_once_confirmed(): void
    {
        $booking = Booking::factory()->confirmed()->for($this->listing)->create();

        $this->actingAs($booking->tourist)->get(route('tourist.bookings.pdf', $booking))
            ->assertOk()
            ->assertDownload("{$booking->code}.pdf");

        $pending = Booking::factory()->for($this->listing)->create();
        $this->actingAs($pending->tourist)->get(route('tourist.bookings.pdf', $pending))->assertNotFound();
    }

    public function test_the_partner_inbox_lists_requests_for_their_listings(): void
    {
        Booking::factory()->count(2)->for($this->listing)->create();
        Booking::factory()->create();

        $this->actingAs($this->business->owner)->get(route('partner.bookings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('partner/bookings/Index')
                ->has('bookings.data', 2)
                ->where('counts.pending', 2));
    }

    public function test_the_trip_estimate_counts_bookings_at_their_price(): void
    {
        $tourist = User::factory()->create();
        $trip = Trip::factory()->for($tourist, 'owner')->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-10', 'pax' => 4]);
        $this->requestBooking($tourist, ['trip_id' => $trip->id]);

        $this->assertSame(2500.0, app(BudgetService::class)->estimate($trip));
    }
}
