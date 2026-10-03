<?php

namespace App\Enums;

enum TravelMode: string
{
    case Car = 'car';
    case Tricycle = 'tricycle';
    case Walk = 'walk';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Car or van',
            self::Tricycle => 'Tricycle',
            self::Walk => 'On foot',
        };
    }

    /**
     * Typical average speed on Paoay's roads, used when no routing service is configured.
     */
    public function averageSpeedKmh(): float
    {
        return match ($this) {
            self::Car => 30.0,
            self::Tricycle => 20.0,
            self::Walk => 4.5,
        };
    }

    /**
     * The OpenRouteService profile for this mode. Tricycles follow car roads.
     */
    public function routingProfile(): string
    {
        return match ($this) {
            self::Car, self::Tricycle => 'driving-car',
            self::Walk => 'foot-walking',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode) => ['value' => $mode->value, 'label' => $mode->label()],
            self::cases(),
        );
    }
}
