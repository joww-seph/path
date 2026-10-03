<?php

namespace Tests\Feature\Office;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_office_publishes_an_attraction_directly(): void
    {
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->post(route('office.listings.store'), [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Paoay Lake Viewpoint',
            'visit_minutes' => 30,
            'hours_known' => 0,
            'entrance_fee' => 0,
            'is_featured' => 1,
        ])->assertRedirect(route('office.listings.edit', 'paoay-lake-viewpoint'));

        $listing = Listing::sole();
        $this->assertNull($listing->business_id);
        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertTrue($listing->is_featured);
        $this->assertDatabaseHas('activity_logs', ['action' => 'listing.created', 'subject_id' => $listing->id]);
    }

    public function test_the_office_approves_a_pending_partner_listing(): void
    {
        $listing = Listing::factory()->forBusiness()->pending()->create();

        $this->actingAs(User::factory()->officer()->create())
            ->post(route('office.listings.review', $listing), ['decision' => 'approve'])
            ->assertRedirect();

        $listing->refresh();
        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'listing.approved', 'subject_id' => $listing->id]);
    }

    public function test_sending_a_listing_back_needs_a_note(): void
    {
        $listing = Listing::factory()->forBusiness()->pending()->create();
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)
            ->post(route('office.listings.review', $listing), ['decision' => 'reject'])
            ->assertSessionHasErrors('note');

        $this->actingAs($officer)
            ->post(route('office.listings.review', $listing), ['decision' => 'reject', 'note' => 'Add photos of the vehicles.']);

        $listing->refresh();
        $this->assertSame(ListingStatus::Rejected, $listing->status);
        $this->assertSame('Add photos of the vehicles.', $listing->review_note);
    }

    public function test_only_pending_listings_can_be_reviewed(): void
    {
        $listing = Listing::factory()->forBusiness()->draft()->create();

        $this->actingAs(User::factory()->officer()->create())
            ->post(route('office.listings.review', $listing), ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_archiving_hides_a_listing_from_explore(): void
    {
        $listing = Listing::factory()->create();

        $this->actingAs(User::factory()->officer()->create())->post(route('office.listings.archive', $listing));

        $this->assertSame(ListingStatus::Archived, $listing->refresh()->status);

        $this->app['auth']->guard('web')->logout();
        $this->get(route('listings.show', $listing))->assertForbidden();
        $this->get(route('explore'))->assertInertia(fn ($page) => $page->has('listings.data', 0));
    }

    public function test_partners_cannot_use_the_office_listing_tools(): void
    {
        $listing = Listing::factory()->forBusiness()->pending()->create();

        $this->actingAs($listing->business->owner)
            ->post(route('office.listings.review', $listing), ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_the_office_adds_heritage_stories_to_a_listing(): void
    {
        $listing = Listing::factory()->create();
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->post(route('office.listings.stories.store', $listing), [
            'title' => 'Built to survive earthquakes',
            'body' => 'Twenty-four buttresses…',
        ])->assertRedirect();

        $story = $listing->heritageStories()->sole();

        $other = Listing::factory()->create();
        $this->actingAs($officer)
            ->delete(route('office.listings.stories.destroy', ['listing' => $other, 'heritageStory' => $story]))
            ->assertNotFound();
    }
}
