<?php

namespace Tests\Feature\Guide;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrivacyNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_read_the_privacy_notice_with_the_configured_contact(): void
    {
        config(['app.privacy_email' => 'dpo@example.gov.ph']);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('guide/Privacy')->where('contactEmail', 'dpo@example.gov.ph'));
    }
}
