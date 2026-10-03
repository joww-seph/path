<?php

namespace Tests\Feature\Trips;

use App\Models\EmergencyContact;
use App\Models\Hotline;
use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Models\User;
use App\Services\ItineraryPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trip_downloads_with_everything_needed_offline(): void
    {
        $trip = Trip::factory()->create();
        ItineraryItem::factory()->for($trip)->create();
        Hotline::create(['name' => 'Emergency', 'type' => 'emergency', 'phone' => '911']);
        EmergencyContact::factory()->for($trip->owner)->create(['name' => 'Rosa']);

        $this->actingAs($trip->owner)->getJson(route('api.trips.offline', $trip))
            ->assertOk()
            ->assertJsonPath('trip.id', $trip->id)
            ->assertJsonCount(1, 'days.0.items')
            ->assertJsonPath('hotlines.0.phone', '911')
            ->assertJsonPath('emergency_contacts.0.name', 'Rosa')
            ->assertJsonPath('can_update', true)
            ->assertJsonStructure(['saved_at', 'listings', 'budget', 'expenses', 'travellers']);
    }

    public function test_strangers_cannot_download_a_trip(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.trips.offline', Trip::factory()->create()))
            ->assertForbidden();

        $this->app['auth']->guard('web')->logout();
        $this->getJson(route('api.trips.offline', Trip::factory()->create()))->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function operation(string $type, Trip $trip, array $data, string $madeAt = '2026-10-10 10:00:00'): array
    {
        return ['id' => (string) str()->uuid(), 'type' => $type, 'trip_id' => $trip->id, 'made_at' => $madeAt, 'data' => $data];
    }

    public function test_offline_expenses_are_saved_once_even_if_sent_twice(): void
    {
        $trip = Trip::factory()->create();
        $expense = $this->operation('expense.create', $trip, [
            'client_uuid' => '0f8a5a0e-3c2b-4d6e-9f1a-2b3c4d5e6f70',
            'category' => 'food',
            'amount' => 120,
            'spent_on' => '2026-10-10',
            'note' => 'Empanada',
        ]);

        $this->actingAs($trip->owner)->postJson(route('api.sync'), ['operations' => [$expense]])
            ->assertJsonPath('results.0.status', 'applied');

        $this->actingAs($trip->owner)->postJson(route('api.sync'), ['operations' => [$expense]])
            ->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSame(1, $trip->expenses()->count());
        $this->assertSame('Empanada', $trip->expenses()->sole()->note);
    }

    public function test_a_stop_ticked_offline_is_applied_when_newer_than_the_server_copy(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->for($trip)->create();

        $this->travelTo('2026-10-10 12:00:00');
        $this->actingAs($trip->owner)->postJson(route('api.sync'), ['operations' => [
            $this->operation('item.update', $trip, ['item_id' => $item->id, 'is_done' => true, 'notes' => 'Loved it'], '2026-10-10 11:00:00'),
        ]])->assertJsonPath('results.0.status', 'applied');

        $item->refresh();
        $this->assertTrue($item->is_done);
        $this->assertSame('Loved it', $item->notes);
    }

    public function test_an_offline_edit_older_than_the_server_copy_loses(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->for($trip)->create(['notes' => 'Newer note from a companion']);

        $this->actingAs($trip->owner)->postJson(route('api.sync'), ['operations' => [
            $this->operation('item.update', $trip, ['item_id' => $item->id, 'notes' => 'Old offline note'], '2026-10-10 09:00:00'),
        ]])->assertJsonPath('results.0.status', 'conflict');

        $this->assertSame('Newer note from a companion', $item->refresh()->notes);
    }

    public function test_operations_on_trips_the_user_cannot_edit_are_rejected(): void
    {
        $trip = Trip::factory()->create();
        $viewer = User::factory()->create();
        $trip->members()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->postJson(route('api.sync'), ['operations' => [
            $this->operation('expense.create', $trip, ['client_uuid' => '0f8a5a0e-3c2b-4d6e-9f1a-2b3c4d5e6f71', 'category' => 'food', 'amount' => 50, 'spent_on' => '2026-10-10']),
        ]])->assertJsonPath('results.0.status', 'rejected');

        $this->assertSame(0, $trip->expenses()->count());
    }

    public function test_an_item_from_another_trip_cannot_be_changed(): void
    {
        $trip = Trip::factory()->create();
        $otherItem = ItineraryItem::factory()->create();

        $this->actingAs($trip->owner)->postJson(route('api.sync'), ['operations' => [
            $this->operation('item.update', $trip, ['item_id' => $otherItem->id, 'is_done' => true]),
        ]])->assertJsonPath('results.0.status', 'rejected');

        $this->assertFalse($otherItem->refresh()->is_done);
    }

    public function test_scheduling_does_not_make_offline_edits_look_stale(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $trip = Trip::factory()->create();
        $item = ItineraryItem::factory()->for($trip)->create();

        $this->travelTo('2026-10-10 12:00:00');
        app(ItineraryPlanner::class)->schedule($trip);

        $this->assertSame('2026-10-10 08:00:00', $item->refresh()->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_the_offline_page_is_public(): void
    {
        $this->get(route('offline'))->assertOk()->assertSee('Saved trips');
    }
}
