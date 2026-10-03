<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_filipino_sends_filipino_translations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'fil'])->assertRedirect();

        $this->assertSame('fil', $user->refresh()->locale);

        $this->actingAs($user)->get(route('tourist.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale.current', 'fil')
                ->where('locale.translations.Travel preferences', 'Mga kagustuhan sa biyahe'));
    }

    public function test_guests_can_switch_language_for_their_session(): void
    {
        $this->post(route('locale.update'), ['locale' => 'fil'])->assertSessionHas('locale', 'fil');
    }

    public function test_an_unsupported_language_is_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'xx'])->assertSessionHasErrors('locale');
    }
}
