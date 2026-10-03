<?php

namespace Tests\Feature\Tourist;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tourist_can_save_travel_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('tourist.preferences.update'), [
            'interests' => ['heritage', 'food'],
            'group_size' => 4,
            'budget_min' => 3000,
            'budget_max' => 8000,
            'accessibility_needs' => ['senior'],
            'home_province' => 'Pangasinan',
            'home_country' => 'ph',
        ])->assertRedirect(route('tourist.preferences.edit'));

        $profile = $user->touristProfile()->sole();
        $this->assertSame(['heritage', 'food'], $profile->interests);
        $this->assertSame(4, $profile->group_size);
        $this->assertSame(['senior'], $profile->accessibility_needs);
        $this->assertSame('PH', $profile->home_country);
    }

    public function test_saving_again_updates_the_same_profile(): void
    {
        $user = User::factory()->create();
        $payload = ['group_size' => 2, 'home_country' => 'PH'];

        $this->actingAs($user)->put(route('tourist.preferences.update'), $payload);
        $this->actingAs($user)->put(route('tourist.preferences.update'), [...$payload, 'group_size' => 5]);

        $this->assertDatabaseCount('tourist_profiles', 1);
        $this->assertSame(5, $user->touristProfile()->sole()->group_size);
        $this->assertSame([], $user->touristProfile()->sole()->interests);
    }

    public function test_unknown_interests_and_an_inverted_budget_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->put(route('tourist.preferences.update'), [
            'interests' => ['casino'],
            'group_size' => 2,
            'budget_min' => 9000,
            'budget_max' => 1000,
            'home_country' => 'PH',
        ])->assertSessionHasErrors(['interests.0', 'budget_max']);
    }

    public function test_the_preferences_page_shows_the_saved_profile(): void
    {
        $user = User::factory()->create();
        $user->touristProfile()->create(['group_size' => 3, 'interests' => ['nature']]);

        $this->actingAs($user)->get(route('tourist.preferences.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tourist/Preferences')
                ->where('profile.group_size', 3)
                ->where('profile.interests', ['nature']));
    }
}
