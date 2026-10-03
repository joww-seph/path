<?php

namespace App\Actions\Trips;

use App\Http\Resources\ItineraryItemResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Services\ItineraryPlanner;
use Illuminate\Http\Request;

/**
 * The props for a trip's day-by-day itinerary, shared by the planner, shared and print views.
 */
class BuildPlannerView
{
    public function __construct(
        private ItineraryPlanner $planner,
        private BudgetService $budget,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Trip $trip, Request $request): array
    {
        $trip->load(['owner', 'members', 'items.listing.category', 'items.listing.coverPhoto', 'items.booking']);
        $items = $trip->items;

        $days = collect(range(1, max($trip->dayCount(), (int) $items->max('day_number'))))
            ->map(fn (int $day) => [
                'number' => $day,
                'date' => $trip->dateForDay($day)->toDateString(),
                'in_trip' => $day <= $trip->dayCount(),
                'items' => ItineraryItemResource::collection($items->where('day_number', $day)->values())->resolve(),
            ]);

        return [
            'trip' => (new TripResource($trip))->resolve(),
            'days' => $days,
            'warnings' => $this->planner->warnings($trip),
            'estimatedCost' => $this->budget->estimate($trip),
        ];
    }
}
