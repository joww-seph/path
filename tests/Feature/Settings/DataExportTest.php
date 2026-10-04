<?php

namespace Tests\Feature\Settings;

use App\Models\Booking;
use App\Models\EmergencyContact;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_export_asks_for_the_password_first(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.export'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_guests_cannot_export_data(): void
    {
        $this->get(route('profile.export'))->assertRedirect(route('login'));
    }

    public function test_users_download_their_own_data_and_nobody_elses(): void
    {
        $user = User::factory()->create(['name' => 'Ana Reyes', 'email' => 'ana@example.com']);
        EmergencyContact::factory()->for($user)->create(['name' => 'Lito Reyes']);
        Trip::factory()->for($user, 'owner')->create(['title' => 'Paoay weekend']);
        Booking::factory()->for($user, 'tourist')->create(['code' => 'PTH-ANA111']);

        $stranger = User::factory()->create();
        Trip::factory()->for($stranger, 'owner')->create(['title' => 'Someone else’s trip']);
        Booking::factory()->for($stranger, 'tourist')->create(['code' => 'PTH-OTH222']);

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.export'));

        $response->assertOk()
            ->assertDownload('path-my-data-'.now()->format('Y-m-d').'.json')
            ->assertJsonPath('account.email', 'ana@example.com')
            ->assertJsonPath('emergency_contacts.0.name', 'Lito Reyes')
            ->assertJsonCount(1, 'trips')
            ->assertJsonPath('trips.0.title', 'Paoay weekend')
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.code', 'PTH-ANA111')
            ->assertJsonMissingPath('account.password');
    }
}
