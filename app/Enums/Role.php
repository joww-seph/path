<?php

namespace App\Enums;

enum Role: string
{
    case Tourist = 'tourist';
    case Partner = 'partner';
    case TourismOfficer = 'tourism_officer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Tourist => 'Tourist',
            self::Partner => 'Business partner',
            self::TourismOfficer => 'Tourism officer',
            self::Admin => 'Administrator',
        };
    }

    /**
     * The name of the route each role lands on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Tourist => 'tourist.dashboard',
            self::Partner => 'partner.dashboard',
            self::TourismOfficer => 'office.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
