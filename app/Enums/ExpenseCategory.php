<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Lodging = 'lodging';
    case Food = 'food';
    case Transport = 'transport';
    case Activities = 'activities';
    case Pasalubong = 'pasalubong';
    case Others = 'others';

    public function label(): string
    {
        return match ($this) {
            self::Lodging => 'Lodging',
            self::Food => 'Food',
            self::Transport => 'Transport',
            self::Activities => 'Activities and fees',
            self::Pasalubong => 'Pasalubong',
            self::Others => 'Others',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category) => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
