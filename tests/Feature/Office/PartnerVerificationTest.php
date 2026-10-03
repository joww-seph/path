<?php

namespace Tests\Feature\Office;

use App\Enums\VerificationStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PartnerVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_queue_lists_pending_businesses_by_default(): void
    {
        Business::factory()->count(2)->create();
        Business::factory()->approved()->create();

        $this->actingAs(User::factory()->officer()->create())->get(route('office.partners.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('office/Partners')
                ->where('status', 'pending')
                ->has('businesses.data', 2)
                ->where('counts.approved', 1));
    }

    public function test_an_officer_approves_a_business(): void
    {
        $officer = User::factory()->officer()->create();
        $business = Business::factory()->create();

        $this->actingAs($officer)->put(route('office.partners.update', $business), ['decision' => 'approve'])->assertRedirect();

        $business->refresh();
        $this->assertSame(VerificationStatus::Approved, $business->verification_status);
        $this->assertSame($officer->id, $business->verified_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'business.approved', 'subject_id' => $business->id]);
    }

    public function test_rejecting_a_business_needs_a_note_the_partner_can_read(): void
    {
        $officer = User::factory()->officer()->create();
        $business = Business::factory()->create();

        $this->actingAs($officer)->put(route('office.partners.update', $business), ['decision' => 'reject'])
            ->assertSessionHasErrors('note');

        $this->actingAs($officer)->put(route('office.partners.update', $business), ['decision' => 'reject', 'note' => 'Permit expired.']);

        $business->refresh();
        $this->assertSame(VerificationStatus::Rejected, $business->verification_status);
        $this->assertSame('Permit expired.', $business->verification_note);
    }

    public function test_partners_cannot_verify_businesses(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($business->owner)->put(route('office.partners.update', $business), ['decision' => 'approve'])
            ->assertForbidden();

        $this->assertSame(VerificationStatus::Pending, $business->refresh()->verification_status);
    }
}
