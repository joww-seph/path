<?php

namespace Tests\Feature\Settings;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }

    public function test_accounts_with_upcoming_bookings_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        Booking::factory()->confirmed()->for($user, 'tourist')->create(['date' => now()->addDays(3)->toDateString()]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrors(['account' => 'You have upcoming bookings. Cancel or complete them before deleting your account.']);

        $this->assertModelExists($user);
    }

    public function test_partners_with_upcoming_bookings_from_guests_cannot_delete_their_account(): void
    {
        $booking = Booking::factory()->create(['date' => now()->addDays(3)->toDateString()]);
        $partner = $booking->listing->business->owner;

        $this->actingAs($partner)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrors('account');

        $this->assertModelExists($partner);
    }

    public function test_deleting_an_account_removes_its_reviews_from_listing_ratings(): void
    {
        $listing = Listing::factory()->create();
        Review::factory()->for($listing)->create(['rating' => 5]);
        $leaving = User::factory()->create();
        Review::factory()->for($listing)->for($leaving)->create(['rating' => 1]);
        $this->assertSame(3.0, (float) $listing->fresh()->rating_average);

        $this->actingAs($leaving)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($leaving);
        $this->assertSame(1, $listing->fresh()->reviews_count);
        $this->assertSame(5.0, (float) $listing->fresh()->rating_average);
    }
}
