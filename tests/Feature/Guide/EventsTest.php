<?php

namespace Tests\Feature\Guide;

use App\Models\Event;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_calendar_shows_events_that_overlap_the_month(): void
    {
        $this->travelTo('2027-01-15 09:00');

        Event::factory()->create(['title' => 'In February', 'starts_at' => '2027-02-09 08:00', 'ends_at' => '2027-02-09 18:00']);
        Event::factory()->create(['title' => 'Spans January into February', 'starts_at' => '2027-01-30 08:00', 'ends_at' => '2027-02-02 18:00']);
        Event::factory()->create(['title' => 'In March', 'starts_at' => '2027-03-21 06:00', 'ends_at' => '2027-03-28 12:00']);

        $this->get(route('events.index', ['month' => '2027-02']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('guide/Events')
                ->where('month', '2027-02')
                ->has('events', 2)
                ->where('events.0.title', 'Spans January into February')
                ->has('upcoming', 3));
    }

    public function test_an_event_page_can_be_viewed(): void
    {
        $event = Event::factory()->create(['title' => 'Guling-Guling Festival', 'starts_at' => '2027-02-09 08:00']);

        $this->assertSame('guling-guling-festival-2027', $event->slug);

        $this->get(route('events.show', $event))
            ->assertInertia(fn (Assert $page) => $page->component('guide/Event')->where('event.title', 'Guling-Guling Festival'));
    }

    public function test_the_office_can_add_update_and_delete_events(): void
    {
        $officer = User::factory()->officer()->create();
        $venue = Listing::factory()->create();

        $this->actingAs($officer)->post(route('office.events.store'), [
            'title' => 'Paoay Kite Festival',
            'starts_at' => '2027-04-10 09:00',
            'ends_at' => '2027-04-10 17:00',
            'venue_listing_id' => $venue->id,
            'is_featured' => '1',
        ])->assertSessionHasNoErrors();

        $event = Event::sole();
        $this->assertTrue($event->is_featured);
        $this->assertSame($officer->id, $event->created_by);

        $this->actingAs($officer)->put(route('office.events.update', $event), [
            'title' => 'Paoay Kite Festival 2027',
            'starts_at' => '2027-04-10 09:00',
            'venue_name' => 'Suba beach',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Paoay Kite Festival 2027', $event->refresh()->title);
        $this->assertFalse($event->is_featured);

        $this->actingAs($officer)->delete(route('office.events.destroy', $event))->assertRedirect();
        $this->assertModelMissing($event);
    }

    public function test_an_event_needs_a_venue_and_cannot_end_before_it_starts(): void
    {
        $this->actingAs(User::factory()->officer()->create())->post(route('office.events.store'), [
            'title' => 'Bad event',
            'starts_at' => '2027-04-10 09:00',
            'ends_at' => '2027-04-09 09:00',
        ])->assertSessionHasErrors(['venue_name', 'ends_at']);
    }

    public function test_tourists_cannot_manage_events(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('office.events.store'), ['title' => 'X', 'starts_at' => '2027-01-01', 'venue_name' => 'Y'])
            ->assertForbidden();
    }
}
