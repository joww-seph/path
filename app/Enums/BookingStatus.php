<?php

namespace App\Enums;

/**
 * Booking states and the moves allowed between them (gameplan section 4, "Booking status rules").
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for the partner',
            self::Confirmed => 'Confirmed',
            self::Declined => 'Declined',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::Completed => 'Completed',
            self::NoShow => 'No-show',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Declined, self::Cancelled, self::Expired],
            self::Confirmed => [self::Cancelled, self::Completed, self::NoShow],
            default => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * Whether a booking in this state holds a slot on its date.
     */
    public function holdsSlot(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Completed], true);
    }

    public function isFinal(): bool
    {
        return $this->allowedNext() === [];
    }
}
