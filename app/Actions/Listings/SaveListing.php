<?php

namespace App\Actions\Listings;

use App\Http\Requests\Listings\ListingRequest;
use App\Models\ActivityLog;
use App\Models\Listing;

class SaveListing
{
    /**
     * Fill a new or existing listing from a validated request and save it.
     */
    public function handle(Listing $listing, ListingRequest $request): Listing
    {
        $creating = ! $listing->exists;

        $listing->fill($request->listingAttributes());

        if ($request->canFeature()) {
            $listing->is_featured = $request->boolean('is_featured');
        }

        if (! $creating && $listing->isDirty('name')) {
            $listing->slug = Listing::uniqueSlug($listing->name, $listing->id);
        }

        $listing->save();

        ActivityLog::record($creating ? 'listing.created' : 'listing.updated', $listing);

        return $listing;
    }
}
