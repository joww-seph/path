<?php

namespace App\Jobs;

use App\Models\Advisory;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\AdvisoryPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells everyone travelling while an advisory is in effect. When it names sites, only trips that
 * include those sites are told.
 */
class NotifyTravellersOfAdvisory implements ShouldQueue
{
    use Queueable;

    public function __construct(public Advisory $advisory) {}

    public function handle(): void
    {
        $advisory = $this->advisory->load('listings:id');
        $listingIds = $advisory->listings->pluck('id');
        $endDate = ($advisory->ends_at ?? $advisory->starts_at)->toDateString();

        $trips = Trip::query()
            ->where('start_date', '<=', $endDate.' 23:59:59')
            ->where('end_date', '>=', $advisory->starts_at->toDateString())
            ->when($listingIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('items', fn (Builder $query) => $query->whereIn('listing_id', $listingIds)))
            ->with(['owner', 'members'])
            ->get();

        $notified = [];

        foreach ($trips as $trip) {
            foreach ($trip->travellers() as $traveller) {
                /** @var User $traveller */
                if (isset($notified[$traveller->id])) {
                    continue;
                }

                $traveller->notify(new AdvisoryPublished($advisory, $trip));
                $notified[$traveller->id] = true;
            }
        }

        $advisory->forceFill(['notified_at' => now()])->save();
    }
}
