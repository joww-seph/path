<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingCardResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    private const NEARBY_COUNT = 4;

    /**
     * A listing's detail page. Drafts are visible only to their partner and the tourism office.
     */
    public function show(Listing $listing): Response
    {
        Gate::authorize('view', $listing);

        $listing->load(['category', 'business', 'rates' => fn ($query) => $query->where('is_active', true), 'photos', 'coverPhoto', 'heritageStories']);

        return Inertia::render('guide/Listing', [
            'listing' => (new ListingResource($listing))->resolve(),
            'events' => $listing->events()->upcoming()->orderBy('starts_at')->limit(3)->get(['id', 'title', 'slug', 'starts_at', 'ends_at']),
            'nearby' => ListingCardResource::collection($this->nearby($listing))->resolve(),
            'paymentInstructions' => $listing->business?->payment_instructions,
        ]);
    }

    /**
     * @return Collection<int, Listing>
     */
    private function nearby(Listing $listing)
    {
        if (! $listing->hasLocation()) {
            return collect();
        }

        return Listing::published()
            ->whereKeyNot($listing->id)
            ->whereNotNull('latitude')
            ->with(['category', 'coverPhoto'])
            ->get()
            ->each(fn (Listing $other) => $other->setAttribute('distance_km', $other->distanceKmFrom($listing->latitude, $listing->longitude)))
            ->sortBy('distance_km')
            ->take(self::NEARBY_COUNT)
            ->values();
    }
}
