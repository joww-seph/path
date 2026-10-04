<?php

namespace Tests\Feature\Auth;

use App\Enums\BusinessType;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Jun Agcaoili',
            'email' => 'jun@example.com',
            'phone' => '+63 918 123 4567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_name' => 'Paoay Dunes 4x4',
            'business_type' => BusinessType::ActivityOperator->value,
            'permit_no' => 'BP-2026-00412',
            'address' => 'Brgy. Suba, Paoay',
            'privacy_consent' => '1',
        ];
    }

    public function test_the_partner_registration_page_can_be_rendered(): void
    {
        $this->get(route('partner.register'))->assertOk();
    }

    public function test_a_business_owner_can_register_and_waits_for_verification(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('partner.register.store'), $this->validPayload())
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'jun@example.com')->sole();
        $business = $user->businesses()->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Partner, $user->role);
        $this->assertSame('09181234567', $user->phone);
        $this->assertSame('Paoay Dunes 4x4', $business->name);
        $this->assertSame(VerificationStatus::Pending, $business->verification_status);
        $this->assertSame('09181234567', $business->contact_phone);
        Event::assertDispatched(Registered::class);
    }

    public function test_business_details_are_required(): void
    {
        $this->post(route('partner.register.store'), [
            ...$this->validPayload(),
            'business_name' => '',
            'business_type' => 'casino',
            'permit_no' => '',
        ])->assertSessionHasErrors(['business_name', 'business_type', 'permit_no']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_mobile_number_must_be_a_philippine_mobile_number(): void
    {
        $this->post(route('partner.register.store'), [
            ...$this->validPayload(),
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');
    }
}
