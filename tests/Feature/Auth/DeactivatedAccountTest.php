<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->deactivated()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'This account has been deactivated. Contact the PaTH administrator.']);

        $this->assertGuest();
    }

    public function test_a_signed_in_user_is_signed_out_once_deactivated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->get(route('tourist.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_active_user_can_still_log_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
