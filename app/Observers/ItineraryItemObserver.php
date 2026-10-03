<?php

namespace App\Observers;

use App\Models\ItineraryItem;
use App\Models\SiteVisit;

class ItineraryItemObserver
{
    /**
     * Ticking off a stop on or after its day counts as a visit for everyone on the trip.
     */
    public function saved(ItineraryItem $item): void
    {
        if (! $item->is_done || (! $item->wasChanged('is_done') && ! $item->wasRecentlyCreated) || $item->listing_id === null) {
            return;
        }

        $trip = $item->trip()->with(['owner', 'members'])->first();
        $date = $trip->dateForDay($item->day_number);

        if ($date->isFuture()) {
            return;
        }

        foreach ($trip->travellers() as $traveller) {
            SiteVisit::record($item->listing_id, $traveller->id, $date->toDateString(), 'itinerary');
        }
    }
}
