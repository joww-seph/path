<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingStartingSoon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * About an hour before a confirmed booking with a set time, remind the tourist.
 */
class SendBookingReminders implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $now = now();
        $soon = $now->addMinutes(70);

        Booking::where('status', BookingStatus::Confirmed)
            ->whereDate('date', $now->toDateString())
            ->whereNotNull('time')
            ->whereNull('reminded_at')
            ->with(['tourist', 'listing'])
            ->get()
            ->filter(function (Booking $booking) use ($now, $soon) {
                $startsAt = $booking->date->setTimeFromTimeString((string) $booking->time);

                return $startsAt->gt($now) && $startsAt->lte($soon);
            })
            ->each(function (Booking $booking) {
                $booking->tourist->notify(new BookingStartingSoon($booking));
                $booking->forceFill(['reminded_at' => now()])->save();
            });
    }
}
