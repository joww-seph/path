<?php

namespace Tests\Feature\Safety;

use App\Jobs\SendBookingReminders;
use App\Jobs\SendTripReminders;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BookingStartingSoon;
use App\Notifications\TripStartsTomorrow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_travellers_are_reminded_the_evening_before_their_trip_once(): void
    {
        $this->travelTo('2026-10-03 18:00:00');
        $tomorrow = Trip::factory()->create(['start_date' => '2026-10-04', 'end_date' => '2026-10-05']);
        $friend = User::factory()->create();
        $tomorrow->members()->attach($friend, ['role' => 'viewer']);
        $nextWeek = Trip::factory()->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-11']);

        (new SendTripReminders)->handle();
        (new SendTripReminders)->handle();

        Notification::assertSentToTimes($tomorrow->owner, TripStartsTomorrow::class, 1);
        Notification::assertSentTo($friend, TripStartsTomorrow::class);
        Notification::assertNotSentTo($nextWeek->owner, TripStartsTomorrow::class);
        $this->assertNotNull($tomorrow->fresh()->reminded_at);
    }

    public function test_tourists_are_reminded_about_an_hour_before_a_confirmed_booking(): void
    {
        $this->travelTo('2026-10-03 07:00:00');
        $soon = Booking::factory()->confirmed()->create(['date' => '2026-10-03', 'time' => '08:00']);
        $later = Booking::factory()->confirmed()->create(['date' => '2026-10-03', 'time' => '14:00']);
        $pending = Booking::factory()->create(['date' => '2026-10-03', 'time' => '08:00']);

        (new SendBookingReminders)->handle();
        (new SendBookingReminders)->handle();

        Notification::assertSentToTimes($soon->tourist, BookingStartingSoon::class, 1);
        Notification::assertNotSentTo([$later->tourist, $pending->tourist], BookingStartingSoon::class);
    }
}
