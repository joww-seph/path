<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Philippine mobile numbers, written as 09XXXXXXXXX or +639XXXXXXXXX.
     */
    public const PATTERN = '/^(\+?63|0)9\d{9}$/';

    /**
     * Drop the spaces, dashes and brackets people type into phone numbers.
     */
    public static function clean(?string $phone): ?string
    {
        return blank($phone) ? null : preg_replace('/[\s()\-]/', '', $phone);
    }

    /**
     * Store every valid mobile number as 09XXXXXXXXX so numbers can be compared.
     */
    public static function normalize(?string $phone): ?string
    {
        $phone = self::clean($phone);

        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '63') ? '0'.substr($digits, 2) : $digits;
    }
}
