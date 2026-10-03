<?php

namespace Tests\Feature\Safety;

use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-03 09:00:00');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function slot(string $time, array $overrides = []): array
    {
        return array_replace_recursive([
            'dt' => strtotime($time.' Asia/Manila'),
            'main' => ['temp_min' => 26, 'temp_max' => 31],
            'weather' => [['id' => 800, 'main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
            'wind' => ['speed' => 3, 'gust' => 5],
            'pop' => 0.1,
        ], $overrides);
    }

    private function fakeForecast(): void
    {
        $this->app->instance(WeatherService::class, new WeatherService('test-key'));

        Http::fake(['api.openweathermap.org/*' => Http::response(['list' => [
            $this->slot('2026-10-04 11:00'),
            $this->slot('2026-10-04 14:00', ['main' => ['temp_min' => 28, 'temp_max' => 33]]),
            $this->slot('2026-10-05 11:00', ['pop' => 0.9, 'weather' => [['id' => 501, 'main' => 'Rain', 'description' => 'moderate rain', 'icon' => '10d']]]),
            $this->slot('2026-10-06 11:00', ['wind' => ['gust' => 22]]),
        ]])]);
    }

    public function test_the_forecast_is_summarised_per_day_with_warnings(): void
    {
        $this->fakeForecast();
        $weather = app(WeatherService::class);

        $daily = $weather->daily();

        $this->assertSame(['2026-10-04', '2026-10-05', '2026-10-06'], array_keys($daily));
        $this->assertSame(26, $daily['2026-10-04']['min']);
        $this->assertSame(33, $daily['2026-10-04']['max']);
        $this->assertNull($daily['2026-10-04']['warning']);
        $this->assertSame('rain', $daily['2026-10-05']['warning']);
        $this->assertSame(90, $daily['2026-10-05']['rain_chance']);
        $this->assertSame('storm', $daily['2026-10-06']['warning']);

        // Cached: a second call does not hit the API again.
        $weather->forDate('2026-10-05');
        Http::assertSentCount(1);
    }

    public function test_without_a_key_or_when_the_api_fails_there_is_no_forecast(): void
    {
        $this->assertSame([], (new WeatherService(null))->daily());

        Http::fake(['api.openweathermap.org/*' => Http::response('down', 500)]);
        $this->assertSame([], (new WeatherService('test-key'))->daily());
    }

    public function test_the_planner_shows_the_forecast_and_warns_about_bad_weather(): void
    {
        $this->fakeForecast();
        $trip = Trip::factory()->create(['start_date' => '2026-10-04', 'end_date' => '2026-10-05']);
        ItineraryItem::factory()->for($trip)->create(['day_number' => 2]);

        $this->actingAs($trip->owner)->get(route('tourist.trips.show', $trip))
            ->assertInertia(fn (Assert $page) => $page
                ->has('weather.2026-10-04')
                ->where('weather.2026-10-05.warning', 'rain')
                ->where('warnings', fn ($warnings) => collect($warnings)->contains(fn ($warning) => $warning['type'] === 'weather' && $warning['day'] === 2)));
    }
}
