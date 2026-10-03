<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Notifications\TripStartsTomorrow;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * The evening before a trip, remind the travellers to save it offline and check the forecast.
 */
class SendTripReminders implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Trip::whereDate('start_date', now()->addDay()->toDateString())
            ->whereNull('reminded_at')
            ->with(['owner', 'members'])
            ->each(function (Trip $trip) {
                Notification::send($trip->travellers(), new TripStartsTomorrow($trip));
                $trip->forceFill(['reminded_at' => now()])->save();
            });
    }
}
