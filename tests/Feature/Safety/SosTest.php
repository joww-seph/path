<?php

namespace Tests\Feature\Safety;

use App\Enums\SosStatus;
use App\Models\EmergencyContact;
use App\Models\SosAlert;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\SosRaised;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class SosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 15:00:00');
        Notification::fake();
    }

    public function test_sos_texts_contacts_with_a_map_link_and_alerts_the_office(): void
    {
        $tourist = User::factory()->create(['name' => 'Ana Reyes']);
        EmergencyContact::factory()->for($tourist)->count(2)->create();
        $trip = Trip::factory()->for($tourist, 'owner')->create(['start_date' => now()->startOfDay(), 'end_date' => now()->startOfDay()->addDay()]);
        $officer = User::factory()->officer()->create();
        User::factory()->officer()->deactivated()->create();

        $this->mock(SmsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('send')->twice()->withArgs(fn (string $phone, string $text) => str_contains($text, 'Ana Reyes')
                && str_contains($text, 'mlat=18.0613&mlon=120.5211'));
        });

        $this->actingAs($tourist)->post(route('tourist.sos.store'), [
            'latitude' => 18.0613,
            'longitude' => 120.5211,
            'accuracy_meters' => 20,
            'message' => 'Twisted my ankle at the dunes',
        ])->assertRedirect();

        $alert = SosAlert::sole();
        $this->assertSame($trip->id, $alert->trip_id);
        $this->assertSame(2, $alert->contacts_notified);
        $this->assertSame(SosStatus::Open, $alert->status);

        Notification::assertSentTo($officer, SosRaised::class);
        Notification::assertSentTimes(SosRaised::class, 3);
        Notification::assertSentOnDemand(SosRaised::class, fn ($notification, array $channels, AnonymousNotifiable $notifiable) => isset($notifiable->routes['mail']));
    }

    public function test_sos_works_without_location_or_contacts(): void
    {
        $tourist = User::factory()->create();

        $this->actingAs($tourist)->post(route('tourist.sos.store'))->assertRedirect();

        $alert = SosAlert::sole();
        $this->assertNull($alert->mapUrl());
        $this->assertSame(0, $alert->contacts_notified);
    }

    public function test_the_sos_page_lists_contacts_and_hotlines(): void
    {
        $tourist = User::factory()->create();
        EmergencyContact::factory()->for($tourist)->create();

        $this->actingAs($tourist)->get(route('tourist.sos'))
            ->assertInertia(fn (Assert $page) => $page->component('tourist/Sos')->has('contacts', 1)->has('hotlines'));
    }

    public function test_office_staff_acknowledge_and_resolve_alerts(): void
    {
        $alert = (new SosAlert(['latitude' => 18.06, 'longitude' => 120.52]))->forceFill(['user_id' => User::factory()->create()->id]);
        $alert->save();
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->get(route('office.sos.index'))
            ->assertInertia(fn (Assert $page) => $page->component('office/SosMonitor')->has('active', 1)->has('resolved', 0));

        $this->actingAs($officer)->put(route('office.sos.update', $alert), ['status' => 'acknowledged'])->assertRedirect();
        $alert->refresh();
        $this->assertSame(SosStatus::Acknowledged, $alert->status);
        $this->assertSame($officer->id, $alert->acknowledged_by);

        $this->actingAs($officer)->put(route('office.sos.update', $alert), ['status' => 'resolved', 'office_notes' => 'MDRRMO team assisted.'])->assertRedirect();
        $alert->refresh();
        $this->assertSame(SosStatus::Resolved, $alert->status);
        $this->assertNotNull($alert->resolved_at);
        $this->assertSame('MDRRMO team assisted.', $alert->office_notes);

        $this->actingAs(User::factory()->create())->get(route('office.sos.index'))->assertForbidden();
    }
}
