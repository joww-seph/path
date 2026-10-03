<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_search_users_by_name_email_or_phone(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Maria Clara', 'phone' => '09170000001']);
        User::factory()->create(['name' => 'Crisostomo Ibarra']);

        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Clara']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Users')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Maria Clara'));

        $this->actingAs($admin)->get(route('admin.users.index', ['search' => '09170000001']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
    }

    public function test_an_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->partner()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'partner']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
    }

    public function test_an_admin_can_change_a_users_role_and_it_is_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $user), ['role' => 'tourism_officer'])
            ->assertRedirect();

        $this->assertSame(Role::TourismOfficer, $user->refresh()->role);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'user.role_changed',
            'subject_id' => $user->id,
        ]);
    }

    public function test_an_admin_cannot_change_their_own_role_or_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin), ['role' => 'tourist'])
            ->assertSessionHasErrors(['user' => 'You cannot change your own account here.']);

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $admin))
            ->assertSessionHasErrors('user');

        $admin->refresh();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertNull($admin->deactivated_at);
    }

    public function test_an_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.deactivate', $user))->assertRedirect();
        $this->assertNotNull($user->refresh()->deactivated_at);

        $this->actingAs($admin)->post(route('admin.users.reactivate', $user))->assertRedirect();
        $this->assertNull($user->refresh()->deactivated_at);

        $this->assertDatabaseHas('activity_logs', ['action' => 'user.deactivated', 'subject_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.reactivated', 'subject_id' => $user->id]);
    }

    public function test_an_invalid_role_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.users.role', $user), ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    }

    public function test_a_tourism_officer_cannot_manage_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs(User::factory()->officer()->create())
            ->patch(route('admin.users.role', $user), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(Role::Tourist, $user->refresh()->role);
    }

    public function test_the_activity_log_lists_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.users.deactivate', $user));

        $this->actingAs($admin)->get(route('admin.activity.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ActivityLog')
                ->where('entries.data.0.action', 'user.deactivated'));
    }
}
