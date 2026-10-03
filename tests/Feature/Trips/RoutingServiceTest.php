<?php

namespace Tests\Feature\Trips;

use App\Enums\TravelMode;
use App\Services\RoutingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutingServiceTest extends TestCase
{
    // Paoay Church to Malacañang of the North: about 6.2 km apart in a straight line.
    private const CHURCH = [18.0617, 120.5214];

    private const PALACE = [18.1142, 120.5411];

    public function test_without_an_api_key_travel_time_is_estimated_from_distance(): void
    {
        Http::fake();

        $leg = (new RoutingService(null))->between(...[...self::CHURCH, ...self::PALACE, TravelMode::Car]);

        $this->assertTrue($leg['estimated']);
        // About 8.1 km by road; at 30 km/h that is 17 minutes, plus 3 minutes for parking.
        $this->assertEqualsWithDelta(8.06, $leg['km'], 0.05);
        $this->assertSame(20, $leg['minutes']);
        Http::assertNothingSent();
    }

    public function test_walking_is_slower_and_has_no_parking_time(): void
    {
        $car = (new RoutingService(null))->between(...[...self::CHURCH, ...self::PALACE, TravelMode::Car]);
        $walk = (new RoutingService(null))->between(...[...self::CHURCH, ...self::PALACE, TravelMode::Walk]);

        $this->assertGreaterThan($car['minutes'] * 4, $walk['minutes']);
    }

    public function test_the_same_place_takes_no_travel_time(): void
    {
        $leg = (new RoutingService('key'))->between(...[...self::CHURCH, ...self::CHURCH, TravelMode::Car]);

        $this->assertSame(['minutes' => 0, 'km' => 0.0, 'estimated' => false], $leg);
    }

    public function test_openrouteservice_is_used_when_configured(): void
    {
        Http::fake([
            'api.openrouteservice.org/*' => Http::response([
                'features' => [['properties' => ['summary' => ['distance' => 9120.0, 'duration' => 900.0]]]],
            ]),
        ]);

        $leg = (new RoutingService('secret'))->between(...[...self::CHURCH, ...self::PALACE, TravelMode::Car]);

        $this->assertSame(['minutes' => 18, 'km' => 9.12, 'estimated' => false], $leg);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v2/directions/driving-car')
            && $request['start'] === '120.5214,18.0617');
    }

    public function test_it_falls_back_to_an_estimate_when_the_service_fails(): void
    {
        Http::fake(['api.openrouteservice.org/*' => Http::response('Quota exceeded', 429)]);

        $leg = (new RoutingService('secret'))->between(...[...self::CHURCH, ...self::PALACE, TravelMode::Car]);

        $this->assertTrue($leg['estimated']);
        $this->assertSame(20, $leg['minutes']);
    }
}
