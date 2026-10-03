<?php

namespace App\Support;

class Geo
{
    /**
     * The middle of Paoay town, near San Agustin Church. Maps open here.
     */
    public const PAOAY_CENTER = ['lat' => 18.0617, 'lng' => 120.5222];

    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Straight-line ("as the crow flies") distance between two points, in kilometres.
     */
    public static function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
