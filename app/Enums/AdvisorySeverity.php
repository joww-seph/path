<?php

namespace App\Enums;

enum AdvisorySeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Notice',
            self::Warning => 'Warning',
            self::Danger => 'Danger',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $severity) => ['value' => $severity->value, 'label' => $severity->label()], self::cases());
    }
}
