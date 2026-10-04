<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{Role, string}>
     */
    public static function roleHomes(): array
    {
        return [
            'tourist' => [Role::Tourist, '/my'],
            'partner' => [Role::Partner, '/partner'],
            'tourism officer' => [Role::TourismOfficer, '/office'],
            'admin' => [Role::Admin, '/admin'],
        ];
    }

    #[DataProvider('roleHomes')]
    public function test_the_dashboard_sends_each_role_to_its_own_area(Role $role, string $home): void
    {
        $user = User::factory()->role($role)->create();
        Business::factory()->for($user, 'owner')->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect($home);
        $this->actingAs($user)->get($home)->assertOk();
    }

    /**
     * @return array<string, array{Role, string}>
     */
    public static function forbiddenAreas(): array
    {
        return [
            'tourist in partner area' => [Role::Tourist, '/partner'],
            'tourist in office area' => [Role::Tourist, '/office'],
            'tourist in admin area' => [Role::Tourist, '/admin'],
            'partner in tourist area' => [Role::Partner, '/my'],
            'partner in admin area' => [Role::Partner, '/admin'],
            'officer in admin area' => [Role::TourismOfficer, '/admin'],
            'officer in partner area' => [Role::TourismOfficer, '/partner'],
        ];
    }

    #[DataProvider('forbiddenAreas')]
    public function test_a_role_cannot_open_another_roles_area(Role $role, string $path): void
    {
        $this->actingAs(User::factory()->role($role)->create())
            ->get($path)
            ->assertForbidden();
    }

    public function test_administrators_can_use_the_tourism_office_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/office')
            ->assertOk();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/my')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_new_registrations_are_tourists_even_if_they_ask_for_another_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_consent' => '1',
            'role' => 'admin',
        ]);

        $this->assertSame(Role::Tourist, User::where('email', 'sneaky@example.com')->sole()->role);
    }
}
