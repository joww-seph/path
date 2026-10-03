<?php

namespace App\Enums;

enum TripRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Editor => 'Can edit',
            self::Viewer => 'Can view',
        };
    }
}
