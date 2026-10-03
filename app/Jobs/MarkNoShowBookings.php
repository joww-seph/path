<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Confirmed bookings that were not checked in by the end of their date become no-shows.
 */
class MarkNoShowBookings implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookings): void
    {
        Booking::where('status', BookingStatus::Confirmed)
            ->whereDate('date', '<', now()->toDateString())
            ->each(fn (Booking $booking) => $bookings->markNoShow($booking));
    }
}
