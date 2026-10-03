<?php

namespace App\Enums;

enum RateUnit: string
{
    case PerPerson = 'person';
    case PerGroup = 'group';
    case PerRide = 'ride';
    case PerNight = 'night';
    case PerHour = 'hour';
    case PerItem = 'item';

    public function label(): string
    {
        return match ($this) {
            self::PerPerson => 'per person',
            self::PerGroup => 'per group',
            self::PerRide => 'per ride',
            self::PerNight => 'per night',
            self::PerHour => 'per hour',
            self::PerItem => 'per item',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $unit) => ['value' => $unit->value, 'label' => $unit->label()],
            self::cases(),
        );
    }
}
