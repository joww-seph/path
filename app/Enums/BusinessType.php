<?php

namespace App\Enums;

enum BusinessType: string
{
    case Lodging = 'lodging';
    case Restaurant = 'restaurant';
    case TourOperator = 'tour_operator';
    case ActivityOperator = 'activity_operator';
    case Transport = 'transport';
    case Shop = 'shop';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lodging => 'Resort or lodging',
            self::Restaurant => 'Restaurant or food place',
            self::TourOperator => 'Tour operator',
            self::ActivityOperator => '4x4 or activity operator',
            self::Transport => 'Tricycle or van transport',
            self::Shop => 'Souvenir or pasalubong shop',
            self::Other => 'Other service',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
