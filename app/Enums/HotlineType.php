<?php

namespace App\Enums;

enum HotlineType: string
{
    case Emergency = 'emergency';
    case Police = 'police';
    case Fire = 'fire';
    case Rescue = 'rescue';
    case Medical = 'medical';
    case Tourism = 'tourism';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Emergency',
            self::Police => 'Police',
            self::Fire => 'Fire',
            self::Rescue => 'Rescue and disaster response',
            self::Medical => 'Hospital and medical',
            self::Tourism => 'Tourism office',
            self::Other => 'Other',
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
