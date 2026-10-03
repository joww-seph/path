<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Review;
use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * After the trip: the places visited, photos, total spending, and prompts to review.
 */
class TripRecapController extends Controller
{
    public function __invoke(Request $request, Trip $trip, BudgetService $budget): Response
    {
        Gate::authorize('view', $trip);

        $trip->load(['owner', 'members', 'items.listing.coverPhoto', 'items.listing.category']);
        $visited = $trip->items->where('is_done', true)->values();
        $reviewed = Review::where('user_id', $request->user()->id)->whereIn('listing_id', $visited->pluck('listing_id')->filter())->pluck('rating', 'listing_id');

        return Inertia::render('tourist/trips/Recap', [
            'trip' => (new TripResource($trip))->resolve(),
            'stats' => [
                'stops_planned' => $trip->items->count(),
                'stops_visited' => $visited->count(),
                'distance_km' => round((float) $visited->sum('distance_km_from_previous'), 1),
                'bookings_completed' => $trip->bookings()->where('status', BookingStatus::Completed)->count(),
            ],
            'visited' => $visited->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title(),
                'day_number' => $item->day_number,
                'listing' => $item->listing?->only(['id', 'name', 'slug']),
                'photo' => $item->listing?->coverPhoto?->url,
                'color' => $item->listing?->category?->color,
                'my_rating' => $item->listing_id ? ($reviewed[$item->listing_id] ?? null) : null,
            ]),
            'spending' => $budget->summary($trip, $budget->estimate($trip)),
        ]);
    }
}
