<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_map_shows_every_barangay_of_paoay(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame(31, substr_count($response->getContent(), 'class="piece '));
        $response->assertSee('aria-label="Oaig Upay Abulao"', false);
    }

    public function test_a_barangay_links_to_its_configured_facebook_page(): void
    {
        config(['barangays.facebook.laoa' => 'https://www.facebook.com/example-laoa']);

        $this->get('/')->assertSee('href="https://www.facebook.com/example-laoa"', false);
    }

    public function test_a_barangay_without_a_page_falls_back_to_a_facebook_search(): void
    {
        config(['barangays.facebook.suba' => null]);

        $this->get('/')->assertSee(
            'https://www.facebook.com/search/pages/?q='.rawurlencode('Barangay Suba Paoay Ilocos Norte'),
            false,
        );
    }
}
