<?php

namespace Tests\Feature\Safety;

use App\Models\Hotline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_see_the_hotlines_page(): void
    {
        Hotline::create(['name' => 'Emergency', 'type' => 'emergency', 'phone' => '911', 'position' => 1]);

        $this->get(route('hotlines'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('guide/Hotlines')->has('hotlines', 1));
    }

    public function test_admins_manage_hotlines(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.hotlines.store'), [
            'name' => 'Paoay MDRRMO',
            'type' => 'rescue',
            'phone' => '(077) 123-4567',
        ])->assertRedirect();

        $hotline = Hotline::sole();
        $this->assertSame(1, $hotline->position);

        $this->actingAs($admin)->put(route('admin.hotlines.update', $hotline), [
            'name' => 'Paoay MDRRMO (24/7)',
            'type' => 'rescue',
            'phone' => '0917 000 0000',
        ])->assertRedirect();
        $this->assertSame('Paoay MDRRMO (24/7)', $hotline->fresh()->name);

        $this->actingAs($admin)->post(route('admin.hotlines.store'), ['name' => 'Bad', 'type' => 'rescue', 'phone' => 'call me'])
            ->assertSessionHasErrors('phone');

        $this->actingAs($admin)->delete(route('admin.hotlines.destroy', $hotline))->assertRedirect();
        $this->assertModelMissing($hotline);
        $this->assertDatabaseHas('activity_logs', ['action' => 'hotline.deleted']);
    }

    public function test_officers_cannot_manage_hotlines(): void
    {
        $this->actingAs(User::factory()->officer()->create())->get(route('admin.hotlines.index'))->assertForbidden();
    }
}
