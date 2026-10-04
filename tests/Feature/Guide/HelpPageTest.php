<?php

namespace Tests\Feature\Guide;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_signed_in_users_can_open_the_help_guides(): void
    {
        $this->get(route('help'))->assertInertia(fn (Assert $page) => $page->component('guide/Help'));

        $this->actingAs(User::factory()->partner()->create())
            ->get(route('help'))
            ->assertOk();
    }
}
