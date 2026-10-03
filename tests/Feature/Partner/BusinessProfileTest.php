<?php

namespace Tests\Feature\Partner;

use App\Enums\BusinessType;
use App\Enums\VerificationStatus;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'name' => 'Paoay Lake Resort',
            'type' => BusinessType::Lodging->value,
            'permit_no' => 'BP-2026-1',
            'contact_phone' => '09181234567',
            'address' => 'Brgy. Nanguyudan',
            'payment_instructions' => 'GCash 0918 123 4567',
        ];
    }

    public function test_a_partner_can_update_their_business_profile(): void
    {
        $business = Business::factory()->approved()->create();

        $this->actingAs($business->owner)
            ->put(route('partner.business.update'), $this->payload())
            ->assertRedirect(route('partner.business.edit'));

        $business->refresh();
        $this->assertSame('Paoay Lake Resort', $business->name);
        $this->assertSame(BusinessType::Lodging, $business->type);
        $this->assertSame(VerificationStatus::Approved, $business->verification_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'business.updated', 'subject_id' => $business->id]);
    }

    public function test_updating_a_rejected_business_sends_it_back_for_verification(): void
    {
        $business = Business::factory()->rejected()->create();

        $this->actingAs($business->owner)->put(route('partner.business.update'), $this->payload());

        $business->refresh();
        $this->assertSame(VerificationStatus::Pending, $business->verification_status);
        $this->assertNull($business->verification_note);
    }

    public function test_the_partner_dashboard_shows_the_verification_status(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($business->owner)->get(route('partner.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('partner/Dashboard')
                ->where('business.verification_status', 'pending'));
    }
}
