<?php

namespace Tests\Feature\Tourist;

use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tourist_can_add_an_emergency_contact(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tourist.emergency-contacts.store'), [
            'name' => 'Rosa Reyes',
            'relationship' => 'Mother',
            'phone' => '0917-123-4567',
        ])->assertRedirect(route('tourist.emergency-contacts.index'));

        $contact = $user->emergencyContacts()->sole();
        $this->assertSame('Rosa Reyes', $contact->name);
        $this->assertSame('09171234567', $contact->phone);
    }

    public function test_a_tourist_can_update_and_remove_their_contact(): void
    {
        $user = User::factory()->create();
        $contact = EmergencyContact::factory()->for($user)->create();

        $this->actingAs($user)->put(route('tourist.emergency-contacts.update', $contact), [
            'name' => 'New Name',
            'phone' => '09181112222',
        ])->assertRedirect();
        $this->assertSame('New Name', $contact->refresh()->name);

        $this->actingAs($user)->delete(route('tourist.emergency-contacts.destroy', $contact))->assertRedirect();
        $this->assertModelMissing($contact);
    }

    public function test_a_tourist_cannot_change_someone_elses_contact(): void
    {
        $contact = EmergencyContact::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->put(route('tourist.emergency-contacts.update', $contact), [
            'name' => 'Hijacked',
            'phone' => '09181112222',
        ])->assertForbidden();

        $this->actingAs($intruder)->delete(route('tourist.emergency-contacts.destroy', $contact))->assertForbidden();

        $this->assertNotSame('Hijacked', $contact->refresh()->name);
    }

    public function test_a_tourist_can_save_at_most_five_contacts(): void
    {
        $user = User::factory()->create();
        EmergencyContact::factory()->count(5)->for($user)->create();

        $this->actingAs($user)->post(route('tourist.emergency-contacts.store'), [
            'name' => 'Sixth Contact',
            'phone' => '09171234567',
        ])->assertSessionHasErrors(['name' => 'You can save up to 5 emergency contacts.']);

        $this->assertSame(5, $user->emergencyContacts()->count());
    }

    public function test_the_phone_number_is_required_and_must_be_valid(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('tourist.emergency-contacts.store'), ['name' => 'Rosa', 'phone' => 'call me'])
            ->assertSessionHasErrors('phone');
    }
}
