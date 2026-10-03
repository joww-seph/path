<?php

namespace App\Services;

use App\Enums\TravelMode;
use App\Support\Geo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Travel time and distance between two points.
 *
 * Uses OpenRouteService when an API key is configured and caches each answer. Without a key,
 * or when the service fails, it estimates from straight-line distance and typical speeds.
 */
class RoutingService
{
    /**
     * Roads wind; straight-line distance is multiplied by this to estimate road distance.
     */
    private const ROAD_FACTOR = 1.3;

    /**
     * Minutes added to every non-walking leg for parking, boarding and waiting.
     */
    private const HANDLING_MINUTES = 3;

    private const CACHE_DAYS = 30;

    public function __construct(private ?string $apiKey) {}

    /**
     * @return array{minutes: int, km: float, estimated: bool}
     */
    public function between(float $fromLat, float $fromLng, float $toLat, float $toLng, TravelMode $mode): array
    {
        if (Geo::distanceKm($fromLat, $fromLng, $toLat, $toLng) < 0.05) {
            return ['minutes' => 0, 'km' => 0.0, 'estimated' => false];
        }

        if (blank($this->apiKey)) {
            return $this->estimate($fromLat, $fromLng, $toLat, $toLng, $mode);
        }

        $key = sprintf('route:%s:%.5f,%.5f:%.5f,%.5f', $mode->routingProfile(), $fromLat, $fromLng, $toLat, $toLng);

        return Cache::remember($key, now()->addDays(self::CACHE_DAYS), function () use ($fromLat, $fromLng, $toLat, $toLng, $mode) {
            try {
                $response = Http::timeout(8)
                    ->acceptJson()
                    ->get("https://api.openrouteservice.org/v2/directions/{$mode->routingProfile()}", [
                        'api_key' => $this->apiKey,
                        'start' => "{$fromLng},{$fromLat}",
                        'end' => "{$toLng},{$toLat}",
                    ])
                    ->throw();

                $summary = $response->json('features.0.properties.summary');

                return [
                    'minutes' => (int) ceil(($summary['duration'] ?? 0) / 60) + ($mode === TravelMode::Walk ? 0 : self::HANDLING_MINUTES),
                    'km' => round(($summary['distance'] ?? 0) / 1000, 2),
                    'estimated' => false,
                ];
            } catch (Throwable $exception) {
                report($exception);

                return $this->estimate($fromLat, $fromLng, $toLat, $toLng, $mode);
            }
        });
    }

    /**
     * @return array{minutes: int, km: float, estimated: bool}
     */
    public function estimate(float $fromLat, float $fromLng, float $toLat, float $toLng, TravelMode $mode): array
    {
        $km = Geo::distanceKm($fromLat, $fromLng, $toLat, $toLng) * self::ROAD_FACTOR;
        $minutes = (int) ceil($km / $mode->averageSpeedKmh() * 60) + ($mode === TravelMode::Walk ? 0 : self::HANDLING_MINUTES);

        return ['minutes' => $minutes, 'km' => round($km, 2), 'estimated' => true];
    }
}
