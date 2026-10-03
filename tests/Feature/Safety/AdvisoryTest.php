<?php

namespace Tests\Feature\Safety;

use App\Jobs\NotifyTravellersOfAdvisory;
use App\Models\Advisory;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\AdvisoryPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdvisoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');
    }

    public function test_officers_publish_advisories_and_travellers_are_notified(): void
    {
        Queue::fake();
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->post(route('office.advisories.store'), [
            'title' => 'Typhoon signal no. 2',
            'body' => 'Sand dunes rides are suspended.',
            'severity' => 'danger',
            'starts_at' => '2026-10-04 00:00',
            'ends_at' => '2026-10-06 00:00',
        ])->assertRedirect();

        $advisory = Advisory::sole();
        Queue::assertPushed(NotifyTravellersOfAdvisory::class, fn ($job) => $job->advisory->is($advisory));
        $this->assertDatabaseHas('activity_logs', ['action' => 'advisory.published']);

        $this->actingAs($officer)->get(route('office.advisories.index'))
            ->assertInertia(fn (Assert $page) => $page->component('office/Advisories')->has('advisories.data', 1));

        $this->actingAs(User::factory()->create())->post(route('office.advisories.store'), [])->assertForbidden();
    }

    public function test_town_wide_warnings_appear_on_every_page_while_in_effect(): void
    {
        $officer = User::factory()->officer()->create();
        Queue::fake();

        $this->actingAs($officer)->post(route('office.advisories.store'), [
            'title' => 'Heavy rain',
            'body' => 'Expect flooding on the lakeside road.',
            'severity' => 'warning',
            'starts_at' => '2026-10-03 00:00',
        ]);
        $this->actingAs($officer)->post(route('office.advisories.store'), [
            'title' => 'Fiesta parade',
            'body' => 'Roads around the church close at 3 PM.',
            'severity' => 'info',
            'starts_at' => '2026-10-03 00:00',
        ]);

        $this->get(route('explore'))
            ->assertInertia(fn (Assert $page) => $page->has('townAdvisories', 1)->where('townAdvisories.0.title', 'Heavy rain'));
    }

    public function test_the_job_notifies_only_travellers_whose_trips_overlap_and_include_the_sites(): void
    {
        Notification::fake();
        $dunes = Listing::factory()->create();
        $church = Listing::factory()->create();

        $affected = Trip::factory()->create(['start_date' => '2026-10-04', 'end_date' => '2026-10-05']);
        $friend = User::factory()->create();
        $affected->members()->attach($friend, ['role' => 'viewer']);
        ItineraryItem::factory()->for($affected)->create(['listing_id' => $dunes->id]);

        $otherSite = Trip::factory()->create(['start_date' => '2026-10-04', 'end_date' => '2026-10-04']);
        ItineraryItem::factory()->for($otherSite)->create(['listing_id' => $church->id]);

        $later = Trip::factory()->create(['start_date' => '2026-11-01', 'end_date' => '2026-11-02']);
        ItineraryItem::factory()->for($later)->create(['listing_id' => $dunes->id]);

        $advisory = Advisory::create([
            'title' => 'Dunes closed',
            'body' => 'Strong winds.',
            'severity' => 'warning',
            'starts_at' => '2026-10-04 00:00',
            'ends_at' => '2026-10-05 23:00',
            'author_id' => User::factory()->officer()->create()->id,
        ]);
        $advisory->listings()->sync([$dunes->id]);

        (new NotifyTravellersOfAdvisory($advisory))->handle();

        Notification::assertSentTo([$affected->owner, $friend], AdvisoryPublished::class);
        Notification::assertNotSentTo([$otherSite->owner, $later->owner], AdvisoryPublished::class);
        $this->assertNotNull($advisory->fresh()->notified_at);
    }

    public function test_advisories_show_on_the_listings_they_name_and_in_the_planner(): void
    {
        $listing = Listing::factory()->create();
        $advisory = Advisory::create([
            'title' => 'Church under restoration',
            'body' => 'The interior is closed.',
            'severity' => 'info',
            'starts_at' => '2026-10-01 00:00',
            'ends_at' => '2026-10-31 00:00',
            'author_id' => User::factory()->officer()->create()->id,
        ]);
        $advisory->listings()->sync([$listing->id]);

        $this->get(route('listings.show', $listing->slug))
            ->assertInertia(fn (Assert $page) => $page->has('advisories', 1));

        $trip = Trip::factory()->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-10']);
        ItineraryItem::factory()->for($trip)->create(['listing_id' => $listing->id]);

        $this->actingAs($trip->owner)->get(route('tourist.trips.show', $trip))
            ->assertInertia(fn (Assert $page) => $page->where('warnings', fn ($warnings) => collect($warnings)->contains('type', 'advisory')));
    }
}
