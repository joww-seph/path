<?php

namespace App\Enums;

enum SosStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Needs help',
            self::Acknowledged => 'Office responding',
            self::Resolved => 'Resolved',
        };
    }
}
