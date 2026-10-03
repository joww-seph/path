<?php

namespace Tests\Feature\Trips;

use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TripSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_invites_a_companion_by_email(): void
    {
        $trip = Trip::factory()->create();
        $friend = User::factory()->create(['email' => 'friend@example.com']);

        $this->actingAs($trip->owner)->post(route('tourist.trips.members.store', $trip), [
            'email' => 'Friend@Example.com',
            'role' => 'editor',
        ])->assertSessionHasNoErrors();

        $this->assertSame('editor', $trip->roleFor($friend)->value);
    }

    public function test_inviting_an_unknown_email_explains_what_to_do(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs($trip->owner)->post(route('tourist.trips.members.store', $trip), [
            'email' => 'nobody@example.com',
            'role' => 'viewer',
        ])->assertSessionHasErrors(['email' => 'No tourist account uses that email. Ask your companion to sign up for PaTH first.']);
    }

    public function test_companions_cannot_invite_others(): void
    {
        $trip = Trip::factory()->create();
        $editor = User::factory()->create();
        $trip->members()->attach($editor, ['role' => 'editor']);
        User::factory()->create(['email' => 'other@example.com']);

        $this->actingAs($editor)->post(route('tourist.trips.members.store', $trip), [
            'email' => 'other@example.com',
            'role' => 'editor',
        ])->assertForbidden();
    }

    public function test_a_companion_can_leave_a_trip(): void
    {
        $trip = Trip::factory()->create();
        $viewer = User::factory()->create();
        $trip->members()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->delete(route('tourist.trips.members.destroy', [$trip, $viewer]))
            ->assertRedirect(route('tourist.trips.index'));

        $this->assertNull($trip->roleFor($viewer));
    }

    public function test_a_share_link_shows_a_read_only_itinerary_until_it_is_turned_off(): void
    {
        $trip = Trip::factory()->create();
        ItineraryItem::factory()->for($trip)->create();

        $this->actingAs($trip->owner)->post(route('tourist.trips.share.store', $trip))->assertRedirect();
        $token = $trip->refresh()->share_token;
        $this->assertNotNull($token);

        $this->app['auth']->guard('web')->logout();
        $this->get(route('trips.shared', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->component('guide/SharedTrip')
                ->has('days.0.items', 1)
                ->missing('trip.share_url'));

        $this->actingAs($trip->owner)->delete(route('tourist.trips.share.destroy', $trip));
        $this->get(route('trips.shared', $token))->assertNotFound();
    }
}
