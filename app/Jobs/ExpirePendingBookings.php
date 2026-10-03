<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Requests the partner has not answered within 48 hours expire and free their slots.
 */
class ExpirePendingBookings implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookings): void
    {
        Booking::where('status', BookingStatus::Pending)
            ->where('expires_at', '<=', now())
            ->each(fn (Booking $booking) => $bookings->expire($booking));
    }
}
