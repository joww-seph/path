<?php

namespace App\Services;

use App\Support\Geo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Daily weather for Paoay from OpenWeatherMap's 5-day forecast, cached for an hour.
 * Without an API key, or when the service fails, there is simply no forecast.
 */
class WeatherService
{
    /**
     * A day gets a warning when rain is this likely (0–1)…
     */
    private const RAIN_CHANCE_WARNING = 0.7;

    /**
     * …or when gusts reach this speed in m/s (about 62 km/h, typhoon signal no. 2 territory).
     */
    private const STORM_GUST_MS = 17.0;

    public function __construct(private ?string $apiKey) {}

    /**
     * @return array<string, array{date: string, min: int, max: int, condition: string, description: string, icon: string, rain_chance: int, warning: string|null}>
     */
    public function daily(): array
    {
        if (blank($this->apiKey)) {
            return [];
        }

        return Cache::remember('weather:paoay:daily', now()->addHour(), function () {
            try {
                $response = Http::timeout(8)->acceptJson()->get('https://api.openweathermap.org/data/2.5/forecast', [
                    'lat' => Geo::PAOAY_CENTER['lat'],
                    'lon' => Geo::PAOAY_CENTER['lng'],
                    'units' => 'metric',
                    'appid' => $this->apiKey,
                ])->throw();
            } catch (Throwable $exception) {
                report($exception);

                return [];
            }

            return collect($response->json('list', []))
                ->groupBy(fn (array $slot) => now()->setTimestamp($slot['dt'])->toDateString())
                ->map(fn ($slots, string $date) => $this->summarise($date, $slots->all()))
                ->all();
        });
    }

    /**
     * @return array{date: string, min: int, max: int, condition: string, description: string, icon: string, rain_chance: int, warning: string|null}|null
     */
    public function forDate(string $date): ?array
    {
        return $this->daily()[$date] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $slots
     * @return array{date: string, min: int, max: int, condition: string, description: string, icon: string, rain_chance: int, warning: string|null}
     */
    private function summarise(string $date, array $slots): array
    {
        // Describe the day by its midday conditions when available.
        $midday = collect($slots)->sortBy(fn ($slot) => abs((int) date('G', $slot['dt']) - 12))->first();
        $rainChance = (float) collect($slots)->max('pop');
        $gust = (float) collect($slots)->max(fn ($slot) => $slot['wind']['gust'] ?? $slot['wind']['speed'] ?? 0);
        $thunder = collect($slots)->contains(fn ($slot) => str_starts_with((string) ($slot['weather'][0]['id'] ?? ''), '2'));

        $warning = match (true) {
            $gust >= self::STORM_GUST_MS => 'storm',
            $thunder => 'thunderstorm',
            $rainChance >= self::RAIN_CHANCE_WARNING => 'rain',
            default => null,
        };

        return [
            'date' => $date,
            'min' => (int) round(collect($slots)->min(fn ($slot) => $slot['main']['temp_min'])),
            'max' => (int) round(collect($slots)->max(fn ($slot) => $slot['main']['temp_max'])),
            'condition' => $midday['weather'][0]['main'] ?? 'Unknown',
            'description' => $midday['weather'][0]['description'] ?? '',
            'icon' => $midday['weather'][0]['icon'] ?? '01d',
            'rain_chance' => (int) round($rainChance * 100),
            'warning' => $warning,
        ];
    }
}
